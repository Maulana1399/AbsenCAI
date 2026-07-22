<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use App\Services\Attendance\AttendanceBackfillService;
use App\Services\Attendance\AttendanceParityService;
use App\Services\Attendance\AttendanceReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mv_event(): Event
{
    return Event::create(['name' => 'MV Event '.str()->random(6), 'slug' => 'mv-'.str()->random(6), 'status' => 'active']);
}

function mv_person(): Person
{
    return Person::create(['nama' => 'MV Person '.str()->random(6)]);
}

function mv_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi '.str()->random(6), 'tanggal' => '2026-08-10', 'aktif' => true]);
}

function mv_peserta(array $o = []): peserta
{
    return peserta::create(array_merge([
        'nama' => 'MV '.str()->random(6), 'nip' => random_int(50000, 99999),
        'attendance_code' => 'KJA-MV-'.str()->random(6),
        'participant_number' => 'KL'.random_int(100, 999),
        'status_registrasi' => 'Belum Registrasi',
    ], $o));
}

function mv_mapping(peserta $p, Person $person, Participation $part, Event $event): LegacyPesertaMapping
{
    return LegacyPesertaMapping::create([
        'peserta_id' => $p->id, 'person_id' => $person->id,
        'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now(),
    ]);
}

function mv_participant(Event $event): object
{
    $person = mv_person();
    $peserta = mv_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    mv_mapping($peserta, $person, $participation, $event);
    return (object) compact('person', 'peserta', 'participation');
}

// ---------------------------------------------------------------------------
// A. GO criteria — perfect parity
// ---------------------------------------------------------------------------

test('perfect parity produces GO status', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    EventAttendance::create([
        'participation_id' => $m->participation->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['missing_canonical'])->toBe(0)
        ->and($result['orphan_canonical'])->toBe(0)
        ->and($result['status_conflicts'])->toBe(0)
        ->and($result['parity_percentage'])->toBe(100.0);
});

// ---------------------------------------------------------------------------
// B. NO-GO criteria — missing canonical, conflicts
// ---------------------------------------------------------------------------

test('missing canonical produces NO-GO', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['missing_canonical'])->toBeGreaterThan(0)
        ->and($result['parity_percentage'])->toBeLessThan(100.0);
});

test('status conflict produces NO-GO', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    EventAttendance::create([
        'participation_id' => $m->participation->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_IZIN,
        'attended_at' => now(), 'method' => 'izin',
    ]);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['status_conflicts'])->toBeGreaterThan(0);
});

// ---------------------------------------------------------------------------
// C. AttendanceReadService canonical precedence
// ---------------------------------------------------------------------------

test('ReadService uses canonical when both canonical and legacy exist', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    EventAttendance::create([
        'participation_id' => $m->participation->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    $entry = $data['attendance']->first();

    expect($entry->status)->toBe('hadir')
        ->and($entry->source)->toBe('canonical');
});

test('ReadService falls back to legacy when canonical missing', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    $entry = $data['attendance']->first();

    expect($entry->status)->toBe('hadir')
        ->and($entry->source)->toBe('legacy');
});

test('ReadService legacy fallback for izin', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    IzinAbsensi::create(['peserta_id' => $m->peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    $entry = $data['attendance']->first();

    expect($entry->status)->toBe('izin')
        ->and($entry->source)->toBe('legacy');
});

test('ReadService returns belum when nothing exists', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    $entry = $data['attendance']->first();

    expect($entry->status)->toBe('belum')
        ->and($entry->source)->toBe('none');
});

