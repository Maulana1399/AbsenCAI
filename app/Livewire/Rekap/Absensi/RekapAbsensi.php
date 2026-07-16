<?php

namespace App\Livewire\Rekap\Absensi;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\peserta;
use App\Models\regu;
use App\Models\SesiAbsensi;
use Livewire\Component;

class RekapAbsensi extends Component
{
    public $sesi_id = '';
    public $regu_id = '';

    public function render()
    {
        $daftarSesi = SesiAbsensi::orderBy('tanggal', 'desc')->get();
        $daftarRegu = regu::orderBy('regu')->get();

        $totalPeserta = 0;
        $sudahAbsen = collect();
        $pesertaIzin = collect();
        $pesertaBelumAbsen = collect();
        $sudahAbsenCount = 0;
        $izinCount = 0;
        $belumAbsenCount = 0;
        $persentase = 0;

        if ($this->sesi_id) {
            $pesertaQuery = peserta::with(['regu', 'kelompok', 'desa']);
            if ($this->regu_id) {
                $pesertaQuery->where('regu_id', $this->regu_id);
            }

            $pesertaScope = $pesertaQuery->get();
            $participantIds = $pesertaScope->pluck('id');
            $participantNips = $pesertaScope->pluck('nip');

            $absensiQuery = Absensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa'])
                ->where('sesi_id', $this->sesi_id)
                ->whereIn('nip', $participantNips);

            $izinQuery = IzinAbsensi::with(['peserta.regu', 'peserta.kelompok', 'peserta.desa'])
                ->where('sesi_id', $this->sesi_id)
                ->whereIn('peserta_id', $participantIds);

            $sudahAbsen = $absensiQuery->orderBy('jam_scan', 'asc')->get();
            $pesertaIzin = $izinQuery->orderBy('created_at', 'asc')->get();

            $absenNips = $sudahAbsen->pluck('nip')->unique();
            $izinPesertaIds = $pesertaIzin->pluck('peserta_id')->unique();

            $totalPeserta = $pesertaScope->count();
            $sudahAbsenCount = $sudahAbsen->count();
            $izinCount = $pesertaIzin->count();
            $pesertaBelumAbsen = $pesertaScope->reject(function ($peserta) use ($absenNips, $izinPesertaIds) {
                return $absenNips->contains($peserta->nip) || $izinPesertaIds->contains($peserta->id);
            })->values();
            $belumAbsenCount = $pesertaBelumAbsen->count();
            $persentase = $totalPeserta > 0
                ? round(($sudahAbsenCount / $totalPeserta) * 100, 2)
                : 0;
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
}
