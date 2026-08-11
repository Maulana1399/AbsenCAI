<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\Import\Support\PersonIdentityNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Normalize the capitalization of existing Person names to canonical Proper
 * Case ("REFIANTITO" → "Refiantito", "yudhistira ahmad" → "Yudhistira Ahmad").
 *
 * - Dry run by default (pure audit, no writes). Pass --apply to persist.
 * - Only the `nama` field is ever written; identity fields (jenis_kelamin,
 *   desa_id, kelompok_id, tanggal_lahir) and all participation/attendance data
 *   are never touched.
 * - Collision-safe: if normalizing would make two People indistinguishable
 *   under the identity contract (nama + desa_id + tanggal_lahir), those rows
 *   are reported and never auto-updated.
 */
class PersonNormalizeNames extends Command
{
    protected $signature = 'person:normalize-names
        {--apply : Persist normalized nama to the database (default is a read-only dry run)}
        {--limit=50 : Max rows to print per section}';

    protected $description = 'Audit and normalize existing Person name capitalization (Proper Case)';

    public function handle(PersonIdentityNormalizer $normalizer): int
    {
        $limit = (int) $this->option('limit');
        $apply = (bool) $this->option('apply');

        $people = Person::orderBy('id')->get();
        $total = $people->count();

        $this->section('PERSON NAME CAPITALIZATION AUDIT');
        $this->line($this->keyValue('total Person', $total));

        $rows = [];
        $willChange = 0;

        foreach ($people as $person) {
            $normalized = $normalizer->normalizeNama($person->nama);
            $changed = $normalized !== $person->nama;

            $rows[] = [
                'id' => $person->id,
                'nama' => $person->nama,
                'nama_normalized' => $normalized,
                'changed' => $changed,
                'desa_id' => $person->desa_id,
                'tanggal_lahir' => $person->tanggal_lahir?->format('Y-m-d'),
            ];

            if ($changed) {
                $willChange++;
            }
        }

        $this->line($this->keyValue('names needing change', $willChange));

        $groups = [];
        foreach ($rows as $row) {
            $key = $this->identityKey($row['nama_normalized'], $row['desa_id'], $row['tanggal_lahir']);
            $groups[$key][] = $row;
        }

        $collisionGroups = array_values(array_filter($groups, fn (array $members) => count($members) > 1));

        $collisionIds = [];
        foreach ($collisionGroups as $members) {
            foreach ($members as $member) {
                $collisionIds[$member['id']] = true;
            }
        }

        $this->line($this->keyValue('collision groups (identity: nama + desa_id + tanggal_lahir)', count($collisionGroups)));

        $this->section('NAMES TO BE NORMALIZED');

        $toChange = array_values(array_filter($rows, fn (array $row) => $row['changed']));
        $toChangeCount = count($toChange);

        $this->line($this->keyValue('records to update', $toChangeCount));

        foreach (array_slice($toChange, 0, $limit) as $row) {
            $collisionFlag = isset($collisionIds[$row['id']]) ? '  [COLLISION - skipped]' : '';
            $this->line("  #{$row['id']}  {$row['nama']} → {$row['nama_normalized']}{$collisionFlag}");
        }

        if ($toChangeCount > $limit) {
            $this->line('  ... and '.($toChangeCount - $limit).' more records');
        }

        $this->section('COLLISIONS');

        $this->line($this->keyValue('groups', count($collisionGroups)));

        $printed = 0;
        foreach ($collisionGroups as $members) {
            if ($printed >= $limit) {
                break;
            }
            $this->line('  identity: '.$this->describeGroup($members));
            foreach ($members as $member) {
                $this->line("    #{$member['id']}  old: '{$member['nama']}'  →  normalized: '{$member['nama_normalized']}'");
            }
            $printed++;
        }

        if (count($collisionGroups) > $limit) {
            $this->line('  ... and '.count($collisionGroups).' more groups printed at limit');
        }

        if (! $apply) {
            $this->line('');
            $this->info('DRY RUN: no database writes performed. Re-run with --apply to persist.');
            $this->line('Database writes performed: 0');

            return Command::SUCCESS;
        }

        $toUpdate = array_values(array_filter($toChange, fn (array $row) => ! isset($collisionIds[$row['id']])));

        $this->section('APPLYING UPDATES');

        DB::transaction(function () use ($toUpdate) {
            foreach ($toUpdate as $row) {
                Person::whereKey($row['id'])->update(['nama' => $row['nama_normalized']]);
            }
        });

        $this->line($this->keyValue('updated', count($toUpdate)));
        $this->line($this->keyValue('skipped (collision)', $toChangeCount - count($toUpdate)));
        $this->line('Database writes performed: '.count($toUpdate));

        return Command::SUCCESS;
    }

    private function identityKey(string $nama, mixed $desaId, mixed $tanggalLahir): string
    {
        return $nama.'|'.$this->keyPart($desaId).'|'.$this->keyPart($tanggalLahir);
    }

    private function keyPart(mixed $value): string
    {
        return $value === null || $value === '' ? 'NULL' : (string) $value;
    }

    /**
     * @param  array<int, array{id: int, nama: string, nama_normalized: string, desa_id: mixed, tanggal_lahir: ?string}>  $members
     */
    private function describeGroup(array $members): string
    {
        $first = $members[0];

        return "'{$first['nama_normalized']}' | desa_id={$this->keyPart($first['desa_id'])} | tanggal_lahir={$this->keyPart($first['tanggal_lahir'])}";
    }

    private function section(string $title): void
    {
        $this->line('');
        $this->line(str_repeat('=', 60));
        $this->line($title);
        $this->line(str_repeat('-', 60));
    }

    private function keyValue(string $key, mixed $value): string
    {
        return "  {$key}: {$value}";
    }
}
