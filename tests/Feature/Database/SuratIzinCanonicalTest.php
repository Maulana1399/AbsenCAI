<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Services\Attendance\SuratIzinBackfillService;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function si_event(): Event
{
    return Event::create(['name' => 'SI Event '.str()->random(6), 'slug' => 'si-'.str()->random(6), 'status' => 'active']);
}

function si_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'SI Sesi', 'tanggal' => '2026-08-15', 'aktif' => true]);
}

function si_participant(Event $event): object
{
    $person = Person::create(['nama' => 'SI Person']);
    $peserta = peserta::create([
        'nama' => 'SI Peserta', 'nip' => random_int(90000, 99999),
        'attendance_code' => 'KJA-SI-'.str()->random(8),
        'participant_number' => 'KL'.random_int(100, 999),
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

// ---------------------------------------------------------------------------
// A. SuratIzin can store participation_id
// ---------------------------------------------------------------------------

test('SuratIzin can store participation_id', function () {
    $event = si_event();
    $m = si_participant($event);

    $surat = SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'participation_id' => $m->participation->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    expect($surat->participation_id)->toBe($m->participation->id);
});

test('existing legacy SuratIzin with participation_id=null still works', function () {
    $event = si_event();
    $peserta = peserta::create(['nama' => 'Legacy', 'nip' => 11111, 'attendance_code' => 'KJA-LEG', 'status_registrasi' => 'Belum Registrasi']);

    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    expect($surat->participation_id)->toBeNull()
        ->and($surat->peserta->nama)->toBe('Legacy');
});

// ---------------------------------------------------------------------------
// B. New mapped SuratIzin stores participation_id
// ---------------------------------------------------------------------------

test('new mapped SuratIzin stores event_id + participation_id + peserta_id', function () {
    $event = si_event();
    $m = si_participant($event);

    app(ActiveEventContext::class)->set($event);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);

    expect($surat->event_id)->toBe($event->id)
        ->and($surat->participation_id)->toBe($m->participation->id)
        ->and($surat->peserta_id)->toBe($m->peserta->id);
});

test('new unmapped SuratIzin stores legacy peserta_id and null participation_id', function () {
    $event = si_event();
    $peserta = peserta::create(['nama' => 'Unmapped', 'nip' => 22222, 'attendance_code' => 'KJA-UNM', 'status_registrasi' => 'Belum Registrasi']);

    app(ActiveEventContext::class)->set($event);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);

    expect($surat->peserta_id)->toBe($peserta->id)
        ->and($surat->participation_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// C. Backfill tests
// ---------------------------------------------------------------------------

test('backfill dry-run returns complete stats without mutating', function () {
    $event = si_event();
    $m = si_participant($event);

    $surat = SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $stats = app(SuratIzinBackfillService::class)->dryRun();

    expect($stats)->toMatchArray([
        'total' => 1,
        'mapped' => 1,
        'updated' => 0,
        'skip_existing' => 0,
        'no_peserta' => 0,
        'no_mapping' => 0,
        'no_participation' => 0,
    ])->and($surat->fresh()->participation_id)->toBeNull();
});

test('backfill --force populates participation_id and updates stats', function () {
    $event = si_event();
    $m = si_participant($event);

    $surat = SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $stats = app(SuratIzinBackfillService::class)->backfill();

    expect($stats['updated'])->toBe(1)
        ->and($stats['mapped'])->toBe(1)
        ->and($surat->fresh()->participation_id)->toBe($m->participation->id);
});

test('backfill is idempotent', function () {
    $event = si_event();
    $m = si_participant($event);

    SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $svc = app(SuratIzinBackfillService::class);
    $first = $svc->backfill();
    $second = $svc->backfill();

    expect($first['updated'])->toBe(1)
        ->and($first['mapped'])->toBe(1)
        ->and($second['updated'])->toBe(0)
        ->and($second['mapped'])->toBe(0);
});

test('backfill skips no participation', function () {
    $event = si_event();
    $peserta = peserta::create(['nama' => 'No Map', 'nip' => 33333, 'attendance_code' => 'KJA-NOMAP', 'status_registrasi' => 'Belum Registrasi']);

    SuratIzin::create([
        'peserta_id' => $peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $stats = app(SuratIzinBackfillService::class)->backfill();

    expect($stats['no_participation'])->toBe(1);
});

// ---------------------------------------------------------------------------
// D. Approval behavior
// ---------------------------------------------------------------------------

test('approval mapped + ATTENDANCE_LEGACY_WRITE=false creates EventAttendance izin only', function () {
    config(['features.attendance_legacy_write' => false]);

    $event = si_event();
    $session = si_session($event);
    $m = si_participant($event);

    app(ActiveEventContext::class)->set($event);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);

    app(SuratIzinService::class)->submit($surat);
    $result = app(SuratIzinService::class)->approve($surat->fresh(), $user);

    expect($result['created'])->toHaveCount(1)
        ->and(EventAttendance::where('participation_id', $m->participation->id)->count())->toBe(1)
        ->and(IzinAbsensi::where('peserta_id', $m->peserta->id)->count())->toBe(0);
});

test('approval unmapped still creates IzinAbsensi fallback', function () {
    $event = si_event();
    $session = si_session($event);
    $peserta = peserta::create(['nama' => 'Unmapped', 'nip' => 44444, 'attendance_code' => 'KJA-UNM4', 'status_registrasi' => 'Belum Registrasi']);

    app(ActiveEventContext::class)->set($event);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);

    app(SuratIzinService::class)->submit($surat);
    $result = app(SuratIzinService::class)->approve($surat->fresh(), $user);

    expect($result['created'])->toHaveCount(1)
        ->and(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// E. Duplicate prevention
// ---------------------------------------------------------------------------

test('existing hadir cannot be overwritten by surat izin', function () {
    $event = si_event();
    $session = si_session($event);
    $m = si_participant($event);

    EventAttendance::create([
        'participation_id' => $m->participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    app(ActiveEventContext::class)->set($event);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    app(SuratIzinService::class)->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);
})->throws(\Illuminate\Validation\ValidationException::class, 'Peserta sudah melakukan absensi sehingga surat izin tidak dapat dibuat.');

test('existing izin cannot be duplicated', function () {
    $event = si_event();
    $session = si_session($event);
    $m = si_participant($event);

    IzinAbsensi::create([
        'peserta_id' => $m->peserta->id,
        'sesi_id' => $session->id,
        'status' => 'izin',
        'source' => 'manual',
    ]);

    app(ActiveEventContext::class)->set($event);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);

    app(SuratIzinService::class)->submit($surat);
    $result = app(SuratIzinService::class)->approve($surat->fresh(), $user);

    expect($result['skipped_izin'])->toHaveCount(1)
        ->and($result['created'])->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// F. Cross-event isolation
// ---------------------------------------------------------------------------

test('SuratIzin Event A cannot affect sessions in Event B', function () {
    $eventA = si_event();
    $eventB = si_event();
    $sessionB = si_session($eventB);
    $m = si_participant($eventA);

    app(ActiveEventContext::class)->set($eventA);
    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-15',
        'tanggal_selesai' => '2026-08-15',
    ], $user->id);

    app(SuratIzinService::class)->submit($surat);

    app(ActiveEventContext::class)->set($eventB);
    $result = app(SuratIzinService::class)->approve($surat->fresh(), $user);

    expect($result['sesi_found'])->toBe(1)
        ->and(EventAttendance::where('event_id', $eventB->id)->count())->toBe(0);
});
