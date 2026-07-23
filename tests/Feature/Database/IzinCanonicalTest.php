<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use App\Services\Attendance\AttendanceExceptionService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function iz_event(): Event
{
    return Event::create(['name' => 'IZ Event ' . str()->random(6), 'slug' => 'iz-' . str()->random(6), 'status' => 'active']);
}

function iz_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'IZ Sesi', 'tanggal' => '2026-08-25', 'aktif' => true]);
}

function iz_mappedParticipant(Event $event): object
{
    $person = Person::create(['nama' => 'IZ Mapped']);
    $peserta = peserta::create([
        'nama' => 'IZ Peserta', 'nip' => random_int(70000, 79999),
        'attendance_code' => 'KJA-IZ-' . str()->random(8),
        'status_registrasi' => 'Belum Registrasi',
    ]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
        'legacy_nip' => $peserta->nip,
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

function iz_canonicalOnlyParticipant(Event $event): object
{
    $person = Person::create(['nama' => 'IZ Canonical']);
    $participation = Participation::create([
        'person_id' => $person->id, 'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
        'attendance_code' => 'KJA-IZ-CANON-' . str()->random(8),
        'participant_number' => 'KL' . random_int(100, 999),
    ]);
    return (object) compact('person', 'participation');
}

// ---------------------------------------------------------------------------
// A. Canonical-only participant manual izin
// ---------------------------------------------------------------------------

test('canonical-only participant can manual izin', function () {
    $event = iz_event();
    $session = iz_session($event);
    $m = iz_canonicalOnlyParticipant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    );

    expect(EventAttendance::where('participation_id', $m->participation->id)->count())->toBe(1);
    expect(IzinAbsensi::where('sesi_id', $session->id)->count())->toBe(0);
});

test('canonical-only izin creates EventAttendance with status izin', function () {
    $event = iz_event();
    $session = iz_session($event);
    $m = iz_canonicalOnlyParticipant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    );

    $ea = EventAttendance::where('participation_id', $m->participation->id)->first();
    expect($ea)->not->toBeNull()
        ->and($ea->status)->toBe(EventAttendance::STATUS_IZIN)
        ->and($ea->method)->toBe('izin');
});

// ---------------------------------------------------------------------------
// B. Mapped participant izin — still works
// ---------------------------------------------------------------------------

test('mapped participant izin works', function () {
    $event = iz_event();
    $session = iz_session($event);
    $m = iz_mappedParticipant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: $m->peserta->id,
        sesiId: $session->id,
        source: 'manual',
    );

    expect(EventAttendance::where('participation_id', $m->participation->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// C. Legacy-only fallback — unmapped peserta still creates IzinAbsensi
// ---------------------------------------------------------------------------

test('legacy-only fallback creates IzinAbsensi', function () {
    $event = iz_event();
    $session = iz_session($event);
    $peserta = peserta::create(['nama' => 'Legacy Only', 'nip' => 50001, 'attendance_code' => 'KJA-LEG-IZIN', 'status_registrasi' => 'Belum Registrasi']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: $peserta->id,
        sesiId: $session->id,
        source: 'manual',
    );

    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// D. Cross-event isolation
// ---------------------------------------------------------------------------

test('Event A participant cannot izin in Event B', function () {
    $eventA = iz_event();
    $eventB = iz_event();
    $sessionB = iz_session($eventB);
    $m = iz_mappedParticipant($eventA);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: $m->peserta->id,
        sesiId: $sessionB->id,
        source: 'manual',
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('canonical Event A participation rejected for Event B session', function () {
    $eventA = iz_event();
    $eventB = iz_event();
    $sessionB = iz_session($eventB);
    $m = iz_canonicalOnlyParticipant($eventA);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $sessionB->id,
        source: 'manual',
        participationId: $m->participation->id,
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

// ---------------------------------------------------------------------------
// E. Existing canonical hadir rejects izin
// ---------------------------------------------------------------------------

test('existing canonical hadir rejects izin', function () {
    $event = iz_event();
    $session = iz_session($event);
    $m = iz_canonicalOnlyParticipant($event);

    EventAttendance::create([
        'participation_id' => $m->participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

// ---------------------------------------------------------------------------
// F. Duplicate canonical izin rejected
// ---------------------------------------------------------------------------

test('duplicate canonical izin rejected', function () {
    $event = iz_event();
    $session = iz_session($event);
    $m = iz_canonicalOnlyParticipant($event);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    );

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

// ---------------------------------------------------------------------------
// G. ATTENDANCE_LEGACY_WRITE=false canonical-only does NOT create IzinAbsensi  
// ---------------------------------------------------------------------------

test('config false canonical izin does not create IzinAbsensi', function () {
    $event = iz_event();
    $session = iz_session($event);
    $m = iz_canonicalOnlyParticipant($event);

    config(['features.attendance_legacy_write' => false]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'super_admin']));

    app(AttendanceExceptionService::class)->recordIzin(
        pesertaId: null,
        sesiId: $session->id,
        source: 'manual',
        participationId: $m->participation->id,
    );

    expect(EventAttendance::count())->toBe(1);
    expect(IzinAbsensi::count())->toBe(0);
});
