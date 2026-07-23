<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetEventData extends Command
{
    protected $signature = 'app:reset-event-data
        {--dry-run : Preview what would be deleted without modifying the database}';

    protected $description = 'Reset all event operational data while preserving master data including legacy_peserta_mappings';

    private array $masterTables = ['pesertas', 'desas', 'kelompoks', 'regus', 'people', 'users', 'legacy_peserta_mappings'];

    private array $systemTables = [
        'migrations', 'cache', 'cache_locks', 'failed_jobs',
        'job_batches', 'jobs', 'password_reset_tokens', 'sessions', 'sqlite_sequence',
    ];

    private array $operationalTables = [
        'absensis',
        'event_attendances',
        'sesi_absensis',
        'izin_absensis',
        'surat_izins',
        'desa_access_grants',
        'cai_participant_replacements',
        'identity_correction_requests',
        'activity_logs',
        'legacy_participation_mappings',
        'participations',
        'activities',
        'activity_categories',
        'activity_groups',
        'activity_registrations',
        'category_definitions',
        'event_committee_assignments',
        'event_roles',
        'rundown_items',
        'rundowns',
        'venues',
        'events',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('============================================');
        $this->info('  RESET EVENT DATA — AbsenCAI');
        $this->info('============================================');
        $this->line('');

        if ($dryRun) {
            $this->warn(' >>> DRY RUN MODE — No changes will be made to the database');
            $this->line('');
        }

        // --- Phase 0: Dependency check ---
        $this->info('--- Phase 0: Foreign Key Dependency Check ---');
        $this->checkDependencies();
        $this->line('');

        // --- Phase 1: Snapshot master data ---
        $this->info('--- Phase 1: Snapshot Master Data ---');
        $snapshot = $this->takeSnapshot();
        foreach ($snapshot as $table => $data) {
            if (is_array($data) && isset($data['count'])) {
                $this->line("  {$table}: {$data['count']} rows");
            }
        }
        $this->line('');

        // --- Phase 2: Show what will be deleted ---
        $this->info('--- Phase 2: Operational Data to be Deleted ---');
        $totalDeleted = 0;
        $deletePlan = [];
        foreach ($this->operationalTables as $table) {
            $count = DB::table($table)->count();
            if ($count > 0) {
                $deletePlan[$table] = $count;
                $totalDeleted += $count;
                $this->line("  {$table}: {$count} rows");
            } else {
                $this->line("  {$table}: 0 rows (already empty)");
            }
        }
        $this->line('');
        $this->line("  TOTAL DATA TO BE DELETED: {$totalDeleted} rows");
        $this->line('');

        // --- Phase 3: Delete order ---
        $this->info('--- Phase 3: Delete Order (safe for FK constraints) ---');
        $order = $this->getDeleteOrder();
        foreach ($order as $i => $item) {
            $this->line(sprintf('  %2d. %s (%s)', $i + 1, $item['table'], $item['reason']));
        }
        $this->line('');

        if ($dryRun) {
            // --- Validation check (read-only, skip operational zero-check) ---
            $this->info('--- Read-Only Integrity Check ---');
            $this->runValidation($snapshot, true);
            $this->line('');

            $this->info('=== DRY RUN SUMMARY ===');
            $this->line('');
            $this->info('Master data to be PRESERVED:');
            foreach ($this->masterTables as $table) {
                $count = DB::table($table)->count();
                $this->line("  {$table}: {$count} rows (unchanged)");
            }
            $this->line('');
            $this->info("Operational data to be DELETED: {$totalDeleted} rows across " . count($deletePlan) . " tables");
            $this->line('');
            $this->info('System tables NOT touched: ' . implode(', ', $this->systemTables));
            $this->line('');
            $this->warn('No changes were made. Run without --dry-run to execute the reset.');
            $this->line('');

            return 0;
        }

        // ============== EXECUTION ==============
        $this->warn('>>> EXECUTING RESET...');
        $this->line('');

        DB::beginTransaction();

        try {
            $this->executeDelete($order);

            // --- Phase 4: Validate ---
            $this->info('--- Phase 4: Post-Delete Validation ---');
            $this->runValidation($snapshot);

            DB::commit();
            $this->info('');
            $this->info('============================================');
            $this->info('  RESET COMPLETED SUCCESSFULLY');
            $this->info('============================================');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('');
            $this->error('============================================');
            $this->error('  ROLLBACK: ' . $e->getMessage());
            $this->error('============================================');

            return 1;
        }

        return 0;
    }

    private function checkDependencies(): void
    {
        $masterTables = $this->masterTables;
        $dangerousCascade = [
            'pesertas.desa_id -> desas.id' => ['trigger' => 'DELETE desas', 'effect' => 'CASCADE hapus pesertas'],
            'pesertas.kelompok_id -> kelompoks.id' => ['trigger' => 'DELETE kelompoks', 'effect' => 'CASCADE hapus pesertas'],
            'pesertas.regu_id -> regus.id (LEGACY — frozen column)' => ['trigger' => 'DELETE regus', 'effect' => 'CASCADE hapus pesertas (only if FK present)'],
            'kelompoks.desa_id -> desas.id' => ['trigger' => 'DELETE desas', 'effect' => 'CASCADE hapus kelompoks'],
            'surat_izins.peserta_id -> pesertas.id' => ['trigger' => 'DELETE pesertas', 'effect' => 'CASCADE hapus surat_izins'],
            'surat_izins.created_by -> users.id' => ['trigger' => 'DELETE users', 'effect' => 'CASCADE hapus surat_izins'],
            'izin_absensis.peserta_id -> pesertas.id' => ['trigger' => 'DELETE pesertas', 'effect' => 'CASCADE hapus izin_absensis'],
            'izin_absensis.sesi_id -> sesi_absensis.id' => ['trigger' => 'DELETE sesi_absensis', 'effect' => 'CASCADE hapus izin_absensis (planned)'],
        ];

        $this->line('  Dangerous CASCADE (master data protection):');
        foreach ($dangerousCascade as $fk => $info) {
            if (in_array(explode('.', $fk)[0], $masterTables) || in_array(explode(' -> ', explode('.', $fk)[1])[0], $masterTables)) {
                $this->line("    ⚠️  {$fk}");
                $this->line("        -> Trigger: {$info['trigger']} | Effect: {$info['effect']}");
            }
        }

        $verifyMaster = ['pesertas', 'desas', 'kelompoks', 'regus', 'people', 'users', 'legacy_peserta_mappings'];
        $this->line('  Verify master tables never deleted:');
        foreach ($verifyMaster as $table) {
            $this->line("    ✅ {$table} — NOT in delete list");
        }
    }

    private function takeSnapshot(): array
    {
        $snapshot = [];

        foreach ($this->masterTables as $table) {
            $snapshot[$table] = [
                'count' => DB::table($table)->count(),
            ];
        }

        $snapshot['pesertas_fields'] = DB::table('pesertas')
            ->select('id', 'nip', 'participant_number', 'attendance_code', 'desa_id', 'kelompok_id')
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->keyBy('id')
            ->toArray();

        $snapshot['valid_desa_ids'] = DB::table('desas')->pluck('id')->toArray();
        $snapshot['valid_kelompok_ids'] = DB::table('kelompoks')->pluck('id')->toArray();
        $snapshot['valid_regu_ids'] = DB::table('regus')->pluck('id')->toArray();

        return $snapshot;
    }

    private function getDeleteOrder(): array
    {
        return [
            ['table' => 'activity_registrations', 'reason' => 'FK RESTRICT ke participations, activities, events'],
            ['table' => 'izin_absensis', 'reason' => 'FK CASCADE ke sesi_absensis — hapus sebelum sesi'],
            ['table' => 'absensis', 'reason' => 'FK SET NULL ke sesi_absensis — hapus sebelum sesi'],
            ['table' => 'event_attendances', 'reason' => 'FK RESTRICT ke participations & events'],
            ['table' => 'identity_correction_requests', 'reason' => 'FK RESTRICT ke people (SET NULL ke events)'],
            ['table' => 'cai_participant_replacements', 'reason' => 'FK RESTRICT ke events, pesertas, people, participations'],
            ['table' => 'legacy_participation_mappings', 'reason' => 'FK RESTRICT ke participations, pesertas, people, events'],
            ['table' => 'legacy_peserta_mappings', 'reason' => 'FK RESTRICT ke pesertas, people'],
            ['table' => 'surat_izins', 'reason' => 'FK SET NULL participation_id — update dulu, lalu hapus'],
            ['table' => 'desa_access_grants', 'reason' => 'FK RESTRICT ke events & desas'],
            ['table' => 'event_committee_assignments', 'reason' => 'FK RESTRICT ke events, people, event_roles'],
            ['table' => 'activity_logs', 'reason' => 'FK SET NULL ke users — hapus aman'],
            ['table' => 'sesi_absensis', 'reason' => 'FK RESTRICT ke events — hapus sebelum events'],
            ['table' => 'participations', 'reason' => 'FK RESTRICT ke events & people'],
            ['table' => 'activities', 'reason' => 'FK RESTRICT ke events & activity_groups'],
            ['table' => 'activity_categories', 'reason' => 'FK RESTRICT ke events, activities, category_definitions'],
            ['table' => 'activity_groups', 'reason' => 'FK RESTRICT ke events'],
            ['table' => 'category_definitions', 'reason' => 'FK RESTRICT ke events'],
            ['table' => 'event_roles', 'reason' => 'FK RESTRICT ke events'],
            ['table' => 'rundown_items', 'reason' => 'FK RESTRICT ke events, rundowns, activities, venues'],
            ['table' => 'rundowns', 'reason' => 'FK RESTRICT ke events'],
            ['table' => 'venues', 'reason' => 'FK RESTRICT ke events'],
            ['table' => 'events', 'reason' => 'SEMUA dependency sudah dibersihkan — aman dihapus'],
        ];
    }

    private function executeDelete(array $order): void
    {
        $this->info('--- Executing DELETE in safe order ---');

        foreach ($order as $item) {
            $table = $item['table'];
            $count = DB::table($table)->count();

            if ($count === 0) {
                $this->line("  {$table}: 0 rows (skipped)");
                continue;
            }

            if ($table === 'surat_izins') {
                DB::table('surat_izins')->update(['participation_id' => null]);
                $this->line("  surat_izins: SET NULL participation_id (FK SET NULL)");
            }

            DB::table($table)->delete();
            $this->line("  {$table}: {$count} rows deleted");
        }
    }

    private function runValidation(array $snapshot, bool $dryRun = false): void
    {
        $errors = [];

        // 1. Master table counts
        foreach ($this->masterTables as $table) {
            $expected = $snapshot[$table]['count'];
            $actual = DB::table($table)->count();
            if ($actual !== $expected) {
                $errors[] = "{$table}: count changed! Expected {$expected}, got {$actual}";
            } else {
                $this->line("  ✅ {$table}: {$actual} rows (correct)");
            }
        }

        // 2. Peserta fields unchanged
        $pesertasNow = DB::table('pesertas')
            ->select('id', 'nip', 'participant_number', 'attendance_code', 'desa_id', 'kelompok_id')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        foreach ($snapshot['pesertas_fields'] as $id => $before) {
            $after = $pesertasNow->get($id);
            if (!$after) {
                $errors[] = "Peserta id={$id} is MISSING!";
                continue;
            }
            $after = (array) $after;
            foreach (['participant_number', 'attendance_code', 'desa_id', 'kelompok_id'] as $field) {
                if ((string) $before[$field] !== (string) $after[$field]) {
                    $errors[] = "Peserta id={$id} {$field} changed: '{$before[$field]}' → '{$after[$field]}'";
                }
            }
        }
        if (empty($errors)) {
            $this->line('  ✅ All peserta fields unchanged (nip, participant_number, attendance_code, desa_id, kelompok_id)');
        }

        // 3. No orphan FK
        $orphanDesa = DB::table('pesertas')
            ->whereNotNull('desa_id')
            ->whereNotIn('desa_id', $snapshot['valid_desa_ids'])
            ->count();
        if ($orphanDesa > 0) {
            $errors[] = "{$orphanDesa} peserta(s) have invalid desa_id";
        } else {
            $this->line('  ✅ No orphan desa_id');
        }

        $orphanKelompok = DB::table('pesertas')
            ->whereNotNull('kelompok_id')
            ->whereNotIn('kelompok_id', $snapshot['valid_kelompok_ids'])
            ->count();
        if ($orphanKelompok > 0) {
            $errors[] = "{$orphanKelompok} peserta(s) have invalid kelompok_id";
        } else {
            $this->line('  ✅ No orphan kelompok_id');
        }

        $orphanRegu = DB::table('participations')
            ->whereNotNull('regu_id')
            ->whereNotIn('regu_id', $snapshot['valid_regu_ids'])
            ->count();
        if ($orphanRegu > 0) {
            $errors[] = "{$orphanRegu} participation(s) have invalid regu_id";
        } else {
            $this->line('  ✅ No orphan regu_id in participations');
        }

        // 4. Operational tables must be 0 (only during actual execution)
        if (!$dryRun) {
            $mustBeZero = [
                'absensis', 'event_attendances', 'sesi_absensis', 'izin_absensis',
                'surat_izins', 'desa_access_grants', 'cai_participant_replacements',
                'identity_correction_requests', 'activity_logs',
                'legacy_participation_mappings',
                'participations', 'events',
            ];
            foreach ($mustBeZero as $table) {
                $count = DB::table($table)->count();
                if ($count !== 0) {
                    $errors[] = "{$table} should be 0 but has {$count} rows";
                } else {
                    $this->line("  ✅ {$table}: 0 rows");
                }
            }

            $eventModuleTables = [
                'activities', 'activity_categories', 'activity_groups', 'activity_registrations',
                'category_definitions', 'event_committee_assignments', 'event_roles',
                'rundown_items', 'rundowns', 'venues',
            ];
            foreach ($eventModuleTables as $table) {
                $count = DB::table($table)->count();
                if ($count !== 0) {
                    $errors[] = "{$table} should be 0 but has {$count} rows";
                } else {
                    $this->line("  ✅ {$table}: 0 rows");
                }
            }
        }

        if (!empty($errors)) {
            $msg = "Validation FAILED:\n" . implode("\n", array_map(fn ($e) => "  - {$e}", $errors));
            throw new \RuntimeException($msg);
        }

        $this->line('');
        $this->info('  ✓ ALL VALIDATIONS PASSED');
    }
}
