<?php

namespace App\Livewire\Competition\Category;

use App\Models\CompetitionCategory;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public bool $showCreateForm = false;

    public string $newName = '';

    public string $newCode = '';

    public string $newSortOrder = '';

    public ?int $editId = null;

    public string $editName = '';

    public string $editCode = '';

    public string $editSortOrder = '';

    public bool $processing = false;

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->reset(['newName', 'newCode', 'newSortOrder']);
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
                'newCode' => 'nullable|string|max:50',
                'newSortOrder' => 'nullable|integer|min:0',
            ]);

            $event = app(ActiveEventContext::class)->requireCurrent();

            CompetitionCategory::create([
                'event_id' => $event->id,
                'name' => $this->newName,
                'code' => $this->newCode ?: null,
                'sort_order' => $this->newSortOrder !== '' ? (int) $this->newSortOrder : null,
            ]);

            $this->showCreateForm = false;
            $this->reset(['newName', 'newCode', 'newSortOrder']);
            session()->flash('success', 'Kategori berhasil dibuat.');
        } finally {
            $this->processing = false;
        }
    }

    public function edit(int $id): void
    {
        $category = CompetitionCategory::findOrFail($id);
        $this->editId = $category->id;
        $this->editName = $category->name;
        $this->editCode = $category->code ?? '';
        $this->editSortOrder = $category->sort_order ?? '';
    }

    public function update(): void
    {
        Gate::authorize('manage-events');

        $this->validate([
            'editName' => 'required|string|max:255',
            'editCode' => 'nullable|string|max:50',
            'editSortOrder' => 'nullable|integer|min:0',
        ]);

        $category = CompetitionCategory::findOrFail($this->editId);
        $category->update([
            'name' => $this->editName,
            'code' => $this->editCode ?: null,
            'sort_order' => $this->editSortOrder !== '' ? (int) $this->editSortOrder : null,
        ]);

        $this->reset(['editId', 'editName', 'editCode', 'editSortOrder']);
        session()->flash('success', 'Kategori berhasil diperbarui.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'editName', 'editCode', 'editSortOrder']);
    }

    public function toggleActive(int $id): void
    {
        Gate::authorize('manage-events');

        $category = CompetitionCategory::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        return view('livewire.competition.category.index', [
            'categories' => CompetitionCategory::where('event_id', $event?->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
