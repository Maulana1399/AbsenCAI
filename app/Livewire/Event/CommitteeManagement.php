<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Services\Activity\EventCommitteeService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;

class CommitteeManagement extends Component
{
    public bool $processing = false;

    public ?int $eventId = null;
    public ?string $eventName = null;

    public ?int $newPersonId = null;
    public string $newEventRoleId = '';
    public string $searchPerson = '';
    public string $selectedPersonNama = '';

    public ?int $deleteAssignmentId = null;

    #[On('manageCommittee')]
    public function load(int $id): void
    {
        $event = Event::findOrFail($id);
        $this->eventId = $event->id;
        $this->eventName = $event->name;
        $this->resetForm();
        Flux::modal('manage-committee')->show();
    }

    public function selectPerson(int $id): void
    {
        $person = Person::find($id);
        if ($person) {
            $this->newPersonId = $person->id;
            $this->selectedPersonNama = $person->nama;
            $this->searchPerson = '';
        }
    }

    public function removePerson(): void
    {
        $this->newPersonId = null;
        $this->selectedPersonNama = '';
    }

    public function create(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) return;
        $this->processing = true;

        try {
            $this->validate([
                'newPersonId' => 'required|exists:people,id',
                'newEventRoleId' => 'required|exists:event_roles,id',
            ]);

            $event = Event::findOrFail($this->eventId);
            $role = EventRole::findOrFail($this->newEventRoleId);

            if ((int) $role->event_id !== (int) $event->id) {
                $this->addError('newEventRoleId', 'Role harus berasal dari event yang sama.');
                return;
            }

            $result = app(EventCommitteeService::class)->assignAndEnsureUser([
                'event_id' => $event->id,
                'person_id' => $this->newPersonId,
                'event_role_id' => $role->id,
            ]);

            $this->resetForm();

            if ($result['user_created']) {
                session()->flash('success', "Panitia berhasil ditambahkan.\n\nAkun Login\nUsername: {$result['user']->username}\nPassword: {$result['plain_password']}");
            } else {
                session()->flash('success', 'Panitia berhasil ditambahkan. Menggunakan akun login yang sudah ada.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $this->addError('newPersonId', 'Person ini sudah memiliki role yang sama di event ini.');
            } else {
                throw $e;
            }
        } finally {
            $this->processing = false;
        }
    }

    #[On('confirmDeleteAssignment')]
    public function confirmDelete(int $assignmentId): void
    {
        $this->deleteAssignmentId = $assignmentId;
    }

    public function delete(): void
    {
        Gate::authorize('manage-events');

        if ($this->deleteAssignmentId === null) return;

        $assignment = EventCommitteeAssignment::where('id', $this->deleteAssignmentId)
            ->where('event_id', $this->eventId)
            ->first();

        if ($assignment === null) {
            session()->flash('error', 'Penugasan tidak ditemukan.');
            return;
        }

        $assignment->delete();
        $this->deleteAssignmentId = null;
    }

    #[On('refreshCommittee')]
    public function refresh(): void
    {
    }

    private function resetForm(): void
    {
        $this->reset(['newPersonId', 'newEventRoleId', 'searchPerson', 'selectedPersonNama', 'deleteAssignmentId']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $assignments = $this->eventId
            ? EventCommitteeAssignment::with(['person', 'eventRole'])
                ->where('event_id', $this->eventId)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        $roles = $this->eventId
            ? EventRole::where('event_id', $this->eventId)->where('is_active', true)->orderBy('name')->get()
            : collect();

        $personResults = [];
        if (strlen($this->searchPerson) >= 2) {
            $personResults = Person::where('nama', 'like', '%' . $this->searchPerson . '%')
                ->limit(10)
                ->get();
        }

        return view('livewire.event.committee-management', [
            'assignments' => $assignments,
            'roles' => $roles,
            'personResults' => $personResults,
        ]);
    }
}
