<?php

namespace App\Livewire\Event;

use App\Models\Event;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;

class EditStatus extends Component
{
    public ?int $eventId = null;
    public string $editName = '';
    public string $editDescription = '';
    public string $editStartDate = '';
    public string $editEndDate = '';

    public bool $processing = false;

    #[On('editEvent')]
    public function load(int $id): void
    {
        $event = Event::findOrFail($id);
        $this->eventId = $event->id;
        $this->editName = $event->name;
        $this->editDescription = (string) ($event->description ?? '');
        $this->editStartDate = $event->start_date?->toDateString() ?? '';
        $this->editEndDate = $event->end_date?->toDateString() ?? '';

        Flux::modal('edit-event')->show();
    }

    public function update(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                'editName' => 'required|string|max:255',
                'editDescription' => 'nullable|string',
                'editStartDate' => 'nullable|date',
                'editEndDate' => 'nullable|date|after_or_equal:editStartDate',
            ]);

            $event = Event::findOrFail($this->eventId);
            $event->update([
                'name' => $this->editName,
                'description' => $this->editDescription ?: null,
                'start_date' => $this->editStartDate ?: null,
                'end_date' => $this->editEndDate ?: null,
            ]);

            Flux::modal('edit-event')->close();
            session()->flash('success', 'Event berhasil diperbarui.');
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.event.edit-status');
    }
}
