<?php

namespace App\Livewire\Competition\Schedule;

use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Services\Competition\CompetitionWorkflowService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EntryManager extends Component
{
    public CompetitionSchedule $schedule;

    public array $available = [];

    public array $assigned = [];

    public function mount(CompetitionSchedule $schedule): void
    {
        app(ActiveEventContext::class)->requireCurrent();
        $this->schedule = $schedule->load(['competitionClass.competitionCategory', 'venue']);
        $this->loadLists();
    }

    public function loadLists(): void
    {
        $allRegs = CompetitionRegistration::with([
            'participation.person.desa',
            'participation.person.kelompok',
        ])->where('competition_class_id', $this->schedule->competition_class_id)
            ->orderBy('id')
            ->get();

        $assignedIds = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->pluck('competition_registration_id')
            ->toArray();

        $this->assigned = $allRegs->filter(fn ($r) => in_array($r->id, $assignedIds))
            ->values()
            ->map(fn ($r) => $this->formatEntry($r))
            ->toArray();

        $this->available = $allRegs->filter(fn ($r) => ! in_array($r->id, $assignedIds))
            ->values()
            ->map(fn ($r) => $this->formatEntry($r))
            ->toArray();

        $this->updateOrderNumbers();
    }

    private function formatEntry($reg): array
    {
        return [
            'id' => $reg->id,
            'name' => $reg->participation?->person?->nama ?? '-',
            'number' => $reg->participation?->participant_number ?? '-',
            'desa' => $reg->participation?->person?->desa?->desa_asal ?? '-',
            'kelompok' => $reg->participation?->person?->kelompok?->kelompok_asal ?? '-',
        ];
    }

    private function workflow(): CompetitionWorkflowService
    {
        return app(CompetitionWorkflowService::class);
    }

    public function assign(int $registrationId): void
    {
        Gate::authorize('manage-events');

        CompetitionScheduleEntry::create([
            'competition_schedule_id' => $this->schedule->id,
            'competition_registration_id' => $registrationId,
        ]);

        $this->loadLists();
        $this->workflow()->checkAutoReady($this->schedule);
    }

    public function unassign(int $registrationId): void
    {
        Gate::authorize('manage-events');

        CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->where('competition_registration_id', $registrationId)
            ->delete();

        $this->loadLists();
        $this->workflow()->checkAutoReady($this->schedule);
    }

    public function moveUp(int $registrationId): void
    {
        Gate::authorize('manage-events');

        $entries = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        $ids = $entries->pluck('competition_registration_id')->toArray();
        $pos = array_search($registrationId, $ids);

        if ($pos === false || $pos === 0) {
            return;
        }

        $ids[$pos] = $ids[$pos - 1];
        $ids[$pos - 1] = $registrationId;

        foreach ($ids as $i => $rid) {
            CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
                ->where('competition_registration_id', $rid)
                ->update(['order_number' => $i + 1]);
        }

        $this->loadLists();
    }

    public function moveDown(int $registrationId): void
    {
        Gate::authorize('manage-events');

        $entries = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        $ids = $entries->pluck('competition_registration_id')->toArray();
        $pos = array_search($registrationId, $ids);

        if ($pos === false || $pos === count($ids) - 1) {
            return;
        }

        $ids[$pos] = $ids[$pos + 1];
        $ids[$pos + 1] = $registrationId;

        foreach ($ids as $i => $rid) {
            CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
                ->where('competition_registration_id', $rid)
                ->update(['order_number' => $i + 1]);
        }

        $this->loadLists();
    }

    private function updateOrderNumbers(): void
    {
        foreach ($this->assigned as $i => $entry) {
            CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
                ->where('competition_registration_id', $entry['id'])
                ->update(['order_number' => $i + 1]);
        }
    }

    public function render()
    {
        return view('livewire.competition.schedule.entry-manager', [
            'className' => $this->schedule->competitionClass?->name ?? '-',
            'categoryName' => $this->schedule->competitionClass?->competitionCategory?->name ?? '-',
            'venueName' => $this->schedule->venue?->name ?? '-',
        ]);
    }
}
