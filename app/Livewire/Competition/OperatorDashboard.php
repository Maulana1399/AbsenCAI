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

        $schedule = CompetitionSchedule::findOrFail($scheduleId);

        if ($schedule->start_at && Carbon::parse($schedule->start_at)->isFuture()) {
            $user = auth()->user();
            if (! $user->can('manage-events')) {
                session()->flash('error', 'Jadwal ini belum dimulai. Silakan tunggu waktu yang ditentukan.');
                return;
            }
        }

        $next = match ($schedule->status) {
            'Scheduled' => 'Ready',
            'Ready' => 'NowPlaying',
            'NowPlaying' => 'Finished',
            default => null,
        };

        if ($next) {
            $schedule->update(['status' => $next]);
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

        $schedules = CompetitionSchedule::with(['competitionClass.competitionCategory', 'venue'])
            ->whereIn('competition_class_id', CompetitionClass::where('event_id', $event?->id)->pluck('id'))
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

        $nowPlaying = $schedules->where('status', 'NowPlaying');
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
