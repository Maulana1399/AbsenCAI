<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class MatchCenter extends Component
{
    public ?string $filterVenueId = null;

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function filterByVenue($id = null): void
    {
        $this->filterVenueId = $id ? (string) $id : null;
    }

    public function startMatch(int $scheduleId): void
    {
        Gate::authorize('manage-matches');

        $schedule = CompetitionSchedule::withCount('scheduleEntries as participants_count')->findOrFail($scheduleId);

        if ($schedule->status !== 'Ready') {
            session()->flash('error', 'Only Ready matches can be started.');
            return;
        }

        if (!$schedule->isReadyForStart()) {
            $required = $schedule->required_participants ?? 1;
            $current = $schedule->participants_count ?? 0;
            session()->flash('error', "Cannot start match: need {$required} participant(s), currently {$current} assigned.");
            return;
        }

        $schedule->update(['status' => 'Playing']);
        session()->flash('success', 'Match started.');
    }

    public function finishMatch(int $scheduleId): void
    {
        Gate::authorize('manage-matches');

        $schedule = CompetitionSchedule::findOrFail($scheduleId);

        if ($schedule->status !== 'Playing') {
            session()->flash('error', 'Only Playing matches can be finished.');
            return;
        }

        $schedule->update(['status' => 'Finished']);
        session()->flash('success', 'Match finished.');
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $classIds = CompetitionClass::where('event_id', $event?->id)->pluck('id');

        $schedules = CompetitionSchedule::with([
            'competitionClass.competitionCategory',
            'venue',
            'scheduleEntries.competitionRegistration.participation.person',
        ])
            ->withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', $classIds)
            ->whereIn('status', ['Ready', 'Playing'])
            ->when($this->filterVenueId, fn($q) => $q->where('venue_id', $this->filterVenueId))
            ->orderBy('sort_order')
            ->orderBy('start_at')
            ->get();

        return view('livewire.competition.match-center', [
            'schedules' => $schedules,
            'venues' => \App\Models\Venue::where('event_id', $event?->id)
                ->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
