<?php

namespace App\Livewire\Dashboard;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
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
        $this->totalPeserta = peserta::count();
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
        $pesertaQuery = peserta::with(['regu', 'kelompok', 'desa']);

        if ($this->regu_id) {
            $pesertaQuery->where('regu_id', $this->regu_id);
        }

        $pesertaScope = $pesertaQuery->get();
        $participantIds = $pesertaScope->pluck('id');
        $participantNips = $pesertaScope->pluck('nip');

        $absensiQuery = Absensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa']);
        $izinQuery = IzinAbsensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa']);

        if ($sesiAktif) {
            $absensiQuery->where('sesi_id', $sesiAktif->id)->whereIn('nip', $participantNips);
            $izinQuery->where('sesi_id', $sesiAktif->id)->whereIn('peserta_id', $participantIds);
        } else {
            $absensiQuery->whereRaw('0 = 1');
            $izinQuery->whereRaw('0 = 1');
        }

        $absensis = $absensiQuery->orderBy('jam_scan', 'asc')->get();
        $izinAbsensis = $izinQuery->orderBy('created_at', 'asc')->get();

        $absenNips = $absensis->pluck('nip')->unique();
        $izinPesertaIds = $izinAbsensis->pluck('peserta_id')->unique();

        $pesertaBelumAbsen = $sesiAktif
            ? $pesertaScope->reject(function ($peserta) use ($absenNips, $izinPesertaIds) {
                return $absenNips->contains($peserta->nip) || $izinPesertaIds->contains($peserta->id);
            })->values()
            : $pesertaScope;

        $sudahAbsenCount = $absensis->count();
        $izinCount = $izinAbsensis->count();
        $belumAbsenCount = $pesertaBelumAbsen->count();
        $totalPesertaFiltered = $pesertaScope->count();
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
    $event = app(ActiveEventContext::class)->requireCurrent();

    SesiAbsensi::where('event_id', $event->id)->update([
        'aktif' => false
    ]);

    SesiAbsensi::where('event_id', $event->id)->where('id', $id)->update([
        'aktif' => true
    ]);
}
}
