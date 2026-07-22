<?php

use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Console\Commands\AttendanceBackfill;
use App\Services\Attendance\AttendanceBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function br_event(): Event
{
    return Event::create(['name' => 'BR Event ' . str()->random(6), 'slug' => 'br-' . str()->random(6), 'status' => 'active']);
}

function br_person(): Person
{
    return Person::create(['nama' => 'BR Person ' . str()->random(6)]);
}

function br_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'BR Sesi', 'tanggal' => '2026-08-11', 'aktif' => true]);
}

function br_peserta(array $o = []): peserta
{
    return peserta::create(array_merge([
        'nama' => 'BR ' . str()->random(6), 'nip' => random_int(80000, 99999),
        'attendance_code' => 'KJA-BR-' . str()->random(6),
        'participant_number' => 'KL' . random_int(100, 999),
        'status_registrasi' => 'Belum Registrasi',
    ], $o));
}

function br_mapping(peserta $p, Person $person, Participation $part, Event $event): LegacyPesertaMapping
{
    return LegacyPesertaMapping::create([
        'peserta_id' => $p->id, 'person_id' => $person->id,
        'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// A. SuratIzin dry-run does not crash
// ---------------------------------------------------------------------------

test('dry-run with SuratIzin mappable does not crash', function () {
    $event = br_event();
    $person = br_person();
    $peserta = br_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    br_mapping($peserta, $person, $participation, $event);
    $user = User::factory()->create(['role' => 'super_admin']);

    $session = br_session($event);
    \App\Models\IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-11',
        'tanggal_selesai' => '2026-08-11',
        'status' => 'approved',
        'created_by' => $user->id,
    ]);

    $result = app(AttendanceBackfillService::class)->dryRunSuratIzin();

    expect($result)->toHaveKey('updated')
        ->and($result['updated'])->toBe(0)
        ->and($result['total'])->toBe(1)
        ->and($result['mapped'])->toBe(1);
});

// ---------------------------------------------------------------------------
// B. Dry-run does not modify SuratIzin.event_id
// ---------------------------------------------------------------------------

test('dry-run does not modify SuratIzin event_id', function () {
    $event = br_event();
    $person = br_person();
    $peserta = br_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    br_mapping($peserta, $person, $participation, $event);
    $user = User::factory()->create(['role' => 'super_admin']);

    $session = br_session($event);
    \App\Models\IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-11',
        'tanggal_selesai' => '2026-08-11',
        'status' => 'approved',
        'created_by' => $user->id,
    ]);

    app(AttendanceBackfillService::class)->dryRunSuratIzin();

    expect($surat->fresh()->event_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// C. Force mode updates SuratIzin.event_id
// ---------------------------------------------------------------------------

test('force mode updates SuratIzin event_id', function () {
    $event = br_event();
    $person = br_person();
    $peserta = br_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    br_mapping($peserta, $person, $participation, $event);
    $user = User::factory()->create(['role' => 'super_admin']);

    $session = br_session($event);
    \App\Models\IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-11',
        'tanggal_selesai' => '2026-08-11',
        'status' => 'approved',
        'created_by' => $user->id,
    ]);

    app(AttendanceBackfillService::class)->backfillSuratIzin();

    expect($surat->fresh()->event_id)->toBe($event->id);
});

// ---------------------------------------------------------------------------
// D. Comprehensive dry-run with all three sections
// ---------------------------------------------------------------------------

test('dry-run with all three sections completes without crash', function () {
    $event = br_event();
    $session = br_session($event);
    $person = br_person();
    $peserta = br_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    br_mapping($peserta, $person, $participation, $event);
    $user = User::factory()->create(['role' => 'super_admin']);

    \App\Models\Absensi::create(['nip' => $peserta->nip, 'nama' => 'X', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    \App\Models\IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    SuratIzin::create([
        'peserta_id' => $peserta->id, 'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-11', 'tanggal_selesai' => '2026-08-11',
        'status' => 'approved', 'created_by' => $user->id,
    ]);

    $hadir = app(AttendanceBackfillService::class)->dryRunHadir();
    $izin = app(AttendanceBackfillService::class)->dryRunIzin();
    $surat = app(AttendanceBackfillService::class)->dryRunSuratIzin();

    expect($hadir)->toHaveKey('total_scanned')
        ->and($hadir)->toHaveKey('mapped');
    expect($izin)->toHaveKey('total_scanned')
        ->and($izin)->toHaveKey('mapped');
    expect($surat)->toHaveKey('total')
        ->and($surat)->toHaveKey('updated')
        ->and($surat)->toHaveKey('mapped');
});

// ---------------------------------------------------------------------------
// E. Force mode updated count matches mapped for SuratIzin
// ---------------------------------------------------------------------------

test('force mode SuratIzin updated count matches mapped', function () {
    $event = br_event();
    $person = br_person();
    $peserta = br_peserta();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    br_mapping($peserta, $person, $participation, $event);
    $user = User::factory()->create(['role' => 'super_admin']);

    $session = br_session($event);
    \App\Models\IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);

    SuratIzin::create([
        'peserta_id' => $peserta->id, 'alasan' => 'Test',
        'tanggal_mulai' => '2026-08-11', 'tanggal_selesai' => '2026-08-11',
        'status' => 'approved', 'created_by' => $user->id,
    ]);

    $result = app(AttendanceBackfillService::class)->backfillSuratIzin();

    expect($result['updated'])->toBe(1)
        ->and($result['updated'])->toBe($result['mapped']);
});
