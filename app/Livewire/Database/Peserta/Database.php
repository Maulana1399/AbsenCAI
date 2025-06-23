<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\kelompok;
use App\Models\desa;
use App\Models\regu;
use App\Models\peserta;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class Database extends Component
{
    // public $daftarPeserta = [];

    public $daftarkelompok = [];
    public $kelompok_id;
    public $daftarDesa = [];
    public $desa_id;
    public $daftarRegu = [];
    public $regu_id;
    public $search = '';

    public function mount()
    {
        $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
        $this->daftarRegu = regu::all();
    }

    public function render()
    {
        logger('search: ' . $this->search); // Tambahkan ini

        $pesertaQuery = peserta::with(['desa', 'kelompok', 'regu']);

        if ($this->search) {
            $search = $this->search;
            $pesertaQuery->where(function($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                  ->orWhere('nip', 'like', '%'.$search.'%');
            });
        }
        // dd($pesertaQuery->get());
        return view('livewire.database.peserta.database', [
            'daftarPeserta' => $pesertaQuery->get(),
            'daftarkelompok' => $this->daftarkelompok,
            'daftarDesa' => $this->daftarDesa,
            'daftarRegu' => $this->daftarRegu
        ]);
    }

    public function edit($id)
    {
        $this->dispatch('editPeserta', id: $id);
    }

    public function delete($id)
    {
        $this->dispatch('HapusPeserta', id: $id);
    }

    #[On('refreshPeserta')]
    public function refreshPeserta()
    {
        $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
        $this->daftarRegu = regu::all();
    }

    public function cari()
    {

    }
}
