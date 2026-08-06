<?php

use App\Http\Controllers\ImportDataController;
use App\Http\Controllers\PublicEventController;
use App\Livewire\Audit\ActivityLogIndex;
use App\Livewire\Dashboard\PlatformDashboard;
use App\Livewire\Event\Dashboard as EventDashboard;
use App\Livewire\Event\Index as EventIndex;
use App\Livewire\MasterData\User\IndexUser;
use App\Livewire\QRLabel\Index as QRLabelIndex;
use App\Livewire\Registrasi\SelfRegister;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Models\Participation;
use App\Models\peserta;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicEventController::class, 'home'])->name('public.home');

Route::get('events/{event}', [PublicEventController::class, 'event'])->name('public.event');
Route::get('events/{event}/schedule', [PublicEventController::class, 'schedule'])->name('public.schedule');
Route::get('events/{event}/brackets/{bracket?}', [PublicEventController::class, 'bracket'])->name('public.bracket');
Route::get('events/{event}/announcements', [PublicEventController::class, 'announcements'])->name('public.announcements');

Route::get('dashboard', PlatformDashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('events/{event}/dashboard', EventDashboard::class)
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:view-dashboard'])
    ->name('events.dashboard');

Route::view('events/{event}/registrasi', 'registrasi.peserta')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-registration'])
    ->name('registrasi.peserta');

Route::get('events/{event}/registrasi/self', SelfRegister::class)
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-registration'])
    ->name('registrasi.self');

Route::get('events/{event}/registrasi/import-participation/template', [ImportDataController::class, 'participationTemplate'])
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-registration'])
    ->name('import.participation.template');

Route::view('events/{event}/registrasi/ulang', 'registrasi.ulang')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-registration'])
    ->name('registrasi.ulang');

Route::view('events/{event}/database', 'database.database')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-participants'])
    ->name('database');

Route::view('desa', 'database.desa')
    ->middleware(['auth', 'verified', 'can:view-master-data'])
    ->name('desa');

Route::view('kelompok', 'database.kelompok')
    ->middleware(['auth', 'verified', 'can:view-master-data'])
    ->name('kelompok');

Route::view('regu', 'database.regu')
    ->middleware(['auth', 'verified'])
    ->name('regu');

Route::view('events/{event}/sesi-absensi', 'database.sesi')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-sessions'])
    ->name('sesi.absensi');

Route::view('events/{event}/rekap-peserta', 'rekap.peserta')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:view-reports'])
    ->name('rekap.peserta');

Route::view('events/{event}/rekap-absensi', 'rekap.absensi')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:view-reports'])
    ->name('rekap.absensi');

Route::get('events/{event}/qr-label', QRLabelIndex::class)
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-qr-labels'])
    ->name('qr-label.index');

/*
|--------------------------------------------------------------------------
| QR Label — Single Print
|--------------------------------------------------------------------------
| Uses Participation ID directly.
| Legacy peserta mapping is optional.
*/

Route::get('events/{event}/qr-label/print/selected/{participant}', [PublicEventController::class, 'qrLabelPrintSelected'])
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-qr-labels'])
    ->name('qr-label.print.selected');

/*
|--------------------------------------------------------------------------
| QR Label — Print All Filtered
|--------------------------------------------------------------------------
| Uses Participation directly and scopes all records to current Event.
*/

Route::get('events/{event}/qr-label/print/filtered', [PublicEventController::class, 'qrLabelPrintFiltered'])
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-qr-labels'])
    ->name('qr-label.print.filtered');

/*
|--------------------------------------------------------------------------
| QR Label — Print A4
|--------------------------------------------------------------------------
| Uses Participation directly and scopes all records to current Event.
| Supports modern event participants without requiring legacy peserta.
*/

Route::get('events/{event}/qr-label/print/a4', [PublicEventController::class, 'qrLabelPrintA4'])
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-qr-labels'])
    ->name('qr-label.print.a4');

Route::view('events/{event}/absensi', 'dashboard.absensi')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-attendance'])
    ->name('absensi');

Route::view('events/{event}/surat-izin', 'surat-izin.index')
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-secretariat'])
    ->name('surat-izin');

Route::get('events/{event}/surat-izin/{surat}/print', [PublicEventController::class, 'suratIzinPrint'])
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:manage-secretariat'])
    ->name('surat-izin.print');

Route::get('events/{event}/activity-log', ActivityLogIndex::class)
    ->middleware(['auth', 'verified', 'resolve.active-event', 'can:view-activity-log'])
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
        ->middleware('can:manage-master-data')
        ->name('import.desa');

    Route::get('import/desa/template', [ImportDataController::class, 'desaTemplate'])
        ->middleware('can:manage-master-data')
        ->name('import.desa.template');

    Route::get('import/kelompok/template', [ImportDataController::class, 'kelompokTemplate'])
        ->middleware('can:manage-master-data')
        ->name('import.kelompok.template');

    Route::get('import/regu/template', [ImportDataController::class, 'reguTemplate'])
        ->middleware('can:manage-participants')
        ->name('import.regu.template');

    Route::get('import/person/template', [ImportDataController::class, 'personTemplate'])
        ->middleware('can:manage-master-data')
        ->name('import.person.template');

    Route::post('import/person', [ImportDataController::class, 'person'])
        ->middleware('can:manage-master-data')
        ->name('import.person');

    Route::post('import/kelompok', [ImportDataController::class, 'kelompok'])
        ->middleware('can:manage-master-data')
        ->name('import.kelompok');

    Route::post('import/regu', [ImportDataController::class, 'regu'])
        ->middleware('can:manage-participants')
        ->name('import.regu');

    Route::post('import/peserta', [ImportDataController::class, 'peserta'])
        ->middleware('can:manage-participants')
        ->name('import.peserta');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('master-data', 'master-data.index')
        ->middleware('can:view-master-data')
        ->name('master-data.index');

    Route::view('person', 'master-data.person.index')
        ->middleware('can:view-master-data')
        ->name('person.index');

    Route::get('correction-requests', App\Livewire\MasterData\CorrectionRequest\IndexCorrectionRequest::class)
        ->middleware('can:view-master-data')
        ->name('correction-requests.index');

    Route::get('users', IndexUser::class)
        ->middleware('can:manage-users')
        ->name('users.index');

    Route::get('events', EventIndex::class)
        ->middleware(['auth', 'verified', 'can:manage-events'])
        ->name('events.index');

    Route::get(
        'koreksi-data',
        App\Livewire\Pengajian\IdentityCorrectionReview::class
    )->middleware('can:manage-pengajian')->name('koreksi.data');

    Route::get(
        'events/{event}/pengajian/report',
        App\Livewire\Pengajian\RegionalReport::class
    )->middleware(['resolve.active-event', 'can:view-reports'])->name('pengajian.report');

    Route::get(
        'events/{event}/pengajian/admin/access',
        App\Livewire\Pengajian\Admin\AccessIndex::class
    )->middleware(['resolve.active-event', 'can:manage-pengajian'])->name('pengajian.admin.access');

    Route::get(
        'events/{event}/pengajian/admin/manual-entry',
        App\Livewire\Pengajian\Admin\ManualEntry::class
    )->middleware(['resolve.active-event', 'can:manage-pengajian'])->name('pengajian.admin.manual-entry');

    Route::get(
        'events/{event}/pengajian/admin/import-massal',
        App\Livewire\Pengajian\Admin\ImportMassal::class
    )->middleware(['resolve.active-event', 'can:manage-pengajian'])->name('pengajian.import-massal');

    Route::get(
        'events/{event}/pengajian/admin/import-massal/template',
        function () {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PersonImportTemplateExport,
                'template_import_person.xlsx',
            );
        }
    )->middleware(['resolve.active-event', 'can:manage-pengajian'])->name('pengajian.import-massal.template');
});

