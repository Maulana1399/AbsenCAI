<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Services\Activity\EventCommitteeService;
use App\Support\EventOwnership;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class CommitteeManagement extends Component
{
    public bool $processing = false;

    public ?int $eventId = null;

    public ?string $eventName = null;

    public ?int $newPersonId = null;

    public string $newEventRoleId = '';

    public string $searchPerson = '';

    public string $selectedPersonNama = '';

    public bool $showGuestForm = false;

    public string $guestName = '';

    public string $guestEmail = '';

    public string $guestEventRoleId = '';

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

    public function toggleGuestForm(): void
    {
        $this->showGuestForm = ! $this->showGuestForm;
        $this->resetGuestForm();
        $this->resetErrorBag();
    }

    /**
     * Create (or reuse) a Guest account without a Person and bind it to an
     * event role through a User-based membership.
     */
    public function createGuest(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                'guestName' => 'nullable|string|max:255',
                'guestEmail' => 'required|email|max:255',
                'guestEventRoleId' => 'required|exists:event_roles,id',
            ]);

            $event = Event::findOrFail($this->eventId);
            $role = EventRole::findOrFail($this->guestEventRoleId);

            if (! EventOwnership::belongsToEvent($role, $event)) {
                $this->addError('guestEventRoleId', 'Role harus berasal dari event yang sama.');

                return;
            }

            $result = app(EventCommitteeService::class)->createGuestAndAssign([
                'event_id' => $event->id,
                'event_role_id' => $role->id,
                'name' => $this->guestName,
                'email' => $this->guestEmail,
            ]);

            $this->resetGuestForm();
            $this->showGuestForm = false;

            if ($result['user_created']) {
                session()->flash('success', "Akun Guest berhasil ditambahkan.\n\nAkun Login\nUsername/Email: {$result['user']->email}\nPassword: {$result['plain_password']}");
            } else {
                session()->flash('success', 'Akun Guest berhasil ditambahkan. Menggunakan akun login yang sudah ada.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $this->addError('guestEmail', 'User ini sudah memiliki role yang sama di event ini.');
            } else {
                throw $e;
            }
        } finally {
            $this->processing = false;
        }
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
                'newPersonId' => 'required|exists:people,id',
                'newEventRoleId' => 'required|exists:event_roles,id',
            ]);

            $event = Event::findOrFail($this->eventId);
            $role = EventRole::findOrFail($this->newEventRoleId);

            if (! EventOwnership::belongsToEvent($role, $event)) {
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

    public function delete(int $assignmentId): void
    {
        Gate::authorize('manage-events');

        $assignment = EventCommitteeAssignment::where('id', $assignmentId)
            ->where('event_id', $this->eventId)
            ->first();

        if ($assignment === null) {
            session()->flash('error', 'Penugasan tidak ditemukan.');

            return;
        }

        $assignment->delete();
    }

    #[On('refreshCommittee')]
    public function refresh(): void {}

    private function resetForm(): void
    {
        $this->reset(['newPersonId', 'newEventRoleId', 'searchPerson', 'selectedPersonNama']);
        $this->resetErrorBag();
    }

    private function resetGuestForm(): void
    {
        $this->reset(['guestName', 'guestEmail', 'guestEventRoleId']);
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
            $personResults = Person::where('nama', 'like', '%'.$this->searchPerson.'%')
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
