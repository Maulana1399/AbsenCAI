<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Peserta;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\Regu;
use App\Models\Absensi;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $totalPeserta;
    public $totalDesa;
    public $totalKelompok;
    public $totalRegu;
    public $absensis;
    public $rekapAbsenPerSesi; // Variabel untuk menyimpan rekap absen per sesi

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

        // Menambahkan sesi berdasarkan jam_scan
        $this->absensis = $this->absensis->map(function ($absensi) {
            // Memparse jam_scan dengan Carbon untuk menentukan sesi
            $jamScan = Carbon::parse($absensi->jam_scan);

            // Tentukan sesi berdasarkan jam
            if ($jamScan->hour >= 0 && $jamScan->hour < 6) {
                $absensi->sesi = 'Subuh';  // Sesi Subuh
            } elseif ($jamScan->hour >= 6 && $jamScan->hour < 12) {
                $absensi->sesi = 'Pagi';
            } elseif ($jamScan->hour >= 12 && $jamScan->hour < 18) {
                $absensi->sesi = 'Siang';
            } else {
                $absensi->sesi = 'Malam';
            }

            return $absensi;
        });

        // Hitung rekap absen per sesi
        $this->rekapAbsenPerSesi = $this->absensis->groupBy('sesi')->map(function ($group) {
            return $group->count(); // Menghitung jumlah absensi per sesi
        });
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard', [
            'absensis' => $this->absensis,
            'rekapAbsenPerSesi' => $this->rekapAbsenPerSesi, // Menampilkan rekap absen per sesi
        ]);
    }
}
 