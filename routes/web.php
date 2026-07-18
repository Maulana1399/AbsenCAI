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

Route::get('qr-label/print/selected/{participant}', function (peserta $participant) {
    $mapping = $participant->legacyPesertaMapping()->with(['participation.person'])->first();

    abort_if($mapping === null || $mapping->participation === null, 404);

    $participation = $mapping->participation;

    app(ActivityLogService::class)->log(
        action: 'print_viewed',
        module: 'print',
        description: 'Membuka tampilan cetak label QR '.$participation->person->nama,
        subject: $participation->person,
        properties: [
            'print_type'      => 'qr_label_single',
            'peserta_id'      => $participant->id,
            'participant_id'  => $participation->id,
            'attendance_code' => $participation->attendance_code,
        ],
    );

    $html = app(PrintEngine::class)->label4x4($participation);

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

Route::get('qr-label/print/filtered', function () {
    $query = peserta::with(['desa', 'kelompok', 'regu'])->whereNotNull('attendance_code');

    request()->filled('desa') && $query->where('desa_id', request('desa'));
    request()->filled('kelompok') && $query->where('kelompok_id', request('kelompok'));
    request()->filled('regu') && $query->where('regu_id', request('regu'));
    request()->filled('gender') && $query->where('jenis_kelamin', request('gender'));

    $keyword = trim((string) request('keyword', ''));
    if ($keyword !== '') {
        $query->where(function ($builder) use ($keyword) {
            $builder->where('nama', 'like', '%'.$keyword.'%')
                ->orWhere('participant_number', 'like', '%'.$keyword.'%')
                ->orWhere('attendance_code', 'like', '%'.$keyword.'%');
        });
    }

    $participants = $query->orderBy('nama')->get();
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
        $participantName = htmlspecialchars((string) $participant->nama, ENT_QUOTES, 'UTF-8');
        return '<div class="label-page"><div class="label"><div class="participant-number">'.$participantNumber.'</div><div class="qr"><img src="data:image/png;base64,'.$qrBase64.'" alt="QR Code"></div><div class="participant-name">'.$participantName.'</div></div></div>';
    })->implode('');

    return response('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><style>@page { size: 4cm 4cm; margin: 0; } html, body { margin: 0; padding: 0; } .label-page { width: 4cm; height: 4cm; page-break-after: always; break-after: page; } .label { width: 4cm; height: 4cm; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 2px; padding: 2mm; box-sizing: border-box; font-family: Arial, sans-serif; } .participant-number { font-size: 10pt; font-weight: bold; } .participant-name { font-size: 8pt; line-height: 1.1; } .qr { width: 1.8cm; height: 1.8cm; } .qr img { width: 100%; height: 100%; object-fit: contain; }</style><script>window.addEventListener("load",()=>window.print());</script></head><body>'.$pages.'</body></html>')->header('Content-Type', 'text/html');
})->middleware(['auth', 'verified'])->name('qr-label.print.filtered');

Route::get('qr-label/print/a4', function () {
    $query = peserta::with(['desa', 'kelompok', 'regu'])->whereNotNull('attendance_code');

    request()->filled('desa') && $query->where('desa_id', request('desa'));
    request()->filled('kelompok') && $query->where('kelompok_id', request('kelompok'));
    request()->filled('regu') && $query->where('regu_id', request('regu'));
    request()->filled('gender') && $query->where('jenis_kelamin', request('gender'));

    $keyword = trim((string) request('keyword', ''));
    if ($keyword !== '') {
        $query->where(function ($builder) use ($keyword) {
            $builder->where('nama', 'like', '%'.$keyword.'%')
                ->orWhere('participant_number', 'like', '%'.$keyword.'%')
                ->orWhere('attendance_code', 'like', '%'.$keyword.'%');
        });
    }

    $participants = $query->orderBy('nama')->get();
    abort_if($participants->isEmpty(), 404);

    app(ActivityLogService::class)->log(
        action: 'print_viewed',
        module: 'print',
        description: 'Membuka tampilan cetak label QR A4 sebanyak '.$participants->count().' peserta',
        properties: [
            'print_type' => 'qr_label_a4',
            'count'      => $participants->count(),
            'format'     => 'a4_grid',
        ],
    );

    $qrService = app(QRService::class);
    $pages = $participants->chunk(35)->map(function ($chunk) use ($qrService) {
        $labels = $chunk->map(function ($participant) use ($qrService) {
            $qrBase64 = base64_encode($qrService->generatePng((string) $participant->attendance_code));
            $participantNumber = htmlspecialchars((string) $participant->participant_number, ENT_QUOTES, 'UTF-8');
            $participantName = htmlspecialchars((string) $participant->nama, ENT_QUOTES, 'UTF-8');

            return '<div class="label"><div class="participant-number">'.$participantNumber.'</div><div class="qr"><img src="data:image/png;base64,'.$qrBase64.'" alt="QR Code"></div><div class="participant-name">'.$participantName.'</div></div>';
        })->implode('');

        return '<div class="a4-page">'.$labels.'</div>';
    })->implode('');

    return response('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><style>@page { size: A4 portrait; margin: 5mm; } html, body { margin: 0; padding: 0; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: Arial, sans-serif; } .a4-page { width: 200mm; display: grid; grid-template-columns: repeat(5, 4cm); grid-auto-rows: 4cm; gap: 0; justify-content: center; align-content: start; page-break-after: always; break-after: page; } .a4-page:last-child { page-break-after: auto; break-after: auto; } .label { width: 4cm; height: 4cm; box-sizing: border-box; break-inside: avoid; page-break-inside: avoid; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 2px; padding: 2mm; } .participant-number { font-size: 10pt; font-weight: bold; } .participant-name { font-size: 8pt; line-height: 1.1; } .qr { width: 1.8cm; height: 1.8cm; } .qr img { width: 100%; height: 100%; object-fit: contain; }</style><script>window.addEventListener("load",()=>window.print());</script></head><body>'.$pages.'</body></html>')->header('Content-Type', 'text/html');
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

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('import/desa', [ImportDataController::class, 'desa'])->name('import.desa');
    Route::post('import/kelompok', [ImportDataController::class, 'kelompok'])->name('import.kelompok');
    Route::post('import/regu', [ImportDataController::class, 'regu'])->name('import.regu');
    Route::post('import/peserta', [ImportDataController::class, 'peserta'])->name('import.peserta');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('events', EventIndex::class)->name('events.index');
});

require __DIR__.'/auth.php';
