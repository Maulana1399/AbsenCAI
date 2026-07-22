<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\Migration\LegacyParticipationBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillLegacyParticipation extends Command
{
    protected $signature = 'backfill:legacy-participation {--dry-run : Dry run} {--execute : Execute} {--event= : Target event slug}';

    protected $description = 'Backfill legacy participation bridge from LegacyPesertaMapping';

    public function handle(LegacyParticipationBackfillService $service): int
    {
        $dryRun = ! $this->option('execute');
        $eventSlug = $this->option('event');

        $event = $eventSlug ? Event::where('slug', $eventSlug)->first() : Event::active()->first();
        if (! $event) return self::FAILURE;

        $report = $service->execute($event, $dryRun, $dryRun ? null : (string) Str::uuid());
        $this->table(['created','skipped','conflicts','missing'], [[ $report['created'], $report['skipped'], $report['conflicts'], $report['missing'] ]]);
        return self::SUCCESS;
    }
}
