<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;

class TambahPeserta extends Component
{
    public $nama = '';
    public $nip = '';
    public $daftarDesa = [];
    public $desa_id;
    public $daftarKelompok = [];
    public $kelompok_id;
    public $daftarRegu = [];
    public $regu_id;
    public $jenis_kelamin;
    public $daftarJenisKelamin = ['Laki - Laki', 'Perempuan'];

    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->daftarKelompok = kelompok::all();
        $this->daftarRegu = regu::all();
    }

    public function simpan()
    {
        $this->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'required|integer|max:300',
            'jenis_kelamin' => 'required|in:Laki - Laki,Perempuan',
            'desa_id' => 'required|exists:desas,id',
            'kelompok_id' => 'required|exists:kelompoks,id',
            'regu_id' => 'required|exists:regus,id',
        ]);

        // Debug
        logger([
            'nama' => $this->nama,
            'nip' => $this->nip,
            'jenis_kelamin' => $this->jenis_kelamin,
            'desa_id' => $this->desa_id,
            'kelompok_id' => $this->kelompok_id,
            'regu_id' => $this->regu_id,
        ]);

        // Simpan data peserta
        peserta::create([
            'nama' => $this->nama,
            'nip' => $this->nip,
            'jenis_kelamin' => $this->jenis_kelamin,
            'desa_id' => $this->desa_id,
            'kelompok_id' => $this->kelompok_id,
            'regu_id' => $this->regu_id,
        ]);

        return redirect()->to('/database');
    }

    public function render()
    {
        return view('livewire.database.peserta.tambah-peserta');
    }
}
