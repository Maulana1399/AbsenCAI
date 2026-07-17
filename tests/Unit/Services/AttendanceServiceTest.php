<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('process scan records successful attendance by attendance code', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Scan',
        'nip' => 1001,
        'attendance_code' => 'KJA-SCAN123',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Pagi',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $result = app(AttendanceService::class)->processScan('kja-scan123');

    expect($result['status'])->toBe('success')
        ->and($result['peserta']->is($participant))->toBeTrue()
        ->and($result['sesi']->is($session))->toBeTrue()
        ->and($result['absensi'])->toBeInstanceOf(Absensi::class);

    $this->assertDatabaseHas('absensis', [
        'nip' => 1001,
        'nama' => 'Peserta Scan',
        'sesi_id' => $session->id,
    ]);
});

test('process scan prevents duplicate attendance in the same session', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Duplicate',
        'nip' => 1002,
        'attendance_code' => 'KJA-DUPL123',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Duplicate',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);
    Absensi::create([
        'nip' => $participant->nip,
        'nama' => $participant->nama,
        'jam_scan' => '2026-07-15 08:00:00',
        'sesi_id' => $session->id,
    ]);

    $result = app(AttendanceService::class)->processScan('KJA-DUPL123', $session->id);

    expect($result['status'])->toBe('duplicate')
        ->and($result['peserta']->is($participant))->toBeTrue()
        ->and($result['sesi']->is($session))->toBeTrue();

    expect(Absensi::where('nip', $participant->nip)->where('sesi_id', $session->id)->count())->toBe(1);
});

test('process scan requires an active session after participant is found', function () {
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    peserta::create([
        'nama' => 'Peserta No Session',
        'nip' => 1003,
        'attendance_code' => 'KJA-NOSESS1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $result = app(AttendanceService::class)->processScan('KJA-NOSESS1');

    expect($result['status'])->toBe('session_required')
        ->and($result['message'])->toBe('Pilih sesi absensi terlebih dahulu');

    expect(Absensi::count())->toBe(0);
});

test('process scan returns not found for an invalid identifier', function () {
    $result = app(AttendanceService::class)->processScan('unknown-identifier');

    expect($result['status'])->toBe('not_found')
        ->and($result['message'])->toBe('Data peserta tidak ditemukan!');

    expect(Absensi::count())->toBe(0);
});

test('process scan still accepts legacy nip fallback', function () {
    // Protects legacy NIP scan behavior until the S04 identity migration is complete.
    $event = Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);

    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Legacy Nip',
        'nip' => 1999,
        'attendance_code' => 'KJA-LEGACY1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi Legacy',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $result = app(AttendanceService::class)->processScan('1999');

    expect($result['status'])->toBe('success')
        ->and($result['peserta']->is($participant))->toBeTrue();

    $this->assertDatabaseHas('absensis', [
        'nip' => 1999,
        'sesi_id' => $session->id,
    ]);
});
