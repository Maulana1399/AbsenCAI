<?php

namespace App\Livewire\Rekap\Peserta;

use Livewire\Component;
use App\Models\peserta;
use App\Models\regu;
use App\Models\kelompok;
use App\Models\desa;

class RekapPeserta extends Component
{
    public $regu_id = '';
    public $kelompok_id = '';
    public $desa_id = '';

    public $daftarRegu = [];
    public $daftarKelompok = [];
    public $daftarDesa = [];

    public function mount()
    {
        $this->daftarRegu = regu::all();
        $this->daftarKelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
    }

    public function render()
    {
        $query = peserta::with(['regu','kelompok','desa']);

        if ($this->regu_id) {
            $query->where('regu_id', $this->regu_id);
        }

        if ($this->kelompok_id) {
            $query->where('kelompok_id', $this->kelompok_id);
        }

        if ($this->desa_id) {
            $query->where('desa_id', $this->desa_id);
        }

        $daftar = $query->orderBy('nama')->get();

        $total = $daftar->count();
        $totalLaki = $daftar->where('jenis_kelamin','Laki - Laki')->count();
        $totalPerempuan = $daftar->where('jenis_kelamin','Perempuan')->count();
        $sudahRegUlang = $daftar->where('status_registrasi','Registrasi Ulang')->count();
        $belumRegUlang = $daftar->where('status_registrasi','Belum Registrasi')->count();

        return view('livewire.rekap.peserta.rekap-peserta', [
            'daftarPeserta' => $daftar,
            'total' => $total,
            'totalLaki' => $totalLaki,
            'totalPerempuan' => $totalPerempuan,
            'sudahRegUlang' => $sudahRegUlang,
            'belumRegUlang' => $belumRegUlang,
        ]);
    }
}
