<?php

namespace App\Livewire\Database\Desa;

use App\Models\desa;
use Livewire\Component;

class TambahDesa extends Component
{

    public $Desa = '';

    public function render()
    {
        return view('livewire.database.desa.data-desa');
    }

    public function simpan(){
        $this->validate([
            "Desa" => "required"
        ]);

        desa::create([
            'desa_asal' => $this->Desa,
        ]);
        
        return redirect()->to('/desa');
    }
}