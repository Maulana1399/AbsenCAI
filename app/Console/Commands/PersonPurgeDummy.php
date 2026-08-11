<?php

namespace App\Console\Commands;

use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Hard-delete dummy Person records (e.g. "Udin", "Bira") together with all of
 * their dependent data — participations, attendance, legacy mappings, identity
 * corrections, committee assignments — in dependency order inside a single
 * transaction.
 *
 * Safety:
 *  - Dry run by default; only --apply performs deletes.
 *  - Ambiguous names (more than one Person with the same name) abort and list
 *    candidates; use --id to target a specific Person.
 *  - A JSON backup of every affected row is written before any delete.
 *  - Only the targeted Persons and their exclusive dependents are touched.
 *    Events, users, desa/kelompok/regu and other master data are never
 *    deleted; nullable FK columns (users.person_id, surat_izins.participation_id,
 *    event_committee_assignments.participation_id) are set to NULL instead.
 *  - Legacy pesertas/absensis rows are intentionally left untouched (out of
 *    scope for Person cleanup); they are reported for awareness.
 */
class PersonPurgeDummy extends Command
{
    protected $signature = 'person:purge-dummy
        {names?* : Person names to purge (matched case-insensitively on trimmed nama)}
        {--id=* : Explicit person IDs to purge (bypasses name matching; repeatable)}
        {--apply : Actually delete (default is a read-only dry run)}
        {--backup-path= : JSON backup file path (default storage/app/backups)}';

    protected $description = 'Hard-delete dummy Person records and their dependent data';

    public function handle(): int
    {
        $names = array_map(fn ($n) => trim((string) $n), $this->argument('names'));
        $ids = array_map('intval', $this->option('id'));
        $apply = (bool) $this->option('apply');

        $personIds = $this->resolveTargets($names, $ids);

        if ($personIds === null) {
            return Command::FAILURE;
        }

        if ($personIds === []) {
            $this->error('No Person matched the given names. Nothing to do.');

            return Command::FAILURE;
        }

        $people = Person::whereIn('id', $personIds)->orderBy('id')->get();
        $participationIds = $this->participationIds($personIds);

        $this->section('IDENTIFICATION');
        foreach ($people as $person) {
            $this->line("  Person #{$person->id}: '{$person->nama}' (jenis_kelamin=".($person->jenis_kelamin ?? 'null').', desa_id='.($person->desa_id ?? 'null').', kelompok_id='.($person->kelompok_id ?? 'null').')');
        }
        $this->line('  Participations: '.($participationIds ? implode(', ', $participationIds) : 'none'));

        $affected = $this->collectAffected($personIds, $participationIds);
        $this->printAffected($affected);

        $legacyNote = $this->legacyPesertaRows($personIds, $participationIds);
        if ($legacyNote !== []) {
            $this->section('LEGACY PESERTAS (NOT deleted - out of scope)');
            foreach ($legacyNote as $row) {
                $this->line('  '.json_encode($row));
            }
        }

        if (! $apply) {
            $this->line('');
            $this->info('DRY RUN: nothing was deleted. Re-run with --apply to purge.');
            $this->line('Database writes performed: 0');

            return Command::SUCCESS;
        }

        $backupPath = $this->backup($affected, $personIds, $participationIds);

        $this->section('PURGING');
        DB::transaction(function () use ($personIds, $participationIds) {
            $this->deleteDependents($personIds, $participationIds);

            if ($this->tableHas('participations')) {
                DB::table('participations')->whereIn('person_id', $personIds)->delete();
            }

            DB::table('people')->whereIn('id', $personIds)->delete();
        });
        $this->line('  Purged Person IDs: '.implode(', ', $personIds));
        $this->line('  Purged Participation IDs: '.($participationIds ? implode(', ', $participationIds) : 'none'));
        $this->line('  Backup: '.$backupPath);
        $this->line('Database writes performed: complete');

        $this->section('VERIFICATION');
        $this->verify($personIds, $participationIds);

        return Command::SUCCESS;
    }

