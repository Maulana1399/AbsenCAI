<?php

namespace App\Livewire\Dashboard;

use App\Services\Attendance\AttendanceService;
use App\Models\SesiAbsensi;
use Livewire\Component;

class Scan extends Component
{
    public $nama;
    public $nip;
    public $jam_scan;
    public $message;

    public $sesi_id = '';
    public $daftarSesi;

    public $restartScanner = false;


    public function mount()
    {
        $this->daftarSesi = SesiAbsensi::orderBy('tanggal', 'asc')->get();

        $sesiAktif = SesiAbsensi::where('aktif', true)->first();

        if ($sesiAktif) {
            $this->sesi_id = $sesiAktif->id;
        }
    }


    public function scanPeserta($data)
    {
        $result = app(AttendanceService::class)->processScan(
            (string) $data,
            $this->sesi_id ? (int) $this->sesi_id : null
        );

        $this->message = $result['message'];

        if ($result['status'] === 'not_found' || $result['status'] === 'session_required') {
            $this->nama = null;
            $this->nip = null;
            $this->jam_scan = null;

            return;
        }

        $this->nip = $result['peserta']->nip;
        $this->nama = $result['peserta']->nama;
        $this->jam_scan = $result['jam_scan'] ?? null;
    }


    public function restartScan()
    {
        $this->nama = null;
        $this->nip = null;
        $this->jam_scan = null;
        $this->message = null;

        $this->dispatch('restartScanner');
    }


    public function render()
    {
        return view('livewire.dashboard.scan');
    }
}