<?php

namespace App\Services\Competition;

use App\Models\CompetitionBracket;
use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionClass;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionScheduleEntry;
use App\Models\CompetitionTeam;

/**
 * Auto-seed initial round of a single-elimination bracket (Sprint R4E + R4G).
 *
 * Fills `competition_schedule_entries` of the FIRST-round matches (round =
 * totalRounds) from the class competitors deterministically:
 *
 *   Individual vs Individual:  [A,B,C,D] → M1 = A+B, M2 = C+D
 *   Team vs Team:              [A,B,C,D] → M1 = A+B, M2 = C+D
 *
 * Contract:
 * - Event-scoped (class wajib milik event).
 * - Class-scoped (hanya competitor class bracket).
 * - Idempotent (tidak membuat duplicate; tidak overwrite entry yang ada).
 * - Hanya initial round; round berikutnya tetap TBD sampai winner advancement.
 * - Match yang sudah punya entry (manual) TIDAK di-overwrite.
 * - Setelah seeding, match round pertama yang entry-nya sudah lengkap di-
 *   promote ke `Ready` via `CompetitionWorkflowService::checkAutoReady()`
 *   sehingga muncul di Match Center (Sprint R4G).
 */
class CompetitionBracketSeederService
{
    public function __construct(
        private readonly CompetitionWorkflowService $workflow,
    ) {}

    /**
     * @return array{
     *     seeded: int,
     *     initial_matches: int,
     *     competitor_type: string,
     * }
     */
    public function seedInitialRound(int $eventId, int $bracketId): array
    {
        $bracket = CompetitionBracket::findOrFail($bracketId);

        $class = CompetitionClass::where('event_id', $eventId)
            ->findOrFail($bracket->competition_class_id);

        $totalRounds = (int) log($bracket->participant_count, 2);

        $initialMatches = CompetitionBracketMatch::where('competition_bracket_id', $bracket->id)
            ->where('round', $totalRounds)
            ->orderBy('position')
            ->get();

        if ($class->isTeamFormat()) {
            $competitors = CompetitionTeam::where('event_id', $eventId)
                ->where('competition_class_id', $class->id)
                ->orderBy('id')
                ->get();
            $column = 'competition_team_id';
            $competitorType = 'team';
        } else {
            $competitors = CompetitionRegistration::where('competition_class_id', $class->id)
                ->orderBy('id')
                ->get();
            $column = 'competition_registration_id';
            $competitorType = 'registration';
        }

        $seeded = 0;

        foreach ($initialMatches as $match) {
            // Jangan campur dengan seeding manual: match yang sudah punya entry
            // dibiarkan (dan slot pasangannya tidak dipakai untuk match lain).
            if ($match->schedule === null || $match->schedule->scheduleEntries()->exists()) {
                continue;
            }

            // Pasangan deterministik berdasarkan posisi match:
            // match posisi P mengambil competitor [2*(P-1), 2*(P-1)+1].
            $base = ($match->position - 1) * 2;

            for ($slot = 0; $slot < 2; $slot++) {
                $competitor = $competitors->get($base + $slot);

                if ($competitor === null) {
                    break;
                }

                CompetitionScheduleEntry::create([
                    'competition_schedule_id' => $match->schedule->id,
                    $column => $competitor->id,
                    'order_number' => $slot + 1,
                ]);

                $seeded++;
            }

            // Sprint R4G: setelah seeding, evaluasi status lewat workflow
            // existing. Hanya match yang entry-nya sudah memenuhi kebutuhan
            // (required_participants) yang naik ke `Ready`; yang masih kurang
            // tetap `Scheduled` (TBD). checkAutoReady() idempotent.
            $this->workflow->checkAutoReady($match->schedule);
        }

        return [
            'seeded' => $seeded,
            'initial_matches' => $initialMatches->count(),
            'competitor_type' => $competitorType,
        ];
    }
}
