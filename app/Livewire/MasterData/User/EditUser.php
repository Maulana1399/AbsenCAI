<?php

namespace App\Livewire\MasterData\User;

use App\Enums\Role;
use App\Models\Person;
use App\Models\User;
use App\Services\User\UserManagementService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;

class EditUser extends Component
{
    public bool $processing = false;

    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $role = '';
    public ?int $person_id = null;
    public string $selectedPersonNama = '';
    public string $searchPerson = '';

    public bool $roleLocked = false;
    public ?string $roleLockReason = null;

    #[On('editUser')]
    public function editUser(int $id): void
    {
        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role?->value ?? '';
        $this->person_id = $user->person_id;
        $this->selectedPersonNama = $user->person?->nama ?? '';
        $this->searchPerson = '';

        $service = app(UserManagementService::class);
        $reasons = $service->canChangeRole($user);

        if (! empty($reasons)) {
            $this->roleLocked = true;
            $this->roleLockReason = implode(' ', $reasons);
        } else {
            $this->roleLocked = false;
            $this->roleLockReason = null;
        }

        $this->resetErrorBag();
        Flux::modal('edit-user')->show();
    }

    public function update(): void
    {
        Gate::authorize('manage-users');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate();

            $user = User::findOrFail($this->userId);

            app(UserManagementService::class)->update($user, [
                'name' => trim($this->name),
                'email' => trim($this->email),
                'role' => $this->role,
                'person_id' => $this->person_id,
            ]);

            $this->dispatch('refreshUser');
            Flux::modal('edit-user')->close();
            session()->flash('success', 'User berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->processing = false;
            throw $e;
        } catch (\RuntimeException $e) {
            $this->addError('role', $e->getMessage());
            $this->processing = false;
        } finally {
            $this->processing = false;
        }
    }

    public function selectPerson(int $id): void
    {
        $person = Person::find($id);
        if ($person) {
            $this->person_id = $person->id;
            $this->selectedPersonNama = $person->nama;
            $this->searchPerson = '';
        }
    }

    public function removePerson(): void
    {
        $this->person_id = null;
        $this->selectedPersonNama = '';
    }

    protected function rules(): array
    {
        $uniqueRule = 'unique:users,email';

        if ($this->userId !== null) {
            $uniqueRule .= ',' . $this->userId;
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|' . $uniqueRule,
            'role' => 'required|in:' . implode(',', Role::values()),
            'person_id' => 'nullable|exists:people,id',
        ];
    }

    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah digunakan.',
        'role.required' => 'Role wajib dipilih.',
        'role.in' => 'Role tidak valid.',
        'person_id.exists' => 'Person tidak ditemukan.',
    ];

    public function render()
    {
        $personResults = [];
        if (strlen($this->searchPerson) >= 2) {
            $linkedIds = User::whereNotNull('person_id')
                ->where('id', '!=', $this->userId)
                ->pluck('person_id');
            $personResults = Person::where(function ($q) use ($linkedIds) {
                $q->whereNotIn('id', $linkedIds)
                    ->where('nama', 'like', '%' . $this->searchPerson . '%');
                if ($this->person_id !== null) {
                    $q->orWhere('id', $this->person_id);
                }
            })->limit(10)->get();
        }

        return view('livewire.master-data.user.edit-user', [
            'roles' => Role::cases(),
            'personResults' => $personResults,
        ]);
    }
}
