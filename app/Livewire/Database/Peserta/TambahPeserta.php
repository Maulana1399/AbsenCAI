<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;

class TambahPeserta extends Component
{
    public bool $processing = false;

    public $nama = '';
    public $nip = '';
    public $daftarDesa = [];
    public $desa_id;
    public $daftarKelompok = [];
    public $kelompok_id;
    public $regu_id;
    public string $regu_nama = '-';
    public $jenis_kelamin;
    public $daftarJenisKelamin = ['Laki - Laki', 'Perempuan'];

    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->daftarKelompok = kelompok::all();
        $this->generateAutoFields();
    }

    public function generateAutoFields(): void
    {
        $autoPlacement = peserta::autoPlacement($this->jenis_kelamin ?: null);

        $this->nip = $autoPlacement['nip'];
        $this->regu_id = $autoPlacement['regu_id'];
        $this->regu_nama = $autoPlacement['regu_nama'];
    }

    public function updatedJenisKelamin(): void
    {
        $this->generateAutoFields();
    }

    public function simpan()
    {
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->generateAutoFields();

            $this->validate([
                'nama' => 'required|string|max:255',
                'nip' => 'required|integer|unique:pesertas,nip',
                'jenis_kelamin' => 'required|in:Laki - Laki,Perempuan',
                'desa_id' => 'required|exists:desas,id',
                'kelompok_id' => 'required|exists:kelompoks,id',
                'regu_id' => 'required|exists:regus,id',
            ]);

            peserta::create([
                'nama' => $this->nama,
                'nip' => $this->nip,
                'jenis_kelamin' => $this->jenis_kelamin,
                'desa_id' => $this->desa_id,
                'kelompok_id' => $this->kelompok_id,
                'regu_id' => $this->regu_id,
                'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
            ]);

            return redirect()->to('/database');
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.database.peserta.tambah-peserta');
    }
}
