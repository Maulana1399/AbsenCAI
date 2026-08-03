<?php

namespace App\Http\Controllers;

use App\Models\CompetitionAnnouncement;
use App\Models\CompetitionBracket;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\Participation;
use App\Models\SuratIzin;
use App\Models\Venue;
use App\Services\Audit\ActivityLogService;
use App\Services\Print\PrintEngine;
use App\Services\QR\QRService;
use App\Support\ActiveEventContext;
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
            'pengajianEvent' => Event::active()->where('event_type', 'pengajian')->orderBy('start_date')->first(),
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

    public function qrLabelPrintSelected(Event $event, Participation $participant)
    {
        abort_if((int) $participant->event_id !== (int) $event->id, 404);

        $participant->load(['person', 'legacyParticipationMapping']);

        abort_if($participant->person === null, 404);
        abort_if(
            $participant->legacyParticipationMapping !== null
            && (int) $participant->legacyParticipationMapping->event_id !== (int) $event->id,
            404
        );

        $mapping = $participant->legacyParticipationMapping;

        app(ActivityLogService::class)->log(
            action: 'print_viewed',
            module: 'print',
            description: 'Membuka tampilan cetak label QR '.$participant->person->nama,
            subject: $participant->person,
            properties: [
                'print_type'      => 'qr_label_single',
                'peserta_id'      => $mapping?->peserta_id,
                'participant_id'  => $participant->id,
                'attendance_code' => $participant->attendance_code,
            ],
        );

        $html = app(PrintEngine::class)->label4x4($participant);

        $html = str_replace(
            '</body>',
            '<script>
                window.addEventListener("load", function () {
                    window.print();
                });
            </script></body>',
            $html
        );

        return response($html)->header('Content-Type', 'text/html');
    }

    public function qrLabelPrintFiltered(Request $request)
    {
        $event = app(ActiveEventContext::class)->current();

        abort_if($event === null, 404);

        $query = Participation::with([
            'person.desa',
            'legacyParticipationMapping.peserta',
        ])
            ->where('event_id', $event->id)
            ->whereNotNull('attendance_code');

        if ($request->filled('desa')) {
            $query->whereHas('person', fn ($q) =>
                $q->where('desa_id', $request->input('desa'))
            );
        }

        if ($request->filled('kelompok')) {
            $query->whereHas('legacyParticipationMapping.peserta', function ($q) use ($request) {
                $q->where('kelompok_id', $request->input('kelompok'));
            });
        }

        if ($request->filled('regu')) {
            $query->where('regu_id', $request->input('regu'));
        }

        if ($request->filled('gender')) {
            $query->whereHas('person', fn ($q) =>
                $q->where('jenis_kelamin', $request->input('gender'))
            );
        }

        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->whereHas('person', fn ($q) =>
                        $q->where('nama', 'like', '%'.$keyword.'%')
                    )
                    ->orWhere('participant_number', 'like', '%'.$keyword.'%')
                    ->orWhere('attendance_code', 'like', '%'.$keyword.'%');
            });
        }

        $participants = $query
            ->get()
            ->sortBy(fn ($participant) => $participant->person?->nama ?? '')
            ->values();

        abort_if($participants->isEmpty(), 404);

        app(ActivityLogService::class)->log(
            action: 'print_viewed',
            module: 'print',
            description: 'Membuka tampilan cetak batch label QR sebanyak '.$participants->count().' peserta',
            properties: [
                'print_type' => 'qr_label_filtered',
                'count'      => $participants->count(),
                'format'     => '4x4_single',
            ],
        );

        $qrService = app(QRService::class);

        $pages = $participants->map(function ($participant) use ($qrService) {
            $qrBase64 = base64_encode($qrService->generatePng((string) $participant->attendance_code));
            $participantNumber = htmlspecialchars((string) $participant->participant_number, ENT_QUOTES, 'UTF-8');
            $participantName = htmlspecialchars((string) $participant->person?->nama ?? '-', ENT_QUOTES, 'UTF-8');

            return <<<HTML
<div class="label-page">
    <div class="label">
        <div class="participant-number">{$participantNumber}</div>
        <div class="qr">
            <img src="data:image/png;base64,{$qrBase64}" alt="QR Code">
        </div>
        <div class="participant-name">{$participantName}</div>
    </div>
</div>
HTML;
        })->implode('');

        return response(
            '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <style>
                    @page {
                        size: 4cm 4cm;
                        margin: 0;
                    }

                    html, body {
                        margin: 0;
                        padding: 0;
                    }

                    .label-page {
                        width: 4cm;
                        height: 4cm;
                        page-break-after: always;
                        break-after: page;
                    }

                    .label {
                        width: 4cm;
                        height: 4cm;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                        gap: 2px;
                        padding: 2mm;
                        box-sizing: border-box;
                        font-family: Arial, sans-serif;
                    }

                    .participant-number {
                        font-size: 10pt;
                        font-weight: bold;
                    }

                    .participant-name {
                        font-size: 8pt;
                        line-height: 1.1;
                    }

                    .qr {
                        width: 1.8cm;
                        height: 1.8cm;
                    }

                    .qr img {
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                    }
                </style>

                <script>
                    window.addEventListener("load", function () {
                        window.print();
                    });
                </script>
            </head>
            <body>'
            .$pages.
            '</body>
            </html>'
        )->header('Content-Type', 'text/html');
    }

    public function qrLabelPrintA4(Request $request)
    {
        $event = app(ActiveEventContext::class)->current();

        abort_if($event === null, 404);

        $query = Participation::with([
            'person.desa',
            'legacyParticipationMapping.peserta',
        ])
            ->where('event_id', $event->id)
            ->whereNotNull('attendance_code');

        if ($request->filled('desa')) {
            $query->whereHas('person', fn ($q) =>
                $q->where('desa_id', $request->input('desa'))
            );
        }

        if ($request->filled('kelompok')) {
            $query->whereHas('legacyParticipationMapping.peserta', function ($q) use ($request) {
                $q->where('kelompok_id', $request->input('kelompok'));
            });
        }

        if ($request->filled('regu')) {
            $query->where('regu_id', $request->input('regu'));
        }

        if ($request->filled('gender')) {
            $query->whereHas('person', fn ($q) =>
                $q->where('jenis_kelamin', $request->input('gender'))
            );
        }

        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->whereHas('person', fn ($q) =>
                        $q->where('nama', 'like', '%'.$keyword.'%')
                    )
                    ->orWhere('participant_number', 'like', '%'.$keyword.'%')
                    ->orWhere('attendance_code', 'like', '%'.$keyword.'%');
            });
        }

        $participants = $query
            ->get()
            ->sortBy(fn ($participant) => $participant->person?->nama ?? '')
            ->values();

        abort_if($participants->isEmpty(), 404);

        app(ActivityLogService::class)->log(
            action: 'print_viewed',
            module: 'print',
            description: 'Membuka tampilan cetak label QR A4 sebanyak '.$participants->count().' peserta',
            properties: [
                'print_type' => 'qr_label_a4',
                'count'      => $participants->count(),
                'format'     => 'a4_grid',
                'event_id'   => $event->id,
            ],
        );

        $qrService = app(QRService::class);

        $pages = $participants
            ->chunk(35)
            ->map(function ($chunk) use ($qrService) {
                $labels = $chunk
                    ->map(function ($participant) use ($qrService) {
                        $qrBase64 = base64_encode($qrService->generatePng((string) $participant->attendance_code));
                        $participantNumber = htmlspecialchars((string) $participant->participant_number, ENT_QUOTES, 'UTF-8');
                        $participantName = htmlspecialchars((string) $participant->person?->nama ?? '-', ENT_QUOTES, 'UTF-8');

                        return <<<HTML
<div class="label">
    <div class="participant-number">{$participantNumber}</div>
    <div class="qr">
        <img src="data:image/png;base64,{$qrBase64}" alt="QR Code">
    </div>
    <div class="participant-name">{$participantName}</div>
</div>
HTML;
                    })
                    ->implode('');

                return '<div class="a4-page">'.$labels.'</div>';
            })
            ->implode('');

        return response(
            '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <style>
                    @page {
                        size: A4 portrait;
                        margin: 5mm;
                    }

                    html,
                    body {
                        margin: 0;
                        padding: 0;
                    }

                    body {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        font-family: Arial, sans-serif;
                    }

                    .a4-page {
                        width: 200mm;

                        display: grid;
                        grid-template-columns: repeat(5, 4cm);
                        grid-auto-rows: 4cm;

                        gap: 0;

                        justify-content: center;
                        align-content: start;

                        page-break-after: always;
                        break-after: page;
                    }

                    .a4-page:last-child {
                        page-break-after: auto;
                        break-after: auto;
                    }

                    .label {
                        width: 4cm;
                        height: 4cm;

                        box-sizing: border-box;

                        break-inside: avoid;
                        page-break-inside: avoid;

                        display: flex;
                        flex-direction: column;

                        align-items: center;
                        justify-content: center;

                        text-align: center;

                        gap: 2px;
                        padding: 2mm;
                    }

                    .participant-number {
                        font-size: 10pt;
                        font-weight: bold;
                    }

                    .participant-name {
                        font-size: 8pt;
                        line-height: 1.1;
                    }

                    .qr {
                        width: 1.8cm;
                        height: 1.8cm;
                    }

                    .qr img {
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                    }
                </style>

                <script>
                    window.addEventListener("load", () => {
                        window.print();
                    });
                </script>
            </head>

            <body>'
            .$pages.
            '</body>

            </html>'
        )->header('Content-Type', 'text/html');
    }

    public function suratIzinPrint(Event $event, SuratIzin $surat)
    {
        abort_if($surat->event_id !== null && (int) $surat->event_id !== (int) $event->id, 404);
        abort_if($surat->participation !== null && (int) $surat->participation->event_id !== (int) $event->id, 404);
        abort_if(! $surat->isApproved(), 403);

        app(ActivityLogService::class)->log(
            action: 'print_viewed',
            module: 'print',
            description: 'Membuka tampilan cetak surat izin '.$surat->nomor_surat,
            subject: $surat,
            properties: [
                'print_type'    => 'surat_izin',
                'peserta_id'    => $surat->peserta_id,
                'surat_izin_id' => $surat->id,
                'nomor_surat'   => $surat->nomor_surat,
            ],
        );

        return view('surat-izin.print', compact('surat'));
    }
}
