<?php

namespace App\Services\Competition;

use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Models\CompetitionTeamOutcome;
use App\Support\CompetitionFormat;
use Carbon\Carbon;

class CompetitionWorkflowService
{
    public const array LEGAL_TRANSITIONS = [
        'Scheduled' => ['Ready'],
        'Ready' => ['Playing'],
        'Playing' => ['Waiting Result', 'Finished'],
        'Waiting Result' => ['Finished'],
        'Finished' => ['Scheduled'],
    ];

    public function canTransitionTo(CompetitionSchedule $schedule, string $newStatus): bool
    {
        return in_array($newStatus, self::LEGAL_TRANSITIONS[$schedule->status] ?? [], true);
    }

    public function isBracketMatch(CompetitionSchedule $schedule): bool
    {
        return $schedule->bracketMatch()->exists();
    }

    public function requiresOfficial(CompetitionSchedule $schedule): bool
    {
        if ($this->isBracketMatch($schedule)) {
            return true;
        }

        // Non-bracket vs-format (team_vs_team / individual_vs_individual) juga
        // memakai official flow: Playing → Waiting Result → official submit.
        return CompetitionFormat::isVsFormat($schedule->competitionClass?->format);
    }

    /** Match dengan competitor TEAM (team_vs_team / team_mass class). */
    public function isTeamMatch(CompetitionSchedule $schedule): bool
    {
        return $schedule->competitionClass?->isTeamFormat() ?? false;
    }

    public function prepareMatch(CompetitionSchedule $schedule): bool
    {
        if (! $this->canTransitionTo($schedule, 'Ready')) {
            return false;
        }

        if (! $schedule->canAutoReady()) {
            return false;
        }

        $schedule->update(['status' => 'Ready']);

        return true;
    }

    public function startMatch(CompetitionSchedule $schedule): bool
    {
        if (! $this->canTransitionTo($schedule, 'Playing')) {
            return false;
        }

        if (! $schedule->isReadyForStart()) {
            return false;
        }

        $schedule->update(['status' => 'Playing']);

        return true;
    }

    public function completeMatch(CompetitionSchedule $schedule): string
    {
        if ($this->requiresOfficial($schedule)) {
            if (! $this->canTransitionTo($schedule, 'Waiting Result')) {
                return $schedule->status;
            }
            $schedule->update(['status' => 'Waiting Result']);

            return 'Waiting Result';
        }

        if (! $this->canTransitionTo($schedule, 'Finished')) {
            return $schedule->status;
        }

        $schedule->update(['status' => 'Finished']);

        return 'Finished';
    }

    public function finishMatch(CompetitionSchedule $schedule): bool
    {
        if ($this->requiresOfficial($schedule)) {
            return false;
        }

        if (! $this->canTransitionTo($schedule, 'Finished')) {
            return false;
        }

        $schedule->update(['status' => 'Finished']);

        $this->finalizePodium($schedule);

        return true;
    }

    public function moveToWaitingResult(CompetitionSchedule $schedule): bool
    {
        if (! $this->canTransitionTo($schedule, 'Waiting Result')) {
            return false;
        }

        $schedule->update(['status' => 'Waiting Result']);

        return true;
    }

    public function submitResult(
        CompetitionSchedule $schedule,
        int $winnerRegistrationId,
        string $finishReason,
        ?string $finishNotes
    ): void {
        if (! $this->canTransitionTo($schedule, 'Finished')) {
            throw new \RuntimeException('Cannot submit result: match status does not allow transition to Finished.');
        }

        $schedule->update([
            'winner_registration_id' => $winnerRegistrationId,
            'finish_reason' => $finishReason,
            'finish_notes' => $finishNotes ?: null,
            'finished_at' => Carbon::now(),
            'finished_by' => auth()->id(),
            'status' => 'Finished',
        ]);

        $this->advanceWinner($schedule);

        $this->finalizePodium($schedule);
    }

    public function advanceWinner(CompetitionSchedule $schedule): void
    {
        $bracketMatch = $schedule->bracketMatch;
        if (! $bracketMatch) {
            return;
        }

        $winnerRegId = $schedule->winner_registration_id;
        if (! $winnerRegId) {
            return;
        }

        $nextMatch = CompetitionBracketMatch::where(function ($q) use ($bracketMatch) {
            $q->where('source_match_a_id', $bracketMatch->id)
                ->orWhere('source_match_b_id', $bracketMatch->id);
        })
            ->with('schedule')
            ->first();

        if (! $nextMatch || ! $nextMatch->schedule) {
            return;
        }

        $existingEntry = CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->where('competition_registration_id', $winnerRegId)
            ->exists();

        if ($existingEntry) {
            return;
        }

        $isSourceA = $nextMatch->source_match_a_id === $bracketMatch->id;

        $maxOrder = CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->max('order_number') ?? 0;

        $assignedCount = CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->count();

        if ($assignedCount >= 2) {
            return;
        }

        CompetitionScheduleEntry::create([
            'competition_schedule_id' => $nextMatch->schedule->id,
            'competition_registration_id' => $winnerRegId,
            'order_number' => $maxOrder + 1,
            'corner' => $isSourceA ? 'Merah' : 'Biru',
            'position' => $isSourceA ? 1 : 2,
        ]);

        $nextSchedule = $nextMatch->schedule;
        if ($nextSchedule->status === 'Scheduled' && $nextSchedule->canAutoReady()) {
            $nextSchedule->update(['status' => 'Ready']);
        }
    }

