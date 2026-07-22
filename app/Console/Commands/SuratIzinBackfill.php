<?php

namespace App\Console\Commands;

use App\Services\Attendance\SuratIzinBackfillService;
use Illuminate\Console\Command;

class SuratIzinBackfill extends Command
{
    protected $signature = 'surat-izin:backfill
        {--dry-run : Preview only, no writes}
        {--force : Execute writes}
        {--event= : Scope to specific event ID}';

    protected $description = 'Backfill participation_id for SuratIzin records';

    public function handle(SuratIzinBackfillService $service): int
    {
        $isDryRun = $this->option('dry-run') || ! $this->option('force');
        $eventId = $this->option('event') ? (int) $this->option('event') : null;

        if ($eventId !== null) {
            $this->line("Event scope: {$eventId}");
        }

        $this->line($isDryRun ? 'Mode: DRY RUN (no writes)' : 'Mode: EXECUTE');

        $stats = $isDryRun
            ? $service->dryRun($eventId)
            : $service->backfill($eventId);

        $this->newLine();
        $this->line('=== SuratIzin Participation Backfill ===');
        $this->line("  Total scanned:    {$stats['total']}");
        $this->line("  Mapped:           {$stats['mapped']}");
        $this->line("  Updated:          {$stats['updated']}");
        $this->line("  Skip existing:    {$stats['skip_existing']}");
        $this->line("  No peserta:       {$stats['no_peserta']}");
        $this->line("  No mapping:       {$stats['no_mapping']}");
        $this->line("  No participation: {$stats['no_participation']}");
        $this->line("  Ambiguous:        {$stats['ambiguous']}");
        $this->line("  Errors:           {$stats['errors']}");

        if ($isDryRun) {
            $this->warn('Dry-run complete. Use --force to execute.');
        }

        return 0;
    }
}
