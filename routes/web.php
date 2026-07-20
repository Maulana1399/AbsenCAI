<?php

use App\Livewire\Audit\ActivityLogIndex;
use App\Livewire\Event\Index as EventIndex;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\QRLabel\Index as QRLabelIndex;
use App\Livewire\Registrasi\SelfRegister;
use App\Http\Controllers\ImportDataController;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\peserta;
use App\Models\SuratIzin;
use App\Services\Audit\ActivityLogService;
use App\Services\Print\PrintEngine;
use App\Services\QR\QRService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('registrasi', 'registrasi.peserta')
    ->middleware(['auth', 'verified'])
    ->name('registrasi.peserta');

Route::get('registrasi/self', SelfRegister::class)
    ->middleware(['auth', 'verified'])
    ->name('registrasi.self');

Route::view('registrasi/ulang', 'registrasi.ulang')
    ->middleware(['auth', 'verified'])
    ->name('registrasi.ulang');

Route::view('database', 'database.database')
    ->middleware(['auth', 'verified'])
    ->name('database');

Route::view('desa', 'database.desa')
    ->middleware(['auth', 'verified'])
    ->name('desa');

Route::view('kelompok', 'database.kelompok')
    ->middleware(['auth', 'verified'])
    ->name('kelompok');

Route::view('regu', 'database.regu')
    ->middleware(['auth', 'verified'])
    ->name('regu');

Route::view('sesi-absensi', 'database.sesi')
    ->middleware(['auth', 'verified'])
    ->name('sesi.absensi');

Route::view('rekap-peserta', 'rekap.peserta')
    ->middleware(['auth', 'verified'])
    ->name('rekap.peserta');

Route::view('rekap-absensi', 'rekap.absensi')
    ->middleware(['auth', 'verified'])
    ->name('rekap.absensi');

Route::get('qr-label', QRLabelIndex::class)
    ->middleware(['auth', 'verified'])
    ->name('qr-label.index');

/*
|--------------------------------------------------------------------------
| QR Label — Single Print
|--------------------------------------------------------------------------
| Uses Participation ID directly.
| Legacy peserta mapping is optional.
*/

Route::get('qr-label/print/selected/{participant}', function (Participation $participant) {
    $event = app(App\Support\ActiveEventContext::class)->current();

    abort_if($event === null, 404);
    abort_if((int) $participant->event_id !== (int) $event->id, 404);

    $participant->load('person');

    abort_if($participant->person === null, 404);

    $mapping = $participant->legacyPesertaMapping()->first();

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
})->middleware(['auth', 'verified'])->name('qr-label.print.selected');

/*
|--------------------------------------------------------------------------
| QR Label — Print All Filtered
|--------------------------------------------------------------------------
| Uses Participation directly and scopes all records to current Event.
*/

Route::get('qr-label/print/filtered', function () {
    $event = app(App\Support\ActiveEventContext::class)->current();

    abort_if($event === null, 404);

    $query = Participation::with([
        'person.desa',
        'legacyPesertaMapping.peserta',
    ])
        ->where('event_id', $event->id)
        ->whereNotNull('attendance_code');

    if (request()->filled('desa')) {
        $query->whereHas('person', fn ($q) =>
            $q->where('desa_id', request('desa'))
        );
    }

    if (request()->filled('kelompok') || request()->filled('regu')) {
        $query->whereHas('legacyPesertaMapping.peserta', function ($q) {
            if (request()->filled('kelompok')) {
                $q->where('kelompok_id', request('kelompok'));
            }

            if (request()->filled('regu')) {
                $q->where('regu_id', request('regu'));
            }
        });
    }

    if (request()->filled('gender')) {
        $query->whereHas('person', fn ($q) =>
            $q->where('jenis_kelamin', request('gender'))
        );
    }

    $keyword = trim((string) request('keyword', ''));

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
        $qrBase64 = base64_encode(
            $qrService->generatePng((string) $participant->attendance_code)
        );

        $participantNumber = htmlspecialchars(
            (string) $participant->participant_number,
            ENT_QUOTES,
            'UTF-8'
        );

        $participantName = htmlspecialchars(
            (string) ($participant->person?->nama ?? '-'),
            ENT_QUOTES,
            'UTF-8'
        );

        return '<div class="label-page">'
            .'<div class="label">'
            .'<div class="participant-number">'.$participantNumber.'</div>'
            .'<div class="qr">'
            .'<img src="data:image/png;base64,'.$qrBase64.'" alt="QR Code">'
            .'</div>'
            .'<div class="participant-name">'.$participantName.'</div>'
            .'</div>'
            .'</div>';
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
                window.addEventListener("load", () => window.print());
            </script>
        </head>
        <body>'
        .$pages.
        '</body>
        </html>'
    )->header('Content-Type', 'text/html');
})->middleware(['auth', 'verified'])->name('qr-label.print.filtered');

/*
|--------------------------------------------------------------------------
| QR Label — Print A4
|--------------------------------------------------------------------------
| Uses Participation directly and scopes all records to current Event.
| Supports modern event participants without requiring legacy peserta.
*/

