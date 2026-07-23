<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

afterEach(function () {
    Str::createRandomStringsNormally();
});

function s41_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'S41 Event',
        'slug' => 's41-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function s41_seedParticipant(array $participantOverrides = [], array $personOverrides = [], array $participationOverrides = []): array
{
    $event = $participationOverrides['event_id'] ?? s41_makeEvent();
    $participant = peserta::create(array_merge([
        'nama' => 'Legacy Person',
        'nip' => random_int(1000, 9999),
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-S41AAA1',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Belum Registrasi',
    ], $participantOverrides));
    $person = Person::create(array_merge([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => 'L',
    ], $personOverrides));
    $participation = Participation::create(array_merge([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => $participant->participant_number,
        'attendance_code' => $participant->attendance_code,
        'jenis_peserta' => $participant->jenis_peserta,
    ], $participationOverrides));
    LegacyPesertaMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'legacy_nip' => $participant->nip,
        'legacy_participant_number' => $participant->participant_number,
        'legacy_attendance_code' => $participant->attendance_code,
        'migrated_at' => now(),
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'migrated_at' => now(),
    ]);

    return [$participant, $person, $participation, $event];
}

test('mapped legacy update syncs person but preserves participation identifiers', function () {
    [$participant, $person, $participation, $event] = s41_seedParticipant();
    app(ActiveEventContext::class)->set($event);

    $beforeNumber = $participation->participant_number;
    $beforeCode = $participation->attendance_code;

    app(RegistrationService::class)->updateParticipant($participant->id, [
        'nama' => 'Legacy Updated',
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => 'Kiriman',
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => null,
    ]);

    expect($person->fresh()->nama)->toBe('Legacy Updated')
        ->and($person->fresh()->jenis_kelamin)->toBe('P')
        ->and($participation->fresh()->participant_number)->toBe($beforeNumber)
        ->and($participation->fresh()->attendance_code)->toBe($beforeCode)
        ->and($participation->fresh()->jenis_peserta)->toBe('Kiriman')
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(1);
});

test('same person across multiple events keeps each participation stable on legacy update', function () {
    $eventA = s41_makeEvent(['slug' => 's41-event-a']);
    $eventB = s41_makeEvent(['slug' => 's41-event-b']);
    $person = Person::create(['nama' => 'Multi Event Person', 'nip' => 5001, 'jenis_kelamin' => 'L']);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KL101', 'attendance_code' => 'KJA-S41A01', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KL102', 'attendance_code' => 'KJA-S41B01', 'jenis_peserta' => 'Wajib']);
    $legacyA = peserta::create(['nama' => 'Legacy A', 'nip' => 5001, 'participant_number' => 'KL101', 'attendance_code' => 'KJA-S41A01', 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'status_registrasi' => 'Belum Registrasi']);
    LegacyParticipationMapping::create(['peserta_id' => $legacyA->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);
    LegacyPesertaMapping::create(['peserta_id' => $legacyA->id, 'person_id' => $person->id, 'legacy_nip' => 5001, 'legacy_participant_number' => 'KL101', 'legacy_attendance_code' => 'KJA-S41A01', 'migrated_at' => now()]);
    app(ActiveEventContext::class)->set($eventA);

    app(RegistrationService::class)->updateParticipant($legacyA->id, [
        'nama' => 'Multi Event Updated',
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => 'Kiriman',
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => null,
    ]);

    expect($person->fresh()->nama)->toBe('Multi Event Updated')
        ->and($partA->fresh()->participant_number)->toBe('KL101')
        ->and($partA->fresh()->attendance_code)->toBe('KJA-S41A01')
        ->and($partB->fresh()->participant_number)->toBe('KL102')
        ->and($partB->fresh()->attendance_code)->toBe('KJA-S41B01')
        ->and(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(2)
        ->and(LegacyPesertaMapping::count())->toBe(1);
});

test('registration service still creates normalized participant bundle', function () {
    Str::createRandomStringsUsing(fn () => 's41aaaa1');
    $event = s41_makeEvent();
    app(ActiveEventContext::class)->set($event);

    $participant = app(RegistrationService::class)->createParticipant([
        'nama' => 'Fresh Registrant',
        'nip' => 7001,
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => null,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $bridge = LegacyParticipationMapping::with('participation.person')->where('peserta_id', $participant->id)->first();
    $mapping = LegacyPesertaMapping::with('person')->where('peserta_id', $participant->id)->first();

    expect($participant->participant_number)->toBe('KP001')
        ->and($participant->attendance_code)->toBe('KJA-S41AAAA1')
        ->and($bridge?->participation?->participant_number)->toBe('KP001')
        ->and($bridge?->participation?->attendance_code)->toBe('KJA-S41AAAA1')
        ->and($mapping?->person?->nama)->toBe('Fresh Registrant');
});
