<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Support\ActiveEventContext;
use Flux\Flux;
use Livewire\Component;

class Index extends Component
{
    public bool $showCreateForm = false;

    public string $newName = '';
    public string $newSlug = '';
    public string $newEventType = 'cai';
    public string $newDescription = '';
    public string $newStartDate = '';
    public string $newEndDate = '';

    public bool $processing = false;

    public function render()
    {
        return view('livewire.event.index', [
            'events' => Event::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        $this->resetForm();
    }

    public function create(): void
    {
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                'newName' => 'required|string|max:255',
                'newSlug' => 'required|string|max:255|unique:events,slug|regex:/^[a-z0-9-]+$/',
                'newEventType' => 'required|in:cai,pengajian',
                'newDescription' => 'nullable|string',
                'newStartDate' => 'nullable|date',
                'newEndDate' => 'nullable|date|after_or_equal:newStartDate',
            ]);

            Event::create([
                'name' => $this->newName,
                'slug' => $this->newSlug,
                'event_type' => $this->newEventType,
                'description' => $this->newDescription ?: null,
                'start_date' => $this->newStartDate ?: null,
                'end_date' => $this->newEndDate ?: null,
                'status' => 'active',
            ]);

            $this->showCreateForm = false;
            $this->resetForm();
            session()->flash('success', 'Event berhasil dibuat.');
        } finally {
            $this->processing = false;
        }
    }

    public function archive(int $eventId): void
    {
        $event = Event::findOrFail($eventId);

        if ($event->slug === 'cai-operational') {
            session()->flash('error', 'Legacy CAI Event tidak dapat diarsipkan.');

            return;
        }

        $event->update(['status' => 'archived']);

        $context = app(ActiveEventContext::class);
        if ($context->id() === $event->id) {
            $context->clear();
        }

        session()->flash('success', 'Event berhasil diarsipkan.');
    }

    public function activate(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $event->update(['status' => 'active']);

        session()->flash('success', 'Event berhasil diaktifkan.');
    }

    public function edit(int $eventId): void
    {
        $this->dispatch('editEvent', id: $eventId);
    }

    private function resetForm(): void
    {
        $this->reset(['newName', 'newSlug', 'newEventType', 'newDescription', 'newStartDate', 'newEndDate']);
        $this->newEventType = 'cai';
        $this->resetErrorBag();
    }
}
