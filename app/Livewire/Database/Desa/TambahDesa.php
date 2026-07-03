<?php

namespace App\Livewire\Database\Desa;

use App\Models\desa;
use Livewire\Component;

class TambahDesa extends Component
{
    public bool $processing = false;

    public $Desa = '';

    public function render()
    {
        return view('livewire.database.desa.tambah-desa');
    }

    public function simpan(){
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                "Desa" => "required|unique:desas,desa_asal"
            ]);

            desa::create([
                'desa_asal' => $this->Desa,
            ]);
            
            return redirect()->to('/desa');
        } finally {
            $this->processing = false;
        }
    }
}