<?php

namespace App\Livewire\Dashboard;

use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Support\ActiveEventContext;
use App\Support\EventOwnership;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Scan extends Component
{
    public $nama;
    public $jam_scan;
    public $message;

    public $manualSearch = '';
    public $manualResults = [];
    public $selectedManualParticipantId = null;
    public $selectedSource = null;

    public $sesi_id = '';
    public $daftarSesi;

    public $restartScanner = false;


    public function mount()
    {
        $event = app(ActiveEventContext::class)->current();
        $this->loadSesiForEvent($event);

        $sesiAktif = $event
            ? SesiAbsensi::where('event_id', $event->id)->where('aktif', true)->first()
            : null;

        if ($sesiAktif) {
            $this->sesi_id = $sesiAktif->id;
        }
    }

    private function loadSesiForEvent($event): void
    {
        if ($event) {
            $this->daftarSesi = SesiAbsensi::where('event_id', $event->id)
                ->orderBy('tanggal', 'asc')
                ->get();
        } else {
            $this->daftarSesi = collect();
        }
    }


    public function updatedManualSearch(): void
    {
        $event = app(ActiveEventContext::class)->current();
        if (! $event) {
            $this->manualResults = [];
            return;
        }

        $search = '%' . trim($this->manualSearch) . '%';

        // 1. Canonical: Participation + Person scoped to active event
        $participations = Participation::with(['person', 'legacyParticipationMapping.peserta'])
            ->where('event_id', $event->id)
            ->where(function ($q) use ($search) {
                $q->whereHas('person', fn ($pq) => $pq->where('nama', 'like', $search))
                  ->orWhere('participant_number', 'like', $search);
            })
            ->limit(10)
            ->get()
            ->map(fn ($part) => [
                'id' => $part->id,
                'person_id' => $part->person_id,
                'peserta_id' => $part->person?->legacyPesertaMapping?->peserta_id,
                'nama' => $part->person?->nama,
                'participant_number' => $part->participant_number,
                'attendance_code' => $part->attendance_code,
                'source' => 'canonical',
            ]);

        // 2. Legacy fallback: event-aware bridge-first resolution
        $resolver = app(LegacyParticipationResolver::class);
        $searchPeserta = \App\Models\peserta::where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                  ->orWhere('participant_number', 'like', $search);
            })
            ->limit(20)
            ->get()
            ->map(function ($p) use ($event, $resolver) {
                $participation = $resolver->resolveByPesertaAndEvent($p->id, $event->id);
                if (! $participation) {
                    return null;
                }

                return [
                    'id' => $participation->id,
                    'person_id' => $participation->person_id,
                    'peserta_id' => $resolver->resolvePesertaByParticipation($participation->id, $event->id)?->id ?? $p->id,
                    'nama' => $participation->person?->nama ?? $p->nama,
                    'participant_number' => $participation->participant_number,
                    'attendance_code' => $participation->attendance_code,
                    'source' => 'legacy',
                ];
            })->filter()->values();

        $this->manualResults = $participations->concat($searchPeserta)->take(10);
    }

    public function selectManualParticipant(int $id, ?string $source = null): void
    {
        $event = app(ActiveEventContext::class)->current();
        if (! $event) {
            return;
        }

        $resolver = app(LegacyParticipationResolver::class);

        if ($source === 'canonical') {
            $part = Participation::with('person')->find($id);
            if (! $part || ! EventOwnership::belongsToEvent($part, $event)) {
                return;
            }

            $this->selectedManualParticipantId = $part->id;
            $this->selectedSource = 'canonical';
            $this->nama = $part->person?->nama;
            $this->manualSearch = ($part->person?->nama ?? '') . ' · ' . ($part->participant_number ?? '-');
            $this->message = null;

            return;
        }

        $peserta = \App\Models\peserta::find($id);
        if ($peserta) {
            $participation = $resolver->resolveByPesertaAndEvent($peserta->id, $event->id);
            if ($participation) {
                $this->selectedManualParticipantId = $participation->id;
                $this->selectedSource = 'canonical';
                $this->nama = $participation->person?->nama ?? $peserta->nama;
                $this->manualSearch = ($participation->person?->nama ?? $peserta->nama) . ' · ' . ($participation->participant_number ?? '-');
                $this->message = null;

                return;
            }
        }
    }

    public function manualAttend(): void
    {
        Gate::authorize('manage-attendance');

        $event = app(ActiveEventContext::class)->current();
        if (! $event) {
            $this->message = 'Tidak ada event aktif';
            return;
        }

        if (! $this->validateSessionForEvent($event->id)) {
            $this->message = 'Sesi absensi tidak valid atau bukan milik event ini.';
            $this->nama = null;
            $this->jam_scan = null;
            return;
        }

        $pesertaId = null;
        $attendanceCode = null;

        if ($this->selectedSource === 'canonical' && is_int($this->selectedManualParticipantId)) {
            $part = Participation::with('person.legacyPesertaMapping.peserta')
                ->find($this->selectedManualParticipantId);
            if ($part) {
                $legacyPeserta = $part->person?->legacyPesertaMapping?->peserta;
                if ($legacyPeserta) {
                    $pesertaId = $legacyPeserta->id;
                    $attendanceCode = $legacyPeserta->attendance_code;
                } elseif ($part->attendance_code) {
                    $pesertaId = $part->id;
                    $attendanceCode = $part->attendance_code;
                }
            }
        } else {
            $p = \App\Models\peserta::find($this->selectedManualParticipantId);
            if ($p) {
                $pesertaId = $p->id;
                $attendanceCode = $p->attendance_code;
            }
        }

        if (! $attendanceCode) {
            $this->message = 'Pilih peserta terlebih dahulu';
            $this->nama = null;
            $this->jam_scan = null;
            return;
        }

        $result = app(AttendanceService::class)->processScan(
            $attendanceCode,
            $this->sesi_id ? (int) $this->sesi_id : null,
            'manual'
        );

        $this->message = $result['message'];

        if ($result['status'] === 'not_found' || $result['status'] === 'session_required' || $result['status'] === 'wrong_event') {
            $this->nama = null;
            $this->jam_scan = null;
            return;
        }

        $this->nama = $result['identity']->nama;
        $this->jam_scan = $result['jam_scan'] ?? null;
    }

    public function manualIzin(): void
    {
        Gate::authorize('manage-attendance');

        $event = app(ActiveEventContext::class)->current();
        if (! $event) return;

        if (! $this->validateSessionForEvent($event->id)) {
            $this->message = 'Sesi absensi tidak valid atau bukan milik event ini.';
            return;
        }

        if (! $this->sesi_id) {
            $this->message = 'Pilih sesi absensi terlebih dahulu';
            return;
        }

        $pesertaId = null;
        $participationId = null;

        if ($this->selectedSource === 'canonical' && is_int($this->selectedManualParticipantId)) {
            $part = Participation::with('person.legacyPesertaMapping.peserta')
                ->find($this->selectedManualParticipantId);
            if ($part) {
                $legacyPeserta = $part->person?->legacyPesertaMapping?->peserta;
                if ($legacyPeserta) {
                    $pesertaId = $legacyPeserta->id;
                }
                $participationId = $part->id;
            }
        } else {
            $p = \App\Models\peserta::find($this->selectedManualParticipantId);
            if ($p) $pesertaId = $p->id;
        }

        if (! $pesertaId && ! $participationId) {
            $this->message = 'Pilih peserta terlebih dahulu';
            return;
        }

        try {
            app(AttendanceExceptionService::class)->recordIzin(
                pesertaId: $pesertaId,
                sesiId: (int) $this->sesi_id,
                source: 'manual',
                participationId: $participationId,
            );

            $this->nama = \App\Models\peserta::find($pesertaId)?->nama ?? '-';
            $this->jam_scan = null;
            $this->message = 'Peserta berhasil dicatat sebagai izin';
        } catch (ValidationException $exception) {
            $this->message = $exception->validator->errors()->first('peserta')
                ?? $exception->validator->errors()->first('participation')
                ?? 'Gagal mencatat izin.';
        }
    }

    public function scanPeserta($data)
    {
        Gate::authorize('manage-attendance');

        $event = app(ActiveEventContext::class)->current();
        if ($event && $this->sesi_id && ! $this->validateSessionForEvent($event->id)) {
            $this->message = 'Sesi absensi tidak valid atau bukan milik event ini.';
            return;
        }

        $result = app(AttendanceService::class)->processScan((string) $data, $this->sesi_id ? (int) $this->sesi_id : null);

        $this->message = $result['message'];

        if ($result['status'] === 'not_found' || $result['status'] === 'session_required' || $result['status'] === 'wrong_event') {
            $this->nama = null;
            $this->jam_scan = null;

            return;
        }

        $this->nama = $result['identity']->nama;
        $this->jam_scan = $result['jam_scan'] ?? null;
    }


    private function validateSessionForEvent(?int $eventId): bool
    {
        if ($this->sesi_id === '' || $this->sesi_id === null) {
            return false;
        }
        return SesiAbsensi::where('id', $this->sesi_id)
            ->where('event_id', $eventId)
            ->exists();
    }

    public function restartScan()
    {
        $this->nama = null;
        $this->jam_scan = null;
        $this->message = null;
        $this->manualSearch = '';
        $this->manualResults = [];
        $this->selectedManualParticipantId = null;
        $this->selectedSource = null;

        $this->dispatch('restartScanner');
    }


    public function render()
    {
        return view('livewire.dashboard.scan');
    }
}