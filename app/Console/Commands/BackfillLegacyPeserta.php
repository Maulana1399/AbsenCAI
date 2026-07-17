<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\Migration\LegacyPesertaBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillLegacyPeserta extends Command
{
    protected $signature = 'backfill:legacy-peserta
                            {--dry-run : Perform a dry run without writing (default if --execute not set)}
                            {--execute : Execute the backfill (requires explicit opt-in)}
                            {--event=cai-operational : Target event slug}';

    protected $description = 'Backfill legacy peserta data into Person + Participation + Mapping';

    public function handle(LegacyPesertaBackfillService $service): int
    {
        $dryRun = $this->option('dry-run');
        $execute = $this->option('execute');
        $eventSlug = $this->option('event');

        if ($dryRun && $execute) {
            $this->error('Cannot use --dry-run and --execute together.');

            return Command::FAILURE;
        }

        $dryRun = !$execute;

        $event = Event::where('slug', $eventSlug)->first();

        if (!$event) {
            $this->error("Event with slug '{$eventSlug}' not found.");

            return Command::FAILURE;
        }

        if (!$event->isActive()) {
            $this->error("Event '{$eventSlug}' is not active (status: {$event->status}).");

            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->info('MODE: DRY RUN');
        } else {
            $this->info('MODE: EXECUTE');
        }

        $this->line('');
        $this->line("Target Event: {$event->name} (slug: {$event->slug}, id: {$event->id})");
        $this->line('');

        $batchId = $dryRun ? null : (string) Str::uuid();

        $report = $service->execute($event, $dryRun, $batchId);

        $this->renderReport($report, $dryRun);

        return Command::SUCCESS;
    }

    private function renderReport($report, bool $dryRun): void
    {
        $mappingsToCreate = $report->count('CREATE_PERSON');

        $this->line(str_repeat('=', 60));
        $this->line('BACKFILL REPORT');
        $this->line(str_repeat('-', 60));

        $this->line('');

        $this->line("  Total Legacy Peserta:        {$report->totalPeserta}");
        $this->line("  Already Mapped:               {$report->count('ALREADY_MAPPED')}");
        $this->line("  Person Matched by NIP:        {$report->count('MATCHED_BY_NIP')}");
        $this->line("  Person To Create:             {$mappingsToCreate}");
        $this->line("  Participation Existing:       {$report->count('MATCHED_BY_NIP')}");
        $this->line("  Participation To Create:      {$mappingsToCreate}");
        $this->line("  Mapping To Create:            {$mappingsToCreate}");
        $this->line("  Conflicts:                    {$report->count('CONFLICT')}");
        $this->line("  Review Required:              {$report->count('REVIEW_REQUIRED')}");
        $this->line("  Broken Mapping:               {$report->count('BROKEN_MAPPING')}");
        $this->line("  Drift Detected:               {$report->count('DRIFT_DETECTED')}");
        $this->line("  Errors:                       {$report->count('ERROR')}");
        $this->line("  Skipped:                      {$report->count('SKIPPED')}");

        $this->line('');

        if ($dryRun) {
            $this->line("  Database Writes:              0");
        } else {
            $this->line("  People Created:               {$report->totalPeopleCreated()}");
            $this->line("  Participations Created:       {$report->totalParticipationsCreated()}");
            $this->line("  Mappings Created:             {$report->totalMappingsCreated()}");
            $this->line("  Database Writes:              {$report->totalDatabaseWrites()}");
        }

        $this->renderIssues($report->conflicts(), 'CONFLICTS');
        $this->renderIssues($report->reviews(), 'REVIEW REQUIRED');
        $this->renderIssues($report->errors(), 'ERRORS');
        $this->renderIssues($report->brokenMappings(), 'BROKEN MAPPINGS');
        $this->renderIssues($report->drifts(), 'DRIFT DETECTED');

        $this->line('');
        $this->line(str_repeat('=', 60));
    }

    private function renderIssues(array $items, string $label): void
    {
        if (empty($items)) {
            return;
        }

        $this->line('');
        $this->line("  {$label}:");
        $this->line(str_repeat('-', 60));

        foreach ($items as $item) {
            $this->line("  Peserta #{$item->pesertaId} | NIP: {$item->nip} | {$item->pesertaNama}");
            foreach ($item->messages as $message) {
                $this->line("    -> {$message}");
            }
        }
    }
}
