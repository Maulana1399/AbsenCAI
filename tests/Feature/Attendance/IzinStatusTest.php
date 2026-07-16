<?php

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

it('records izin attendance exception', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Izin',
        'nip' => 5001,
        'attendance_code' => 'KJA-IZIN001',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Izin',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    $record = app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id);

    expect($record)->toBeInstanceOf(IzinAbsensi::class)
        ->and($record->peserta_id)->toBe($participant->id)
        ->and($record->sesi_id)->toBe($session->id)
        ->and($record->status)->toBe('izin');

    $this->assertDatabaseHas('izin_absensis', [
        'peserta_id' => $participant->id,
        'sesi_id' => $session->id,
        'status' => 'izin',
    ]);
});

it('rejects duplicate izin for same participant and session', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Izin Duplicate',
        'nip' => 5002,
        'attendance_code' => 'KJA-IZIN002',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Izin Duplicate',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id);

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id))
        ->toThrow(ValidationException::class);
});

it('prevents hadir participant from becoming izin', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Hadir Lalu Izin',
        'nip' => 5003,
        'attendance_code' => 'KJA-HADIRIZIN',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Hadir Izin',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    Absensi::create([
        'nip' => $participant->nip,
        'nama' => $participant->nama,
        'jam_scan' => '2026-07-16 08:00:00',
        'sesi_id' => $session->id,
    ]);

    expect(fn () => app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id))
        ->toThrow(ValidationException::class);
});

it('prevents hadir recording when participant is already izin', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Izin Lalu Hadir',
        'nip' => 5004,
        'attendance_code' => 'KJA-IZINHADIR',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Izin Hadir',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    app(AttendanceExceptionService::class)->recordIzin($participant->id, $session->id);

    expect(fn () => app(AttendanceService::class)->processScan('KJA-IZINHADIR', $session->id))
        ->toThrow(ValidationException::class);
});
