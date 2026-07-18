<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
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

test('create participant stores normalized participation identifiers', function () {
    Str::createRandomStringsUsing(fn () => 'reg12345');

    $event = Event::create(['name' => 'Registration Event', 'slug' => 'registration-event', 'status' => 'active']);
    app(ActiveEventContext::class)->set($event);

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

    $mapping = LegacyPesertaMapping::with('participation.person')->where('peserta_id', $participant->id)->first();

    expect($participant->participant_number)->toBe('KP001')
        ->and($participant->attendance_code)->toBe('KJA-REG12345')
        ->and($mapping?->participation?->participant_number)->toBe('KP001')
        ->and($mapping?->participation?->attendance_code)->toBe('KJA-REG12345')
        ->and($mapping?->person?->nama)->toBe('Peserta Registration');
});

test('update participant persists changed identity fields to mapped participation', function () {
    $event = Event::create(['name' => 'Registration Event Update', 'slug' => 'registration-event-update', 'status' => 'active']);
    app(ActiveEventContext::class)->set($event);

    $participant = peserta::create([
        'nama' => 'Peserta Lama',
        'nip' => 1001,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-OLD0001',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    $person = Person::create(['nama' => 'Peserta Lama', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-OLD0001', 'jenis_peserta' => peserta::JENIS_WAJIB]);
    LegacyPesertaMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id]);

    $updated = app(RegistrationService::class)->updateParticipant($participant->id, [
        'nama' => 'Peserta Baru',
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => null,
    ]);

    expect($updated->nama)->toBe('Peserta Baru')
        ->and($updated->jenis_kelamin)->toBe('Perempuan')
        ->and($participation->fresh()->jenis_peserta)->toBe(peserta::JENIS_KIRIMAN);
});