test('ReadService canonical hadir wins over legacy izin', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    IzinAbsensi::create(['peserta_id' => $m->peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);
    EventAttendance::create([
        'participation_id' => $m->participation->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);

    $data = app(AttendanceReadService::class)->getSessionAttendance($event->id, $session->id);
    $entry = $data['attendance']->first();

    expect($entry->status)->toBe('hadir');
});

// ---------------------------------------------------------------------------
// D. Cross-event isolation in ReadService
// ---------------------------------------------------------------------------

test('ReadService does not mix Event A and Event B attendance', function () {
    $eventA = mv_event(); $eventB = mv_event();
    $sessionA = mv_session($eventA); $sessionB = mv_session($eventB);
    $mA = mv_participant($eventA);
    Absensi::create(['nip' => $mA->peserta->nip, 'nama' => 'A', 'jam_scan' => now(), 'sesi_id' => $sessionA->id]);
    EventAttendance::create([
        'participation_id' => $mA->participation->id, 'sesi_absensi_id' => $sessionA->id,
        'event_id' => $eventA->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);

    $dataA = app(AttendanceReadService::class)->getSessionAttendance($eventA->id, $sessionA->id);
    $dataB = app(AttendanceReadService::class)->getSessionAttendance($eventB->id, $sessionB->id);

    expect($dataA['hadir_count'])->toBe(1);
    expect($dataB['total'])->toBe(0);
});

// ---------------------------------------------------------------------------
// E. Backfill idempotency + safety
// ---------------------------------------------------------------------------

test('backfill dry-run makes no writes', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    app(AttendanceBackfillService::class)->dryRunHadir();

    expect(EventAttendance::count())->toBe(0);
});

test('backfill is idempotent', function () {
    $event = mv_event();
    $session = mv_session($event);
    $m = mv_participant($event);
    Absensi::create(['nip' => $m->peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $svc = app(AttendanceBackfillService::class);
    $svc->backfillHadir();
    $second = $svc->backfillHadir();

    expect($second['skip_existing'])->toBeGreaterThanOrEqual(1);
    expect($second['created'])->toBe(0);
});

// ---------------------------------------------------------------------------
// F. Multi-event overall summary
// ---------------------------------------------------------------------------

test('auditAll returns summary across events', function () {
    $eventA = mv_event(); $eventB = mv_event();
    $sessionA = mv_session($eventA); $sessionB = mv_session($eventB);
    $mA = mv_participant($eventA); $mB = mv_participant($eventB);
    Absensi::create(['nip' => $mA->peserta->nip, 'nama' => 'A', 'jam_scan' => now(), 'sesi_id' => $sessionA->id]);
    EventAttendance::create([
        'participation_id' => $mA->participation->id, 'sesi_absensi_id' => $sessionA->id,
        'event_id' => $eventA->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);
    Absensi::create(['nip' => $mB->peserta->nip, 'nama' => 'B', 'jam_scan' => now(), 'sesi_id' => $sessionB->id]);
    EventAttendance::create([
        'participation_id' => $mB->participation->id, 'sesi_absensi_id' => $sessionB->id,
        'event_id' => $eventB->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);

    $all = app(AttendanceParityService::class)->auditAll();

    expect($all['events'])->toHaveCount(2);
    expect($all['totals']['matched'])->toBe(2);
});

// ---------------------------------------------------------------------------
// G. Pengajian excluded from CAI parity
// ---------------------------------------------------------------------------

test('Pengajian attendance excluded from CAI parity', function () {
    $event = mv_event();
    $person = mv_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);
    EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'attended_at' => now(), 'method' => 'self',
    ]);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['canonical_total'])->toBe(0);
});

// ---------------------------------------------------------------------------
// H. Cross-event parity isolation
// ---------------------------------------------------------------------------

test('parity per-event isolation', function () {
    $eventA = mv_event(); $eventB = mv_event();
    $sessionA = mv_session($eventA); $sessionB = mv_session($eventB);
    $mA = mv_participant($eventA); $mB = mv_participant($eventB);
    Absensi::create(['nip' => $mA->peserta->nip, 'nama' => 'A', 'jam_scan' => now(), 'sesi_id' => $sessionA->id]);
    EventAttendance::create([
        'participation_id' => $mA->participation->id, 'sesi_absensi_id' => $sessionA->id,
        'event_id' => $eventA->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);
    Absensi::create(['nip' => $mB->peserta->nip, 'nama' => 'B', 'jam_scan' => now(), 'sesi_id' => $sessionB->id]);

    $resultA = app(AttendanceParityService::class)->audit($eventA->id);
    $resultB = app(AttendanceParityService::class)->audit($eventB->id);

    expect($resultA['matched'])->toBe(1);
    expect($resultB['missing_canonical'])->toBe(1);
});
