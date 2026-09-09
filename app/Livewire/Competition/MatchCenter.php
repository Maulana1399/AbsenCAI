<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionClass;
use App\Models\CompetitionMatchOfficial;
use App\Models\CompetitionSchedule;
use App\Models\User;
use App\Services\Competition\CompetitionWorkflowService;
use App\Support\ActiveEventContext;
use App\Support\CompetitionFormat;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class MatchCenter extends Component
{
    public ?string $filterVenueId = null;

    /** Filter jenis pertandingan: null = semua, 'heat', atau 'bracket'. */
    public ?string $filterType = null;

    public bool $showOfficialDialog = false;

    public ?int $officialScheduleId = null;

    public string $newOfficialUserId = '';

    public string $newOfficialRole = 'referee';

    protected function rules(): array
    {
        return [
            'newOfficialUserId' => 'required|integer|exists:users,id',
            'newOfficialRole' => 'required|in:referee,judge,scorer,supervisor',
        ];
    }

    protected $messages = [
        'newOfficialUserId.required' => 'Pilih official.',
        'newOfficialRole.required' => 'Pilih peran.',
    ];

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function filterByVenue($id = null): void
    {
        $this->filterVenueId = $id ? (string) $id : null;
    }

    public function setFilterType(?string $type): void
    {
        $this->filterType = in_array($type, ['heat', 'bracket'], true) ? $type : null;
    }

    private function workflow(): CompetitionWorkflowService
    {
        return app(CompetitionWorkflowService::class);
    }

    public function startMatch(int $scheduleId): void
    {
        Gate::authorize('manage-matches');

        $schedule = CompetitionSchedule::withCount('scheduleEntries as participants_count')->findOrFail($scheduleId);

        if (! $this->workflow()->startMatch($schedule)) {
            $required = $schedule->minParticipantsToStart();
            $current = $schedule->participants_count ?? 0;
            session()->flash('error', "Cannot start match: need {$required} participant(s), currently {$current} assigned.");

            return;
        }

        session()->flash('success', 'Match started.');
    }

    public function moveToWaitingResult(int $scheduleId): void
    {
        Gate::authorize('manage-matches');

        $schedule = CompetitionSchedule::findOrFail($scheduleId);

        if (! $this->workflow()->moveToWaitingResult($schedule)) {
            session()->flash('error', 'Only Playing matches can be sent to Waiting Result.');

            return;
        }

        session()->flash('success', 'Match menunggu hasil dari official.');
    }

    // Official assignment
    public function openOfficialDialog(int $scheduleId): void
    {
        Gate::authorize('manage-officials');

        $this->officialScheduleId = $scheduleId;
        $this->newOfficialUserId = '';
        $this->newOfficialRole = 'referee';
        $this->showOfficialDialog = true;
        $this->resetErrorBag();
    }

    public function assignOfficial(): void
    {
        Gate::authorize('manage-officials');

        $this->validate();

        CompetitionMatchOfficial::create([
            'competition_schedule_id' => $this->officialScheduleId,
            'user_id' => $this->newOfficialUserId,
            'role' => $this->newOfficialRole,
        ]);

        $this->newOfficialUserId = '';
        session()->flash('success', 'Official ditambahkan.');
    }

    public function removeOfficial(int $officialId): void
    {
        Gate::authorize('manage-officials');

        CompetitionMatchOfficial::findOrFail($officialId)->delete();
        session()->flash('success', 'Official dihapus.');
    }

    public function closeOfficialDialog(): void
    {
        $this->showOfficialDialog = false;
        $this->officialScheduleId = null;
        $this->newOfficialUserId = '';
        $this->newOfficialRole = 'referee';
        $this->resetErrorBag();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $classIds = CompetitionClass::where('event_id', $event?->id)->pluck('id');

        $schedules = CompetitionSchedule::with([
            'competitionClass.competitionCategory',
            'venue',
            'scheduleEntries.competitionRegistration.participation.person',
            'scheduleEntries.team',
            'winner.participation.person',
            'winnerTeam',
            'finishedBy',
            'matchOfficials.user',
        ])
            ->withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', $classIds)
            ->whereIn('status', ['Ready', 'Playing', 'Waiting Result'])
            ->when($this->filterVenueId, fn ($q) => $q->where('venue_id', $this->filterVenueId))
            ->when($this->filterType === 'bracket', fn ($q) => $q->whereHas('bracketMatch'))
            ->when($this->filterType === 'heat', fn ($q) => $q->whereHas('competitionClass', fn ($q2) => $q2->whereIn('format', [CompetitionFormat::INDIVIDUAL_HEAT, CompetitionFormat::TEAM_HEAT])))
            ->orderByRaw("CASE WHEN status = 'Playing' THEN 0 WHEN status = 'Waiting Result' THEN 1 ELSE 2 END")
            ->orderBy('sort_order')
            ->orderBy('start_at')
            ->get();

        $playing = $schedules->where('status', 'Playing');
        $waitingResult = $schedules->where('status', 'Waiting Result');
        $ready = $schedules->where('status', 'Ready');

        $countPlaying = CompetitionSchedule::whereIn('competition_class_id', $classIds)
            ->where('status', 'Playing')->count();
        $countWaiting = CompetitionSchedule::whereIn('competition_class_id', $classIds)
            ->where('status', 'Waiting Result')->count();
        $countReady = CompetitionSchedule::whereIn('competition_class_id', $classIds)
            ->where('status', 'Ready')->count();
        $countFinished = CompetitionSchedule::whereIn('competition_class_id', $classIds)
            ->where('status', 'Finished')->count();

        $availableOfficials = User::whereIn('role', ['super_admin', 'admin', 'juri'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('livewire.competition.match-center', [
            'playing' => $playing,
            'waitingResult' => $waitingResult,
            'ready' => $ready,
            'countPlaying' => $countPlaying,
            'countWaiting' => $countWaiting,
            'countReady' => $countReady,
            'countFinished' => $countFinished,
            'venues' => \App\Models\Venue::where('event_id', $event?->id)
                ->orderBy('sort_order')->orderBy('name')->get(),
            'availableOfficials' => $availableOfficials,
        ]);
    }
}
