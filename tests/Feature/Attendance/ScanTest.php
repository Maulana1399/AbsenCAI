<?php

use App\Livewire\Dashboard\Scan;
use App\Models\Absensi;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use Livewire\Livewire;

it('Scan orchestration handles successful attendance scan', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Livewire',
        'nip' => 3001,
        'participant_number' => 'PN-3001',
        'attendance_code' => 'KJA-LWSCAN1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Livewire',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->call('scanPeserta', 'kja-lwscan1');

    $response->assertSet('message', 'Absensi berhasil!');
    $response->assertSet('nip', (string) $participant->nip);
    $response->assertSet('nama', 'Peserta Livewire');
    $response->assertNotSet('jam_scan', null);

    $this->assertDatabaseHas('absensis', [
        'nip' => $participant->nip,
        'nama' => $participant->nama,
        'sesi_id' => $session->id,
    ]);
});

it('Scan orchestration preserves duplicate attendance prevention', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Duplicate Livewire',
        'nip' => 3002,
        'participant_number' => 'PN-3002',
        'attendance_code' => 'KJA-LWDUP1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Duplicate Livewire',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    Absensi::create([
        'nip' => $participant->nip,
        'nama' => $participant->nama,
        'jam_scan' => '2026-07-15 08:00:00',
        'sesi_id' => $session->id,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->call('scanPeserta', 'KJA-LWDUP1');

    $response->assertSet('message', 'Peserta sudah absen pada sesi ini');
    $response->assertSet('nip', (string) $participant->nip);
    $response->assertSet('nama', 'Peserta Duplicate Livewire');

    expect(Absensi::where('nip', $participant->nip)->where('sesi_id', $session->id)->count())->toBe(1);
});

it('Scan orchestration handles missing active session', function () {
    peserta::create([
        'nama' => 'Peserta Tanpa Sesi',
        'nip' => 3003,
        'participant_number' => 'PN-3003',
        'attendance_code' => 'KJA-LWNOS1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', null)
        ->call('scanPeserta', 'KJA-LWNOS1');

    $response->assertSet('message', 'Pilih sesi absensi terlebih dahulu');
    $response->assertSet('nama', null);
    $response->assertSet('nip', null);
    $response->assertSet('jam_scan', null);
});

it('Scan orchestration handles invalid identifier', function () {
    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Invalid Livewire',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->call('scanPeserta', 'unknown-identifier');

    $response->assertSet('message', 'Data peserta tidak ditemukan!');
    $response->assertSet('nama', null);
    $response->assertSet('nip', null);
    $response->assertSet('jam_scan', null);
});

it('participant_number is not treated as an attendance scan identifier', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Number Only',
        'nip' => 3004,
        'participant_number' => 'PN-3004',
        'attendance_code' => 'KJA-LWPN01',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Participant Number',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->call('scanPeserta', $participant->participant_number);

    $response->assertSet('message', 'Data peserta tidak ditemukan!');
    $response->assertSet('nama', null);
    $response->assertSet('nip', null);
    $response->assertSet('jam_scan', null);

    expect(Absensi::count())->toBe(0);
});

it('manual attendance flow records success through AttendanceService', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Manual',
        'nip' => 4001,
        'participant_number' => 'PN-4001',
        'attendance_code' => 'KJA-MANUAL1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Manual',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->set('manualSearch', 'Peserta Manual')
        ->call('selectManualParticipant', $participant->id)
        ->call('manualAttend');

    $response->assertSet('message', 'Absensi berhasil!');
    $response->assertSet('nip', (string) $participant->nip);
    $response->assertSet('nama', 'Peserta Manual');
    $response->assertNotSet('jam_scan', null);

    $this->assertDatabaseHas('absensis', [
        'nip' => $participant->nip,
        'nama' => $participant->nama,
        'sesi_id' => $session->id,
    ]);
});

it('manual attendance flow preserves duplicate prevention', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Manual Duplicate',
        'nip' => 4002,
        'participant_number' => 'PN-4002',
        'attendance_code' => 'KJA-MANUAL2',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Manual Duplicate',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    Absensi::create([
        'nip' => $participant->nip,
        'nama' => $participant->nama,
        'jam_scan' => '2026-07-15 08:00:00',
        'sesi_id' => $session->id,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->set('manualSearch', 'Peserta Manual Duplicate')
        ->call('selectManualParticipant', $participant->id)
        ->call('manualAttend');

    $response->assertSet('message', 'Peserta sudah absen pada sesi ini');
    $response->assertSet('nip', (string) $participant->nip);
    $response->assertSet('nama', 'Peserta Manual Duplicate');

    expect(Absensi::where('nip', $participant->nip)->where('sesi_id', $session->id)->count())->toBe(1);
});

it('manual attendance flow handles missing session', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Manual No Session',
        'nip' => 4003,
        'participant_number' => 'PN-4003',
        'attendance_code' => 'KJA-MANUAL3',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', null)
        ->set('manualSearch', 'Peserta Manual No Session')
        ->call('selectManualParticipant', $participant->id)
        ->call('manualAttend');

    $response->assertSet('message', 'Pilih sesi absensi terlebih dahulu');
    $response->assertSet('nama', null);
    $response->assertSet('nip', null);
    $response->assertSet('jam_scan', null);
});

it('manual attendance flow handles invalid participant selection', function () {
    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Manual Invalid',
        'tanggal' => '2026-07-15',
        'aktif' => true,
    ]);

    $response = Livewire::test(Scan::class)
        ->set('sesi_id', $session->id)
        ->set('manualSearch', 'Tidak Ada')
        ->call('manualAttend');

    $response->assertSet('message', 'Pilih peserta terlebih dahulu');
    $response->assertSet('nama', null);
    $response->assertSet('nip', null);
    $response->assertSet('jam_scan', null);

    expect(Absensi::count())->toBe(0);
});
