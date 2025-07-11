<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\Absensi;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $totalPeserta;
    public $totalDesa;
    public $totalKelompok;
    public $totalRegu;
    public $absensis;
    public $rekapAbsenPerSesi; 
    public $pesertaBelumAbsen; 

    public function mount()
    {
        $this->totalPeserta = Peserta::count();
        $this->totalDesa = Desa::count();
        $this->totalKelompok = Kelompok::count();
        $this->totalRegu = Regu::count();

        // Ambil data absensi hanya untuk hari ini
        $today = Carbon::today(); // Mendapatkan tanggal hari ini
        $this->absensis = Absensi::whereDate('jam_scan', $today) // Memfilter berdasarkan tanggal hari ini
            ->orderBy('jam_scan', 'asc')
            ->with(['peserta.regu', 'peserta.kelompok'])
            ->limit(10)
            ->get();

        // Menambahkan sesi berdasarkan jam_scan
         $this->rekapAbsenPerSesi = Absensi::whereDate('jam_scan', Carbon::today())
            ->with('peserta')
            ->get() // Mengambil semua data absensi untuk hari ini
            ->groupBy(function($absen) {
                $jamScan = Carbon::parse($absen->jam_scan);
                if ($jamScan->hour >= 0 && $jamScan->hour < 6) {
                    return 'Subuh';
                } elseif ($jamScan->hour >= 6 && $jamScan->hour < 12) {
                    return 'Pagi';
                } elseif ($jamScan->hour >= 12 && $jamScan->hour < 18) {
                    return 'Siang';
                } else {
                    return 'Malam';
                }
            })
            ->map(function ($group) {
                return $group->count(); // Menghitung jumlah absensi per sesi
            });
        
            $this->pesertaBelumAbsen = Peserta::with(['regu', 'kelompok'])
            ->whereNotIn('id', Absensi::whereDate('jam_scan', Carbon::today())->pluck('nip'))
            ->get();
}

    public function render()
    {
        return view('livewire.dashboard.dashboard', [
            'absensis' => $this->absensis,
            'rekapAbsenPerSesi' => $this->rekapAbsenPerSesi, // Menampilkan rekap absen per sesi untuk hari ini
            'pesertaBelumAbsen' => $this->pesertaBelumAbsen,
        ]);
    }
}
