<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Absensi;
use App\Models\peserta;

class Scan extends Component
{
    public $nama;
    public $nip;
    public $jam_scan;
    public $message;
    public $restartScanner = false;

    public function scanPeserta($data)
    {
        $nip = trim($data);

        // Validasi: cek apakah nip ada di tabel peserta
        $peserta = Peserta::where('nip', $nip)->first();
        if (!$peserta) {
            $this->message = "Data peserta tidak ditemukan!";
            $this->nama = null;
            $this->nip = null;
            $this->jam_scan = null;
            return;
        }

        $this->nip = $peserta->nip;
        $this->nama = $peserta->nama;
        $this->jam_scan = Carbon::now()->format('Y-m-d H:i:s');

        // Cek apakah sudah absen dalam 1 jam terakhir
        $last = Absensi::where('nip', $nip)
            ->where('jam_scan', '>=', Carbon::now()->subHour())
            ->first();

        if ($last) {
            $this->message = "Sudah melakukan scan dalam 1 jam terakhir!";
            return;
        }

        Absensi::create([
            'nip' => $peserta->nip,
            'nama' => $peserta->nama,
            'jam_scan' => $this->jam_scan,
        ]);
        $this->message = "Absensi berhasil!";
    }

    public function restartScan()
    {
        $this->nama = null;
        $this->nip = null;
        $this->jam_scan = null;
        $this->message = null;
        // Jika ingin trigger JS restart scanner, bisa tambahkan:
        $this->dispatch('restartScanner');
    }

    public function render()
    {
        return view('livewire.dashboard.scan');
    }
}