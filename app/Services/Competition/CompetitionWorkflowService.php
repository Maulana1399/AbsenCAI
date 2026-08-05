<?php

namespace App\Services\Competition;

use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionClass;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Support\ActiveEventContext;
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
        return $this->isBracketMatch($schedule);
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

        $this->promoteReadyMatch($schedule->venue_id);

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

        $this->promoteReadyMatch($schedule->venue_id);

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

        $venueId = $schedule->venue_id;

        $schedule->update([
            'winner_registration_id' => $winnerRegistrationId,
            'finish_reason' => $finishReason,
            'finish_notes' => $finishNotes ?: null,
            'finished_at' => Carbon::now(),
            'finished_by' => auth()->id(),
            'status' => 'Finished',
        ]);

        $this->advanceWinner($schedule);

        $this->promoteReadyMatch($venueId);
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

        $this->rollbackAdvancement($schedule);

        $entryIds = $schedule->scheduleEntries()->pluck('competition_registration_id');

        CompetitionOutcome::whereIn('competition_registration_id', $entryIds)->delete();

        $schedule->update([
            'winner_registration_id' => null,
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

    public function promoteReadyMatch(?int $venueId): void
    {
        $event = app(ActiveEventContext::class)->current();
        if (! $event) {
            return;
        }

        $classIds = CompetitionClass::where('event_id', $event->id)->pluck('id');

        $nextReady = CompetitionSchedule::withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', $classIds)
            ->where('status', 'Ready')
            ->where('venue_id', $venueId)
            ->orderBy('sort_order')
            ->orderBy('start_at')
            ->first();

        if ($nextReady) {
            $nextReady->update(['status' => 'Playing']);
        }
    }
}
