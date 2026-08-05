<?php

namespace App\Livewire\MasterData\Person;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Person;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class IndexPerson extends Component
{
    use WithPagination;

    public string $search = '';

    public string $desaId = '';

    public string $kelompokId = '';

    public string $jenisKelamin = '';

    protected $updatesQueryString = ['search', 'desaId', 'kelompokId', 'jenisKelamin'];

    public function render()
    {
        Gate::authorize('view-master-data');

        $query = Person::with(['desa', 'kelompok']);

        if (trim($this->search) !== '') {
            $keyword = trim($this->search);
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'like', '%'.$keyword.'%');
            });
        }

        if ($this->desaId !== '') {
            $query->where('desa_id', $this->desaId);
        }

        if ($this->kelompokId !== '') {
            $query->where('kelompok_id', $this->kelompokId);
        }

        if ($this->jenisKelamin !== '') {
            $query->where('jenis_kelamin', $this->jenisKelamin);
        }

        $people = $query->orderBy('created_at', 'desc')->paginate(20);

        $desas = desa::orderBy('desa_asal')->get();
        $kelompoks = $this->desaId !== ''
            ? kelompok::where('desa_id', $this->desaId)->orderBy('kelompok_asal')->get()
            : kelompok::orderBy('kelompok_asal')->get();

        return view('livewire.master-data.person.index-person', [
            'people' => $people,
            'desas' => $desas,
            'kelompoks' => $kelompoks,
            'totalPerson' => Person::count(),
        ]);
    }

    public function updatedDesaId(): void
    {
        $this->kelompokId = '';
        $this->resetPage();
    }

    public function resetFilter(): void
    {
        $this->search = '';
        $this->desaId = '';
        $this->kelompokId = '';
        $this->jenisKelamin = '';
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        Gate::authorize('view-master-data');

        $this->dispatch('editPerson', id: $id);
    }

    public function delete(int $id): void
    {
        Gate::authorize('view-master-data');

        $this->dispatch('deletePerson', id: $id);
    }

    #[On('refreshPerson')]
    public function refreshPerson(): void
    {
        Gate::authorize('view-master-data');

        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDesaId(): void
    {
        $this->resetPage();
    }

    public function updatingKelompokId(): void
    {
        $this->resetPage();
    }

    public function updatingJenisKelamin(): void
    {
        $this->resetPage();
    }
}
