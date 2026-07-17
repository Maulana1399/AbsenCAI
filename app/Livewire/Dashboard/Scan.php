<?php

namespace App\Livewire\Dashboard;

use App\Models\Absensi;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceService;
use App\Support\ActiveEventContext;
use Livewire\Component;
use App\Services\Attendance\AttendanceExceptionService;
use Illuminate\Validation\ValidationException;

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

        $event = app(ActiveEventContext::class)->current();
        $sesiAktif = $event
            ? SesiAbsensi::where('event_id', $event->id)->where('aktif', true)->first()
            : null;

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

    public function manualIzin(): void
    {
        $participant = $this->selectedManualParticipantId
            ? peserta::find($this->selectedManualParticipantId)
            : null;

        if (! $participant) {
            $this->message = 'Pilih peserta terlebih dahulu';
            return;
        }

        if (! $this->sesi_id) {
            $this->message = 'Pilih sesi absensi terlebih dahulu';
            return;
        }

        try {
            app(AttendanceExceptionService::class)->recordIzin(
                $participant->id,
                (int) $this->sesi_id,
                'manual'
            );

            $this->nama = $participant->nama;
            $this->nip = $participant->nip;
            $this->jam_scan = null;
            $this->message = 'Peserta berhasil dicatat sebagai izin';
        } catch (ValidationException $exception) {
            $this->message = $exception->validator->errors()->first('peserta');
        }
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