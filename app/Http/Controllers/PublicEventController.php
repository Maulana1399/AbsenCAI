<?php

namespace App\Http\Controllers;

use App\Models\CompetitionAnnouncement;
use App\Models\CompetitionBracket;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    public function home()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $upcoming = Event::where('status', 'active')
            ->where('start_date', '>', now())
            ->orWhereNull('start_date')
            ->orderBy('start_date')
            ->take(6)
            ->get();

        $running = Event::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->take(6)
            ->get();

        $finished = Event::whereIn('status', ['archived', 'finished'])
            ->orWhere('end_date', '<', now())
            ->orderBy('end_date', 'desc')
            ->take(6)
            ->get();

        return view('public.home', [
            'upcoming' => $upcoming,
            'running' => $running,
            'finished' => $finished,
            'metaTitle' => config('app.name') . ' – Portal Informasi Event',
            'metaDescription' => 'Lihat jadwal, bracket, dan hasil pertandingan event KJA terbaru.',
        ]);
    }

    public function event(Event $event)
    {
        abort_unless($event->isActive(), 404);

        $classIds = CompetitionClass::where('event_id', $event->id)->pluck('id');

        $scheduleCount = CompetitionSchedule::whereIn('competition_class_id', $classIds)->count();
        $bracketCount = CompetitionBracket::whereIn('competition_class_id', $classIds)->count();
        $announcementCount = CompetitionAnnouncement::active()->where('event_id', $event->id)->count();

        return view('public.event', [
            'event' => $event,
            'scheduleCount' => $scheduleCount,
            'bracketCount' => $bracketCount,
            'announcementCount' => $announcementCount,
            'venues' => Venue::where('event_id', $event->id)->orderBy('sort_order')->orderBy('name')->get(),
            'metaTitle' => $event->name . ' – ' . config('app.name'),
            'metaDescription' => 'Informasi event ' . $event->name . ' – jadwal, bracket, dan hasil pertandingan.',
        ]);
    }

    public function schedule(Event $event)
    {
        abort_unless($event->isActive(), 404);

        $classIds = CompetitionClass::where('event_id', $event->id)->pluck('id');

        $venues = Venue::where('event_id', $event->id)->orderBy('sort_order')->orderBy('name')->get();
        $classes = CompetitionClass::where('event_id', $event->id)
            ->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        $venueId = request('venue_id');
        $classId = request('class_id');
        $status = request('status');

        $query = CompetitionSchedule::with([
            'competitionClass.competitionCategory',
            'venue',
            'scheduleEntries.competitionRegistration.participation.person',
        ])
            ->withCount('scheduleEntries as participants_count')
            ->whereIn('competition_class_id', $classIds);

        if ($venueId) $query->where('venue_id', $venueId);
        if ($classId) $query->where('competition_class_id', $classId);
        if ($status) $query->where('status', $status);

        $schedules = $query->orderBy('sort_order')->orderBy('start_at')->paginate(20);

        return view('public.schedule', [
            'event' => $event,
            'schedules' => $schedules,
            'venues' => $venues,
            'classes' => $classes,
            'metaTitle' => 'Jadwal – ' . $event->name . ' – ' . config('app.name'),
            'metaDescription' => 'Jadwal pertandingan ' . $event->name,
        ]);
    }

    public function bracket(Event $event, CompetitionBracket $bracket = null)
    {
        abort_unless($event->isActive(), 404);

        $classIds = CompetitionClass::where('event_id', $event->id)->pluck('id');

        $brackets = CompetitionBracket::with('competitionClass')
            ->whereIn('competition_class_id', $classIds)
            ->get();

        if (!$bracket && $brackets->isNotEmpty()) {
            $bracket = $brackets->first();
        }

        $bracketRounds = [];

        if ($bracket) {
            $bracket->load([
                'bracketMatches.schedule.scheduleEntries.competitionRegistration.participation.person',
                'bracketMatches.schedule.winner.participation.person',
                'bracketMatches.sourceMatchA',
                'bracketMatches.sourceMatchB',
            ]);

            $totalRounds = (int) log($bracket->participant_count, 2);
            $matches = $bracket->bracketMatches->groupBy('round')->sortKeysDesc();

            foreach ($matches as $round => $roundMatches) {
                $label = match ($round) {
                    1 => 'Final',
                    2 => 'Semi Final',
                    3 => 'Quarter Final',
                    default => 'Round ' . ($totalRounds - $round + 1),
                };
                $bracketRounds[] = [
                    'label' => $label,
                    'round' => $round,
                    'matches' => $roundMatches->sortBy('position')->values(),
                ];
            }
        }

        return view('public.bracket', [
            'event' => $event,
            'brackets' => $brackets,
            'selectedBracket' => $bracket,
            'bracketRounds' => $bracketRounds,
            'metaTitle' => 'Bracket – ' . $event->name . ' – ' . config('app.name'),
            'metaDescription' => 'Bracket pertandingan ' . $event->name,
        ]);
    }

    public function announcements(Event $event)
    {
        abort_unless($event->isActive(), 404);

        $announcements = CompetitionAnnouncement::where('event_id', $event->id)
            ->latest()
            ->paginate(20);

        return view('public.announcements', [
            'event' => $event,
            'announcements' => $announcements,
            'metaTitle' => 'Pengumuman – ' . $event->name . ' – ' . config('app.name'),
            'metaDescription' => 'Pengumuman event ' . $event->name,
        ]);
    }
}
