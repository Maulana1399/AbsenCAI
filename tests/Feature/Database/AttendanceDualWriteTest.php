<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function dw_event(): Event
{
    return Event::create([
        'name' => 'DW Event '.str()->random(6),
        'slug' => 'dw-'.str()->random(6),
        'status' => 'active',
    ]);
}

function dw_person(): Person
{
    return Person::create(['nama' => 'DW Person '.str()->random(6)]);
}

function dw_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi '.str()->random(6),
        'tanggal' => '2026-08-06',
        'aktif' => true,
    ]);
}

function dw_peserta(array $overrides = []): peserta
{
    return peserta::create(array_merge([
        'nama' => 'Legacy '.str()->random(6),
        'attendance_code' => 'KJA-'.str()->random(8),
        'participant_number' => 'KL'.random_int(100, 999),
        'status_registrasi' => 'Belum Registrasi',
    ], $overrides));
}

function dw_mapping(peserta $p, Person $person, Participation $participation, Event $event): LegacyPesertaMapping
{
    LegacyParticipationMapping::create([
        'peserta_id' => $p->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'migrated_at' => now(),
    ]);

    $nipValue = $person->id;

    return LegacyPesertaMapping::create([
        'peserta_id' => $p->id,
        'person_id' => $person->id,
        'legacy_nip' => $nipValue,
        'legacy_participant_number' => $p->participant_number,
        'legacy_attendance_code' => $p->attendance_code,
        'migrated_at' => now(),
    ]);
}

function dw_mappedParticipant(Event $event): object
{
    $person = dw_person();
    $peserta = dw_peserta();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
    ]);
    dw_mapping($peserta, $person, $participation, $event);

    return (object) compact('person', 'peserta', 'participation');
}

function dw_admin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

// ---------------------------------------------------------------------------
// A. Scan attendance — mapped participant
// ---------------------------------------------------------------------------

test('scan creates EventAttendance for mapped participant', function () {
    config(['features.attendance_legacy_write' => true]);

    $event = dw_event();
    $session = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);
});

test('scan EventAttendance has correct fields', function () {
    $event = dw_event();
    $session = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $result = app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);
    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();

    expect($ea)->not->toBeNull()
        ->and($ea->participation_id)->toBe($m->participation->id)
        ->and($ea->event_id)->toBe($event->id)
        ->and($ea->sesi_absensi_id)->toBe($session->id)
        ->and($ea->status)->toBe(EventAttendance::STATUS_HADIR)
        ->and($ea->method)->toBe('scan')
        ->and($ea->recorded_by)->toBe($user->id)
        ->and($ea->attended_at)->not->toBeNull();
});

test('scan records attended_at timestamp', function () {
    config(['features.attendance_legacy_write' => true]);

    $event = dw_event();
    $session = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();

    expect($ea->attended_at)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// B. Legacy-only fallback (no mapping)
// ---------------------------------------------------------------------------

test('scan without mapping creates legacy only', function () {
    $event = dw_event();
    $session = dw_session($event);
    $peserta = dw_peserta();
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $result = app(AttendanceService::class)->processScan((string) $peserta->attendance_code, $session->id);

    expect($result['status'])->toBe('not_found');
    expect(Absensi::where('sesi_id', $session->id)->count())->toBe(0);
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// C. Duplicate protection
// ---------------------------------------------------------------------------

test('legacy duplicate scan is still rejected', function () {
    config(['features.attendance_legacy_write' => true]);

    $event = dw_event();
    $session = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);
    $result = app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    expect($result['status'])->toBe('duplicate');
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);
});

test('same participation different sessions both succeed', function () {
    config(['features.attendance_legacy_write' => true]);

    $event = dw_event();
    $sessionA = dw_session($event);
    $sessionB = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $sessionA->id);
    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $sessionB->id);

    expect(EventAttendance::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// D. Atomicity — canonical failure rolls back legacy
// ---------------------------------------------------------------------------

test('EventAttendance creation failure rolls back Absensi', function () {
    $event = dw_event();
    $session = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    EventAttendance::create([
        'participation_id' => $m->participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    $result = app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);
    expect($result['status'])->toBe('duplicate');
    expect(Absensi::where('sesi_id', $session->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// E. Manual izin dual-write
// ---------------------------------------------------------------------------

test('manual izin creates IzinAbsensi and EventAttendance', function () {
    config(['features.attendance_legacy_write' => true]);

    $event = dw_event();
    $session = dw_session($event);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(AttendanceExceptionService::class)->recordIzin($m->peserta->id, $session->id, 'manual');

    expect(IzinAbsensi::where('sesi_id', $session->id)->count())->toBe(0);
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(1);

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();
    expect($ea->status)->toBe(EventAttendance::STATUS_IZIN)
        ->and($ea->method)->toBe('izin')
        ->and($ea->participation_id)->toBe($m->participation->id);
});

test('manual izin without mapping creates legacy only', function () {
    $event = dw_event();
    $session = dw_session($event);
    $unmappedPeserta = dw_peserta();
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(AttendanceExceptionService::class)->recordIzin($unmappedPeserta->id, $session->id, 'manual');

    expect(IzinAbsensi::where('sesi_id', $session->id)->count())->toBe(1);
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// F. Surat izin dual-write (via recordIzin)
// ---------------------------------------------------------------------------

test('surat izin approval creates EventAttendance with method surat_izin', function () {
    $event = dw_event();
    $session = dw_session($event, ['tanggal' => '2026-08-06']);
    $m = dw_mappedParticipant($event);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $service = app(SuratIzinService::class);
    $surat = $service->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan untuk surat izin approval',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-06',
        'tanggal_selesai' => '2026-08-06',
    ], $user->id);

    $service->submit($surat);
    $result = $service->approve($surat->fresh(), $user);

    expect($result['created'])->toHaveCount(1);

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();
    expect($ea)->not->toBeNull()
        ->and($ea->status)->toBe(EventAttendance::STATUS_IZIN)
        ->and($ea->method)->toBe('surat_izin')
        ->and($ea->participation_id)->toBe($m->participation->id);
});

// ---------------------------------------------------------------------------
// G. Cross-event isolation
// ---------------------------------------------------------------------------

test('Event A participation never used for Event B attendance', function () {
    $eventA = dw_event();
    $eventB = dw_event();
    $sessionB = dw_session($eventB);
    $m = dw_mappedParticipant($eventA);
    $user = dw_admin();

    app(ActiveEventContext::class)->set($eventB);
    $this->actingAs($user);

    $result = app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $sessionB->id);

    expect($result['status'])->toBe('not_found');
    expect(EventAttendance::where('event_id', $eventB->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// H. Pengajian regression
// ---------------------------------------------------------------------------

test('Pengajian self attendance unchanged', function () {
    $event = dw_event();
    $person = dw_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'self',
    ]);

    expect($attendance->sesi_absensi_id)->toBeNull();
    expect($attendance->method)->toBe('self');
});

test('Pengajian operator attendance unchanged', function () {
    $event = dw_event();
    $person = dw_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'operator',
    ]);

    expect($attendance->method)->toBe('operator');
});

test('Pengajian desa_id preserved', function () {
    $event = dw_event();
    $desa = \App\Models\desa::create(['desa_asal' => 'Test Desa']);
    $person = dw_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'attended_at' => now(),
        'method' => 'self',
    ]);

    expect($attendance->desa_id)->toBe($desa->id);
});

test('Pengajian duplicate blocked', function () {
    $event = dw_event();
    $person = dw_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'self',
    ]);

    expect(fn () => EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'operator',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
