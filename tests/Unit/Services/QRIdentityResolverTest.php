<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\QR\QRIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function qrIdentityTest_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-' . str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function qrIdentityTest_makeMappedLegacyPeserta(array $overrides, Event $event): array
{
    $participant = peserta::create(array_merge([
        'jenis_kelamin' => 'Laki - Laki',
    ], $overrides));

    $person = Person::create([
        'nama' => $participant->nama,
        'nip' => $participant->nip,
        'jenis_kelamin' => $participant->jenis_kelamin === 'Perempuan' ? 'P' : 'L',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'attendance_code' => $participant->attendance_code,
        'participant_number' => $participant->participant_number,
        'jenis_peserta' => 'Wajib',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'legacy_nip' => $participant->nip,
        'legacy_attendance_code' => $participant->attendance_code,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $participant->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    return [$participant, $person, $participation];
}

test('resolve participation by attendance code', function () {
    $event = qrIdentityTest_makeEvent();
    [$participant, $person, $participation] = qrIdentityTest_makeMappedLegacyPeserta([
        'nama' => 'QR Person',
        'nip' => 3001,
        'attendance_code' => 'KJA-QR00001',
    ], $event);

    $resolved = app(QRIdentityResolver::class)->resolve('KJA-QR00001');

    expect($resolved?->is($participation))->toBeTrue()
        ->and($resolved?->person?->is($person))->toBeTrue();
});

test('resolve attendance code case insensitively', function () {
    $event = qrIdentityTest_makeEvent();
    [, , $participation] = qrIdentityTest_makeMappedLegacyPeserta([
        'nama' => 'QR Person 2',
        'nip' => 3002,
        'attendance_code' => 'KJA-QR00002',
    ], $event);

    $resolved = app(QRIdentityResolver::class)->resolve('kja-qr00002');

    expect($resolved?->is($participation))->toBeTrue();
});

test('resolve rejects wrong active event', function () {
    $eventA = qrIdentityTest_makeEvent();
    $eventB = qrIdentityTest_makeEvent(['slug' => 'event-b-' . str()->random(6)]);
    [, , $participationB] = qrIdentityTest_makeMappedLegacyPeserta([
        'nama' => 'QR Person B',
        'nip' => 3003,
        'attendance_code' => 'KJA-QR00003',
    ], $eventB);

    expect(app(QRIdentityResolver::class)->resolve('KJA-QR00003', $eventA))->toBeNull();
    expect(app(QRIdentityResolver::class)->resolvePerson('KJA-QR00003', $eventA))->toBeNull();
});

test('legacy mapped peserta resolves to participation', function () {
    $event = qrIdentityTest_makeEvent();
    [$participant, $person, $participation] = qrIdentityTest_makeMappedLegacyPeserta([
        'nama' => 'Legacy QR',
        'nip' => 3004,
        'attendance_code' => 'KJA-LEGQR1',
    ], $event);

    expect(app(QRIdentityResolver::class)->resolve('KJA-LEGQR1', $event)?->is($participation))->toBeTrue()
        ->and(app(QRIdentityResolver::class)->resolvePerson('KJA-LEGQR1', $event)?->is($person))->toBeTrue();
});

test('missing legacy mapping is rejected', function () {
    $event = qrIdentityTest_makeEvent();
    peserta::create(['nama' => 'No Mapping', 'nip' => 3005, 'attendance_code' => 'KJA-NOMAP', 'jenis_kelamin' => 'Laki - Laki']);

    expect(app(QRIdentityResolver::class)->resolve('KJA-NOMAP', $event))->toBeNull();
});

test('broken mapping is rejected', function () {
    $event = qrIdentityTest_makeEvent();
    $other = qrIdentityTest_makeEvent(['slug' => 'other-' . str()->random(6)]);
    $participant = peserta::create(['nama' => 'Broken', 'nip' => 3006, 'attendance_code' => 'KJA-BROKEN', 'jenis_kelamin' => 'Laki - Laki']);
    $person = Person::create(['nama' => 'Broken', 'nip' => 3006, 'jenis_kelamin' => 'L']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $other->id, 'attendance_code' => 'KJA-BROKEN', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id]);
    LegacyParticipationMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id]);

    expect(app(QRIdentityResolver::class)->resolve('KJA-BROKEN', $event))->toBeNull();
});

test('same person with participations in two events resolves event specific identity', function () {
    $person = Person::create(['nama' => 'Multi Event Person', 'nip' => 3007, 'jenis_kelamin' => 'L']);
    $eventA = qrIdentityTest_makeEvent();
    $eventB = qrIdentityTest_makeEvent(['slug' => 'event-b-' . str()->random(6)]);

    $participationA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'attendance_code' => 'KJA-MULTI-A', 'participant_number' => 'KL001', 'jenis_peserta' => 'Wajib']);
    $participationB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'attendance_code' => 'KJA-MULTI-B', 'participant_number' => 'KL002', 'jenis_peserta' => 'Wajib']);

    expect(app(QRIdentityResolver::class)->resolve('KJA-MULTI-A', $eventA)?->is($participationA))->toBeTrue()
        ->and(app(QRIdentityResolver::class)->resolve('KJA-MULTI-B', $eventB)?->is($participationB))->toBeTrue()
        ->and(app(QRIdentityResolver::class)->resolve('KJA-MULTI-A', $eventB))->toBeNull();
});
