<?php

namespace App\Livewire\Event;

use App\Models\CompetitionClass;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\Event;
use App\Models\Participation;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\SesiAbsensi;
use App\Models\Venue;
use App\Services\Attendance\AttendanceReadService;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalPeserta;
    public $totalDesa;
    public $totalKelompok;
    public $totalRegu;
    public $regu_id = '';
    public $eventName;

    public function mount(Event $event)
    {
        abort_unless($event->isActive(), 404);

        abort_unless(
            app(EventAccessService::class)->canAccess(auth()->user(), $event),
            403
        );

        app(ActiveEventContext::class)->set($event);

        $this->eventName = $event->name;
        $this->totalPeserta = $event->participations()->count();
        $this->totalDesa = desa::count();
        $this->totalKelompok = kelompok::count();
        $this->totalRegu = regu::count();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        // Attendance data (CAI)
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

        // Competition data
        $classIds = collect();
        $overview = [];
        $todaySchedules = collect();
        $liveMatches = collect();
        $recentRegistrations = collect();
        $recentResults = collect();

        if ($event && $event->isCompetition()) {
            $classIds = CompetitionClass::where('event_id', $event->id)
                ->where('is_active', true)
                ->pluck('id');

            $venueIds = Venue::where('event_id', $event->id)->pluck('id');

            $schedules = CompetitionSchedule::whereIn('competition_class_id', $classIds);

            $overview = [
                'participants' => CompetitionRegistration::whereIn('competition_class_id', $classIds)->count(),
                'classes' => $classIds->count(),
                'venues' => $venueIds->count(),
                'today_matches' => (clone $schedules)->whereDate('start_at', Carbon::today())->count(),
                'running' => (clone $schedules)->where('status', 'Playing')->count(),
                'finished' => (clone $schedules)->where('status', 'Finished')->count(),
                'pending' => CompetitionRegistration::whereIn('competition_class_id', $classIds)
                    ->whereDoesntHave('scheduleEntries')
                    ->count(),
            ];

            $todaySchedules = CompetitionSchedule::with([
                'competitionClass.competitionCategory',
                'venue',
                'scheduleEntries.competitionRegistration.participation.person',
            ])
                ->whereIn('competition_class_id', $classIds)
                ->where('start_at', '>=', Carbon::now())
                ->whereDate('start_at', Carbon::today())
                ->orderBy('start_at')
                ->take(10)
                ->get();

            $liveMatches = CompetitionSchedule::with([
                'competitionClass.competitionCategory',
                'venue',
                'scheduleEntries.competitionRegistration.participation.person',
            ])
                ->withCount('scheduleEntries as pc')
                ->whereIn('competition_class_id', $classIds)
                ->whereIn('status', ['Playing', 'Waiting Result', 'Ready'])
                ->orderByRaw("CASE WHEN status = 'Playing' THEN 0 WHEN status = 'Waiting Result' THEN 1 ELSE 2 END")
                ->orderBy('sort_order')
                ->get()
                ->groupBy(fn($s) => $s->venue?->name ?? 'Tanpa Venue');

            $recentRegistrations = CompetitionRegistration::with([
                'participation.person',
                'competitionClass',
                'competitionCategory',
            ])
                ->whereIn('competition_class_id', $classIds)
                ->latest()
                ->take(10)
                ->get();

            $recentResults = CompetitionSchedule::with([
                'competitionClass',
                'winner.participation.person',
            ])
                ->whereIn('competition_class_id', $classIds)
                ->where('status', 'Finished')
                ->whereNotNull('finished_at')
                ->latest('finished_at')
                ->take(10)
                ->get();
        }

        return view('livewire.event.dashboard', [
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
            'eventName' => $this->eventName,
            'event' => $event,
            'overview' => $overview,
            'todaySchedules' => $todaySchedules,
            'liveMatches' => $liveMatches,
            'recentRegistrations' => $recentRegistrations,
            'recentResults' => $recentResults,
        ]);
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
