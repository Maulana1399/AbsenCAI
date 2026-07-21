<?php

namespace App\Livewire\Dashboard;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\Participation;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalPeserta;
    public $totalDesa;
    public $totalKelompok;
    public $totalRegu;
    public $regu_id = '';

    public function mount()
    {
        $event = app(ActiveEventContext::class)->current();
        $this->totalPeserta = $event ? Participation::where('event_id', $event->id)->count() : 0;
        $this->totalDesa = desa::count();
        $this->totalKelompok = kelompok::count();
        $this->totalRegu = regu::count();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $sesiAktif = $event
            ? SesiAbsensi::where('event_id', $event->id)->where('aktif', true)->first()
            : null;
        $participationQuery = Participation::with([
            'person.desa',
            'person.legacyPesertaMapping.peserta.regu',
            'person.legacyPesertaMapping.peserta.kelompok',
            'event',
        ]);

        if ($event !== null) {
            $participationQuery->where('event_id', $event->id);
        } else {
            $participationQuery->whereRaw('0 = 1');
        }

        if ($this->regu_id) {
            $participationQuery->whereHas('person.legacyPesertaMapping.peserta', fn ($builder) => $builder->where('regu_id', $this->regu_id));
        }

        $participations = $participationQuery->get();
        $participationPersonIds = $participations->pluck('person_id');
        $participantNips = $participations->map(fn (Participation $participation) => $participation->person?->nip)->filter();

        $absensiQuery = Absensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa']);
        $izinQuery = IzinAbsensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa']);

        if ($sesiAktif) {
            $absensiQuery->where('sesi_id', $sesiAktif->id)->whereIn('nip', $participantNips);
            $izinQuery->where('sesi_id', $sesiAktif->id)->whereIn('peserta_id', $participationPersonIds);
        } else {
            $absensiQuery->whereRaw('0 = 1');
            $izinQuery->whereRaw('0 = 1');
        }

        $absensis = $absensiQuery->orderBy('jam_scan', 'asc')->get();
        $izinAbsensis = $izinQuery->orderBy('created_at', 'asc')->get();

        $absenNips = $absensis->pluck('nip')->unique();
        $izinPesertaIds = $izinAbsensis->pluck('peserta_id')->unique();

        $pesertaBelumAbsen = $sesiAktif
            ? $participations->reject(function ($participation) use ($absenNips, $izinPesertaIds) {
                return $absenNips->contains($participation->person?->nip) || $izinPesertaIds->contains($participation->person_id);
            })->values()
            : $participations;

        $sudahAbsenCount = $absensis->count();
        $izinCount = $izinAbsensis->count();
        $belumAbsenCount = $pesertaBelumAbsen->count();
        $totalPesertaFiltered = $participations->count();
        $this->totalPeserta = $totalPesertaFiltered;
        $persentaseKehadiran = $totalPesertaFiltered > 0
            ? round(($sudahAbsenCount / $totalPesertaFiltered) * 100, 2)
            : 0;

        return view('livewire.dashboard.dashboard', [
            'sesiAktif' => $sesiAktif,
            'absensis' => $absensis,
            'izinAbsensis' => $izinAbsensis,
            'pesertaBelumAbsen' => $pesertaBelumAbsen,
            'sudahAbsenCount' => $sudahAbsenCount,
            'izinCount' => $izinCount,
            'belumAbsenCount' => $belumAbsenCount,
            'persentaseKehadiran' => $persentaseKehadiran,
            'daftarRegu' => regu::all(),
            'selectedReguId' => $this->regu_id,
            'totalPesertaFiltered' => $totalPesertaFiltered,
            'daftarSesi' => SesiAbsensi::orderBy('tanggal', 'asc')->get(),
        ]);
    }


    public function updatedReguId()
{
    logger('REGU FILTER: '.$this->regu_id);
}


    public function activateSesi($id)
{
    Gate::authorize('manage-sessions');

    $event = app(ActiveEventContext::class)->requireCurrent();

    SesiAbsensi::where('event_id', $event->id)->update([
        'aktif' => false
    ]);

    SesiAbsensi::where('event_id', $event->id)->where('id', $id)->update([
        'aktif' => true
    ]);
}
}
