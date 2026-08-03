<?php

namespace App\Livewire\MasterData\User;

use App\Enums\Role;
use App\Models\Person;
use App\Services\User\UserManagementService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class CreateUser extends Component
{
    public bool $processing = false;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public string $role = '';
    public ?int $person_id = null;
    public string $searchPerson = '';
    public string $selectedPersonNama = '';

    public function render()
    {
        $personResults = [];
        if (strlen($this->searchPerson) >= 2) {
            $linkedIds = User::whereNotNull('person_id')->pluck('person_id');
            $personResults = Person::whereNotIn('id', $linkedIds)
                ->where('nama', 'like', '%' . $this->searchPerson . '%')
                ->limit(10)
                ->get();
        }

        return view('livewire.master-data.user.create-user', [
            'roles' => Role::platformCases(),
            'personResults' => $personResults,
        ]);
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

    public function simpan(): void
    {
        Gate::authorize('manage-users');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate();

            app(UserManagementService::class)->create([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'password' => $this->password,
                'role' => $this->role,
                'person_id' => $this->person_id,
            ]);

            $this->dispatch('refreshUser');
            $this->reset(['name', 'email', 'password', 'passwordConfirmation', 'role', 'person_id', 'searchPerson', 'selectedPersonNama']);
            $this->resetErrorBag();

            Flux::modal('tambah-user')->close();
            session()->flash('success', 'User berhasil dibuat.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->processing = false;
            throw $e;
        } finally {
            $this->processing = false;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'passwordConfirmation' => 'required|string|same:password',
            'role' => 'required|in:' . implode(',', Role::platformValues()),
            'person_id' => 'nullable|exists:people,id',
        ];
    }

    protected $messages = [
        'name.required' => 'Nama wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah digunakan.',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal 8 karakter.',
        'passwordConfirmation.required' => 'Konfirmasi password wajib diisi.',
        'passwordConfirmation.same' => 'Konfirmasi password tidak cocok.',
        'role.required' => 'Role wajib dipilih.',
        'role.in' => 'Role tidak valid.',
        'person_id.exists' => 'Person tidak ditemukan.',
    ];
}
