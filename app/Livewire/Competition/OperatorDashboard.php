<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionAnnouncement;
use App\Models\CompetitionClass;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class OperatorDashboard extends Component
{
    public string $announcementMessage = '';
    public bool $showAnnouncementForm = false;
    public bool $processing = false;

    public function advanceStatus(int $scheduleId): void
    {
        Gate::authorize('manage-events');

        $schedule = CompetitionSchedule::withCount('scheduleEntries as participants_count')->findOrFail($scheduleId);

        if ($schedule->start_at && Carbon::parse($schedule->start_at)->isFuture()) {
            $user = auth()->user();
            if (! $user->can('manage-events')) {
                session()->flash('error', 'Jadwal ini belum dimulai. Silakan tunggu waktu yang ditentukan.');
                return;
            }
        }

        if ($schedule->status === 'Scheduled' && !$schedule->canAutoReady()) {
            session()->flash('error', 'Tidak dapat mengubah ke Ready: peserta belum lengkap.');
            return;
        }

        if ($schedule->status === 'Ready' && !$schedule->isReadyForStart()) {
            session()->flash('error', 'Tidak dapat memulai pertandingan: peserta belum lengkap.');
            return;
        }

        $wasPlaying = $schedule->status === 'Playing';
        $venueId = $schedule->venue_id;

        $next = match ($schedule->status) {
            'Scheduled' => 'Ready',
            'Ready' => 'Playing',
            'Playing' => 'Finished',
            default => null,
        };

        if ($next) {
            $schedule->update(['status' => $next]);
        }

        if ($wasPlaying) {
            $this->promoteNextReady($venueId);
        }
    }

    private function promoteNextReady(?int $venueId): void
    {
        $event = app(ActiveEventContext::class)->current();
        $classIds = CompetitionClass::where('event_id', $event?->id)->pluck('id');

        $nextReady = CompetitionSchedule::withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', $classIds)
            ->where('status', 'Ready')
            ->where('venue_id', $venueId)
            ->orderBy('sort_order')
            ->orderBy('start_at')
            ->first();

        if ($nextReady) {
            $nextReady->update(['status' => 'Playing']);
        }
    }

    public function resetStatus(int $scheduleId): void
    {
        Gate::authorize('manage-events');
        $schedule = CompetitionSchedule::findOrFail($scheduleId);
        $schedule->update(['status' => 'Scheduled']);
    }

    public function toggleAnnouncementForm(): void
    {
        $this->showAnnouncementForm = !$this->showAnnouncementForm;
        $this->reset(['announcementMessage']);
        $this->resetErrorBag();
    }

    public function publishAnnouncement(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) return;
        $this->processing = true;

        try {
            $this->validate([
                'announcementMessage' => 'required|string|max:500',
            ]);

            $event = app(ActiveEventContext::class)->requireCurrent();

            CompetitionAnnouncement::where('event_id', $event->id)->update(['is_active' => false]);

            CompetitionAnnouncement::create([
                'event_id' => $event->id,
                'message' => $this->announcementMessage,
                'is_active' => true,
                'expires_at' => now()->addMinutes(5),
            ]);

            $this->showAnnouncementForm = false;
            $this->reset(['announcementMessage']);
            session()->flash('success', 'Pengumuman berhasil dipublikasikan.');
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $user = auth()->user();

        $schedules = CompetitionSchedule::with(['competitionClass.competitionCategory', 'venue', 'winner.participation.person', 'finishedBy'])
            ->whereIn('competition_class_id', CompetitionClass::where('event_id', $event?->id)->pluck('id'))
            ->withCount('scheduleEntries as participants_count')
            ->orderBy('sort_order')
            ->orderBy('start_at')
            ->get()
            ->map(function ($schedule) {
                $schedule->is_future = $schedule->start_at && Carbon::parse($schedule->start_at)->isFuture();
                $schedule->has_outcome = CompetitionOutcome::whereHas('competitionRegistration', function ($q) use ($schedule) {
                    $q->where('competition_class_id', $schedule->competition_class_id);
                })->exists();
                return $schedule;
            });

        $nowPlaying = $schedules->where('status', 'Playing');
        $ready = $schedules->where('status', 'Ready');
        $scheduled = $schedules->where('status', 'Scheduled')->sortBy('start_at');
        $finished = $schedules->where('status', 'Finished');

        $activeAnnouncement = CompetitionAnnouncement::active()
            ->where('event_id', $event?->id)
            ->latest()
            ->first();

        $viewerUrl = $event ? route('competition.viewer', ['event' => $event->id], true) : null;
        $tvUrl = $event ? route('competition.viewer', ['event' => $event->id, 'venue' => null, 'display' => 'tv'], true) : null;

        return view('livewire.competition.operator-dashboard', [
            'nowPlaying' => $nowPlaying,
            'ready' => $ready,
            'scheduled' => $scheduled,
            'finished' => $finished,
            'activeAnnouncement' => $activeAnnouncement,
            'viewerUrl' => $viewerUrl,
            'tvUrl' => $tvUrl,
            'canManage' => $user->can('manage-events'),
            'schedules' => $schedules,
        ]);
    }
}
