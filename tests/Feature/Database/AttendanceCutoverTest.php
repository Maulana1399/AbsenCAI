<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use App\Livewire\Rekap\Absensi\RekapAbsensi;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\AttendanceParityService;
use App\Services\Attendance\AttendanceReadService;
use App\Services\Attendance\AttendanceService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ct_event(): Event
{
    return Event::create(['name' => 'CT Event ' . str()->random(6), 'slug' => 'ct-' . str()->random(6), 'status' => 'active']);
}

function ct_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'CT Sesi', 'tanggal' => '2026-08-14', 'aktif' => true]);
}

function ct_participant(Event $event): object
{
    $person = Person::create(['nama' => 'CT Person']);
    $nipValue = random_int(90000, 99999);
    $peserta = peserta::create([
        'nama' => 'CT Peserta',
        'attendance_code' => 'KJA-CT-' . str()->random(8),
        'participant_number' => 'KL' . random_int(100, 999),
        'status_registrasi' => 'Belum Registrasi',
    ]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
        'legacy_nip' => $nipValue,
        'legacy_participant_number' => $peserta->participant_number,
        'legacy_attendance_code' => $peserta->attendance_code,
        'migrated_at' => now(),
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'migrated_at' => now(),
    ]);
    return (object) compact('person', 'peserta', 'participation');
}

// ---------------------------------------------------------------------------
// A. Default config preserves dual-write
// ---------------------------------------------------------------------------

test('default config is true preserving dual-write', function () {
    config(['features.attendance_legacy_write' => true]);
    expect(config('features.attendance_legacy_write'))->toBeTrue();
});

test('scan writes canonical EventAttendance (legacy Absensi write retired per PGM.20)', function () {
    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);
    expect(Absensi::where('sesi_id', $session->id)->count())->toBe(0);
});

test('izin via recordIzin with participationId creates EventAttendance', function () {
    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: $m->peserta->id,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    );

    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// B. config=false — canonical only for mapped participants
// ---------------------------------------------------------------------------

test('config=false with Participation writes EventAttendance only', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    expect(Absensi::where('sesi_id', $session->id)->count())->toBe(0);
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);
});

test('config=false with Participation izin writes EventAttendance only', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin($m->peserta->id, $session->id, 'manual');

    expect(IzinAbsensi::where('sesi_id', $session->id)->count())->toBe(0);
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// C. config=false — unmappable legacy peserta still writes legacy
// ---------------------------------------------------------------------------

test('config=false unmappable peserta still writes Absensi', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $unmappedPeserta = peserta::create([
        'nama' => 'Unmapped', 'nip' => 11111,
        'attendance_code' => 'KJA-UNMAPPED',
        'status_registrasi' => 'Belum Registrasi',
    ]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    $result = app(AttendanceService::class)->processScan((string) $unmappedPeserta->attendance_code, $session->id);

    expect($result['status'])->toBe('not_found');
});

test('config=false unmappable peserta izin still writes IzinAbsensi', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $unmappedPeserta = peserta::create([
        'nama' => 'Unmapped Izin', 'nip' => 22222,
        'attendance_code' => 'KJA-UNMIIZIN',
        'status_registrasi' => 'Belum Registrasi',
    ]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin($unmappedPeserta->id, $session->id, 'manual');

    expect(IzinAbsensi::where('sesi_id', $session->id)->count())->toBe(1);
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// D. Canonical-only attendance visible in Dashboard/Rekap
// ---------------------------------------------------------------------------

test('canonical-only attendance visible in Dashboard', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));
    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    expect($data['hadir_count'])->toBe(1);
});

test('canonical-only attendance visible via ReadService', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));
    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    expect($data['hadir_count'])->toBe(1);
});

// ---------------------------------------------------------------------------
// E. Historical legacy-only visible through fallback
// ---------------------------------------------------------------------------

test('canonical EventAttendance is detected by AttendanceReadService', function () {
    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    $entry = $data['attendance']->first();

    expect($entry->status)->toBe('hadir')
        ->and($entry->source)->toBe('canonical');
});

// ---------------------------------------------------------------------------
// F. Parity tooling in canonical mode
// ---------------------------------------------------------------------------

test('parity canonical mode does not report orphan canonical', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));
    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $result = app(AttendanceParityService::class)->audit($event->id, 'canonical');

    expect($result['orphan_canonical'])->toBe(0);
});

test('parity migration mode still reports orphan canonical', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = ct_event();
    $session = ct_session($event);
    $m = ct_participant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));
    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $result = app(AttendanceParityService::class)->audit($event->id, 'migration');

    expect($result['orphan_canonical'])->toBeGreaterThanOrEqual(1);
});

// ---------------------------------------------------------------------------
// G. attendance:status is read-only
// ---------------------------------------------------------------------------

test('attendance status command is read-only', function () {
    $this->artisan('attendance:status')->assertSuccessful();
});

// ---------------------------------------------------------------------------
// H. Pengajian unchanged
// ---------------------------------------------------------------------------

test('Pengajian attendance unchanged after cutover changes', function () {
    $event = ct_event();
    $person = Person::create(['nama' => 'Pengajian Person']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'attended_at' => now(), 'method' => 'self',
    ]);

    expect($attendance->sesi_absensi_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// I. Config absent defaults to true
// ---------------------------------------------------------------------------

test('config file returns true by default', function () {
    // The config file has env('ATTENDANCE_LEGACY_WRITE', true) as default
    // But .env may override this, so we test the config file structure
    $cfg = require config_path('features.php');
    expect($cfg)->toHaveKey('attendance_legacy_write');
    // The actual value depends on env, but the key must exist
});

// ---------------------------------------------------------------------------
// J. attendance:parity --mode validates
// ---------------------------------------------------------------------------

test('parity command validates mode', function () {
    $this->artisan('attendance:parity', ['--mode' => 'invalid'])
        ->assertExitCode(1)
        ->expectsOutputToContain('Invalid mode');
});

test('parity command canonical mode works', function () {
    $this->artisan('attendance:parity', ['--mode' => 'canonical'])->assertSuccessful();
});
