<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Services\Competition\CompetitionWorkflowService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class OfficialPanel extends Component
{
    public bool $showSubmitDialog = false;

    public ?int $submitScheduleId = null;

    public ?int $selectedWinnerId = null;

    public string $finishReason = '';

    public string $finishNotes = '';

    public array $availableParticipants = [];

    protected function rules(): array
    {
        return [
            'selectedWinnerId' => 'required|integer',
            'finishReason' => 'required|in:Normal,Walk Over (WO),Disqualification (DQ),Cancel',
            'finishNotes' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'selectedWinnerId.required' => 'Pilih pemenang pertandingan.',
        'finishReason.required' => 'Pilih alasan penyelesaian pertandingan.',
        'finishNotes.max' => 'Catatan maksimal 1000 karakter.',
    ];

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function openSubmitDialog(int $scheduleId): void
    {
        Gate::authorize('submit-result');

        $schedule = CompetitionSchedule::with([
            'scheduleEntries.competitionRegistration.participation.person',
            'scheduleEntries.team',
        ])->findOrFail($scheduleId);

        if ($schedule->status !== 'Waiting Result') {
            session()->flash('error', 'Only Waiting Result matches can be submitted.');

            return;
        }

        $this->submitScheduleId = $schedule->id;
        $this->selectedWinnerId = null;
        $this->finishReason = '';
        $this->finishNotes = '';

        $isTeamMatch = $schedule->competitionClass?->isTeamFormat() ?? false;

        $this->availableParticipants = $schedule->scheduleEntries->map(function ($entry) use ($isTeamMatch) {
            if ($isTeamMatch) {
                $team = $entry->team;
                if (! $team) {
                    return null;
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'number' => 'Team',
                ];
            }

            $reg = $entry->competitionRegistration;
            if (! $reg) {
                return null;
            }

            return [
                'id' => $reg->id,
                'name' => $reg->participation?->person?->nama ?? '?',
                'number' => $reg->participation?->participant_number ?? '-',
            ];
        })->filter()->values()->toArray();

        $this->showSubmitDialog = true;
    }

    private function workflow(): CompetitionWorkflowService
    {
        return app(CompetitionWorkflowService::class);
    }

    public function submitResult(): void
    {
        Gate::authorize('submit-result');

        $this->validate();

        $schedule = CompetitionSchedule::with([
            'scheduleEntries.competitionRegistration',
            'scheduleEntries.team',
        ])->findOrFail($this->submitScheduleId);

        if ($schedule->status !== 'Waiting Result') {
            session()->flash('error', 'Match is no longer waiting for result.');
            $this->cancelSubmitDialog();

            return;
        }

        $isTeamMatch = $schedule->competitionClass?->isTeamFormat() ?? false;

        $assignedIds = $isTeamMatch
            ? $schedule->scheduleEntries->pluck('competition_team_id')->filter()->values()->toArray()
            : $schedule->scheduleEntries->pluck('competition_registration_id')->toArray();

        if (! in_array($this->selectedWinnerId, $assignedIds)) {
            session()->flash('error', 'Pemenang harus merupakan peserta yang bertanding.');

            return;
        }

        if ($isTeamMatch) {
            $this->workflow()->submitTeamResult(
                $schedule,
                $this->selectedWinnerId,
                $this->finishReason,
                $this->finishNotes,
                app(ActiveEventContext::class)->requireCurrent()->id,
            );
        } else {
            $this->workflow()->submitResult(
                $schedule,
                $this->selectedWinnerId,
                $this->finishReason,
                $this->finishNotes,
            );
        }

        $this->cancelSubmitDialog();

        session()->flash('success', 'Hasil pertandingan berhasil dikirim.');
    }

    public function cancelSubmitDialog(): void
    {
        $this->showSubmitDialog = false;
        $this->submitScheduleId = null;
        $this->selectedWinnerId = null;
        $this->finishReason = '';
        $this->finishNotes = '';
        $this->availableParticipants = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $classIds = CompetitionClass::where('event_id', $event?->id)->pluck('id');
        $userId = auth()->id();

        $assignedScheduleIds = \App\Models\CompetitionMatchOfficial::where('user_id', $userId)
            ->pluck('competition_schedule_id');

        $waitingMatches = CompetitionSchedule::with([
            'competitionClass.competitionCategory',
            'venue',
            'scheduleEntries.competitionRegistration.participation.person',
            'scheduleEntries.team',
            'matchOfficials.user',
        ])
            ->withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', $classIds)
            ->whereIn('id', $assignedScheduleIds)
            ->where('status', 'Waiting Result')
            ->orderBy('sort_order')
            ->orderBy('start_at')
            ->get();

        $finishedMatches = CompetitionSchedule::with([
            'competitionClass.competitionCategory',
            'venue',
            'winner.participation.person',
            'winnerTeam',
        ])
            ->whereIn('competition_class_id', $classIds)
            ->whereIn('id', $assignedScheduleIds)
            ->where('status', 'Finished')
            ->orderBy('finished_at', 'desc')
            ->take(20)
            ->get();

        return view('livewire.competition.official-panel', [
            'waitingMatches' => $waitingMatches,
            'finishedMatches' => $finishedMatches,
        ]);
    }
}
