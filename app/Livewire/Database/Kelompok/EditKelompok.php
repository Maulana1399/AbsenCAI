<?php

namespace App\Livewire\Database\Kelompok;

use App\Models\desa;
use App\Models\kelompok;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class EditKelompok extends Component
{
    public $kelompok;

    public $kelompok_id;

    public $daftarDesa = [];

    public $desa_id;

    public function mount()
    {
        $this->daftarDesa = desa::all();
    }

    #[On('editKelompok')]
    public function editKelompok($id)
    {
        $data = kelompok::find($id);
        $this->kelompok_id = $data->id;
        $this->kelompok = $data->kelompok_asal;
        $this->desa_id = $data->desa_id;
        Flux::modal('edit-kelompok')->show();
    }

    public function update()
    {
        Gate::authorize('manage-master-data');

        $this->validate([
            'kelompok' => 'required',
        ]);
        kelompok::where('id', $this->kelompok_id)->update([
            'kelompok_asal' => $this->kelompok,
            'desa_id' => $this->desa_id,
        ]);

        return redirect()->to('/kelompok');
    }

    public function render()
    {
        return view('livewire.database.kelompok.edit-kelompok');
    }
}
