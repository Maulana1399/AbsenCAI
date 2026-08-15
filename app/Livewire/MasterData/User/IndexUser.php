<?php

namespace App\Livewire\MasterData\User;

use App\Enums\Role;
use App\Models\EventCommitteeAssignment;
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
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->filterRole !== '') {
            $query->where('role', $this->filterRole);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.master-data.user.index-user', [
            'users' => $users,
            'roles' => Role::accountCases(),
            'eventRoleLabels' => $this->eventRoleLabels($users),
        ]);
    }

    /**
     * Nama EventRole per user (dari assignment aktif pada event aktif).
     *
     * @return array<int, string> map user_id => label ("Ketua Fosda", "Ketua Fosda (+2)", atau "—")
     */
    private function eventRoleLabels($users): array
    {
        $userIds = collect($users->items())->pluck('id')->all();
        $personIds = collect($users->items())->pluck('person_id')->filter()->all();

        if ($userIds === []) {
            return [];
        }

        $assignments = EventCommitteeAssignment::query()
            ->where(function ($query) use ($userIds, $personIds) {
                $query->whereIn('user_id', $userIds);

                if ($personIds !== []) {
                    $query->orWhereIn('person_id', $personIds);
                }
            })
            ->whereHas('event', fn ($q) => $q->where('status', 'active'))
            ->whereHas('eventRole', fn ($q) => $q->where('is_active', true))
            ->with('eventRole:id,name')
            ->get();

        $labelsByUser = [];

        foreach ($users as $user) {
            $byUser = $assignments->where('user_id', $user->id)->values();
            $byPerson = $user->person_id !== null
                ? $assignments->where('person_id', $user->person_id)->values()
                : collect();

            $names = $byUser->merge($byPerson)
                ->map(fn ($a) => $a->eventRole?->name)
                ->filter()
                ->unique()
                ->values();

            if ($names->isEmpty()) {
                $labelsByUser[$user->id] = '—';
            } elseif ($names->count() === 1) {
                $labelsByUser[$user->id] = $names->first();
            } else {
                $labelsByUser[$user->id] = $names->first().' (+'.($names->count() - 1).')';
            }
        }

        return $labelsByUser;
    }

    public function toggleActive(int $userId): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('refreshUser');
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
