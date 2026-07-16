<?php

namespace App\Livewire\Dashboard;

use App\Models\Absensi;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceService;
use Livewire\Component;

class Scan extends Component
{
    public $nama;
    public $nip;
    public $jam_scan;
    public $message;

    public $manualSearch = '';
    public $manualResults = [];
    public $selectedManualParticipantId = null;

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


    public function updatedManualSearch(): void
    {
        $this->manualResults = peserta::query()
            ->where('nama', 'like', '%'.trim($this->manualSearch).'%')
            ->orWhere('participant_number', 'like', '%'.trim($this->manualSearch).'%')
            ->orderBy('nama')
            ->limit(10)
            ->get();
    }

    public function selectManualParticipant(int $participantId): void
    {
        $participant = peserta::findOrFail($participantId);

        $this->selectedManualParticipantId = $participant->id;
        $this->manualSearch = $participant->nama.' · '.($participant->participant_number ?? $participant->nip);
        $this->nama = $participant->nama;
        $this->nip = $participant->nip;
        $this->message = null;
    }

    public function manualAttend(): void
    {
        $participant = $this->selectedManualParticipantId
            ? peserta::find($this->selectedManualParticipantId)
            : null;

        if (! $participant) {
            $this->message = 'Pilih peserta terlebih dahulu';
            $this->nama = null;
            $this->nip = null;
            $this->jam_scan = null;
            return;
        }

        $result = app(AttendanceService::class)->processScan(
            (string) $participant->attendance_code,
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

    public function scanPeserta($data)
    {
        $result = app(AttendanceService::class)->processScan((string) $data, $this->sesi_id ? (int) $this->sesi_id : null);

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
        $this->manualSearch = '';
        $this->manualResults = [];
        $this->selectedManualParticipantId = null;

        $this->dispatch('restartScanner');
    }


    public function render()
    {
        return view('livewire.dashboard.scan');
    }
}