Route::get('qr-label/print/a4', function () {
    $event = app(App\Support\ActiveEventContext::class)->current();

    abort_if($event === null, 404);

    $query = Participation::with([
        'person.desa',
        'legacyPesertaMapping.peserta',
    ])
        ->where('event_id', $event->id)
        ->whereNotNull('attendance_code');

    /*
    |--------------------------------------------------------------------------
    | Filter Desa
    |--------------------------------------------------------------------------
    | Desa belongs to Person in the modern architecture.
    */

    if (request()->filled('desa')) {
        $query->whereHas('person', fn ($q) =>
            $q->where('desa_id', request('desa'))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Kelompok / Regu
    |--------------------------------------------------------------------------
    | Kelompok and Regu are still legacy data.
    | Only participants with a matching legacy mapping will match these filters.
    */

    if (request()->filled('kelompok') || request()->filled('regu')) {
        $query->whereHas('legacyPesertaMapping.peserta', function ($q) {
            if (request()->filled('kelompok')) {
                $q->where('kelompok_id', request('kelompok'));
            }

            if (request()->filled('regu')) {
                $q->where('regu_id', request('regu'));
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Gender
    |--------------------------------------------------------------------------
    | Gender belongs to Person.
    */

    if (request()->filled('gender')) {
        $query->whereHas('person', fn ($q) =>
            $q->where('jenis_kelamin', request('gender'))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Keyword Search
    |--------------------------------------------------------------------------
    | Search modern participant identity:
    | - Person name
    | - Participant number
    | - Attendance code
    */

    $keyword = trim((string) request('keyword', ''));

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

    /*
    |--------------------------------------------------------------------------
    | Get Event-Scoped Participants
    |--------------------------------------------------------------------------
    */

    $participants = $query
        ->get()
        ->sortBy(fn ($participant) => $participant->person?->nama ?? '')
        ->values();

    abort_if($participants->isEmpty(), 404);

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Generate Labels
    |--------------------------------------------------------------------------
    | A4 layout:
    | 5 columns × 7 rows
    | 35 labels per page
    | Each label = 4cm × 4cm
    */

    $qrService = app(QRService::class);

    $pages = $participants
        ->chunk(35)
        ->map(function ($chunk) use ($qrService) {
            $labels = $chunk
                ->map(function ($participant) use ($qrService) {
                    $qrBase64 = base64_encode(
                        $qrService->generatePng(
                            (string) $participant->attendance_code
                        )
                    );

                    $participantNumber = htmlspecialchars(
                        (string) $participant->participant_number,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $participantName = htmlspecialchars(
                        (string) ($participant->person?->nama ?? '-'),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    return '<div class="label">'
                        .'<div class="participant-number">'.$participantNumber.'</div>'
                        .'<div class="qr">'
                        .'<img src="data:image/png;base64,'.$qrBase64.'" alt="QR Code">'
                        .'</div>'
                        .'<div class="participant-name">'.$participantName.'</div>'
                        .'</div>';
                })
                ->implode('');

            return '<div class="a4-page">'.$labels.'</div>';
        })
        ->implode('');

    /*
    |--------------------------------------------------------------------------
    | Print Response
    |--------------------------------------------------------------------------
    */

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
})->middleware(['auth', 'verified'])->name('qr-label.print.a4');

Route::view('absensi', 'dashboard.absensi')
    ->middleware(['auth', 'verified'])
    ->name('absensi');

Route::view('surat-izin', 'surat-izin.index')
    ->middleware(['auth', 'verified'])
    ->name('surat-izin');

Route::get('surat-izin/{surat}/print', function (SuratIzin $surat) {
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
})->middleware(['auth', 'verified'])->name('surat-izin.print');

Route::get('activity-log', ActivityLogIndex::class)
    ->middleware(['auth', 'verified'])
    ->name('activity-log.index');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)
        ->name('settings.profile');

    Route::get('settings/password', Password::class)
        ->name('settings.password');

    Route::get('settings/appearance', Appearance::class)
        ->name('settings.appearance');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('import/desa', [ImportDataController::class, 'desa'])
        ->name('import.desa');

    Route::post('import/kelompok', [ImportDataController::class, 'kelompok'])
        ->name('import.kelompok');

    Route::post('import/regu', [ImportDataController::class, 'regu'])
        ->name('import.regu');

    Route::post('import/peserta', [ImportDataController::class, 'peserta'])
        ->name('import.peserta');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('events', EventIndex::class)
        ->name('events.index');

    Route::get(
        'koreksi-data',
        App\Livewire\Pengajian\IdentityCorrectionReview::class
    )->name('koreksi.data');

    Route::get(
        'pengajian/report',
        App\Livewire\Pengajian\RegionalReport::class
    )->name('pengajian.report');

    Route::get(
        'pengajian/admin/access',
        App\Livewire\Pengajian\Admin\AccessIndex::class
    )->name('pengajian.admin.access');

    Route::get(
        'pengajian/admin/manual-entry',
        App\Livewire\Pengajian\Admin\ManualEntry::class
    )->name('pengajian.admin.manual-entry');

    Route::get(
        'pengajian/admin/import-massal',
        App\Livewire\Pengajian\Admin\ImportMassal::class
    )->name('pengajian.import-massal');
});

Route::prefix('pengajian')->group(function () {
    Route::get(
        '/',
        App\Livewire\Pengajian\EnterToken::class
    )->name('pengajian.enter-token');

    Route::get(
        'desa',
        App\Livewire\Pengajian\DesaDashboard::class
    )->name('pengajian.desa');

    Route::get(
        'desa/tambah',
        App\Livewire\Pengajian\ManualEntry::class
    )->name('pengajian.desa.tambah');

    Route::get(
        'desa/qr/print',
        App\Livewire\Pengajian\QrPrint::class
    )->name('pengajian.qr-print');

    Route::get(
        'hadir/{nonce}',
        App\Livewire\Pengajian\SelfAttendance::class
    )
        ->middleware('throttle:30,1')
        ->name('pengajian.hadir');
});

require __DIR__.'/auth.php';