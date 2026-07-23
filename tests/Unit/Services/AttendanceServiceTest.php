<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\peserta;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    app()->forgetInstance(ActiveEventContext::class);
    config(['features.attendance_legacy_write' => true]);
});

function attendanceTest_makeEvent(): Event
{
    return Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);
}

function attendanceTest_makeMappedLegacyPeserta(array $overrides, Event $event): array
{
    $participant = peserta::create(array_merge([
        'jenis_kelamin' => 'Laki - Laki',
    ], $overrides));

    $person = Person::create([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => $participant->jenis_kelamin === 'Perempuan' ? 'P' : 'L',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'attendance_code' => $participant->attendance_code,
        'jenis_peserta' => 'Wajib',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'legacy_nip' => $participant->nip,
        'legacy_participant_number' => $participant->participant_number,
        'legacy_attendance_code' => $participant->attendance_code,
        'migrated_at' => now(),
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'migrated_at' => now(),
    ]);

    return [$participant, $person, $participation];
}

test('process scan records successful attendance by attendance code', function () {
    $event = attendanceTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant] = attendanceTest_makeMappedLegacyPeserta([
        'nama' => 'Peserta Scan',
        'nip' => 1001,
        'attendance_code' => 'KJA-SCAN123',
    ], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Pagi', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $result = app(AttendanceService::class)->processScan('kja-scan123');
    expect($result['status'])->toBe('success')->and($result['peserta']->is($participant))->toBeTrue()->and($result['sesi']->is($session))->toBeTrue();
    $this->assertDatabaseHas('event_attendances', ['sesi_absensi_id' => $session->id]);
});

test('process scan prevents duplicate attendance in the same session', function () {
    $event = attendanceTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant, $person, $participation] = attendanceTest_makeMappedLegacyPeserta(['nama' => 'Peserta Duplicate', 'nip' => 1002, 'attendance_code' => 'KJA-DUPL123'], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Duplicate', 'tanggal' => '2026-07-15', 'aktif' => true]);
    EventAttendance::create(['participation_id' => $participation->id, 'sesi_absensi_id' => $session->id, 'event_id' => $event->id, 'status' => 'hadir', 'attended_at' => '2026-07-15 08:00:00', 'method' => 'scan']);
    $result = app(AttendanceService::class)->processScan('KJA-DUPL123', $session->id);
    expect($result['status'])->toBe('duplicate')->and($result['peserta']->is($participant))->toBeTrue()->and($result['sesi']->is($session))->toBeTrue();
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->where('participation_id', $participation->id)->count())->toBe(1);
});

test('process scan requires an active session after participant is found', function () {
    $event = attendanceTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    attendanceTest_makeMappedLegacyPeserta(['nama' => 'Peserta No Session', 'nip' => 1003, 'attendance_code' => 'KJA-NOSESS1'], $event);
    $result = app(AttendanceService::class)->processScan('KJA-NOSESS1');
    expect($result['status'])->toBe('session_required')->and($result['message'])->toBe('Pilih sesi absensi terlebih dahulu');
    expect(EventAttendance::count())->toBe(0);
});

test('process scan returns not found for an invalid identifier', function () {
    $event = attendanceTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    $result = app(AttendanceService::class)->processScan('unknown-identifier');
    expect($result['status'])->toBe('not_found')->and($result['message'])->toBe('Data peserta tidak ditemukan!');
    expect(EventAttendance::count())->toBe(0);
});

test('process scan rejects legacy nip fallback after removal', function () {
    $event = attendanceTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    attendanceTest_makeMappedLegacyPeserta(['nama' => 'Peserta Legacy Nip', 'nip' => 1999, 'attendance_code' => 'KJA-LEGACY1'], $event);
    SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Legacy', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $result = app(AttendanceService::class)->processScan('1999');
    expect($result['status'])->toBe('not_found');
    expect(EventAttendance::count())->toBe(0);
});

test('process scan accepts legacy attendance code through mapping and stays event safe', function () {
    $event = attendanceTest_makeEvent();
    $other = Event::create(['name' => 'Legacy Other', 'slug' => 'legacy-other-'.str()->random(6), 'status' => 'active']);
    app(ActiveEventContext::class)->set($event);
    [$participant] = attendanceTest_makeMappedLegacyPeserta(['nama' => 'Peserta Legacy Code', 'nip' => 1998, 'attendance_code' => 'KJA-LEGCODE1'], $event);
    attendanceTest_makeMappedLegacyPeserta(['nama' => 'Peserta Legacy Code Other', 'nip' => 2998, 'attendance_code' => 'KJA-LEGCODE2'], $other);
    SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Legacy Code', 'tanggal' => '2026-07-15', 'aktif' => true]);

    $result = app(AttendanceService::class)->processScan('KJA-LEGCODE1');

    expect($result['status'])->toBe('success')->and($result['peserta']->is($participant))->toBeTrue();
});

test('process scan rejects missing legacy mapping in CAI', function () {
    $event = attendanceTest_makeEvent(); app(ActiveEventContext::class)->set($event);
    peserta::create(['nama' => 'Peserta Tanpa Mapping', 'nip' => 2001, 'attendance_code' => 'KJA-NOMAP1', 'jenis_kelamin' => 'Laki - Laki']);
    SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi No Mapping', 'tanggal' => '2026-07-15', 'aktif' => true]);
    expect(app(AttendanceService::class)->processScan('KJA-NOMAP1')['status'])->toBe('not_found');
    expect(EventAttendance::count())->toBe(0);
});

