<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\AttendanceBackfillService;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function s62_event(): Event
{
    return Event::create([
        'name' => 'S62 Event '.str()->random(6),
        'slug' => 's62-'.str()->random(6),
        'status' => 'active',
    ]);
}

function s62_person(): Person
{
    return Person::create(['nama' => 'S62 Person '.str()->random(6)]);
}

function s62_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi '.str()->random(6),
        'tanggal' => '2026-08-05',
        'aktif' => true,
    ]);
}

function s62_legacyPeserta(array $overrides = []): peserta
{
    return peserta::create(array_merge([
        'nama' => 'Legacy '.str()->random(6),
        'nip' => random_int(1000, 9999),
        'status_registrasi' => 'Belum Registrasi',
    ], $overrides));
}

function s62_mapping(peserta $peserta, Person $person, Participation $participation, Event $event): LegacyPesertaMapping
{
    return LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'migrated_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// A. Schema — Existing Pengajian EventAttendance survives
// ---------------------------------------------------------------------------

test('existing Pengajian EventAttendance survives migration', function () {
    $event = s62_event();
    $person = s62_person();
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

    expect($attendance->fresh())->not->toBeNull();
    expect($attendance->fresh()->sesi_absensi_id)->toBeNull();
    expect($attendance->fresh()->status)->toBe('hadir');
});

test('nullable sesi_absensi_id works for Pengajian', function () {
    $event = s62_event();
    $person = s62_person();
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
        'status' => EventAttendance::STATUS_HADIR,
    ]);

    expect($attendance->sesi_absensi_id)->toBeNull();
});

test('status can be set explicitly', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'self',
    ]);

    expect($attendance->status)->toBe(EventAttendance::STATUS_HADIR);
});

// ---------------------------------------------------------------------------
// B. Uniqueness — Pengajian
// ---------------------------------------------------------------------------

test('Pengajian duplicate attendance is blocked by unique constraint', function () {
    $event = s62_event();
    $person = s62_person();
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

// ---------------------------------------------------------------------------
// C. Uniqueness — CAI session-based
// ---------------------------------------------------------------------------

test('CAI same participation+same session is blocked', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
    ]);
    $session = s62_session($event);

    EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    expect(fn () => EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('CAI same participation different sessions is allowed', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
    ]);
    $sessionA = s62_session($event);
    $sessionB = s62_session($event);

    EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $sessionA->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    $second = EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $sessionB->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    expect($second->id)->not->toBeNull();
});

test('different participations same session is allowed', function () {
    $event = s62_event();
    $personA = s62_person();
    $personB = s62_person();
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $session = s62_session($event);

    EventAttendance::create([
        'participation_id' => $partA->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'attended_at' => now(), 'method' => 'scan',
    ]);

    $second = EventAttendance::create([
        'participation_id' => $partB->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'attended_at' => now(), 'method' => 'scan',
    ]);

    expect($second->id)->not->toBeNull();
});

// ---------------------------------------------------------------------------
// D. Relationships
// ---------------------------------------------------------------------------

test('EventAttendance belongs to Participation', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    expect($attendance->participation->id)->toBe($participation->id);
});

test('EventAttendance belongs to SesiAbsensi', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $session = s62_session($event);
    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    expect($attendance->sesiAbsensi->id)->toBe($session->id);
});

test('Participation has many EventAttendances', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $sessionA = s62_session($event);
    $sessionB = s62_session($event);

    EventAttendance::create([
        'participation_id' => $participation->id, 'sesi_absensi_id' => $sessionA->id,
        'event_id' => $event->id, 'attended_at' => now(), 'method' => 'scan',
    ]);
    EventAttendance::create([
        'participation_id' => $participation->id, 'sesi_absensi_id' => $sessionB->id,
        'event_id' => $event->id, 'attended_at' => now(), 'method' => 'scan',
    ]);

    expect($participation->eventAttendances)->toHaveCount(2);
});

