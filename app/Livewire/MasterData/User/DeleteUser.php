<?php

namespace App\Livewire\MasterData\User;

use App\Models\User;
use App\Services\User\UserManagementService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;

class DeleteUser extends Component
{
    public ?int $userId = null;
    public ?string $userName = null;
    public ?string $blockReason = null;
    public bool $canDelete = false;

    #[On('deleteUser')]
    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->userName = $user->name;

        if ($user->id === auth()->id()) {
            $this->blockReason = 'Anda tidak dapat menghapus akun Anda sendiri.';
            $this->canDelete = false;
        } else {
            $service = app(UserManagementService::class);
            $reasons = $service->canDelete($user);

            if (! empty($reasons)) {
                $this->blockReason = 'User tidak dapat dihapus karena:<br>' . implode('<br>', $reasons);
                $this->canDelete = false;
            } else {
                $this->blockReason = null;
                $this->canDelete = true;
            }
        }

        Flux::modal('hapus-user')->show();
    }

    public function destroy(): void
    {
        Gate::authorize('manage-users');

        $user = User::find($this->userId);

        if (! $user) {
            Flux::modal('hapus-user')->close();
            return;
        }

        try {
            app(UserManagementService::class)->delete($user, auth()->user());
            $this->dispatch('refreshUser');
            Flux::modal('hapus-user')->close();
            session()->flash('success', 'User berhasil dihapus.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->blockReason = $e->errors()['user'][0] ?? 'User tidak dapat dihapus.';
            $this->canDelete = false;
        }
    }

    public function render()
    {
        return view('livewire.master-data.user.delete-user');
    }
}