test('process scan rejects wrong-event legacy mapping', function () {
    $eventA = attendanceTest_makeEvent(); $eventB = Event::create(['name' => 'Other Event', 'slug' => 'other-event-' . str()->random(6), 'status' => 'active']); app(ActiveEventContext::class)->set($eventA);
    [$participant] = attendanceTest_makeMappedLegacyPeserta(['nama' => 'Peserta Wrong Event', 'nip' => 2002, 'attendance_code' => 'KJA-WRONG1'], $eventB);
    SesiAbsensi::create(['event_id' => $eventA->id, 'nama_sesi' => 'Sesi Wrong Event', 'tanggal' => '2026-07-15', 'aktif' => true]);
    expect(app(AttendanceService::class)->processScan('KJA-WRONG1')['status'])->toBe('not_found');
    expect(EventAttendance::count())->toBe(0);
});

test('process scan rejects broken legacy mapping', function () {
    $event = attendanceTest_makeEvent(); app(ActiveEventContext::class)->set($event);
    $otherEvent = Event::create(['name' => 'Other Event 2', 'slug' => 'other-event-2-' . str()->random(6), 'status' => 'active']);
    $participant = peserta::create(['nama' => 'Peserta Broken Mapping', 'nip' => 2003, 'attendance_code' => 'KJA-BROKEN1', 'jenis_kelamin' => 'Laki - Laki']);
    $person = Person::create(['nama' => $participant->nama, 'nip' => $participant->nip, 'jenis_kelamin' => 'L']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $otherEvent->id, 'attendance_code' => $participant->attendance_code, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id, 'legacy_nip' => $participant->nip, 'legacy_attendance_code' => $participant->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $otherEvent->id, 'migrated_at' => now()]);
    SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Broken', 'tanggal' => '2026-07-15', 'aktif' => true]);
    expect(app(AttendanceService::class)->processScan('KJA-BROKEN1')['status'])->toBe('not_found');
    expect(EventAttendance::count())->toBe(0);
});

test('process scan allows explicit session from active event only', function () {
    $eventA = attendanceTest_makeEvent();
    $eventB = Event::create([
        'name' => 'Other Event Explicit',
        'slug' => 'other-event-explicit-' . str()->random(6),
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($eventA);
    attendanceTest_makeMappedLegacyPeserta([
        'nama' => 'Peserta Explicit Session',
        'nip' => 2100,
        'attendance_code' => 'KJA-EXPL1',
    ], $eventA);

    $sessionB = SesiAbsensi::create([
        'event_id' => $eventB->id,
        'nama_sesi' => 'Sesi Event B',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $before = EventAttendance::count();
    $result = app(AttendanceService::class)->processScan('KJA-EXPL1', $sessionB->id);

    expect($result['status'])->toBe('session_required');
    expect(EventAttendance::count())->toBe($before);
});

test('failed cross-event explicit session creates zero absensi', function () {
    $eventA = attendanceTest_makeEvent();
    $eventB = Event::create([
        'name' => 'Other Event Explicit Two',
        'slug' => 'other-event-explicit-two-' . str()->random(6),
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($eventA);
    attendanceTest_makeMappedLegacyPeserta([
        'nama' => 'Peserta Explicit Rejected',
        'nip' => 2101,
        'attendance_code' => 'KJA-EXPL2',
    ], $eventA);

    $sessionB = SesiAbsensi::create([
        'event_id' => $eventB->id,
        'nama_sesi' => 'Sesi Event B 2',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $before = EventAttendance::count();

    expect(app(AttendanceService::class)->processScan('KJA-EXPL2', $sessionB->id)['status'])->toBe('session_required');
    expect(EventAttendance::count())->toBe($before);
});

test('cross-event duplicate detection stays isolated by session', function () {
    $eventA = attendanceTest_makeEvent();
    $eventB = Event::create([
        'name' => 'Other Event Same Nip',
        'slug' => 'other-event-same-nip-' . str()->random(6),
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($eventA);
    [$participantA] = attendanceTest_makeMappedLegacyPeserta([
        'nama' => 'Peserta Same Nip A',
        'nip' => 2200,
        'attendance_code' => 'KJA-SAMENIP-A',
    ], $eventA);
    $sessionA = SesiAbsensi::create(['event_id' => $eventA->id, 'nama_sesi' => 'Sesi A', 'tanggal' => '2026-07-15', 'aktif' => true]);
    expect(app(AttendanceService::class)->processScan('KJA-SAMENIP-A', $sessionA->id)['status'])->toBe('success');

    app(ActiveEventContext::class)->set($eventB);
    $sessionB = SesiAbsensi::create(['event_id' => $eventB->id, 'nama_sesi' => 'Sesi B', 'tanggal' => '2026-07-16', 'aktif' => true]);
    $before = EventAttendance::count();
    $resultB = app(AttendanceService::class)->processScan('KJA-SAMENIP-A', $sessionB->id);

    expect($resultB['status'])->toBe('not_found');
    expect(EventAttendance::count())->toBe($before);
});

test('historical legacy absensi using nip and sesi_id remains queryable', function () {
    $event = attendanceTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant] = attendanceTest_makeMappedLegacyPeserta([
        'nama' => 'Peserta Histori',
        'nip' => 2300,
        'attendance_code' => 'KJA-HIST1',
    ], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Histori', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $absensi = Absensi::create(['nip' => $participant->nip, 'nama' => $participant->nama, 'jam_scan' => '2026-07-15 08:00:00', 'sesi_id' => $session->id]);

    expect(Absensi::where('nip', 2300)->where('sesi_id', $session->id)->first()?->is($absensi))->toBeTrue();
});
