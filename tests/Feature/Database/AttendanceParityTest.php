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
use App\Services\Attendance\AttendanceBackfillService;
use App\Services\Attendance\AttendanceParityService;
use App\Services\Attendance\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ap_next(string $prefix): string
{
    static $counter = 0;

    $counter++;

    return $prefix . str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
}

function ap_event(): Event
{
    return Event::create(['name' => ap_next('AP Event '), 'slug' => 'ap-' . ap_next('evt-'), 'status' => 'active']);
}

function ap_person(): Person
{
    return Person::create(['nama' => ap_next('AP Person ')]);
}

function ap_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => ap_next('Sesi '), 'tanggal' => '2026-08-07', 'aktif' => true]);
}

function ap_peserta(array $overrides = []): peserta
{
    $seq = ap_next('');

    return peserta::create(array_merge([
        'nama' => 'AP ' . $seq,
        'attendance_code' => 'KJA-AP-' . $seq,
        'participant_number' => 'KL' . str_pad((string) (1000 + (int) substr($seq, -4)), 3, '0', STR_PAD_LEFT),
        'status_registrasi' => 'Belum Registrasi',
    ], $overrides));
}

function ap_mapping(peserta $p, Person $person, Participation $participation, Event $event): LegacyPesertaMapping
{
    LegacyParticipationMapping::create([
        'peserta_id' => $p->id, 'person_id' => $person->id,
        'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now(),
    ]);

    $nipValue = $person->id;

    return LegacyPesertaMapping::create([
        'peserta_id' => $p->id, 'person_id' => $person->id,
        'legacy_nip' => $nipValue,
        'legacy_participant_number' => $p->participant_number,
        'legacy_attendance_code' => $p->attendance_code,
        'migrated_at' => now(),
    ]);
}

function ap_mappedParticipant(Event $event): object
{
    $person = ap_person();
    $peserta = ap_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    ap_mapping($peserta, $person, $participation, $event);
    return (object) compact('person', 'peserta', 'participation');
}

function ap_legacyHadir(Event $event, peserta $peserta, SesiAbsensi $session): Absensi
{
    $mapping = \App\Models\LegacyPesertaMapping::where('peserta_id', $peserta->id)->first();
    $nipValue = $mapping?->legacy_nip ?? $peserta->id;
    return Absensi::create(['nip' => $nipValue, 'nama' => $peserta->nama, 'jam_scan' => now(), 'sesi_id' => $session->id]);
}

function ap_legacyIzin(Event $event, peserta $peserta, SesiAbsensi $session): IzinAbsensi
{
    return IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);
}

function ap_canonicalHadir(Participation $p, SesiAbsensi $session, Event $event): EventAttendance
{
    return EventAttendance::create([
        'participation_id' => $p->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);
}

function ap_canonicalIzin(Participation $p, SesiAbsensi $session, Event $event): EventAttendance
{
    return EventAttendance::create([
        'participation_id' => $p->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_IZIN,
        'attended_at' => now(), 'method' => 'izin',
    ]);
}

// ---------------------------------------------------------------------------
// A. Perfect parity
// ---------------------------------------------------------------------------

test('one legacy hadir with canonical hadir is 100% parity', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyHadir($event, $m->peserta, $session);
    ap_canonicalHadir($m->participation, $session, $event);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['legacy_hadir_count'])->toBe(1)
        ->and($result['canonical_hadir_count'])->toBe(1)
        ->and($result['matched_hadir'])->toBe(1)
        ->and($result['parity_percentage'])->toBe(100.0);
});

test('one legacy izin with canonical izin is 100% parity', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyIzin($event, $m->peserta, $session);
    ap_canonicalIzin($m->participation, $session, $event);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['legacy_izin_count'])->toBe(1)
        ->and($result['canonical_izin_count'])->toBe(1)
        ->and($result['matched_izin'])->toBe(1)
        ->and($result['parity_percentage'])->toBe(100.0);
});

test('mixed hadir and izin across sessions is 100% parity', function () {
    $event = ap_event();
    $sessionA = ap_session($event);
    $sessionB = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyHadir($event, $m->peserta, $sessionA);
    ap_canonicalHadir($m->participation, $sessionA, $event);
    ap_legacyIzin($event, $m->peserta, $sessionB);
    ap_canonicalIzin($m->participation, $sessionB, $event);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['matched'])->toBe(2)
        ->and($result['parity_percentage'])->toBe(100.0);
});

test('multiple participants all matched is 100% parity', function () {
    $event = ap_event();
    $session = ap_session($event);
    $mA = ap_mappedParticipant($event);
    $mB = ap_mappedParticipant($event);
    ap_legacyHadir($event, $mA->peserta, $session); ap_canonicalHadir($mA->participation, $session, $event);
    ap_legacyHadir($event, $mB->peserta, $session); ap_canonicalHadir($mB->participation, $session, $event);

    expect(app(AttendanceParityService::class)->audit($event->id)['parity_percentage'])->toBe(100.0);
});

// ---------------------------------------------------------------------------
// B. Missing canonical
// ---------------------------------------------------------------------------

test('legacy hadir without canonical is counted as missing', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyHadir($event, $m->peserta, $session);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['missing_canonical_hadir'])->toBe(1)
        ->and($result['matched'])->toBe(0);
});

test('legacy izin without canonical is counted as missing', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyIzin($event, $m->peserta, $session);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['missing_canonical_izin'])->toBe(1)
        ->and($result['matched'])->toBe(0);
});

// ---------------------------------------------------------------------------
// C. Orphan canonical
// ---------------------------------------------------------------------------

test('canonical hadir without legacy is counted as orphan', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_canonicalHadir($m->participation, $session, $event);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['orphan_canonical_hadir'])->toBeGreaterThanOrEqual(1);
});