    /**
     * Submit hasil pertandingan dengan pemenang TEAM (Team vs Team).
     *
     * - `$eventId` (opsional): bila diberikan, schedule wajib milik event tsb.
     * - Winner team wajib merupakan salah satu entry (competition_team_id) schedule.
     * - Bracket advancement hanya berjalan bila schedule memang bracket.
     */
    public function submitTeamResult(
        CompetitionSchedule $schedule,
        int $winnerTeamId,
        string $finishReason,
        ?string $finishNotes,
        ?int $eventId = null,
    ): void {
        if ($eventId !== null) {
            $classEventId = $schedule->competitionClass?->event_id;
            if ($classEventId === null || (int) $classEventId !== (int) $eventId) {
                throw new \RuntimeException('Match does not belong to the active event.');
            }
        }

        $isEntry = $schedule->scheduleEntries()
            ->where('competition_team_id', $winnerTeamId)
            ->exists();

        if (! $isEntry) {
            throw new \RuntimeException('Winner team must be an entry of this match.');
        }

        if (! $this->canTransitionTo($schedule, 'Finished')) {
            throw new \RuntimeException('Cannot submit result: match status does not allow transition to Finished.');
        }

        $schedule->update([
            'winner_team_id' => $winnerTeamId,
            'finish_reason' => $finishReason,
            'finish_notes' => $finishNotes ?: null,
            'finished_at' => Carbon::now(),
            'finished_by' => auth()->id(),
            'status' => 'Finished',
        ]);

        $this->advanceWinnerTeam($schedule);

        $this->finalizePodium($schedule);
    }

    /**
     * Salin pemenang TEAM ke match berikutnya (bracket).
     */
    public function advanceWinnerTeam(CompetitionSchedule $schedule): void
    {
        $bracketMatch = $schedule->bracketMatch;
        if (! $bracketMatch) {
            return;
        }

        $winnerTeamId = $schedule->winner_team_id;
        if (! $winnerTeamId) {
            return;
        }

        $nextMatch = CompetitionBracketMatch::where(function ($q) use ($bracketMatch) {
            $q->where('source_match_a_id', $bracketMatch->id)
                ->orWhere('source_match_b_id', $bracketMatch->id);
        })
            ->with('schedule')
            ->first();

        if (! $nextMatch || ! $nextMatch->schedule) {
            return;
        }

        $existingEntry = CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->where('competition_team_id', $winnerTeamId)
            ->exists();

        if ($existingEntry) {
            return;
        }

        $isSourceA = $nextMatch->source_match_a_id === $bracketMatch->id;

        $maxOrder = CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->max('order_number') ?? 0;

        $assignedCount = CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->count();

        if ($assignedCount >= 2) {
            return;
        }

        CompetitionScheduleEntry::create([
            'competition_schedule_id' => $nextMatch->schedule->id,
            'competition_team_id' => $winnerTeamId,
            'order_number' => $maxOrder + 1,
            'corner' => $isSourceA ? 'Merah' : 'Biru',
            'position' => $isSourceA ? 1 : 2,
        ]);

        $nextSchedule = $nextMatch->schedule;
        if ($nextSchedule->status === 'Scheduled' && $nextSchedule->canAutoReady()) {
            $nextSchedule->update(['status' => 'Ready']);
        }
    }

