<?php

namespace App\Console\Commands;

use App\Services\Attendance\AttendanceBackfillService;
use Illuminate\Console\Command;

class AttendanceBackfill extends Command
{
    protected $signature = 'attendance:backfill
        {--dry-run : Preview only, no writes}
        {--force : Execute writes}
        {--event= : Scope to specific event ID}';

    protected $description = 'Backfill legacy Absensi/IzinAbsensi into EventAttendance';

    public function handle(AttendanceBackfillService $service): int
    {
        $isDryRun = $this->option('dry-run') || ! $this->option('force');
        $eventId = $this->option('event') ? (int) $this->option('event') : null;

        if ($eventId !== null) {
            $this->line("Event scope: {$eventId}");
        }

        $this->line($isDryRun ? 'Mode: DRY RUN (no writes)' : 'Mode: EXECUTE');

        $hadirStats = $isDryRun
            ? $service->dryRunHadir($eventId)
            : $service->backfillHadir($eventId);

        $izinStats = $isDryRun
            ? $service->dryRunIzin($eventId)
            : $service->backfillIzin($eventId);

        $suratStats = $isDryRun
            ? $service->dryRunSuratIzin($eventId)
            : $service->backfillSuratIzin($eventId);

        $this->newLine();
        $this->line('=== HADIR (Absensi → EventAttendance) ===');
        $this->outputStats($hadirStats);

        $this->newLine();
        $this->line('=== IZIN (IzinAbsensi → EventAttendance) ===');
        $this->outputStats($izinStats);

        $this->newLine();
        $this->line('=== SURAT IZIN (event_id backfill) ===');
        $this->outputSuratStats($suratStats);

        $this->newLine();
        $totalCreated = ($hadirStats['created'] ?? 0) + ($izinStats['created'] ?? 0);
        $totalSuratUpdated = $suratStats['updated'] ?? 0;
        $this->info("Total EventAttendance records to create: {$totalCreated}");
        $this->info("Total SuratIzin event_id to update: {$totalSuratUpdated}");

        if ($isDryRun) {
            $this->warn('Dry-run complete. Use --force to execute.');
        }

        return 0;
    }

    private function outputStats(array $stats): void
    {
        $this->line("  Total scanned:    {$stats['total_scanned']}");
        $this->line("  Mapped:           {$stats['mapped']}");
        $this->line("  Created:          {$stats['created']}");
        $this->line("  Skip existing:    {$stats['skip_existing']}");
        $this->line("  Skip no session:  {$stats['skip_no_session']}");
        $this->line("  Skip no event:    {$stats['skip_no_event']}");
        $this->line("  Skip no peserta:  {$stats['skip_no_peserta']}");
        $this->line("  Skip no mapping:  {$stats['skip_no_mapping']}");
        $this->line("  Skip no part.:    {$stats['skip_no_participation']}");
        $this->line("  Skip ambiguous:   {$stats['skip_ambiguous']}");
        $this->line("  Errors:           {$stats['errors']}");
    }

    private function outputSuratStats(array $stats): void
    {
        $this->line("  Total scanned:    {$stats['total']}");
        $this->line("  Mapped:           {$stats['mapped']}");
        $this->line("  Updated:          {$stats['updated']}");
        $this->line("  Ambiguous:        {$stats['ambiguous']}");
        $this->line("  Unmappable:       {$stats['unmappable']}");
        $this->line("  Skip existing:    {$stats['skip_existing']}");
        $this->line("  Errors:           {$stats['errors']}");
    }
}
