<?php

namespace App\Console\Commands;

use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DesignCDiagnostics extends Command
{
    protected $signature = 'diagnose:design-c
        {--limit=20 : Maximum rows shown per diagnostic section}';

    protected $description = 'Read-only diagnostics for Design C bridge and participation integrity';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $this->line('=== Design C Integrity Diagnostic (READ-ONLY) ===');
        $this->newLine();

        $checks = [
            'participation_without_person' => Participation::whereNull('person_id')->count(),
            'legacy_participation_without_participation' => LegacyParticipationMapping::whereDoesntHave('participation')->count(),
            'duplicate_legacy_participation_participation_id' => LegacyParticipationMapping::select('participation_id', DB::raw('COUNT(*) as cnt'))->groupBy('participation_id')->having('cnt', '>', 1)->count(),
            'duplicate_legacy_participation_peserta_event' => LegacyParticipationMapping::select('peserta_id', 'event_id', DB::raw('COUNT(*) as cnt'))->groupBy('peserta_id', 'event_id')->having('cnt', '>', 1)->count(),
            'legacy_peserta_pointing_to_missing_participation' => LegacyPesertaMapping::whereNotNull('participation_id')->whereDoesntHave('participation')->count(),
            'bridge_event_mismatch' => LegacyParticipationMapping::whereHas('participation', fn ($q) => $q->whereColumn('participations.event_id', '!=', 'legacy_participation_mappings.event_id'))->count(),
            'bridge_person_mismatch' => LegacyParticipationMapping::whereHas('participation', fn ($q) => $q->whereColumn('participations.person_id', '!=', 'legacy_participation_mappings.person_id'))->count(),
            'orphan_legacy_bridge' => LegacyParticipationMapping::whereNull('participation_id')->count(),
        ];

        foreach ($checks as $label => $count) {
            $this->line(str_pad($label, 56) . ': ' . $count);
        }

        $problemCount = array_sum($checks);
        $this->newLine();
        $this->line('problem_total: ' . $problemCount);

        return Command::SUCCESS;
    }
}