    /**
     * @param  array<int, string>  $names
     * @param  array<int, int>  $ids
     * @return array<int, int>|null
     */
    private function resolveTargets(array $names, array $ids): ?array
    {
        if ($ids !== []) {
            $existing = Person::whereIn('id', $ids)->pluck('id')->map(fn ($v) => (int) $v)->all();

            foreach ($ids as $id) {
                if (! in_array($id, $existing, true)) {
                    $this->error("Person #{$id} does not exist.");

                    return null;
                }
            }

            return $existing;
        }

        $targets = [];

        foreach ($names as $name) {
            if ($name === '') {
                continue;
            }

            $candidates = Person::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($name)])
                ->orderBy('id')
                ->get(['id', 'nama']);

            if ($candidates->count() === 0) {
                $this->warn("No Person named '{$name}' was found. Skipped.");

                continue;
            }

            if ($candidates->count() > 1) {
                $this->error("Multiple Persons named '{$name}' — refusing to delete blindly.");
                foreach ($candidates as $candidate) {
                    $this->line("  #{$candidate->id}: '{$candidate->nama}'");
                }
                $this->error('Re-run with --id=<id> to select the correct record(s).');

                return null;
            }

            $targets[] = (int) $candidates->first()->id;
        }

        return array_values(array_unique($targets));
    }

    /**
     * @param  array<int, int>  $personIds
     * @return array<int, int>
     */
    private function participationIds(array $personIds): array
    {
        if ($personIds === []) {
            return [];
        }

        return DB::table('participations')->whereIn('person_id', $personIds)->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Ordered deletion steps. Every condition is built schema-aware so tables
     * that lack a column (e.g. legacy_peserta_mappings lost participation_id in
     * a later migration) still work.
     *
     * @param  array<int, int>  $personIds
     * @param  array<int, int>  $participationIds
     */
    private function deleteDependents(array $personIds, array $participationIds): void
    {
        if ($this->tableHas('event_attendances')) {
            DB::table('event_attendances')->whereIn('participation_id', $participationIds)->delete();
        }

        $registrationIds = $this->registrationIds($participationIds);

        if ($registrationIds !== [0] && $this->tableHas('competition_schedule_entries')) {
            DB::table('competition_schedule_entries')->whereIn('competition_registration_id', $registrationIds)->delete();
        }

        if ($registrationIds !== [0] && $this->tableHas('competition_outcomes')) {
            DB::table('competition_outcomes')->whereIn('competition_registration_id', $registrationIds)->delete();
        }

        if ($this->tableHas('competition_registrations')) {
            DB::table('competition_registrations')->whereIn('participation_id', $participationIds)->delete();
        }

        if ($this->tableHas('activity_registrations')) {
            DB::table('activity_registrations')->whereIn('participation_id', $participationIds)->delete();
        }

        $this->deleteWhereAny('legacy_peserta_mappings', [
            'person_id' => $personIds,
            'participation_id' => $participationIds,
        ]);

        $this->deleteWhereAny('legacy_participation_mappings', [
            'person_id' => $personIds,
            'participation_id' => $participationIds,
        ]);

        $this->deleteWhereAny('cai_participant_replacements', [
            'old_person_id' => $personIds,
            'new_person_id' => $personIds,
            'old_participation_id' => $participationIds,
            'new_participation_id' => $participationIds,
        ]);

        $this->deleteWhereAny('identity_correction_requests', ['person_id' => $personIds]);

        $this->deleteWhereAny('event_committee_assignments', ['person_id' => $personIds]);

        if ($this->tableHas('surat_izins') && $this->tableHasColumn('surat_izins', 'participation_id')) {
            DB::table('surat_izins')->whereIn('participation_id', $participationIds)->update(['participation_id' => null]);
        }

        if ($this->tableHas('users') && $this->tableHasColumn('users', 'person_id')) {
            DB::table('users')->whereIn('person_id', $personIds)->update(['person_id' => null]);
        }
    }

    /**
     * @param  array<string, array<int, int>>  $columns  column => ids
     */
    private function deleteWhereAny(string $table, array $columns): void
    {
        if (! $this->tableHas($table)) {
            return;
        }

        $query = DB::table($table);
        $applied = false;

        foreach ($columns as $column => $ids) {
            if (! $this->tableHasColumn($table, $column) || $ids === []) {
                continue;
            }

            if ($applied) {
                $query->orWhereIn($column, $ids);
            } else {
                $query->whereIn($column, $ids);
                $applied = true;
            }
        }

        if ($applied) {
            $query->delete();
        }
    }

    /**
     * @param  array<int, int>  $participationIds
     * @return array<int, int>
     */
    private function registrationIds(array $participationIds): array
    {
        if (! $this->tableHas('competition_registrations') || $participationIds === []) {
            return [0];
        }

        return DB::table('competition_registrations')->whereIn('participation_id', $participationIds)->pluck('id')->map(fn ($v) => (int) $v)->all() ?: [0];
    }

    /**
     * @param  array<int, int>  $personIds
     * @param  array<int, int>  $participationIds
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function collectAffected(array $personIds, array $participationIds): array
    {
        $out = [];

        if ($this->tableHas('event_attendances')) {
            $out['event_attendances'] = $this->rowsWhere('event_attendances', ['participation_id' => $participationIds]);
        }

        $registrationIds = $this->registrationIds($participationIds);

        if ($this->tableHas('competition_schedule_entries')) {
            $out['competition_schedule_entries'] = $this->tableHasColumn('competition_schedule_entries', 'competition_registration_id')
                ? $this->rowsWhere('competition_schedule_entries', ['competition_registration_id' => $registrationIds])
                : [];
        }

        if ($this->tableHas('competition_outcomes')) {
            $out['competition_outcomes'] = $this->tableHasColumn('competition_outcomes', 'competition_registration_id')
                ? $this->rowsWhere('competition_outcomes', ['competition_registration_id' => $registrationIds])
                : [];
        }

        if ($this->tableHas('competition_registrations')) {
            $out['competition_registrations'] = $this->rowsWhere('competition_registrations', ['participation_id' => $participationIds]);
        }

        if ($this->tableHas('activity_registrations')) {
            $out['activity_registrations'] = $this->rowsWhere('activity_registrations', ['participation_id' => $participationIds]);
        }

        if ($this->tableHas('legacy_peserta_mappings')) {
            $out['legacy_peserta_mappings'] = $this->rowsWhereAny('legacy_peserta_mappings', [
                'person_id' => $personIds,
                'participation_id' => $participationIds,
            ]);
        }

        if ($this->tableHas('legacy_participation_mappings')) {
            $out['legacy_participation_mappings'] = $this->rowsWhereAny('legacy_participation_mappings', [
                'person_id' => $personIds,
                'participation_id' => $participationIds,
            ]);
        }

        if ($this->tableHas('cai_participant_replacements')) {
            $out['cai_participant_replacements'] = $this->rowsWhereAny('cai_participant_replacements', [
                'old_person_id' => $personIds,
                'new_person_id' => $personIds,
                'old_participation_id' => $participationIds,
                'new_participation_id' => $participationIds,
            ]);
        }

        if ($this->tableHas('identity_correction_requests')) {
            $out['identity_correction_requests'] = $this->rowsWhere('identity_correction_requests', ['person_id' => $personIds]);
        }

        if ($this->tableHas('event_committee_assignments')) {
            $out['event_committee_assignments'] = $this->rowsWhere('event_committee_assignments', ['person_id' => $personIds]);
        }

        if ($this->tableHas('surat_izins')) {
            $out['surat_izins'] = $this->tableHasColumn('surat_izins', 'participation_id')
                ? $this->rowsWhere('surat_izins', ['participation_id' => $participationIds])
                : [];
        }

        if ($this->tableHas('users')) {
            $out['users'] = $this->tableHasColumn('users', 'person_id')
                ? $this->rowsWhere('users', ['person_id' => $personIds])
                : [];
        }

        return $out;
    }

    /**
     * @param  array<string, array<int, int>>  $columns
     * @return array<int, array<string, mixed>>
     */
    private function rowsWhereAny(string $table, array $columns): array
    {
        $query = DB::table($table);
        $applied = false;

        foreach ($columns as $column => $ids) {
            if (! $this->tableHasColumn($table, $column) || $ids === []) {
                continue;
            }

            if ($applied) {
                $query->orWhereIn($column, $ids);
            } else {
                $query->whereIn($column, $ids);
                $applied = true;
            }
        }

        if (! $applied) {
            return [];
        }

        return $query->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function rowsWhere(string $table, array $columns): array
    {
        if ($columns === []) {
            return [];
        }

        $query = DB::table($table);
        foreach ($columns as $column => $ids) {
            if ($ids !== []) {
                $query->whereIn($column, $ids);
            }
        }

        return $query->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $affected
     */
    private function printAffected(array $affected): void
    {
        $this->section('DEPENDENCIES TO REMOVE');
        $total = 0;
        foreach ($affected as $table => $rows) {
            $total += count($rows);
            $this->line("  {$table}: ".count($rows).' row(s)');
            foreach ($rows as $row) {
                $this->line('    '.json_encode($row));
            }
        }
        $this->line('  total dependent rows: '.$total);
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $affected
     * @param  array<int, int>  $personIds
     * @param  array<int, int>  $participationIds
     */
    private function backup(array $affected, array $personIds, array $participationIds): string
    {
        $payload = json_encode([
            'generated_at' => now()->toIso8601String(),
            'person_ids' => $personIds,
            'participation_ids' => $participationIds,
            'rows' => $affected,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $path = $this->option('backup-path');

        if ($path === null) {
            $name = 'person-purge-'.now()->format('Y-m-d-H-i-s').'.json';
            Storage::disk('local')->put('backups/'.$name, $payload);

            return storage_path('app/private/backups/'.$name);
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, $payload);

        return $path;
    }

    /**
     * @param  array<int, int>  $personIds
     * @param  array<int, int>  $participationIds
     */
    private function verify(array $personIds, array $participationIds): void
    {
        $remainingPeople = Person::whereIn('id', $personIds)->count();
        $remainingParts = $this->tableHas('participations') ? DB::table('participations')->whereIn('person_id', $personIds)->count() : 0;
        $remainingAttendance = $this->tableHas('event_attendances') ? DB::table('event_attendances')->whereIn('participation_id', $participationIds)->count() : 0;

        $this->line('  Person remaining: '.$remainingPeople.' (expected 0)');
        $this->line('  Participation remaining: '.$remainingParts.' (expected 0)');
        $this->line('  EventAttendance remaining: '.$remainingAttendance.' (expected 0)');

        foreach ($this->countAffected($personIds, $participationIds) as $table => $count) {
            $this->line("  {$table} remaining: {$count} (expected 0)");
        }

        $orphans = $this->orphanScan();
        $this->line('  Orphan scan: '.($orphans === 0 ? 'no orphan references found' : "{$orphans} orphan reference(s) found"));

        if ($remainingPeople !== 0 || $remainingParts !== 0 || $remainingAttendance !== 0 || $orphans !== 0) {
            $this->error('VERIFICATION FAILED — investigate before continuing.');
        } else {
            $this->info('VERIFICATION OK — no Person, Participation or attendance rows remain; no orphans.');
        }
    }

    /**
     * @param  array<int, int>  $personIds
     * @param  array<int, int>  $participationIds
     * @return array<string, int>
     */
    private function countAffected(array $personIds, array $participationIds): array
    {
        $out = [];

        $simple = [
            'event_attendances' => ['participation_id' => $participationIds],
            'activity_registrations' => ['participation_id' => $participationIds],
            'competition_registrations' => ['participation_id' => $participationIds],
            'identity_correction_requests' => ['person_id' => $personIds],
            'event_committee_assignments' => ['person_id' => $personIds],
        ];

        foreach ($simple as $table => $columns) {
            if (! $this->tableHas($table)) {
                continue;
            }
            $out[$table] = count($this->rowsWhere($table, $columns));
        }

        if ($this->tableHas('legacy_peserta_mappings')) {
            $out['legacy_peserta_mappings'] = count($this->rowsWhereAny('legacy_peserta_mappings', [
                'person_id' => $personIds,
                'participation_id' => $participationIds,
            ]));
        }

        if ($this->tableHas('legacy_participation_mappings')) {
            $out['legacy_participation_mappings'] = count($this->rowsWhereAny('legacy_participation_mappings', [
                'person_id' => $personIds,
                'participation_id' => $participationIds,
            ]));
        }

        return $out;
    }

    private function orphanScan(): int
    {
        $checks = [
            'participations' => 'participations p LEFT JOIN people pe ON pe.id = p.person_id WHERE pe.id IS NULL',
            'event_attendances' => 'event_attendances a LEFT JOIN participations p ON p.id = a.participation_id WHERE p.id IS NULL',
            'activity_registrations' => 'activity_registrations a LEFT JOIN participations p ON p.id = a.participation_id WHERE p.id IS NULL',
        ];

        $total = 0;
        foreach ($checks as $table => $join) {
            if (! $this->tableHas($table)) {
                continue;
            }
            try {
                $total += (int) DB::selectOne("SELECT COUNT(*) AS cnt FROM {$join}")->cnt;
            } catch (\Throwable) {
            }
        }

        return $total;
    }

    /**
     * @param  array<int, int>  $personIds
     * @param  array<int, int>  $participationIds
     * @return array<int, array<string, mixed>>
     */
    private function legacyPesertaRows(array $personIds, array $participationIds): array
    {
        if (! $this->tableHas('pesertas') || ! $this->tableHas('legacy_participation_mappings')) {
            return [];
        }

        $pid = $personIds !== [] ? implode(',', $personIds) : '0';
        $pp = $participationIds !== [] ? implode(',', $participationIds) : '0';

        $q = DB::table('pesertas as p')
            ->select('p.*')
            ->leftJoin('legacy_peserta_mappings as m1', 'm1.peserta_id', '=', 'p.id')
            ->leftJoin('legacy_participation_mappings as m2', 'm2.peserta_id', '=', 'p.id');

        $applied = false;
        $conditions = [
            ['m1.person_id', $personIds],
            ['m2.person_id', $personIds],
        ];
        if ($this->tableHasColumn('legacy_peserta_mappings', 'participation_id')) {
            $conditions[] = ['m1.participation_id', $participationIds];
        }
        if ($this->tableHasColumn('legacy_participation_mappings', 'participation_id')) {
            $conditions[] = ['m2.participation_id', $participationIds];
        }

        foreach ($conditions as [$column, $ids]) {
            if ($ids === []) {
                continue;
            }
            if ($applied) {
                $q->orWhereIn($column, $ids);
            } else {
                $q->whereIn($column, $ids);
                $applied = true;
            }
        }

        if (! $applied) {
            return [];
        }

        return $q->distinct()->get()->map(fn ($r) => (array) $r)->all();
    }

    private function tableHas(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function section(string $title): void
    {
        $this->line('');
        $this->line(str_repeat('=', 60));
        $this->line($title);
        $this->line(str_repeat('-', 60));
    }
}
