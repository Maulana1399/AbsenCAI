<?php

namespace App\Livewire\MasterData\User;

use App\Enums\Role;
use App\Models\User;
use App\Services\User\UserManagementService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class EditUser extends Component
{
    public bool $processing = false;

    public ?int $userId = null;

    public string $name = '';

    public ?string $email = null;

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

            $email = trim((string) $this->email);
            $email = $email === '' ? null : $email;

            // Person TIDAK bisa diganti lewat update — identitas akun bersifat read-only.
            app(UserManagementService::class)->update($user, [
                'name' => trim($this->name),
                'email' => $email,
                'role' => $this->role,
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

    protected function rules(): array
    {
        $uniqueRule = 'unique:users,email';

        if ($this->userId !== null) {
            $uniqueRule .= ','.$this->userId;
        }

        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|'.$uniqueRule,
            'role' => 'required|in:'.implode(',', Role::platformValues()),
        ];
    }

    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah digunakan.',
        'role.required' => 'Role wajib dipilih.',
        'role.in' => 'Role tidak valid.',
    ];

    public function render()
    {
        return view('livewire.master-data.user.edit-user', [
            'roles' => Role::platformCases(),
        ]);
    }
}
