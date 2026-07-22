<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildLegacyMappings extends Command
{
    protected $signature = 'app:rebuild-legacy-mappings
        {--dry-run : Preview without modifying data}';

    protected $description = 'Rebuild legacy_peserta_mappings for valid Person/Peserta pairs (NIP match only)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('=== REBUILD legacy_peserta_mappings ===');
        $this->line('');

        // Step 1: Find existing mappings
        $existingCount = DB::table('legacy_peserta_mappings')->count();
        $this->line("Existing mappings: {$existingCount}");
        $this->line('');

        // Step 2: Find all matched Person/Peserta pairs via NIP
        $matched = DB::select('
            SELECT p.id as peserta_id, p.nip,
                   pe.id as person_id
            FROM pesertas p
            JOIN people pe ON p.nip = pe.nip
            ORDER BY p.id
        ');

        $totalMatched = count($matched);
        $this->line("Matched pairs (via NIP): {$totalMatched}");
        $this->line('');

        // Step 3: Check which already have mappings
        $willCreate = 0;
        $alreadyMapped = 0;
        $created = [];

        foreach ($matched as $row) {
            $exists = DB::table('legacy_peserta_mappings')
                ->where('peserta_id', $row->peserta_id)
                ->where('person_id', $row->person_id)
                ->exists();

            if ($exists) {
                $alreadyMapped++;
                continue;
            }

            $willCreate++;
            $created[] = $row;
        }

        $this->line("Already mapped:  {$alreadyMapped}");
        $this->line("Will create:     {$willCreate}");
        $this->line('');

        // Step 4: Show unmatched records
        $unmatchedPesertas = DB::select('
            SELECT p.id, p.nip, p.nama
            FROM pesertas p
            LEFT JOIN people pe ON p.nip = pe.nip
            WHERE pe.id IS NULL
        ');

        $this->line("Unmatched pesertas (no person): " . count($unmatchedPesertas));
        foreach ($unmatchedPesertas as $r) {
            $this->line("  id={$r->id} nip={$r->nip} nama='{$r->nama}'");
        }

        $unmatchedPeople = DB::select('
            SELECT pe.id, pe.nip, pe.nama
            FROM people pe
            LEFT JOIN pesertas p ON pe.nip = p.nip
            WHERE p.id IS NULL
        ');

        $this->line("Unmatched people (no peserta): " . count($unmatchedPeople));
        foreach ($unmatchedPeople as $r) {
            $this->line("  id={$r->id} nip={$r->nip} nama='{$r->nama}'");
        }
        $this->line('');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes made.');
            $this->line('');
            $this->info("Would create {$willCreate} legacy_peserta_mappings.");
            return 0;
        }

        if ($willCreate === 0) {
            $this->info('Nothing to create — all pairs already mapped.');
            return 0;
        }

        // Step 5: Execute create
        DB::beginTransaction();
        try {
            $inserted = 0;
            foreach ($created as $row) {
                DB::table('legacy_peserta_mappings')->insert([
                    'peserta_id' => $row->peserta_id,
                    'person_id' => $row->person_id,
                    'participation_id' => null,
                    'event_id' => null,
                    'backfill_batch_id' => 'rebuild-' . now()->format('YmdHis'),
                    'legacy_nip' => DB::table('pesertas')->where('id', $row->peserta_id)->value('nip'),
                    'legacy_participant_number' => DB::table('pesertas')->where('id', $row->peserta_id)->value('participant_number'),
                    'legacy_attendance_code' => DB::table('pesertas')->where('id', $row->peserta_id)->value('attendance_code'),
                    'migrated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }

            DB::commit();
            $this->info("Created {$inserted} legacy_peserta_mappings.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('ROLLBACK: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
