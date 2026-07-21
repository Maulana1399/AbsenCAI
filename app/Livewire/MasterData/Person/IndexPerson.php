<?php

namespace App\Livewire\MasterData\Person;

use App\Models\Person;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class IndexPerson extends Component
{
    use WithPagination;

    public string $search = '';

    protected $updatesQueryString = ['search'];

    public function render()
    {
        $query = Person::with(['desa', 'kelompok']);

        if (trim($this->search) !== '') {
            $keyword = trim($this->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', '%'.$keyword.'%')
                  ->orWhere('nip', 'like', '%'.$keyword.'%');
            });
        }

        return view('livewire.master-data.person.index-person', [
            'people' => $query->orderBy('created_at', 'desc')->paginate(20),
        ]);
    }

    public function edit(int $id): void
    {
        $this->dispatch('editPerson', id: $id);
    }

    public function delete(int $id): void
    {
        $this->dispatch('deletePerson', id: $id);
    }

    #[On('refreshPerson')]
    public function refreshPerson(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }
}