test('SesiAbsensi has many EventAttendances', function () {
    $event = s62_event();
    $personA = s62_person();
    $personB = s62_person();
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $session = s62_session($event);

    EventAttendance::create([
        'participation_id' => $partA->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'attended_at' => now(), 'method' => 'scan',
    ]);
    EventAttendance::create([
        'participation_id' => $partB->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'attended_at' => now(), 'method' => 'scan',
    ]);

    expect($session->eventAttendances)->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// E. Backfill — Hadir
// ---------------------------------------------------------------------------

test('backfill hadir — safe mapping', function () {
    $event = s62_event();
    $session = s62_session($event);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 5001]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participation, $event);

    Absensi::create(['nip' => 5001, 'nama' => 'Test', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $service = app(AttendanceBackfillService::class);
    $result = $service->backfillHadir();

    expect($result['total_scanned'])->toBe(1)
        ->and($result['mapped'])->toBe(1)
        ->and($result['created'])->toBe(1);

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();
    expect($ea)->not->toBeNull()
        ->and($ea->participation_id)->toBe($participation->id)
        ->and($ea->event_id)->toBe($event->id)
        ->and($ea->status)->toBe('hadir');
});

test('backfill hadir — event resolved from session', function () {
    $eventA = s62_event();
    $eventB = s62_event();
    $sessionB = s62_session($eventB);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 5002]);
    $participationB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participationB, $eventB);

    Absensi::create(['nip' => 5002, 'nama' => 'Test', 'jam_scan' => now(), 'sesi_id' => $sessionB->id]);

    $result = app(AttendanceBackfillService::class)->backfillHadir();

    expect($result['mapped'])->toBe(1);

    $ea = EventAttendance::where('sesi_absensi_id', $sessionB->id)->first();
    expect($ea->event_id)->toBe($eventB->id);
});

test('backfill hadir — cross-event absensi resolves to correct event', function () {
    $eventA = s62_event();
    $eventB = s62_event();
    $sessionA = s62_session($eventA);
    $sessionB = s62_session($eventB);
    $personA = s62_person();
    $personB = s62_person();
    $pesertaA = s62_legacyPeserta(['nip' => 4444]);
    $pesertaB = s62_legacyPeserta(['nip' => 5555]);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($pesertaA, $personA, $partA, $eventA);
    s62_mapping($pesertaB, $personB, $partB, $eventB);

    Absensi::create(['nip' => 4444, 'nama' => 'Event A Person', 'jam_scan' => now(), 'sesi_id' => $sessionA->id]);
    Absensi::create(['nip' => 5555, 'nama' => 'Event B Person', 'jam_scan' => now(), 'sesi_id' => $sessionB->id]);

    $result = app(AttendanceBackfillService::class)->backfillHadir();

    expect($result['mapped'])->toBe(2);

    $eaA = EventAttendance::where('sesi_absensi_id', $sessionA->id)->first();
    $eaB = EventAttendance::where('sesi_absensi_id', $sessionB->id)->first();
    expect($eaA->event_id)->toBe($eventA->id)
        ->and($eaB->event_id)->toBe($eventB->id);
});

