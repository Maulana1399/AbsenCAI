<?php

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Registration\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

afterEach(function () {
    Str::createRandomStringsNormally();
});

test('generate attendance code uses the existing KJA format', function () {
    Str::createRandomStringsUsing(fn () => 'abc123xy');

    expect(app(RegistrationService::class)->generateAttendanceCode())->toBe('KJA-ABC123XY');
});

test('generate attendance code skips existing codes', function () {
    peserta::create([
        'nama' => 'Peserta Existing',
        'nip' => 1001,
        'attendance_code' => 'KJA-AAAAAAAA',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    Str::createRandomStringsUsingSequence(['AAAAAAAA', 'BBBBBBBB']);

    expect(app(RegistrationService::class)->generateAttendanceCode())->toBe('KJA-BBBBBBBB');
});

test('create participant stores generated participant number and attendance code', function () {
    Str::createRandomStringsUsing(fn () => 'reg12345');

    $desa = desa::create(['desa_asal' => 'Desa Registration']);
    $kelompok = kelompok::create([
        'kelompok_asal' => 'Kelompok Registration',
        'desa_id' => $desa->id,
    ]);
    $regu = regu::create([
        'regu' => 'Regu Registration',
        'jenis_kelamin' => 'Perempuan',
    ]);

    $participant = app(RegistrationService::class)->createParticipant([
        'nama' => 'Peserta Registration',
        'nip' => 2001,
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    expect($participant->participant_number)->toBe('KP001')
        ->and($participant->attendance_code)->toBe('KJA-REG12345');

    $this->assertDatabaseHas('pesertas', [
        'id' => $participant->id,
        'participant_number' => 'KP001',
        'attendance_code' => 'KJA-REG12345',
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);
});

test('update participant persists changed identity fields', function () {
    $participant = peserta::create([
        'nama' => 'Peserta Lama',
        'nip' => 1001,
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $updated = app(RegistrationService::class)->updateParticipant($participant->id, [
        'nama' => 'Peserta Baru',
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => null,
    ]);

    expect($updated->nama)->toBe('Peserta Baru')
        ->and($updated->jenis_kelamin)->toBe('Perempuan');
});
