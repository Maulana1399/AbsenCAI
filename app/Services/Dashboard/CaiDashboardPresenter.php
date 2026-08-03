<?php

namespace App\Services\Dashboard;

use App\Models\Event;
use App\Models\Participation;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceReadService;

class CaiDashboardPresenter implements DashboardPresenterContract
{
    public function __construct(
        private AttendanceReadService $attendanceReadService,
    ) {}

    public function present(Event $event): array
    {
        $totalPeserta = Participation::where('event_id', $event->id)->count();

        $sesiAktif = SesiAbsensi::where('event_id', $event->id)
            ->where('aktif', true)
            ->first();

        $sudahAbsenCount    = 0;
        $izinCount          = 0;
        $belumAbsenCount    = 0;
        $totalFiltered      = 0;
        $persentaseKehadiran = 0;
        $attendance         = collect();
        $pesertaBelumAbsen  = collect();

        if ($sesiAktif !== null) {
            $sessionData = $this->attendanceReadService
                ->getSessionAttendance($event->id, $sesiAktif->id, null);

            $attendance          = $sessionData['attendance'];
            $sudahAbsenCount     = $sessionData['hadir_count'];
            $izinCount           = $sessionData['izin_count'];
            $belumAbsenCount     = $sessionData['belum_count'];
            $totalFiltered       = $sessionData['total'];
            $persentaseKehadiran = $sessionData['persentase'];

            $pesertaBelumAbsen = $attendance
                ->filter(fn($entry) => $entry->status === 'belum')
                ->map(fn($entry) => $entry->participation)
                ->values();
        }

        $daftarSesi = SesiAbsensi::where('event_id', $event->id)
            ->orderBy('tanggal')
            ->get();

        return [
            'totalPeserta'       => $totalFiltered > 0 ? $totalFiltered : $totalPeserta,
            'sesiAktif'          => $sesiAktif,
            'sudahAbsenCount'    => $sudahAbsenCount,
            'izinCount'          => $izinCount,
            'belumAbsenCount'    => $belumAbsenCount,
            'persentaseKehadiran' => $persentaseKehadiran,
            'pesertaBelumAbsen'  => $pesertaBelumAbsen,
            'daftarSesi'         => $daftarSesi,
        ];
    }

    public function view(): string
    {
        return 'livewire.event.dashboard.cai';
    }
}