    public function rollbackTeamAdvancement(CompetitionSchedule $schedule): void
    {
        $bracketMatch = $schedule->bracketMatch;
        if (! $bracketMatch) {
            return;
        }

        $winnerTeamId = $schedule->winner_team_id;
        if (! $winnerTeamId) {
            return;
        }

        $nextMatch = CompetitionBracketMatch::where(function ($q) use ($bracketMatch) {
            $q->where('source_match_a_id', $bracketMatch->id)
                ->orWhere('source_match_b_id', $bracketMatch->id);
        })
            ->with('schedule')
            ->first();

        if (! $nextMatch || ! $nextMatch->schedule) {
            return;
        }

        $nextSchedule = $nextMatch->schedule;

        $entry = CompetitionScheduleEntry::where('competition_schedule_id', $nextSchedule->id)
            ->where('competition_team_id', $winnerTeamId)
            ->first();

        if (! $entry) {
            return;
        }

        $entry->delete();

        $remainingCount = CompetitionScheduleEntry::where('competition_schedule_id', $nextSchedule->id)->count();

        $nextSchedule->refresh();

        if ($nextSchedule->status !== 'Finished' && $remainingCount < ($nextSchedule->required_participants ?? 2)) {
            $nextSchedule->update(['status' => 'Scheduled']);
        }

        $this->rollbackTeamAdvancement($nextSchedule);
    }

    public function rollbackAdvancement(CompetitionSchedule $schedule): void
    {
        $bracketMatch = $schedule->bracketMatch;
        if (! $bracketMatch) {
            return;
        }

        $winnerRegId = $schedule->winner_registration_id;
        if (! $winnerRegId) {
            return;
        }

        $nextMatch = CompetitionBracketMatch::where(function ($q) use ($bracketMatch) {
            $q->where('source_match_a_id', $bracketMatch->id)
                ->orWhere('source_match_b_id', $bracketMatch->id);
        })
            ->with('schedule')
            ->first();

        if (! $nextMatch || ! $nextMatch->schedule) {
            return;
        }

        $nextSchedule = $nextMatch->schedule;

        $entry = CompetitionScheduleEntry::where('competition_schedule_id', $nextSchedule->id)
            ->where('competition_registration_id', $winnerRegId)
            ->first();

        if (! $entry) {
            return;
        }

        $entry->delete();

        $remainingCount = CompetitionScheduleEntry::where('competition_schedule_id', $nextSchedule->id)->count();

        $nextSchedule->refresh();

        if ($nextSchedule->status !== 'Finished' && $remainingCount < ($nextSchedule->required_participants ?? 2)) {
            $nextSchedule->update(['status' => 'Scheduled']);
        }

        $this->rollbackAdvancement($nextSchedule);
    }

    public function resetMatch(CompetitionSchedule $schedule): void
    {
        if (! $this->canTransitionTo($schedule, 'Scheduled')) {
            return;
        }

        if ($this->isTeamMatch($schedule)) {
            $this->rollbackTeamAdvancement($schedule);

            $teamIds = $schedule->scheduleEntries()->pluck('competition_team_id');
            CompetitionTeamOutcome::whereIn('competition_team_id', $teamIds)->delete();
        } else {
            $this->rollbackAdvancement($schedule);

            $entryIds = $schedule->scheduleEntries()->pluck('competition_registration_id');
            CompetitionOutcome::whereIn('competition_registration_id', $entryIds)->delete();
        }

        $schedule->update([
            'winner_registration_id' => null,
            'winner_team_id' => null,
            'finish_reason' => null,
            'finish_notes' => null,
            'finished_at' => null,
            'finished_by' => null,
            'status' => 'Scheduled',
        ]);

        $schedule->refresh();
    }

    public function assignParticipant(CompetitionSchedule $schedule, int $registrationId): void
    {
        CompetitionScheduleEntry::create([
            'competition_schedule_id' => $schedule->id,
            'competition_registration_id' => $registrationId,
        ]);

        $this->checkAutoReady($schedule);
    }

    public function unassignParticipant(CompetitionSchedule $schedule, int $registrationId): void
    {
        CompetitionScheduleEntry::where('competition_schedule_id', $schedule->id)
            ->where('competition_registration_id', $registrationId)
            ->delete();

        $this->checkAutoReady($schedule);
    }

    public function checkAutoReady(CompetitionSchedule $schedule): void
    {
        $schedule->refresh();

        if ($schedule->status === 'Scheduled' && $schedule->canAutoReady()) {
            $schedule->update(['status' => 'Ready']);
        } elseif (in_array($schedule->status, ['Ready', 'Scheduled'], true) && ! $schedule->canAutoReady()) {
            $assignedCount = $schedule->scheduleEntries()->count();
            if ($assignedCount < $schedule->required_participants) {
                $schedule->update(['status' => 'Scheduled']);
            }
        }

        $schedule->refresh();
    }

    /**
     * After a match is Finished, finalize Juara 1/2/3 when it is the final of a
     * single-elimination bracket — registration (Individual vs Individual) or
     * team (Team vs Team).
     */
    private function finalizePodium(CompetitionSchedule $schedule): void
    {
        $service = app(CompetitionBracketPodiumService::class);

        if ($this->isTeamMatch($schedule)) {
            $service->finalizeTeamPodiumForSchedule($schedule);

            return;
        }

        $service->finalizePodiumForSchedule($schedule);
    }
}
