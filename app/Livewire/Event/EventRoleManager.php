<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Models\EventRole;
use App\Services\Activity\EventCommitteeService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;

class EventRoleManager extends Component
{
    public bool $processing = false;

    public ?int $eventId = null;
    public ?string $eventName = null;

    public string $newName = '';
    public ?string $newCode = null;
    public ?string $newDescription = null;
    public bool $newIsActive = true;
    public ?int $newSortOrder = null;

    #[On('manageEventRoles')]
    public function load(int $id): void
    {
        $event = Event::findOrFail($id);
        $this->eventId = $event->id;
        $this->eventName = $event->name;
        $this->resetForm();
        Flux::modal('manage-event-roles')->show();
    }

    public function create(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) return;
        $this->processing = true;

        try {
            $this->validate([
                'newName' => 'required|string|max:255',
                'newCode' => 'nullable|string|max:100',
                'newDescription' => 'nullable|string',
                'newIsActive' => 'boolean',
                'newSortOrder' => 'nullable|integer|min:0',
            ]);

            $event = Event::findOrFail($this->eventId);

            app(EventCommitteeService::class)->createRole([
                'event_id' => $event->id,
                'name' => $this->newName,
                'code' => $this->newCode,
                'description' => $this->newDescription,
                'is_active' => $this->newIsActive,
                'sort_order' => $this->newSortOrder,
            ]);

            $this->resetForm();
            session()->flash('success', 'Role berhasil dibuat.');
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE')) {
                $this->addError('newName', 'Nama role sudah digunakan di event ini.');
            } else {
                throw $e;
            }
        } finally {
            $this->processing = false;
        }
    }

    #[On('refreshEventRoles')]
    public function refresh(): void
    {
    }

    private function resetForm(): void
    {
        $this->reset(['newName', 'newCode', 'newDescription', 'newSortOrder']);
        $this->newIsActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $roles = $this->eventId
            ? EventRole::where('event_id', $this->eventId)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('livewire.event.event-role-manager', [
            'roles' => $roles,
        ]);
    }
}