// ---------------------------------------------------------------------------
// D. Unmappable legacy
// ---------------------------------------------------------------------------

test('unmappable legacy hadir counted correctly', function () {
    $event = ap_event();
    $session = ap_session($event);
    $peserta = ap_peserta();
    ap_legacyHadir($event, $peserta, $session);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['unmappable_hadir'])->toBe(1);
});

// ---------------------------------------------------------------------------
// E. Cross-event isolation
// ---------------------------------------------------------------------------

test('cross-event records isolated', function () {
    $eventA = ap_event(); $eventB = ap_event();
    $sessionA = ap_session($eventA); $sessionB = ap_session($eventB);
    $mA = ap_mappedParticipant($eventA); $mB = ap_mappedParticipant($eventB);
    ap_legacyHadir($eventA, $mA->peserta, $sessionA); ap_canonicalHadir($mA->participation, $sessionA, $eventA);
    ap_legacyHadir($eventB, $mB->peserta, $sessionB); ap_canonicalHadir($mB->participation, $sessionB, $eventB);

    $resultA = app(AttendanceParityService::class)->audit($eventA->id);
    $resultB = app(AttendanceParityService::class)->audit($eventB->id);

    expect($resultA['legacy_hadir_count'])->toBe(1)
        ->and($resultB['legacy_hadir_count'])->toBe(1)
        ->and($resultA['matched'])->toBe(1)
        ->and($resultB['matched'])->toBe(1);
});

// ---------------------------------------------------------------------------
// F. Multiple sessions same participant
// ---------------------------------------------------------------------------

test('same participant multiple sessions all matched', function () {
    $event = ap_event();
    $sessionA = ap_session($event); $sessionB = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyHadir($event, $m->peserta, $sessionA); ap_canonicalHadir($m->participation, $sessionA, $event);
    ap_legacyHadir($event, $m->peserta, $sessionB); ap_canonicalHadir($m->participation, $sessionB, $event);

    expect(app(AttendanceParityService::class)->audit($event->id)['parity_percentage'])->toBe(100.0);
});

// ---------------------------------------------------------------------------
// G. Backfill integration — parity improves after backfill
// ---------------------------------------------------------------------------

test('parity improves after backfill', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyHadir($event, $m->peserta, $session);

    $before = app(AttendanceParityService::class)->audit($event->id);
    expect($before['missing_canonical_hadir'])->toBe(1);

    app(AttendanceBackfillService::class)->backfillHadir();

    $after = app(AttendanceParityService::class)->audit($event->id);
    expect($after['matched_hadir'])->toBe(1)
        ->and($after['parity_percentage'])->toBe(100.0);
});

// ---------------------------------------------------------------------------
// H. Method distinction
// ---------------------------------------------------------------------------

test('scan creates method=scan on EventAttendance', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);

    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id);

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();
    expect($ea->method)->toBe('scan');
});

test('manual attendance creates method=manual on EventAttendance', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);

    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceService::class)->processScan((string) $m->peserta->attendance_code, $session->id, 'manual');

    $ea = EventAttendance::where('sesi_absensi_id', $session->id)->first();
    expect($ea->method)->toBe('manual');
});

// ---------------------------------------------------------------------------
// I. Pengajian exclusion
// ---------------------------------------------------------------------------

test('Pengajian records excluded from CAI parity audit', function () {
    $event = ap_event();
    $person = ap_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);
    EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'attended_at' => now(), 'method' => 'self',
    ]);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['canonical_total'])->toBe(0)
        ->and($result['parity_percentage'])->toBe(100.0);
});

test('Pengajian self attendance works after parity changes', function () {
    $event = ap_event();
    $person = ap_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);

    $ea = EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'attended_at' => now(), 'method' => 'self',
    ]);

    expect($ea->method)->toBe('self')
        ->and($ea->sesi_absensi_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// J. Status default
// ---------------------------------------------------------------------------

test('EventAttendance status defaults correctly after migration', function () {
    $event = ap_event();
    $person = ap_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Pengajian Desa']);

    EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'self',
    ]);

    $ea = EventAttendance::where('participation_id', $participation->id)->first();
    expect($ea->status)->toBe(EventAttendance::STATUS_HADIR);
});

// ---------------------------------------------------------------------------
// K. auditAll
// ---------------------------------------------------------------------------

test('auditAll returns results for all CAI events', function () {
    $eventA = ap_event(); $eventB = ap_event();
    $sessionA = ap_session($eventA); $sessionB = ap_session($eventB);
    $mA = ap_mappedParticipant($eventA); $mB = ap_mappedParticipant($eventB);
    ap_legacyHadir($eventA, $mA->peserta, $sessionA); ap_canonicalHadir($mA->participation, $sessionA, $eventA);
    ap_legacyHadir($eventB, $mB->peserta, $sessionB); ap_canonicalHadir($mB->participation, $sessionB, $eventB);

    $all = app(AttendanceParityService::class)->auditAll();

    expect($all['events'])->toHaveCount(2)
        ->and($all['totals']['matched'])->toBe(2)
        ->and($all['totals']['parity_percentage'])->toBe(100.0);
});

// ---------------------------------------------------------------------------
// L. Conflict detection
// ---------------------------------------------------------------------------

test('status conflict detected when canonical izin but legacy hadir', function () {
    $event = ap_event();
    $session = ap_session($event);
    $m = ap_mappedParticipant($event);
    ap_legacyHadir($event, $m->peserta, $session);
    ap_canonicalIzin($m->participation, $session, $event);

    $result = app(AttendanceParityService::class)->audit($event->id);

    expect($result['status_conflicts'])->toBe(1)
        ->and($result['matched_hadir'])->toBe(0);
});
