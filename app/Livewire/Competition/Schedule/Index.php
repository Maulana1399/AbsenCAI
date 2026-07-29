<?php

namespace App\Livewire\Competition\Schedule;

use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Models\Venue;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterCategoryId = '';
    public string $filterClassId = '';
    public string $filterVenueId = '';
    public string $filterStatus = '';

    public bool $showCreateForm = false;
    public string $newCompetitionClassId = '';
    public string $newVenueId = '';
    public string $newStartAt = '';
    public string $newEndAt = '';
    public string $newStatus = 'Scheduled';
    public string $newRequiredParticipants = '1';
    public string $newNotes = '';
    public string $newSortOrder = '';

    public ?int $editId = null;
    public string $editCompetitionClassId = '';
    public string $editVenueId = '';
    public string $editStartAt = '';
    public string $editEndAt = '';
    public string $editStatus = '';
    public string $editRequiredParticipants = '1';
    public string $editNotes = '';
    public string $editSortOrder = '';

    public bool $processing = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategoryId' => ['except' => ''],
        'filterClassId' => ['except' => ''],
        'filterVenueId' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = !$this->showCreateForm;
        $this->reset(['newCompetitionClassId', 'newVenueId', 'newStartAt', 'newEndAt', 'newStatus', 'newRequiredParticipants', 'newNotes', 'newSortOrder']);
        $this->newStatus = 'Scheduled';
        $this->newRequiredParticipants = '1';
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
                'newStatus' => 'required|in:Scheduled,Ready,Playing,Finished',
                'newRequiredParticipants' => 'required|integer|min:1|max:99',
                'newNotes' => 'nullable|string|max:1000',
                'newSortOrder' => 'nullable|integer|min:0',
            ]);

            CompetitionSchedule::create([
                'competition_class_id' => $this->newCompetitionClassId,
                'venue_id' => $this->newVenueId ?: null,
                'start_at' => $this->newStartAt ?: null,
                'end_at' => $this->newEndAt ?: null,
                'status' => $this->newStatus,
                'required_participants' => (int) $this->newRequiredParticipants,
                'notes' => $this->newNotes ?: null,
                'sort_order' => $this->newSortOrder !== '' ? (int) $this->newSortOrder : null,
            ]);

            $this->showCreateForm = false;
            $this->reset(['newCompetitionClassId', 'newVenueId', 'newStartAt', 'newEndAt', 'newNotes', 'newSortOrder']);
            $this->newStatus = 'Scheduled';
            $this->newRequiredParticipants = '1';
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
        $this->editRequiredParticipants = (string) ($schedule->required_participants ?? 1);
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
            'editRequiredParticipants' => 'required|integer|min:1|max:99',
            'editNotes' => 'nullable|string|max:1000',
            'editSortOrder' => 'nullable|integer|min:0',
        ]);

        $schedule = CompetitionSchedule::findOrFail($this->editId);

        $schedule->update([
            'competition_class_id' => $this->editCompetitionClassId,
            'venue_id' => $this->editVenueId ?: null,
            'start_at' => $this->editStartAt ?: null,
            'end_at' => $this->editEndAt ?: null,
            'required_participants' => (int) $this->editRequiredParticipants,
            'notes' => $this->editNotes ?: null,
            'sort_order' => $this->editSortOrder !== '' ? (int) $this->editSortOrder : null,
        ]);

        $this->reset(['editId', 'editCompetitionClassId', 'editVenueId', 'editStartAt', 'editEndAt', 'editStatus', 'editRequiredParticipants', 'editNotes', 'editSortOrder']);
        session()->flash('success', 'Jadwal berhasil diperbarui.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'editCompetitionClassId', 'editVenueId', 'editStartAt', 'editEndAt', 'editStatus', 'editRequiredParticipants', 'editNotes', 'editSortOrder']);
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-events');

        $schedule = CompetitionSchedule::findOrFail($id);
        $schedule->delete();

        session()->flash('success', 'Jadwal berhasil dihapus.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId(): void
    {
        $this->filterClassId = '';
        $this->resetPage();
    }

    public function updatedFilterVenueId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $query = CompetitionSchedule::with(['competitionClass.competitionCategory', 'venue'])
            ->withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', CompetitionClass::where('event_id', $event?->id)->pluck('id'));

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('competitionClass', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                  ->orWhereHas('venue', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            });
        }

        if ($this->filterClassId) {
            $query->where('competition_class_id', $this->filterClassId);
        }

        if ($this->filterVenueId) {
            $query->where('venue_id', $this->filterVenueId);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $schedules = $query->orderBy('sort_order')->orderBy('start_at')->paginate(12);

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
            'allVenues' => Venue::where('event_id', $event?->id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
}
