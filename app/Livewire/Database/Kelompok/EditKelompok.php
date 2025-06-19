<?php

namespace App\Livewire\Database\Kelompok;

use Livewire\Component;
use App\Models\kelompok;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\desa;

class EditKelompok extends Component
{
    public $kelompok;
    public $kelompok_id;
    public $daftarDesa = [];
    public $desa_id;

    public function mount()
    {
        // $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
    }

    #[On("editKelompok")]
    public function editKelompok($id)
    {
        $data = kelompok::find($id);
        $this->kelompok_id = $data->id;
        $this->kelompok = $data->kelompok_asal;
        $this->desa_id = $data->desa_id;
        Flux::modal("edit-kelompok")->show();
    }
    public function update()
    {
        $this->validate([
            'kelompok' => 'required'
        ]);
        kelompok::where('id', $this->kelompok_id)->update([
            'kelompok_asal' => $this->kelompok,
            'desa_id' => $this->desa_id
        ]);
        // Flux::modal("edit-kelompok")->hide();
        return redirect()->to('/kelompok');
    }

    public function render()
    {
        return view('livewire.database.kelompok.edit-kelompok');
    }
}
