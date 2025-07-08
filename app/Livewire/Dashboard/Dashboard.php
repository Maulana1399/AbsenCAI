<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\absensi;

class Dashboard extends Component
{
    public $totalPeserta;
    public $totalDesa;
    public $totalKelompok;
    public $totalRegu;
    public $absensis;

    public function mount()
    {
        $this->totalPeserta = Peserta::count();
        $this->totalDesa = Desa::count();
        $this->totalKelompok = Kelompok::count();
        $this->totalRegu = Regu::count();

        // Ambil 10 data scan pertama beserta relasi peserta, regu, kelompok
        $this->absensis = Absensi::orderBy('jam_scan', 'asc')
            ->with(['peserta.regu', 'peserta.kelompok'])
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard', [
            'absensis' => $this->absensis,
        ]);
    }
}
