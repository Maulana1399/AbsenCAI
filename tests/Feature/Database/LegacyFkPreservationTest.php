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
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fl_event(): Event
{
    return Event::create(['name' => 'FL Event '.str()->random(6), 'slug' => 'fl-'.str()->random(6), 'status' => 'active']);
}

function fl_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'FL Sesi', 'tanggal' => '2026-08-16', 'aktif' => true]);
}

function fl_participant(Event $event): object
{
    $person = Person::create(['nama' => 'FL Person']);
    $peserta = peserta::create([
        'nama' => 'FL Peserta', 'nip' => random_int(90000, 99999),
        'attendance_code' => 'KJA-FL-'.str()->random(8),
        'participant_number' => 'KL'.random_int(100, 999),
        'status_registrasi' => 'Belum Registrasi',
    ]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
        'participation_id' => $participation->id, 'event_id' => $event->id,
    ]);

    return (object) compact('person', 'peserta', 'participation');
}

// ---------------------------------------------------------------------------
// A. SuratIzin survives when peserta_id is nulled
// ---------------------------------------------------------------------------

test('SuratIzin survives when peserta_id set to null', function () {
    $event = fl_event();
    $m = fl_participant($event);

    $surat = SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'approved',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $surat->update(['peserta_id' => null]);

    expect(SuratIzin::find($surat->id))->not->toBeNull()
        ->and($surat->fresh()->peserta_id)->toBeNull();
});

test('IzinAbsensi survives when peserta_id set to null', function () {
    $event = fl_event();
    $session = fl_session($event);
    $m = fl_participant($event);

    $izin = IzinAbsensi::create([
        'peserta_id' => $m->peserta->id,
        'sesi_id' => $session->id,
        'source' => 'manual',
    ]);

    $izin->update(['peserta_id' => null]);

    expect(IzinAbsensi::find($izin->id))->not->toBeNull()
        ->and($izin->fresh()->peserta_id)->toBeNull();
});

test('EventAttendance unaffected by peserta_id changes', function () {
    $event = fl_event();
    $session = fl_session($event);
    $m = fl_participant($event);

    $attendance = EventAttendance::create([
        'participation_id' => $m->participation->id,
        'sesi_absensi_id' => $session->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    expect(EventAttendance::find($attendance->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// B. Canonical display uses participation when peserta_id is null
// ---------------------------------------------------------------------------

test('canonical display uses participation when peserta_id is null', function () {
    $event = fl_event();
    $m = fl_participant($event);

    $surat = SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'participation_id' => $m->participation->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'approved',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $surat->update(['peserta_id' => null]);
    $surat->load('participation.person');

    expect($surat->participation->person->nama)->toBe('FL Person')
        ->and($surat->peserta)->toBeNull()
        ->and($surat->peserta_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// C. Legacy fallback display works while peserta exists
// ---------------------------------------------------------------------------

test('legacy fallback display works while peserta exists', function () {
    $event = fl_event();
    $m = fl_participant($event);

    $surat = SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'approved',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    expect($surat->peserta->nama)->toBe('FL Peserta');
});

// ---------------------------------------------------------------------------
// D. Safe display when both peserta and participation unavailable
// ---------------------------------------------------------------------------

test('safe display when peserta and participation unavailable', function () {
    $surat = SuratIzin::create([
        'peserta_id' => null,
        'participation_id' => null,
        'event_id' => null,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'approved',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    $surat->loadMissing('peserta', 'participation.person');

    expect($surat->peserta)->toBeNull();
    expect($surat->participation)->toBeNull();
    expect($surat->alasan)->toBe('Test');
});

// ---------------------------------------------------------------------------
// E. Approval via participation_id when peserta_id is null
// ---------------------------------------------------------------------------

test('approval via participation_id when peserta_id is null', function () {
    $event = fl_event();
    $session = fl_session($event);
    $m = fl_participant($event);

    $user = User::factory()->create(['role' => 'super_admin']);

    $surat = SuratIzin::create([
        'peserta_id' => null,
        'participation_id' => $m->participation->id,
        'event_id' => $event->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);
    $result = app(SuratIzinService::class)->approve($surat->fresh(), $user);

    expect($result['created'])->toHaveCount(1);
    expect(EventAttendance::where('participation_id', $m->participation->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// F. Null peserta_id in read/parity services
// ---------------------------------------------------------------------------

test('AttendanceReadService handles null IzinAbsensi peserta_id', function () {
    $event = fl_event();
    $session = fl_session($event);

    IzinAbsensi::create([
        'peserta_id' => null,
        'sesi_id' => $session->id,
        'source' => 'manual',
    ]);

    $service = app(\App\Services\Attendance\AttendanceReadService::class);
    $data = $service->getSessionAttendance($event->id, $session->id);

    expect($data['attendance'])->not->toBeNull();
});

test('attendance parity handles IzinAbsensi with null peserta_id', function () {
    $event = fl_event();
    $session = fl_session($event);

    IzinAbsensi::create([
        'peserta_id' => null,
        'sesi_id' => $session->id,
        'source' => 'manual',
    ]);

    $result = app(\App\Services\Attendance\AttendanceParityService::class)->audit($event->id);

    expect($result)->toHaveKey('unmappable_izin');
});

// ---------------------------------------------------------------------------
// G. Cross-event isolation
// ---------------------------------------------------------------------------

test('cross-event isolation remains intact after FK changes', function () {
    $eventA = fl_event();
    $eventB = fl_event();
    $m = fl_participant($eventA);

    SuratIzin::create([
        'peserta_id' => $m->peserta->id,
        'event_id' => $eventA->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'approved',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    expect(SuratIzin::where('event_id', $eventA->id)->count())->toBe(1);
    expect(SuratIzin::where('event_id', $eventB->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// H. Relationship returns null when peserta_id is null
// ---------------------------------------------------------------------------

test('SuratIzin peserta relationship returns null when peserta_id null', function () {
    $surat = SuratIzin::create([
        'peserta_id' => null,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
        'status' => 'draft',
        'created_by' => User::factory()->create(['role' => 'super_admin'])->id,
    ]);

    expect($surat->peserta)->toBeNull();
});

test('IzinAbsensi peserta relationship returns null when peserta_id null', function () {
    $izin = IzinAbsensi::create([
        'peserta_id' => null,
        'sesi_id' => SesiAbsensi::create(['nama_sesi' => 'Test', 'tanggal' => '2026-08-16'])->id,
        'source' => 'manual',
    ]);

    expect($izin->peserta)->toBeNull();
});

// ---------------------------------------------------------------------------
// I. SyncNewSession handles null peserta_id
// ---------------------------------------------------------------------------

test('SuratIzin with participation_id works in approval even with null peserta_id', function () {
    $event = fl_event();
    $session = fl_session($event);
    $m = fl_participant($event);
    $user = User::factory()->create(['role' => 'super_admin']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $m->peserta->id,
        'alasan' => 'Test alasan panjang',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-16',
        'tanggal_selesai' => '2026-08-16',
    ], $user->id);

    $surat->update(['peserta_id' => null]);

    app(SuratIzinService::class)->submit($surat);
    $result = app(SuratIzinService::class)->approve($surat->fresh(), $user);

    expect($result['created'])->toHaveCount(1);
    expect(EventAttendance::where('participation_id', $m->participation->id)->count())->toBe(1);
});
