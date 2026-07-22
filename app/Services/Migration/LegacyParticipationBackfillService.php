<?php

namespace App\Services\Migration;

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\peserta;
use Illuminate\Support\Facades\DB;

class LegacyParticipationBackfillService
{
    public function execute(Event $event, bool $dryRun = true, ?string $batchId = null): array
    {
        $query = LegacyPesertaMapping::with(['peserta', 'person', 'participation'])
            ->where('event_id', $event->id);

        $report = ['created' => 0, 'skipped' => 0, 'conflicts' => 0, 'missing' => 0, 'rows' => []];

        foreach ($query->get() as $legacy) {
            $row = ['peserta_id' => $legacy->peserta_id, 'event_id' => $legacy->event_id, 'status' => 'skipped'];

            if (! $legacy->peserta || ! $legacy->person || ! $legacy->participation) {
                $report['missing']++;
                $row['status'] = 'missing';
                $report['rows'][] = $row;
                continue;
            }

            if ((int) $legacy->participation->event_id !== (int) $legacy->event_id || (int) $legacy->participation->person_id !== (int) $legacy->person_id) {
                $report['conflicts']++;
                $row['status'] = 'conflict';
                $report['rows'][] = $row;
                continue;
            }

            if (LegacyParticipationMapping::where('participation_id', $legacy->participation_id)->exists() || LegacyParticipationMapping::where('peserta_id', $legacy->peserta_id)->where('event_id', $legacy->event_id)->exists()) {
                $report['skipped']++;
                $row['status'] = 'exists';
                $report['rows'][] = $row;
                continue;
            }

            if ($dryRun) {
                $report['created']++;
                $row['status'] = 'would_create';
                $report['rows'][] = $row;
                continue;
            }

            DB::transaction(function () use ($legacy, $batchId) {
                LegacyParticipationMapping::create([
                    'peserta_id' => $legacy->peserta_id,
                    'person_id' => $legacy->person_id,
                    'participation_id' => $legacy->participation_id,
                    'event_id' => $legacy->event_id,
                    'backfill_batch_id' => $batchId,
                    'migrated_at' => now(),
                ]);
            });

            $report['created']++;
            $row['status'] = 'created';
            $report['rows'][] = $row;
        }

        return $report;
    }
}
