<?php

namespace App\Console\Commands;

use App\Services\Attendance\AttendanceParityService;
use Illuminate\Console\Command;

class AttendanceParity extends Command
{
    protected $signature = 'attendance:parity
        {--event= : Audit specific event ID}
        {--mode=migration : Audit mode (migration or canonical)}';

    protected $description = 'Compare legacy CAI attendance against canonical EventAttendance';

    public function handle(AttendanceParityService $service): int
    {
        $eventId = $this->option('event') ? (int) $this->option('event') : null;
        $mode = $this->option('mode') ?? 'migration';

        if (! in_array($mode, ['migration', 'canonical'], true)) {
            $this->error("Invalid mode: {$mode}. Use 'migration' or 'canonical'.");
            return 1;
        }

        if ($eventId !== null) {
            $result = $service->audit($eventId, $mode);
            $this->outputEventTable([$result]);
            $this->newLine();
            $this->outputStatus($result);
            $this->newLine();
            $this->outputTotals($result, true);
        } else {
            $allResults = $service->auditAll($mode);
            $this->outputEventTable($allResults['events']);
            $this->newLine();
            $this->outputStatus($allResults['totals']);
            $this->newLine();
            $this->outputTotals($allResults['totals'], false);
        }

        return 0;
    }

    private function outputEventTable(array $results): void
    {
        $headers = ['Event ID', 'Event Name', 'Legacy H', 'Legacy I', 'Canon H', 'Canon I', 'Matched', 'Missing', 'Orphan', 'Unmap', 'Conflict', 'Parity%'];

        $rows = [];
        foreach ($results as $r) {
            $rows[] = [
                $r['event_id'],
                $r['event_name'],
                $r['legacy_hadir_count'],
                $r['legacy_izin_count'],
                $r['canonical_hadir_count'],
                $r['canonical_izin_count'],
                $r['matched'],
                $r['missing_canonical'],
                $r['orphan_canonical'],
                $r['unmappable'],
                $r['status_conflicts'],
                $r['parity_percentage'] . '%',
            ];
        }

        $this->table($headers, $rows);
    }

    private function outputStatus(array $result): void
    {
        $go = $result['missing_canonical'] === 0
            && $result['orphan_canonical'] === 0
            && $result['status_conflicts'] === 0
            && $result['parity_percentage'] === 100.0;

        $warning = $result['parity_percentage'] >= 95.0 && ! $go;

        if ($go) {
            $this->info('STATUS: GO');
        } elseif ($warning) {
            $this->warn('STATUS: WARNING');
        } else {
            $this->error('STATUS: NO-GO');
        }

        $this->line("  missing_canonical={$result['missing_canonical']} orphan_canonical={$result['orphan_canonical']} conflicts={$result['status_conflicts']} parity={$result['parity_percentage']}%");
    }

    private function outputTotals(array $totals, bool $singleEvent): void
    {
        if ($singleEvent) {
            $this->line("  Legacy: {$totals['legacy_total']} | Canonical: {$totals['canonical_total']} | Matched: {$totals['matched']}");
            $this->line("  Missing canonical: {$totals['missing_canonical']} | Orphan canonical: {$totals['orphan_canonical']} | Unmappable: {$totals['unmappable']} | Conflicts: {$totals['status_conflicts']}");
            $this->line("  Expected mappable: {$totals['expected_mappable']}");
        } else {
            $this->line("  Events audited: " . count($this->laravel->make(AttendanceParityService::class)->auditAll()['events']));
            $this->line("  Legacy total: {$totals['legacy_total']} | Canonical total: {$totals['canonical_total']}");
            $this->line("  Matched: {$totals['matched']} | Missing: {$totals['missing_canonical']} | Orphan: {$totals['orphan_canonical']} | Unmappable: {$totals['unmappable']} | Conflicts: {$totals['status_conflicts']}");
        }
    }
}
