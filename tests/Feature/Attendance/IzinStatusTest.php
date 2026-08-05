<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\AttendanceService;
use App\Support\ActiveEventContext;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['features.attendance_legacy_write' => true]);
});

it('records izin attendance exception', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Izin',
        'nip' => 5001,
        'attendance_code' => 'KJA-IZIN001',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $person = Person::create([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => 'L',
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
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Izin',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    $record = app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id);

    expect($record)->toBeInstanceOf(EventAttendance::class)
        ->and($record->participation_id)->toBe($participation->id)
        ->and($record->sesi_absensi_id)->toBe($session->id)
        ->and($record->status)->toBe(EventAttendance::STATUS_IZIN);

    $this->assertDatabaseHas('event_attendances', [
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $session->id,
        'status' => EventAttendance::STATUS_IZIN,
    ]);
});

it('rejects duplicate izin for same participant and session', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Izin Duplicate',
        'nip' => 5002,
        'attendance_code' => 'KJA-IZIN002',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $person = Person::create([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => 'L',
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
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Izin Duplicate',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id);

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id))
        ->toThrow(ValidationException::class);
});

it('prevents hadir participant from becoming izin', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Hadir Lalu Izin',
        'nip' => 5003,
        'attendance_code' => 'KJA-HADIRIZIN',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $person = Person::create([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => 'L',
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
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Hadir Izin',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => '2026-07-16 08:00:00',
        'method' => 'scan',
    ]);

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id))
        ->toThrow(ValidationException::class);
});

it('prevents hadir recording when participant is already izin', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Izin Lalu Hadir',
        'nip' => 5004,
        'attendance_code' => 'KJA-IZINHADIR',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $person = Person::create([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => 'L',
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
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Izin Hadir',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id);

    expect(fn () => app(AttendanceService::class)->processScan('KJA-IZINHADIR', $session->id))
        ->toThrow(ValidationException::class);
});
