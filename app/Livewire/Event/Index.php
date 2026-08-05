<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
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

    public ?int $deleteEventId = null;

    public function render()
    {
        return view('livewire.event.index', [
            'events' => Event::orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function toggleCreateForm(): void
    {
        Gate::authorize('manage-events');

        $this->showCreateForm = ! $this->showCreateForm;
        $this->resetForm();
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
                'newSlug' => 'required|string|max:255|unique:events,slug|regex:/^[a-z0-9-]+$/',
                'newEventType' => 'required|in:cai,pengajian,competition',
                'newDescription' => 'nullable|string',
                'newStartDate' => 'nullable|date',
                'newEndDate' => 'nullable|date|after_or_equal:newStartDate',
            ]);

            $event = Event::create([
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

            $context = app(ActiveEventContext::class);

            if (! $context->hasActiveEvent()) {
                $context->set($event);

                session()->flash('success', 'Event berhasil dibuat dan dipilih.');
                $this->redirect($event->dashboardRoute(), navigate: true);

                return;
            }

            session()->flash('success', 'Event berhasil dibuat.');
        } finally {
            $this->processing = false;
        }
    }

    public function archive(int $eventId): void
    {
        Gate::authorize('manage-events');

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
        Gate::authorize('manage-events');

        $event = Event::findOrFail($eventId);
        $event->update(['status' => 'active']);

        session()->flash('success', 'Event berhasil diaktifkan.');
    }

    public function confirmDelete(int $eventId): void
    {
        Gate::authorize('manage-events');

        $this->deleteEventId = $eventId;
    }

    public function cancelDelete(): void
    {
        Gate::authorize('manage-events');

        $this->deleteEventId = null;
    }

    public function delete(): void
    {
        Gate::authorize('manage-events');

        if ($this->deleteEventId === null) {
            return;
        }

        $event = Event::find($this->deleteEventId);

        if (! $event) {
            session()->flash('error', 'Event tidak ditemukan.');
            $this->deleteEventId = null;

            return;
        }

        if ($event->slug === 'cai-operational') {
            session()->flash('error', 'Legacy CAI Event tidak dapat dihapus.');
            $this->deleteEventId = null;

            return;
        }

        if ($event->hasRuntimeDependencies()) {
            session()->flash('error', 'Event tidak dapat dihapus karena masih memiliki data terkait.');
            $this->deleteEventId = null;

            return;
        }

        $context = app(ActiveEventContext::class);
        if ($context->id() === $event->id) {
            $context->clear();
        }

        $event->delete();
        $this->deleteEventId = null;
        session()->flash('success', 'Event berhasil dihapus.');
    }

    public function edit(int $eventId): void
    {
        Gate::authorize('manage-events');

        $this->dispatch('editEvent', id: $eventId);
    }

    private function resetForm(): void
    {
        $this->reset(['newName', 'newSlug', 'newEventType', 'newDescription', 'newStartDate', 'newEndDate']);
        $this->newEventType = 'cai';
        $this->resetErrorBag();
    }
}