test('backfill hadir — no mapping skipped', function () {
    $event = s62_event();
    $session = s62_session($event);
    $peserta = s62_legacyPeserta(['nip' => 7001]);
    Absensi::create(['nip' => 7001, 'nama' => 'No Map', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $result = app(AttendanceBackfillService::class)->backfillHadir();

    expect($result['skip_no_participation'])->toBe(1);
});

test('backfill hadir — idempotent rerun', function () {
    $event = s62_event();
    $session = s62_session($event);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 8001]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participation, $event);
    Absensi::create(['nip' => 8001, 'nama' => 'Test', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $service = app(AttendanceBackfillService::class);
    $first = $service->backfillHadir();
    $second = $service->backfillHadir();

    expect($first['created'])->toBe(1)
        ->and($second['skip_existing'])->toBe(1)
        ->and($second['created'])->toBe(0);
});

test('backfill hadir — dry run does not write', function () {
    $event = s62_event();
    $session = s62_session($event);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 9001]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participation, $event);
    Absensi::create(['nip' => 9001, 'nama' => 'Test', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $result = app(AttendanceBackfillService::class)->dryRunHadir();

    expect($result['mapped'])->toBe(1)
        ->and(EventAttendance::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// F. Backfill — Izin
// ---------------------------------------------------------------------------

test('backfill izin — safe mapping creates EventAttendance with status izin', function () {
    $event = s62_event();
    $session = s62_session($event);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 10001]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participation, $event);

    IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    $result = app(AttendanceBackfillService::class)->backfillIzin();

    expect($result['mapped'])->toBe(1);

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();
    expect($ea)->not->toBeNull()
        ->and($ea->status)->toBe('izin')
        ->and($ea->method)->toBe('izin');
});

test('backfill izin — event resolved from session', function () {
    $eventA = s62_event();
    $eventB = s62_event();
    $sessionB = s62_session($eventB);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 10002]);
    $participationB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participationB, $eventB);

    IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $sessionB->id, 'source' => 'manual']);

    $result = app(AttendanceBackfillService::class)->backfillIzin();

    expect($result['mapped'])->toBe(1);

    $ea = EventAttendance::where('sesi_absensi_id', $sessionB->id)->first();
    expect($ea->event_id)->toBe($eventB->id);
});

test('backfill izin — idempotent rerun', function () {
    $event = s62_event();
    $session = s62_session($event);
    $person = s62_person();
    $peserta = s62_legacyPeserta(['nip' => 10003]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    s62_mapping($peserta, $person, $participation, $event);

    IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    $service = app(AttendanceBackfillService::class);
    $first = $service->backfillIzin();
    $second = $service->backfillIzin();

    expect($first['created'])->toBe(1)
        ->and($second['skip_existing'])->toBe(1);
});

// ---------------------------------------------------------------------------
// G. Surat Izin — event_id
// ---------------------------------------------------------------------------

test('new SuratIzin gets active event_id from context', function () {
    $event = s62_event();
    $peserta = s62_legacyPeserta(['nip' => 11001]);
    $user = User::factory()->create(['role' => 'super_admin']);

    app(ActiveEventContext::class)->set($event);

    $service = app(SuratIzinService::class);
    $surat = $service->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan untuk surat izin',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-05',
        'tanggal_selesai' => '2026-08-05',
    ], $user->id);

    expect($surat->event_id)->toBe($event->id);
});

test('SuratIzin belongs to Event', function () {
    $event = s62_event();
    $peserta = s62_legacyPeserta(['nip' => 11002]);
    $user = User::factory()->create(['role' => 'super_admin']);

    app(ActiveEventContext::class)->set($event);

    $service = app(SuratIzinService::class);
    $surat = $service->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-05',
        'tanggal_selesai' => '2026-08-05',
    ], $user->id);

    expect($surat->event->id)->toBe($event->id);
});

// ---------------------------------------------------------------------------
// H. Pengajian Regression
// ---------------------------------------------------------------------------

test('Pengajian self attendance still works', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'self',
    ]);

    expect($attendance->method)->toBe('self')
        ->and($attendance->sesi_absensi_id)->toBeNull();
});

test('Pengajian operator attendance still works', function () {
    $event = s62_event();
    $person = s62_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'operator',
    ]);

    expect($attendance->method)->toBe('operator');
});

test('Pengajian desa behavior unchanged', function () {
    $event = s62_event();
    $desa = \App\Models\desa::create(['desa_asal' => 'Test Desa']);
    $person = s62_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);

    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'attended_at' => now(),
        'method' => 'self',
    ]);

    expect($attendance->desa_id)->toBe($desa->id);
});
