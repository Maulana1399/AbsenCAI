<?php

namespace App\Livewire\Database\Kelompok;

use App\Models\kelompok;
use Livewire\Component;
use App\Models\desa;

class TambahKelompok extends Component
{
    public bool $processing = false;

    public $Kelompok = '';
    public $daftarDesa = [];
    public $desa_id;

    public function mount()
{
    $this->daftarDesa = desa::all();
}


    public function simpan(){
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                "Kelompok" => "required",
                "desa_id" => "required"
            ]);
            
            // Debug
            logger([
                'Kelompok' => $this->Kelompok,
                'desa_id' => $this->desa_id,
            ]);
            
            kelompok::create([
                'kelompok_asal' => $this->Kelompok,
                'desa_id' => $this->desa_id,
            ]);
            
            return redirect()->to('/kelompok');
        } finally {
            $this->processing = false;
        }
    }
    public function render()
    {
        return view('livewire.database.kelompok.tambah-kelompok');
    }
}