Route::prefix('events/{event}/pengajian')->middleware(['resolve.active-event'])->group(function () {
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
});

Route::get(
    'pengajian/hadir/{nonce}',
    App\Livewire\Pengajian\SelfAttendance::class
)->middleware('throttle:30,1')->name('pengajian.hadir');

Route::get(
    'events/{event}/competition',
    App\Livewire\Competition\Dashboard::class
)->middleware(['auth', 'verified', 'resolve.active-event', 'can:view-dashboard'])->name('competition.dashboard');

Route::prefix('events/{event}/competition')->middleware(['auth', 'verified', 'resolve.active-event'])->group(function () {
    Route::get(
        'registration',
        App\Livewire\Competition\Registration::class
    )->middleware('can:manage-registration')->name('competition.registration');

    Route::get(
        'categories',
        App\Livewire\Competition\Category\Index::class
    )->middleware('can:manage-events')->name('competition.category.index');

    Route::get(
        'classes',
        App\Livewire\Competition\Class\Index::class
    )->middleware('can:manage-events')->name('competition.class.index');

    Route::get(
        'venues',
        App\Livewire\Competition\Venue\Index::class
    )->middleware('can:manage-events')->name('competition.venue.index');

    Route::get(
        'participants',
        App\Livewire\Competition\ParticipantList::class
    )->middleware('can:view-dashboard')->name('competition.participants');

    Route::get(
        'schedules',
        App\Livewire\Competition\Schedule\Index::class
    )->middleware('can:manage-events')->name('competition.schedule.index');

    Route::get(
        'operator-dashboard',
        App\Livewire\Competition\OperatorDashboard::class
    )->middleware('can:manage-events')->name('competition.operator-dashboard');

    Route::get(
        'match-center',
        App\Livewire\Competition\MatchCenter::class
    )->middleware('can:manage-matches')->name('competition.match-center');

    Route::get(
        'official-panel',
        App\Livewire\Competition\OfficialPanel::class
    )->middleware('can:submit-result')->name('competition.official-panel');

    Route::get(
        'bracket-manager',
        App\Livewire\Competition\BracketManager::class
    )->middleware('can:manage-events')->name('competition.bracket-manager');

    Route::get(
        'schedules/{schedule}/outcomes',
        App\Livewire\Competition\Schedule\OutcomeManager::class
    )->middleware('can:manage-events')->name('competition.schedule.outcomes');

    Route::get(
        'schedules/{schedule}/entries',
        App\Livewire\Competition\Schedule\EntryManager::class
    )->middleware('can:manage-events')->name('competition.schedule.entries');

    Route::prefix('reports')->middleware('can:view-reports')->group(function () {
        Route::get('summary', App\Livewire\Competition\Report\Summary::class)->name('competition.report.summary');
        Route::get('registration', App\Livewire\Competition\Report\Registration::class)->name('competition.report.registration');
        Route::get('schedule', App\Livewire\Competition\Report\Schedule::class)->name('competition.report.schedule');
        Route::get('outcome', App\Livewire\Competition\Report\Outcome::class)->name('competition.report.outcome');
        Route::get('statistics', App\Livewire\Competition\Report\Statistics::class)->name('competition.report.statistics');
    });
});

