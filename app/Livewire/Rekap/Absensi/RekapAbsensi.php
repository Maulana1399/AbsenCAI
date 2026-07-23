<?php

namespace App\Livewire\Rekap\Absensi;

use App\Models\Participation;
use App\Models\regu;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceReadService;
use App\Support\ActiveEventContext;
use Livewire\Component;

class RekapAbsensi extends Component
{
    public $sesi_id = '';
    public $regu_id = '';

    public function updatedSesiId(): void
    {
        if ($this->sesi_id !== '' && ! $this->availableSessionsQuery()->whereKey($this->sesi_id)->exists()) {
            $this->reset('sesi_id', 'regu_id');
        }
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $daftarSesi = $this->availableSessionsQuery()->orderBy('tanggal', 'desc')->get();
        $daftarRegu = regu::orderBy('regu')->get();

        $totalPeserta = 0;
        $sudahAbsen = collect();
        $pesertaIzin = collect();
        $pesertaBelumAbsen = collect();
        $sudahAbsenCount = 0;
        $izinCount = 0;
        $belumAbsenCount = 0;
        $persentase = 0;

        if ($event !== null && $this->sesi_id !== '') {
            $sesi = $this->availableSessionsQuery()->find($this->sesi_id);

            if ($sesi !== null) {
                $reguId = $this->regu_id ? (int) $this->regu_id : null;
                $sessionData = app(AttendanceReadService::class)->getSessionAttendance($event->id, $sesi->id, $reguId);

                $totalPeserta = $sessionData['total'];
                $sudahAbsenCount = $sessionData['hadir_count'];
                $izinCount = $sessionData['izin_count'];
                $persentase = $sessionData['persentase'];

                $sudahAbsen = $sessionData['attendance']->filter(fn ($e) => $e->status === 'hadir');
                $pesertaIzin = $sessionData['attendance']->filter(fn ($e) => $e->status === 'izin');
                $pesertaBelumAbsen = $sessionData['attendance']->filter(fn ($e) => $e->status === 'belum')
                    ->map(function ($entry) {
                        $lp = $entry->legacyPeserta;
                        $participation = $entry->participation;
                        return (object) [
                            'id' => $participation->id,
                            'peserta' => $lp,
                            'person' => $entry->person,
                            'participation' => $participation,
                            'nama' => $entry->person?->nama,
                            'nip' => $lp?->nip ?? $entry->person?->nip,
                            'regu' => $participation->regu,
                            'kelompok' => $lp?->kelompok,
                            'desa' => $entry->person?->desa,
                        ];
                    });
                $belumAbsenCount = $pesertaBelumAbsen->count();
            }
        }

        return view('livewire.rekap.absensi.rekap-absensi', [
            'daftarSesi' => $daftarSesi,
            'daftarRegu' => $daftarRegu,
            'totalPeserta' => $totalPeserta,
            'sudahAbsen' => $sudahAbsen,
            'pesertaIzin' => $pesertaIzin,
            'pesertaBelumAbsen' => $pesertaBelumAbsen,
            'sudahAbsenCount' => $sudahAbsenCount,
            'izinCount' => $izinCount,
            'belumAbsenCount' => $belumAbsenCount,
            'persentase' => $persentase,
        ]);
    }

    private function availableSessionsQuery()
    {
        $event = app(ActiveEventContext::class)->current();

        if ($event === null) {
            return SesiAbsensi::query()->whereRaw('0 = 1');
        }

        return SesiAbsensi::query()->where('event_id', $event->id);
    }
}
