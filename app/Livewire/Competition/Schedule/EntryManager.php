<?php

namespace App\Livewire\Competition\Schedule;

use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Models\CompetitionTeam;
use App\Services\Competition\CompetitionWorkflowService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class EntryManager extends Component
{
    public CompetitionSchedule $schedule;

    public array $available = [];

    public array $assigned = [];

    public bool $isTeam = false;

    public function mount(CompetitionSchedule $schedule): void
    {
        app(ActiveEventContext::class)->requireCurrent();
        $this->schedule = $schedule->load(['competitionClass.competitionCategory', 'venue']);
        $this->isTeam = $schedule->competitionClass?->isTeamFormat() ?? false;
        $this->loadLists();
    }

    public function loadLists(): void
    {
        if ($this->isTeam) {
            $this->loadTeamLists();

            return;
        }

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

    private function loadTeamLists(): void
    {
        $allTeams = CompetitionTeam::with('kelompok')
            ->where('competition_class_id', $this->schedule->competition_class_id)
            ->orderBy('name')
            ->get();

        $assignedIds = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->pluck('competition_team_id')
            ->toArray();

        $this->assigned = $allTeams->filter(fn ($t) => in_array($t->id, $assignedIds))
            ->values()
            ->map(fn ($t) => $this->formatTeam($t))
            ->toArray();

        $this->available = $allTeams->filter(fn ($t) => ! in_array($t->id, $assignedIds))
            ->values()
            ->map(fn ($t) => $this->formatTeam($t))
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
        ];
    }

    private function formatTeam(CompetitionTeam $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'number' => 'Team',
            'desa' => $team->kelompok?->kelompok_asal ?? '-',
        ];
    }

    private function workflow(): CompetitionWorkflowService
    {
        return app(CompetitionWorkflowService::class);
    }

    private function competitorColumn(): string
    {
        return $this->isTeam ? 'competition_team_id' : 'competition_registration_id';
    }

    public function assign(int $competitorId): void
    {
        Gate::authorize('manage-events');

        if ($this->isTeam) {
            $valid = CompetitionTeam::where('id', $competitorId)
                ->where('competition_class_id', $this->schedule->competition_class_id)
                ->exists();
        } else {
            $valid = CompetitionRegistration::where('id', $competitorId)
                ->where('competition_class_id', $this->schedule->competition_class_id)
                ->exists();
        }

        if (! $valid) {
            session()->flash('error', 'Competitor tidak valid untuk kelas ini.');

            return;
        }

        CompetitionScheduleEntry::create([
            'competition_schedule_id' => $this->schedule->id,
            $this->competitorColumn() => $competitorId,
        ]);

        $this->loadLists();
        $this->workflow()->checkAutoReady($this->schedule);
    }

    public function unassign(int $competitorId): void
    {
        Gate::authorize('manage-events');

        CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->where($this->competitorColumn(), $competitorId)
            ->delete();

        $this->loadLists();
        $this->workflow()->checkAutoReady($this->schedule);
    }

    public function moveUp(int $competitorId): void
    {
        Gate::authorize('manage-events');

        $entries = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        $ids = $entries->pluck($this->competitorColumn())->toArray();
        $pos = array_search($competitorId, $ids);

        if ($pos === false || $pos === 0) {
            return;
        }

        $ids[$pos] = $ids[$pos - 1];
        $ids[$pos - 1] = $competitorId;

        foreach ($ids as $i => $cid) {
            CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
                ->where($this->competitorColumn(), $cid)
                ->update(['order_number' => $i + 1]);
        }

        $this->loadLists();
    }

    public function moveDown(int $competitorId): void
    {
        Gate::authorize('manage-events');

        $entries = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        $ids = $entries->pluck($this->competitorColumn())->toArray();
        $pos = array_search($competitorId, $ids);

        if ($pos === false || $pos === count($ids) - 1) {
            return;
        }

        $ids[$pos] = $ids[$pos + 1];
        $ids[$pos + 1] = $competitorId;

        foreach ($ids as $i => $cid) {
            CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
                ->where($this->competitorColumn(), $cid)
                ->update(['order_number' => $i + 1]);
        }

        $this->loadLists();
    }

    private function updateOrderNumbers(): void
    {
        $entries = CompetitionScheduleEntry::where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        foreach ($entries as $i => $entry) {
            if ((int) $entry->order_number !== $i + 1) {
                $entry->update(['order_number' => $i + 1]);
            }
        }
    }

    public function render()
    {
        return view('livewire.competition.schedule.entry-manager', [
            'className' => $this->schedule->competitionClass?->name ?? '-',
            'categoryName' => $this->schedule->competitionClass?->competitionCategory?->name ?? '-',
            'venueName' => $this->schedule->venue?->name ?? '-',
            'isTeam' => $this->isTeam,
        ]);
    }
}
