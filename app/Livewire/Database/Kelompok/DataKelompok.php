<?php

namespace App\Livewire\Database\Kelompok;

use App\Models\kelompok;
use App\Models\desa;
use Livewire\Component;
use Livewire\Attributes\On;

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

    public function delete($id)
    {
        $this->dispatch('HapusKelompok', id: $id);
    }

    #[On('refreshKelompok')]
    public function refrashKelompok()
    {
        $this->daftarkelompok = kelompok::all();
    }
}
