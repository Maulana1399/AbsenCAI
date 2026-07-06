<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\Absensi;
use App\Models\SesiAbsensi;

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
        $sesiAktif = SesiAbsensi::where('aktif', true)->first();
        $absensiQuery = Absensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa']);

        if ($sesiAktif) {
            $absensiQuery->where('sesi_id', $sesiAktif->id);
        } else {
            $absensiQuery->whereRaw('0 = 1');
        }

        if ($this->regu_id) {
            $absensiQuery->whereHas('peserta', function ($query) {
                $query->where('regu_id', $this->regu_id);
            });
        }

        $absensis = $absensiQuery->orderBy('jam_scan', 'asc')->get();
        $absenNips = $absensis->pluck('nip')->unique();

        $pesertaQuery = peserta::with(['regu', 'kelompok', 'desa']);
        if ($this->regu_id) {
            $pesertaQuery->where('regu_id', $this->regu_id);
        }

        $pesertaBelumAbsen = $sesiAktif
            ? $pesertaQuery->whereNotIn('nip', $absenNips)->get()
            : peserta::with(['regu', 'kelompok', 'desa'])->when($this->regu_id, function ($query) {
                $query->where('regu_id', $this->regu_id);
            })->get();

        $sudahAbsenCount = $absensis->count();
        $totalPesertaFiltered = $this->regu_id ? peserta::where('regu_id', $this->regu_id)->count() : $this->totalPeserta;
        $belumAbsenCount = $sesiAktif ? $pesertaBelumAbsen->count() : $totalPesertaFiltered;
        $persentaseKehadiran = $totalPesertaFiltered > 0
            ? round(($sudahAbsenCount / $totalPesertaFiltered) * 100, 2)
            : 0;

        return view('livewire.dashboard.dashboard', [
            'sesiAktif' => $sesiAktif,
            'absensis' => $absensis,
            'pesertaBelumAbsen' => $pesertaBelumAbsen,
            'sudahAbsenCount' => $sudahAbsenCount,
            'belumAbsenCount' => $belumAbsenCount,
            'persentaseKehadiran' => $persentaseKehadiran,
            'daftarRegu' => regu::all(),
            'selectedReguId' => $this->regu_id,
            'totalPesertaFiltered' => $totalPesertaFiltered,
            'daftarSesi' => SesiAbsensi::orderBy('tanggal', 'asc')->get(),
        ]);
    }

    public function activateSesi($id)
{
    SesiAbsensi::query()->update([
        'aktif' => false
    ]);

    SesiAbsensi::where('id', $id)->update([
        'aktif' => true
    ]);
}
}
