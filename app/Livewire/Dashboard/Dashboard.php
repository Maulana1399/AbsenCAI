<?php

namespace App\Livewire\Dashboard;

use App\Models\Participation;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceReadService;
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

        $sudahAbsenCount = 0;
        $izinCount = 0;
        $belumAbsenCount = 0;
        $totalPesertaFiltered = 0;
        $persentaseKehadiran = 0;
        $attendance = collect();
        $pesertaBelumAbsen = collect();

        if ($event !== null && $sesiAktif !== null) {
            $readService = app(AttendanceReadService::class);
            $reguId = $this->regu_id ? (int) $this->regu_id : null;
            $sessionData = $readService->getSessionAttendance($event->id, $sesiAktif->id, $reguId);

            $attendance = $sessionData['attendance'];
            $sudahAbsenCount = $sessionData['hadir_count'];
            $izinCount = $sessionData['izin_count'];
            $belumAbsenCount = $sessionData['belum_count'];
            $totalPesertaFiltered = $sessionData['total'];
            $persentaseKehadiran = $sessionData['persentase'];
            $this->totalPeserta = $totalPesertaFiltered;

            $pesertaBelumAbsen = $attendance->filter(fn ($entry) => $entry->status === 'belum')
                ->map(fn ($entry) => $entry->participation)
                ->values();
        }

        return view('livewire.dashboard.dashboard', [
            'sesiAktif' => $sesiAktif,
            'attendance' => $attendance,
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
