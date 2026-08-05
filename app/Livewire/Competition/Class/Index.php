<?php

namespace App\Livewire\Competition\Class;

use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public bool $showCreateForm = false;

    public string $newName = '';

    public string $newGender = '';

    public string $newCode = '';

    public string $newSortOrder = '';

    public string $newCompetitionCategoryId = '';

    public ?int $editId = null;

    public string $editName = '';

    public string $editGender = '';

    public string $editCode = '';

    public string $editSortOrder = '';

    public string $editCompetitionCategoryId = '';

    public bool $processing = false;

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->reset(['newName', 'newGender', 'newCode', 'newSortOrder', 'newCompetitionCategoryId']);
        $this->resetErrorBag();
    }

    public function create(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                'newName' => 'required|string|max:255',
                'newGender' => 'required|in:L,P,M',
                'newCode' => 'nullable|string|max:50',
                'newSortOrder' => 'nullable|integer|min:0',
                'newCompetitionCategoryId' => 'required|exists:competition_categories,id',
            ]);

            $event = app(ActiveEventContext::class)->requireCurrent();

            CompetitionClass::create([
                'event_id' => $event->id,
                'competition_category_id' => $this->newCompetitionCategoryId,
                'name' => $this->newName,
                'gender' => $this->newGender ?: null,
                'code' => $this->newCode ?: null,
                'sort_order' => $this->newSortOrder !== '' ? (int) $this->newSortOrder : null,
            ]);

            $this->showCreateForm = false;
            $this->reset(['newName', 'newGender', 'newCode', 'newSortOrder', 'newCompetitionCategoryId']);
            session()->flash('success', 'Kelas berhasil dibuat.');
        } finally {
            $this->processing = false;
        }
    }

    public function edit(int $id): void
    {
        $class = CompetitionClass::with('competitionCategory')->findOrFail($id);
        $this->editId = $class->id;
        $this->editName = $class->name;
        $this->editGender = $class->gender ?? '';
        $this->editCode = $class->code ?? '';
        $this->editSortOrder = $class->sort_order ?? '';
        $this->editCompetitionCategoryId = (string) $class->competition_category_id;
    }

    public function update(): void
    {
        Gate::authorize('manage-events');

        $this->validate([
            'editName' => 'required|string|max:255',
            'editGender' => 'required|in:L,P,M',
            'editCode' => 'nullable|string|max:50',
            'editSortOrder' => 'nullable|integer|min:0',
            'editCompetitionCategoryId' => 'required|exists:competition_categories,id',
        ]);

        $class = CompetitionClass::findOrFail($this->editId);
        $class->update([
            'competition_category_id' => $this->editCompetitionCategoryId,
            'name' => $this->editName,
            'gender' => $this->editGender ?: null,
            'code' => $this->editCode ?: null,
            'sort_order' => $this->editSortOrder !== '' ? (int) $this->editSortOrder : null,
        ]);

        $this->reset(['editId', 'editName', 'editGender', 'editCode', 'editSortOrder', 'editCompetitionCategoryId']);
        session()->flash('success', 'Kelas berhasil diperbarui.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'editName', 'editGender', 'editCode', 'editSortOrder', 'editCompetitionCategoryId']);
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-events');

        $class = CompetitionClass::findOrFail($id);
        $class->update(['is_active' => ! $class->is_active]);
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        return view('livewire.competition.class.index', [
            'classes' => CompetitionClass::with('competitionCategory')
                ->where('event_id', $event?->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'categories' => CompetitionCategory::where('event_id', $event?->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
