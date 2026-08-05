<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionRegistration;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ParticipantList extends Component
{
    public string $competitionCategoryId = '';

    public string $competitionClassId = '';

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
        Gate::authorize('view-dashboard');
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

    public function render()
    {
        $registrations = collect();

        if ($this->competitionClassId) {
            $registrations = CompetitionRegistration::with([
                'participation.person.desa',
                'participation.person.kelompok',
                'competitionCategory',
                'competitionClass',
            ])
                ->where('competition_category_id', $this->competitionCategoryId)
                ->where('competition_class_id', $this->competitionClassId)
                ->orderBy('id')
                ->get();
        }

        return view('livewire.competition.participant-list', [
            'registrations' => $registrations,
            'categories' => $this->categories,
            'classes' => $this->classes,
        ]);
    }
}
