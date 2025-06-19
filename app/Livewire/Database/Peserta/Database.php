<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\kelompok;
use App\Models\desa;
use App\Models\regu;
use App\Models\peserta;

class Database extends Component
{
    public $daftarPeserta = [];

    public $daftarkelompok = [];
    public $kelompok_id;
    public $daftarDesa = [];
    public $desa_id;
    public $daftarRegu = [];
    public $regu_id;

    public function mount()
    {
        $this->daftarPeserta = peserta::with(['desa', 'kelompok', 'regu'])->get();
        $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
        $this->daftarRegu = regu::all();
    }

    public function render()
    {
        return view('livewire.database.peserta.database', [
            'daftarPeserta' => $this->daftarPeserta,
            'daftarkelompok' => $this->daftarkelompok,
            'daftarDesa' => $this->daftarDesa,
            'daftarRegu' => $this->daftarRegu
        ]);
    }

    public function edit($id)
    {
        $this->dispatch('editPeserta', id: $id);
    }
}
