<?php

namespace App\Services\Competition;

use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionTeamOutcome;

/**
 * Bracket Podium (Sprint R3/R4B) — Final → Juara 1/2/3.
 *
 * Registration (Individual vs Individual) → competition_outcomes.
 * Team (Team vs Team) → competition_team_outcomes.
 *
 * - Juara 1 = pemenang final (round 1).
 * - Juara 2 = runner-up final.
 * - Juara 3 = semifinal losers (round 2) — tied 3rd (single-elim, tanpa bronze).
 */
class CompetitionBracketPodiumService
{
    /**
     * @return array{
     *     finalized: bool,
     *     reason?: string,
     *     positions?: array<int, int>, // registration_id => position
     * }
     */
    public function finalizePodiumForSchedule(CompetitionSchedule $schedule): array
    {
        $bracketMatch = $schedule->bracketMatch;

        if (! $bracketMatch || (int) $bracketMatch->round !== 1 || $schedule->status !== 'Finished') {
            return ['finalized' => false, 'reason' => 'not_final'];
        }

        $bracket = $bracketMatch->bracket;
        if (! $bracket) {
            return ['finalized' => false, 'reason' => 'no_bracket'];
        }

        $winner = $schedule->winner_registration_id;
        if ($winner === null) {
            return ['finalized' => false, 'reason' => 'no_winner'];
        }

        $entries = $schedule->scheduleEntries()->pluck('competition_registration_id');
        $finalLoser = $entries->first(fn ($id) => (int) $id !== (int) $winner);

        $positions = [];
        $positions[(int) $winner] = 1;
        if ($finalLoser !== null) {
            $positions[(int) $finalLoser] = 2;
        }

        // Semifinal losers (round 2) — tied 3rd place.
        $semifinals = CompetitionBracketMatch::where('competition_bracket_id', $bracket->id)
            ->where('round', 2)
            ->with('schedule.scheduleEntries')
            ->get();

        foreach ($semifinals as $semi) {
            if (! $semi->schedule || $semi->schedule->status !== 'Finished') {
                continue;
            }

            $semiWinner = $semi->schedule->winner_registration_id;

            foreach ($semi->schedule->scheduleEntries->pluck('competition_registration_id') as $regId) {
                if ((int) $regId !== (int) $semiWinner) {
                    $positions[(int) $regId] = 3;
                }
            }
        }

        foreach ($positions as $registrationId => $position) {
            CompetitionOutcome::updateOrCreate(
                ['competition_registration_id' => $registrationId],
                ['position' => $position, 'status' => 'Juara '.$position, 'remarks' => 'Bracket '.$bracket->name],
            );
        }

        return ['finalized' => true, 'positions' => $positions];
    }

    /**
     * Team version: final of a Team vs Team bracket → Juara 1/2/3 ke
     * `competition_team_outcomes`.
     *
     * @return array{
     *     finalized: bool,
     *     reason?: string,
     *     positions?: array<int, int>, // team_id => position
     * }
     */
    public function finalizeTeamPodiumForSchedule(CompetitionSchedule $schedule): array
    {
        $bracketMatch = $schedule->bracketMatch;

        if (! $bracketMatch || (int) $bracketMatch->round !== 1 || $schedule->status !== 'Finished') {
            return ['finalized' => false, 'reason' => 'not_final'];
        }

        $bracket = $bracketMatch->bracket;
        if (! $bracket) {
            return ['finalized' => false, 'reason' => 'no_bracket'];
        }

        $winner = $schedule->winner_team_id;
        if ($winner === null) {
            return ['finalized' => false, 'reason' => 'no_winner'];
        }

        $entries = $schedule->scheduleEntries()->pluck('competition_team_id');
        $finalLoser = $entries->first(fn ($id) => $id !== null && (int) $id !== (int) $winner);

        $positions = [];
        $positions[(int) $winner] = 1;
        if ($finalLoser !== null) {
            $positions[(int) $finalLoser] = 2;
        }

        // Semifinal losers (round 2) — tied 3rd place.
        $semifinals = CompetitionBracketMatch::where('competition_bracket_id', $bracket->id)
            ->where('round', 2)
            ->with('schedule.scheduleEntries')
            ->get();

        foreach ($semifinals as $semi) {
            if (! $semi->schedule || $semi->schedule->status !== 'Finished') {
                continue;
            }

            $semiWinner = $semi->schedule->winner_team_id;

            foreach ($semi->schedule->scheduleEntries->pluck('competition_team_id') as $teamId) {
                if ($teamId !== null && (int) $teamId !== (int) $semiWinner) {
                    $positions[(int) $teamId] = 3;
                }
            }
        }

        foreach ($positions as $teamId => $position) {
            CompetitionTeamOutcome::updateOrCreate(
                ['competition_team_id' => $teamId],
                ['position' => $position, 'status' => 'Juara '.$position, 'remarks' => 'Bracket '.$bracket->name],
            );
        }

        return ['finalized' => true, 'positions' => $positions];
    }
}
