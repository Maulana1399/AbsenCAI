<?php

namespace App\Livewire\Competition\Team;

use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionTeam;
use App\Models\CompetitionTeamMember;
use App\Services\Competition\CompetitionTeamFormationService;
use App\Services\Competition\CompetitionTeamService;
use App\Support\ActiveEventContext;
use App\Support\CompetitionFormat;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public string $competitionCategoryId = '';

    public string $competitionClassId = '';

    public bool $processing = false;

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
        Gate::authorize('manage-registration');
    }

    public function updatedCompetitionCategoryId(): void
    {
        $this->competitionClassId = '';
    }

    public function getCategoriesProperty()
    {
        $event = app(ActiveEventContext::class)->current();

        return CompetitionCategory::where('event_id', $event?->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getClassesProperty()
    {
        if (blank($this->competitionCategoryId)) {
            return collect();
        }

        return CompetitionClass::where('competition_category_id', $this->competitionCategoryId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getTeamsProperty()
    {
        if (blank($this->competitionClassId)) {
            return collect();
        }

        $event = app(ActiveEventContext::class)->requireCurrent();

        return app(CompetitionTeamService::class)->listForClass($event->id, (int) $this->competitionClassId);
    }

    public function getSelectedClassProperty(): ?CompetitionClass
    {
        if (blank($this->competitionClassId)) {
            return null;
        }

        $event = app(ActiveEventContext::class)->requireCurrent();

        return CompetitionClass::where('event_id', $event->id)->find($this->competitionClassId);
    }

    public function getAvailableRegistrationsProperty()
    {
        if (blank($this->competitionClassId)) {
            return collect();
        }

        $event = app(ActiveEventContext::class)->requireCurrent();

        $assignedRegistrationIds = CompetitionTeamMember::whereHas('team', function ($query) use ($event) {
            $query->where('event_id', $event->id)
                ->where('competition_class_id', $this->competitionClassId);
        })->pluck('competition_registration_id');

        return \App\Models\CompetitionRegistration::with('participation.person.kelompok')
            ->where('competition_class_id', $this->competitionClassId)
            ->whereNotIn('id', $assignedRegistrationIds)
            ->orderBy('id')
            ->get();
    }

    public function autoFormation(?int $teamSize = null): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->requireCurrent();

        if (blank($this->competitionClassId)) {
            return;
        }

        $this->processing = true;

        try {
            $result = app(CompetitionTeamFormationService::class)->formForClass(
                eventId: $event->id,
                competitionClassId: (int) $this->competitionClassId,
                forcedTeamSize: $teamSize,
            );

            session()->flash('success', 'Auto team formation selesai. Team size: '.$result['team_size'].' pemain ('.count($result['teams']).' team).');
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->processing = false;
        }
    }

    /**
     * Rebuild (eksplisit) — mengganti pembagian team yang sudah ada.
     */
    public function rebuildFormation(?int $teamSize = null): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->requireCurrent();

        if (blank($this->competitionClassId)) {
            return;
        }

        $this->processing = true;

        try {
            $result = app(CompetitionTeamFormationService::class)->formForClass(
                eventId: $event->id,
                competitionClassId: (int) $this->competitionClassId,
                forcedTeamSize: $teamSize,
                force: true,
            );

            session()->flash('success', 'Team dibentuk ulang. Team size: '.$result['team_size'].' pemain ('.count($result['teams']).' team).');
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->processing = false;
        }
    }

    public function getIsFormedProperty(): bool
    {
        return $this->teams->isNotEmpty();
    }

    /**
     * Ringkasan: total team + team size (pemain terkecil di antara team).
     */
    public function getSummaryProperty(): array
    {
        $teams = $this->teams;

        if ($teams->isEmpty()) {
            return ['total_teams' => 0, 'team_size' => null];
        }

        return [
            'total_teams' => $teams->count(),
            'team_size' => $teams->map(fn ($team) => $team->players->count())->min(),
        ];
    }

    public function addMember(int $teamId, int $registrationId, bool $asSubstitute = false): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $team = CompetitionTeam::where('event_id', $event->id)->findOrFail($teamId);

        try {
            app(CompetitionTeamService::class)->addMember($team, $registrationId, $asSubstitute);
            session()->flash('success', 'Anggota ditambahkan.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function removeMember(int $teamId, int $memberId): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $team = CompetitionTeam::where('event_id', $event->id)->findOrFail($teamId);

        app(CompetitionTeamService::class)->removeMember($team, $memberId);
        session()->flash('success', 'Anggota dihapus.');
    }

    public function moveToPlayers(int $teamId, int $memberId): void
    {
        $this->setSubstitute($teamId, $memberId, false);
    }

    public function moveToSubstitutes(int $teamId, int $memberId): void
    {
        $this->setSubstitute($teamId, $memberId, true);
    }

    public function setSubstitute(int $teamId, int $memberId, bool $asSubstitute): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $team = CompetitionTeam::where('event_id', $event->id)->findOrFail($teamId);

        app(CompetitionTeamService::class)->setSubstitute($team, $memberId, $asSubstitute);
    }

    public function shuffle(int $teamId): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $team = CompetitionTeam::where('event_id', $event->id)->findOrFail($teamId);

        app(CompetitionTeamService::class)->shuffleMembers($team);
        session()->flash('success', 'Urutan anggota diacak.');
    }

    public function render()
    {
        return view('livewire.competition.team.index', [
            'categories' => $this->categories,
            'classes' => $this->classes,
            'teams' => $this->teams,
            'selectedClass' => $this->selectedClass,
            'formatLabel' => $this->selectedClass ? CompetitionFormat::label($this->selectedClass->format) : null,
            'availableRegistrations' => $this->availableRegistrations,
            'isFormed' => $this->isFormed,
            'summary' => $this->summary,
        ]);
    }
}
