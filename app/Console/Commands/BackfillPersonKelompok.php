<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPersonKelompok extends Command
{
    protected $signature = 'app:backfill-person-kelompok
        {--dry-run : Preview without modifying data}';

    protected $description = 'Backfill people.kelompok_id from pesertas.kelompok_id for NIP-matched records';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('=== BACKFILL people.kelompok_id ===');
        $this->line('');

        // Step 1: Find all matched pairs via NIP
        $matched = DB::select('
            SELECT p.id as peserta_id, p.nip, p.kelompok_id as peserta_kelompok_id,
                   pe.id as person_id, pe.kelompok_id as person_kelompok_id,
                   pe.nama as person_nama, p.nama as peserta_nama
            FROM pesertas p
            JOIN people pe ON p.nip = pe.nip
            WHERE p.kelompok_id IS NOT NULL
        ');

        $totalMatched = count($matched);
        $alreadySet = 0;
        $willUpdate = 0;
        $conflict = 0;
        $conflictRows = [];

        foreach ($matched as $row) {
            if ($row->person_kelompok_id !== null && (int) $row->person_kelompok_id === (int) $row->peserta_kelompok_id) {
                $alreadySet++;
                continue;
            }

            if ($row->person_kelompok_id !== null && (int) $row->person_kelompok_id !== (int) $row->peserta_kelompok_id) {
                $conflict++;
                $conflictRows[] = $row;
                continue;
            }

            $willUpdate++;
        }

        // Step 2: Show summary
        $this->line("Total matched pairs (via NIP): {$totalMatched}");
        $this->line("  Already correct:          {$alreadySet}");
        $this->line("  Will be updated:          {$willUpdate}");
        $this->line("  Conflict (skipped):       {$conflict}");
        $this->line('');

        // Step 3: Show unmatched records
        $unmatchedPesertas = DB::select('
            SELECT p.id, p.nip, p.nama, p.kelompok_id, p.desa_id
            FROM pesertas p
            LEFT JOIN people pe ON p.nip = pe.nip
            WHERE pe.id IS NULL
        ');
        $this->line("Unmatched pesertas (no person with same NIP): " . count($unmatchedPesertas));

        $peopleWithoutNip = DB::table('people')->whereNull('nip')->count();
        $this->line("People without NIP (cannot match): {$peopleWithoutNip}");
        $this->line('');

        if ($conflict > 0) {
            $this->warn("CONFLICTS DETECTED — these will be SKIPPED:");
            foreach ($conflictRows as $r) {
                $this->line("  NIP {$r->nip}: peserta_id={$r->peserta_id} (kelompok_id={$r->peserta_kelompok_id}) vs person_id={$r->person_id} (kelompok_id={$r->person_kelompok_id})");
            }
            $this->line('');
        }

        $this->line("Unmatched pesertas details:");
        foreach ($unmatchedPesertas as $r) {
            $this->line("  id={$r->id} nip={$r->nip} nama='{$r->nama}' kelompok_id={$r->kelompok_id} desa_id={$r->desa_id}");
        }
        $this->line('');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes made.');
            $this->line('');
            $this->info("Would update {$willUpdate} people records with kelompok_id");
            return 0;
        }

        if ($willUpdate === 0) {
            $this->info('Nothing to update.');
            return 0;
        }

        // Step 4: Execute update
        DB::beginTransaction();
        try {
            $updated = 0;
            foreach ($matched as $row) {
                if ($row->person_kelompok_id !== null) {
                    continue; // skip already-set and conflict
                }

                DB::table('people')
                    ->where('id', $row->person_id)
                    ->update(['kelompok_id' => $row->peserta_kelompok_id]);
                $updated++;
            }

            DB::commit();
            $this->info("Updated {$updated} people records with kelompok_id.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('ROLLBACK: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
