<?php

namespace App\Livewire\Database\Kelompok;

use App\Models\kelompok;
use App\Models\desa;
use Livewire\Component;

class DataKelompok extends Component
{
    public $daftarkelompok;
    public $daftarDesa = [];
    public $desa_id;

    public function mount(){
        $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();

        // dd($this->daftarregu);
    }

    public function render()    
    {
         return view('livewire.database.kelompok.data-kelompok', [
            'daftarkelompok' => $this->daftarkelompok
        ]);
    }

    public function edit($id)
    {
        $this->dispatch('editKelompok', id: $id);
    }
}
