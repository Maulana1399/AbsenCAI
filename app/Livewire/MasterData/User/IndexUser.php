<?php

namespace App\Livewire\MasterData\User;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class IndexUser extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterRole = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterRole' => ['except' => ''],
    ];

    public function render()
    {
        Gate::authorize('manage-users');

        $query = User::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterRole !== '') {
            $query->where('role', $this->filterRole);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.master-data.user.index-user', [
            'users' => $users,
            'roles' => Role::cases(),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }
}
