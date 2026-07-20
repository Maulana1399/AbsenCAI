<?php

namespace App\Livewire\Rekap\Absensi;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\regu;
use App\Models\SesiAbsensi;
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
                $participationQuery = Participation::with(['person.desa', 'person.legacyPesertaMapping.peserta', 'event'])
                    ->where('event_id', $event->id);

                if ($this->regu_id) {
                    $participationQuery->whereHas('person.legacyPesertaMapping.peserta', fn ($builder) => $builder->where('regu_id', $this->regu_id));
                }

                $participations = $participationQuery->orderBy('id')->get();
                $participationIds = $participations->pluck('id');
                $personIds = $participations->pluck('person_id');
                $legacyPesertaIds = $participations->map(fn (Participation $participation) => $participation->person?->legacyPesertaMapping?->peserta?->id)->filter()->values();
                $legacyNips = $participations->map(fn (Participation $participation) => $participation->person?->legacyPesertaMapping?->peserta?->nip ?? $participation->person?->nip)->filter()->values();

                $sudahAbsen = Absensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa'])
                    ->where('sesi_id', $sesi->id)
                    ->whereIn('nip', $legacyNips)
                    ->get()
                    ->filter(function (Absensi $absensi) use ($participations) {
                        return $participations->contains(function (Participation $participation) use ($absensi) {
                            $legacyPeserta = $participation->person?->legacyPesertaMapping?->peserta;

                            return $participation->person?->nip == $absensi->nip
                                || ($legacyPeserta !== null && $legacyPeserta->nip == $absensi->nip);
                        });
                    })
                    ->values();

                $pesertaIzin = IzinAbsensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa'])
                    ->where('sesi_id', $sesi->id)
                    ->whereIn('peserta_id', $legacyPesertaIds)
                    ->get()
                    ->filter(fn (IzinAbsensi $izin) => $participationIds->contains($this->resolveParticipationIdFromIzin($izin, $participations)))
                    ->values();

                $absenNips = $sudahAbsen->pluck('nip')->unique();
                $izinPesertaIds = $pesertaIzin->pluck('peserta_id')->unique();

                $totalPeserta = $participations->count();
                $sudahAbsenCount = $sudahAbsen->count();
                $izinCount = $pesertaIzin->count();
                $pesertaBelumAbsen = $participations->reject(function (Participation $participation) use ($absenNips, $izinPesertaIds) {
                    $legacyPeserta = $participation->person?->legacyPesertaMapping?->peserta;

                    return $absenNips->contains($participation->person?->nip)
                        || ($legacyPeserta !== null && $absenNips->contains($legacyPeserta->nip))
                        || ($legacyPeserta !== null && $izinPesertaIds->contains($legacyPeserta->id));
                })->map(function (Participation $participation) {
                    return $this->presentParticipation($participation);
                })->values();
                $belumAbsenCount = $pesertaBelumAbsen->count();
                $persentase = $totalPeserta > 0
                    ? round(($sudahAbsenCount / $totalPeserta) * 100, 2)
                    : 0;
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

    private function presentParticipation(Participation $participation): object
    {
        $legacyPeserta = $participation->person?->legacyPesertaMapping?->peserta;

        return (object) [
            'id' => $participation->id,
            'peserta' => $legacyPeserta,
            'person' => $participation->person,
            'nama' => $participation->person?->nama,
            'nip' => $legacyPeserta?->nip ?? $participation->person?->nip,
            'regu' => $legacyPeserta?->regu,
            'kelompok' => $legacyPeserta?->kelompok,
            'desa' => $participation->person?->desa,
        ];
    }

    private function resolveParticipationIdFromIzin(IzinAbsensi $izin, $participations): ?int
    {
        $peserta = $izin->peserta;

        if ($peserta === null) {
            return null;
        }

        $mapping = LegacyPesertaMapping::where('peserta_id', $peserta->id)
            ->where('event_id', app(ActiveEventContext::class)->current()?->id)
            ->first();

        if ($mapping === null) {
            return null;
        }

        return $participations->contains(fn (Participation $participation) => (int) $participation->id === (int) $mapping->participation_id)
            ? $mapping->participation_id
            : null;
    }
}
