<?php

namespace App\Livewire\MasterData\User;

use App\Models\User;
use App\Services\User\UserManagementService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;

class ResetPasswordUser extends Component
{
    public bool $processing = false;

    public ?int $userId = null;
    public ?string $userName = null;
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    #[On('resetPasswordUser')]
    public function load(int $id): void
    {
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->userName = $user->name;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetErrorBag();

        Flux::modal('reset-password-user')->show();
    }

    public function resetPassword(): void
    {
        Gate::authorize('manage-users');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate();

            $user = User::findOrFail($this->userId);

            app(UserManagementService::class)->resetPassword($user, $this->newPassword);

            $this->reset(['newPassword', 'newPasswordConfirmation', 'userId', 'userName']);
            $this->resetErrorBag();

            Flux::modal('reset-password-user')->close();
            session()->flash('success', 'Password berhasil direset.');
        } finally {
            $this->processing = false;
        }
    }

    protected function rules(): array
    {
        return [
            'newPassword' => 'required|string|min:8',
            'newPasswordConfirmation' => 'required|string|same:newPassword',
        ];
    }

    protected $messages = [
        'newPassword.required' => 'Password baru wajib diisi.',
        'newPassword.min' => 'Password minimal 8 karakter.',
        'newPasswordConfirmation.required' => 'Konfirmasi password wajib diisi.',
        'newPasswordConfirmation.same' => 'Konfirmasi password tidak cocok.',
    ];

    public function render()
    {
        return view('livewire.master-data.user.reset-password-user');
    }
}
