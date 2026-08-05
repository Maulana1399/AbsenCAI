<?php

namespace App\Livewire\Competition\Schedule;

use App\Models\CompetitionOutcome;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class OutcomeManager extends Component
{
    public CompetitionSchedule $schedule;

    public array $outcomes = [];

    public function mount(CompetitionSchedule $schedule): void
    {
        app(ActiveEventContext::class)->requireCurrent();

        $this->schedule = $schedule->load(['competitionClass.competitionCategory', 'venue']);
    }

    public function loadParticipants(): void
    {
        $entries = CompetitionScheduleEntry::with([
            'competitionRegistration.participation.person.desa',
            'competitionRegistration.participation.person.kelompok',
            'competitionRegistration.outcome',
        ])
            ->where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        $this->outcomes = $entries->map(function ($entry) {
            $reg = $entry->competitionRegistration;
            if (! $reg) {
                return null;
            }

            return [
                'registration_id' => $reg->id,
                'person_name' => $reg->participation?->person?->nama ?? '-',
                'participant_number' => $reg->participation?->participant_number ?? '-',
                'desa' => $reg->participation?->person?->desa?->desa_asal ?? '-',
                'kelompok' => $reg->participation?->person?->kelompok?->kelompok_asal ?? '-',
                'position' => $reg->outcome?->position ?? '',
                'status' => $reg->outcome?->status ?? '',
                'score' => $reg->outcome?->score ?? '',
                'remarks' => $reg->outcome?->remarks ?? '',
            ];
        })->filter()->values()->toArray();
    }

    public function saveOutcomes(): void
    {
        Gate::authorize('manage-events');

        $this->validate([
            'outcomes.*.position' => 'nullable|integer|min:0',
            'outcomes.*.status' => 'nullable|string|max:50',
            'outcomes.*.score' => 'nullable|numeric|min:0',
            'outcomes.*.remarks' => 'nullable|string|max:1000',
        ]);

        foreach ($this->outcomes as $data) {
            CompetitionOutcome::updateOrCreate(
                ['competition_registration_id' => $data['registration_id']],
                [
                    'position' => $data['position'] !== '' ? (int) $data['position'] : null,
                    'status' => $data['status'] ?: null,
                    'score' => $data['score'] !== '' ? (float) $data['score'] : null,
                    'remarks' => $data['remarks'] ?: null,
                ]
            );
        }

        session()->flash('success', 'Outcome berhasil disimpan.');
    }

    public function render()
    {
        $this->loadParticipants();

        return view('livewire.competition.schedule.outcome-manager', [
            'className' => $this->schedule->competitionClass?->name ?? '-',
            'categoryName' => $this->schedule->competitionClass?->competitionCategory?->name ?? '-',
            'venueName' => $this->schedule->venue?->name ?? '-',
        ]);
    }
}