Route::get(
    'events/{event}/competition/viewer/{venue?}',
    App\Livewire\Competition\Viewer::class
)->name('competition.viewer');

Route::middleware(['auth', 'verified'])->group(function () {
    $eventModulePath = function (string $module): string {
        $event = app(App\Support\ActiveEventContext::class)->current();

        abort_if($event === null, 404);

        return '/events/'.$event->getRouteKey().'/'.$module;
    };

    Route::get('absensi', fn () => redirect($eventModulePath('absensi')));
    Route::get('sesi-absensi', fn () => redirect($eventModulePath('sesi-absensi')));
    Route::get('database', fn () => redirect($eventModulePath('database')));
    Route::get('registrasi', fn () => redirect($eventModulePath('registrasi')));
    Route::get('registrasi/self', fn () => redirect($eventModulePath('registrasi/self')));
    Route::get('registrasi/ulang', fn () => redirect($eventModulePath('registrasi/ulang')));
    Route::get('rekap', fn () => redirect($eventModulePath('rekap-peserta')));
    Route::get('rekap-peserta', fn () => redirect($eventModulePath('rekap-peserta')));
    Route::get('rekap-absensi', fn () => redirect($eventModulePath('rekap-absensi')));
    Route::get('qr-label', fn () => redirect($eventModulePath('qr-label')));
    Route::get('qr-label/print/selected/{participant}', fn (string $participant) => redirect($eventModulePath('qr-label/print/selected/'.$participant)));
    Route::get('qr-label/print/filtered', fn () => redirect($eventModulePath('qr-label/print/filtered')));
    Route::get('qr-label/print/a4', fn () => redirect($eventModulePath('qr-label/print/a4')));
    Route::get('surat-izin', fn () => redirect($eventModulePath('surat-izin')));
    Route::get('surat-izin/{surat}/print', fn (string $surat) => redirect($eventModulePath('surat-izin/'.$surat.'/print')));
    Route::get('activity-log', fn () => redirect($eventModulePath('activity-log')));
});

require __DIR__.'/auth.php';
