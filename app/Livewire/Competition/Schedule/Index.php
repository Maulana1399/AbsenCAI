<?php

namespace App\Livewire\Competition\Schedule;

use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Models\Venue;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public string $filterCategoryId = '';
    public string $filterClassId = '';

    public bool $showCreateForm = false;
    public string $newCompetitionClassId = '';
    public string $newVenueId = '';
    public string $newStartAt = '';
    public string $newEndAt = '';
    public string $newStatus = 'Scheduled';
    public string $newNotes = '';
    public string $newSortOrder = '';

    public ?int $editId = null;
    public string $editCompetitionClassId = '';
    public string $editVenueId = '';
    public string $editStartAt = '';
    public string $editEndAt = '';
    public string $editStatus = '';
    public string $editNotes = '';
    public string $editSortOrder = '';

    public bool $processing = false;

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->reset(['newCompetitionClassId', 'newVenueId', 'newStartAt', 'newEndAt', 'newStatus', 'newNotes', 'newSortOrder']);
        $this->newStatus = 'Scheduled';
        $this->resetErrorBag();
    }

    public function create(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) return;
        $this->processing = true;

        try {
            $this->validate([
                'newCompetitionClassId' => 'required|exists:competition_classes,id',
                'newVenueId' => 'nullable|exists:venues,id',
                'newStartAt' => 'nullable|date',
                'newEndAt' => 'nullable|date|after_or_equal:newStartAt',
                'newStatus' => 'required|in:Scheduled,Ready,NowPlaying,Finished',
                'newNotes' => 'nullable|string|max:1000',
                'newSortOrder' => 'nullable|integer|min:0',
            ]);

            CompetitionSchedule::create([
                'competition_class_id' => $this->newCompetitionClassId,
                'venue_id' => $this->newVenueId ?: null,
                'start_at' => $this->newStartAt ?: null,
                'end_at' => $this->newEndAt ?: null,
                'status' => $this->newStatus,
                'notes' => $this->newNotes ?: null,
                'sort_order' => $this->newSortOrder !== '' ? (int) $this->newSortOrder : null,
            ]);

            $this->showCreateForm = false;
            $this->reset(['newCompetitionClassId', 'newVenueId', 'newStartAt', 'newEndAt', 'newNotes', 'newSortOrder']);
            $this->newStatus = 'Scheduled';
            session()->flash('success', 'Jadwal berhasil dibuat.');
        } finally {
            $this->processing = false;
        }
    }

    public function edit(int $id): void
    {
        $schedule = CompetitionSchedule::findOrFail($id);
        $this->editId = $schedule->id;
        $this->editCompetitionClassId = (string) $schedule->competition_class_id;
        $this->editVenueId = (string) ($schedule->venue_id ?? '');
        $this->editStartAt = $schedule->start_at?->format('Y-m-d\TH:i') ?? '';
        $this->editEndAt = $schedule->end_at?->format('Y-m-d\TH:i') ?? '';
        $this->editStatus = $schedule->status;
        $this->editNotes = $schedule->notes ?? '';
        $this->editSortOrder = (string) ($schedule->sort_order ?? '');
    }

    public function update(): void
    {
        Gate::authorize('manage-events');

        $this->validate([
            'editCompetitionClassId' => 'required|exists:competition_classes,id',
            'editVenueId' => 'nullable|exists:venues,id',
            'editStartAt' => 'nullable|date',
            'editEndAt' => 'nullable|date|after_or_equal:editStartAt',
            'editStatus' => 'required|in:Scheduled,Ready,NowPlaying,Finished',
            'editNotes' => 'nullable|string|max:1000',
            'editSortOrder' => 'nullable|integer|min:0',
        ]);

        $schedule = CompetitionSchedule::findOrFail($this->editId);
        $schedule->update([
            'competition_class_id' => $this->editCompetitionClassId,
            'venue_id' => $this->editVenueId ?: null,
            'start_at' => $this->editStartAt ?: null,
            'end_at' => $this->editEndAt ?: null,
            'status' => $this->editStatus,
            'notes' => $this->editNotes ?: null,
            'sort_order' => $this->editSortOrder !== '' ? (int) $this->editSortOrder : null,
        ]);

        $this->reset(['editId', 'editCompetitionClassId', 'editVenueId', 'editStartAt', 'editEndAt', 'editStatus', 'editNotes', 'editSortOrder']);
        session()->flash('success', 'Jadwal berhasil diperbarui.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'editCompetitionClassId', 'editVenueId', 'editStartAt', 'editEndAt', 'editStatus', 'editNotes', 'editSortOrder']);
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-events');

        $schedule = CompetitionSchedule::findOrFail($id);
        $schedule->delete();

        session()->flash('success', 'Jadwal berhasil dihapus.');
    }

    public function updatedFilterCategoryId(): void
    {
        $this->filterClassId = '';
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $query = CompetitionSchedule::with(['competitionClass.competitionCategory', 'venue'])
            ->withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', CompetitionClass::where('event_id', $event?->id)->pluck('id'));

        if ($this->filterClassId) {
            $query->where('competition_class_id', $this->filterClassId);
        }

        $schedules = $query->orderBy('sort_order')->orderBy('start_at')->get();

        return view('livewire.competition.schedule.index', [
            'schedules' => $schedules,
            'categories' => CompetitionCategory::where('event_id', $event?->id)
                ->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'filterClasses' => $this->filterCategoryId
                ? CompetitionClass::where('competition_category_id', $this->filterCategoryId)
                    ->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
                : collect(),
            'allClasses' => CompetitionClass::where('event_id', $event?->id)
                ->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'venues' => Venue::where('event_id', $event?->id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
