<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
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
            'selectedWinnerId' => 'required|integer|exists:competition_registrations,id',
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
        ])->findOrFail($scheduleId);

        if ($schedule->status !== 'Waiting Result') {
            session()->flash('error', 'Only Waiting Result matches can be submitted.');
            return;
        }

        $this->submitScheduleId = $schedule->id;
        $this->selectedWinnerId = null;
        $this->finishReason = '';
        $this->finishNotes = '';

        $this->availableParticipants = $schedule->scheduleEntries->map(function ($entry) {
            $reg = $entry->competitionRegistration;
            if (!$reg) return null;
            return [
                'id' => $reg->id,
                'name' => $reg->participation?->person?->nama ?? '?',
                'number' => $reg->participation?->participant_number ?? '-',
            ];
        })->filter()->values()->toArray();

        $this->showSubmitDialog = true;
    }

    public function submitResult(): void
    {
        Gate::authorize('submit-result');

        $this->validate();

        $schedule = CompetitionSchedule::with([
            'scheduleEntries.competitionRegistration',
        ])->findOrFail($this->submitScheduleId);

        if ($schedule->status !== 'Waiting Result') {
            session()->flash('error', 'Match is no longer waiting for result.');
            $this->cancelSubmitDialog();
            return;
        }

        $assignedIds = $schedule->scheduleEntries->pluck('competition_registration_id')->toArray();

        if (!in_array($this->selectedWinnerId, $assignedIds)) {
            session()->flash('error', 'Pemenang harus merupakan peserta yang bertanding.');
            return;
        }

        $venueId = $schedule->venue_id;

        $schedule->update([
            'winner_registration_id' => $this->selectedWinnerId,
            'finish_reason' => $this->finishReason,
            'finish_notes' => $this->finishNotes ?: null,
            'finished_at' => Carbon::now(),
            'finished_by' => auth()->id(),
            'status' => 'Finished',
        ]);

        $this->advanceBracketWinner($schedule);

        $this->cancelSubmitDialog();

        $this->promoteNextReady($venueId);

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

    private function advanceBracketWinner(CompetitionSchedule $schedule): void
    {
        $bracketMatch = $schedule->bracketMatch;
        if (!$bracketMatch) return;

        $winnerRegId = $schedule->winner_registration_id;
        if (!$winnerRegId) return;

        $nextMatch = CompetitionBracketMatch::where(function ($q) use ($bracketMatch) {
                $q->where('source_match_a_id', $bracketMatch->id)
                  ->orWhere('source_match_b_id', $bracketMatch->id);
            })
            ->with('schedule')
            ->first();

        if (!$nextMatch || !$nextMatch->schedule) return;

        $existingEntry = \App\Models\CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->where('competition_registration_id', $winnerRegId)
            ->exists();

        if ($existingEntry) return;

        $isSourceA = $nextMatch->source_match_a_id === $bracketMatch->id;

        $maxOrder = \App\Models\CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->max('order_number') ?? 0;

        $assignedCount = \App\Models\CompetitionScheduleEntry::where('competition_schedule_id', $nextMatch->schedule->id)
            ->count();

        if ($assignedCount >= 2) return;

        \App\Models\CompetitionScheduleEntry::create([
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

    private function promoteNextReady(?int $venueId): void
    {
        $event = app(ActiveEventContext::class)->current();
        $classIds = CompetitionClass::where('event_id', $event?->id)->pluck('id');

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
