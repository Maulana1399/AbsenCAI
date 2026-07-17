<?php

use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function LPesertaMappingFactory_makePeserta(array $overrides = []): peserta
{
    return peserta::create(array_merge([
        'nama' => 'Legacy Peserta',
        'nip' => random_int(1000, 9999),
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => 'Belum Registrasi',
    ], $overrides));
}

function LPesertaMappingFactory_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function LPesertaMappingFactory_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-' . str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function LPesertaMappingFactory_makeParticipation(array $overrides = []): Participation
{
    static $personCounter = 0;
    static $eventCounter = 0;
    $personCounter++;
    $eventCounter++;

    if (! array_key_exists('person_id', $overrides)) {
        $overrides['person_id'] = LPesertaMappingFactory_makePerson(['nama' => 'Person '.$personCounter])->id;
    }

    if (! array_key_exists('event_id', $overrides)) {
        $overrides['event_id'] = LPesertaMappingFactory_makeEvent(['name' => 'Event '.$eventCounter])->id;
    }

    return Participation::create(array_merge([
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

function LPesertaMappingFactory_makeMapping(array $overrides = []): LegacyPesertaMapping
{
    $pesertaId = $overrides['peserta_id'] ?? LPesertaMappingFactory_makePeserta()->id;
    $personId = $overrides['person_id'] ?? LPesertaMappingFactory_makePerson()->id;
    $eventId = $overrides['event_id'] ?? LPesertaMappingFactory_makeEvent()->id;
    $participationId = $overrides['participation_id'] ?? LPesertaMappingFactory_makeParticipation([
        'person_id' => $personId,
        'event_id' => $eventId,
    ])->id;

    return LegacyPesertaMapping::create(array_merge([
        'peserta_id' => $pesertaId,
        'person_id' => $personId,
        'participation_id' => $participationId,
        'event_id' => $eventId,
        'legacy_nip' => 5001,
        'legacy_participant_number' => 'LEGACY-001',
        'legacy_attendance_code' => 'LEGACY-ATT-001',
        'migrated_at' => now(),
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Schema
// ---------------------------------------------------------------------------

test('legacy_peserta_mappings table has expected columns', function () {
    $columns = Schema::getColumnListing('legacy_peserta_mappings');
    $expected = [
        'id', 'peserta_id', 'person_id', 'participation_id', 'event_id',
        'backfill_batch_id', 'legacy_nip', 'legacy_participant_number',
        'legacy_attendance_code', 'migrated_at', 'created_at', 'updated_at',
    ];

    expect($columns)->toMatchArray($expected);
});

// ---------------------------------------------------------------------------
// Model creation
// ---------------------------------------------------------------------------

test('can create a mapping with all fields', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    $person = LPesertaMappingFactory_makePerson();
    $event = LPesertaMappingFactory_makeEvent();
    $participation = LPesertaMappingFactory_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'backfill_batch_id' => 'batch-001',
        'legacy_nip' => 1234,
        'legacy_participant_number' => 'LEG-001',
        'legacy_attendance_code' => 'LEG-ATT-001',
        'migrated_at' => now(),
    ]);

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->backfill_batch_id)->toBe('batch-001')
        ->and($mapping->legacy_nip)->toBe(1234)
        ->and($mapping->legacy_participant_number)->toBe('LEG-001')
        ->and($mapping->legacy_attendance_code)->toBe('LEG-ATT-001')
        ->and($mapping->migrated_at)->not->toBeNull();
});

test('backfill_batch_id is nullable', function () {
    $mapping = LPesertaMappingFactory_makeMapping(['backfill_batch_id' => null]);

    expect($mapping->backfill_batch_id)->toBeNull();
});

test('snapshot fields are nullable', function () {
    $mapping = LPesertaMappingFactory_makeMapping([
        'legacy_nip' => null,
        'legacy_participant_number' => null,
        'legacy_attendance_code' => null,
    ]);

    expect($mapping->legacy_nip)->toBeNull()
        ->and($mapping->legacy_participant_number)->toBeNull()
        ->and($mapping->legacy_attendance_code)->toBeNull();
});

test('migrated_at is nullable', function () {
    $mapping = LPesertaMappingFactory_makeMapping(['migrated_at' => null]);

    expect($mapping->migrated_at)->toBeNull();
});

// ---------------------------------------------------------------------------
// Belongs to relationships
// ---------------------------------------------------------------------------

test('mapping belongs to peserta', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect($mapping->peserta)->not->toBeNull()
        ->and($mapping->peserta->exists)->toBeTrue()
        ->and($mapping->peserta->nama)->toBe('Legacy Peserta');
});

test('mapping belongs to person', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect($mapping->person)->not->toBeNull()
        ->and($mapping->person->exists)->toBeTrue()
        ->and($mapping->person->nama)->toBe('Test Person');
});

test('mapping belongs to participation', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect($mapping->participation)->not->toBeNull()
        ->and($mapping->participation->exists)->toBeTrue();
});

test('mapping belongs to event', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect($mapping->event)->not->toBeNull()
        ->and($mapping->event->exists)->toBeTrue()
        ->and($mapping->event->name)->toBe('Test Event');
});

// ---------------------------------------------------------------------------
// Inverse hasOne/hasMany relationships
// ---------------------------------------------------------------------------

test('peserta has one mapping', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    LPesertaMappingFactory_makeMapping(['peserta_id' => $peserta->id]);

    $peserta->load('legacyPesertaMapping');

    expect($peserta->legacyPesertaMapping)->not->toBeNull()
        ->and($peserta->legacyPesertaMapping->peserta_id)->toBe($peserta->id);
});

test('person has one mapping', function () {
    $person = LPesertaMappingFactory_makePerson();
    LPesertaMappingFactory_makeMapping(['person_id' => $person->id]);

    $person->load('legacyPesertaMapping');

    expect($person->legacyPesertaMapping)->not->toBeNull()
        ->and($person->legacyPesertaMapping->person_id)->toBe($person->id);
});

test('participation has one mapping', function () {
    $person = LPesertaMappingFactory_makePerson();
    $event = LPesertaMappingFactory_makeEvent();
    $participation = LPesertaMappingFactory_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);
    LPesertaMappingFactory_makeMapping([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participation_id' => $participation->id,
    ]);

    $participation->load('legacyPesertaMapping');

    expect($participation->legacyPesertaMapping)->not->toBeNull()
        ->and($participation->legacyPesertaMapping->participation_id)->toBe($participation->id);
});

test('event has many mappings', function () {
    $event = LPesertaMappingFactory_makeEvent();
    $pesertaA = LPesertaMappingFactory_makePeserta(['nama' => 'Peserta A']);
    $pesertaB = LPesertaMappingFactory_makePeserta(['nama' => 'Peserta B']);
    $personA = LPesertaMappingFactory_makePerson(['nama' => 'Person A']);
    $personB = LPesertaMappingFactory_makePerson(['nama' => 'Person B']);

    LPesertaMappingFactory_makeMapping([
        'peserta_id' => $pesertaA->id,
        'person_id' => $personA->id,
        'event_id' => $event->id,
    ]);
    LPesertaMappingFactory_makeMapping([
        'peserta_id' => $pesertaB->id,
        'person_id' => $personB->id,
        'event_id' => $event->id,
    ]);

    $event->load('legacyPesertaMappings');

    expect($event->legacyPesertaMappings->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// UNIQUE constraints
// ---------------------------------------------------------------------------

test('peserta_id must be unique', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    LPesertaMappingFactory_makeMapping(['peserta_id' => $peserta->id]);

    expect(fn () => LPesertaMappingFactory_makeMapping(['peserta_id' => $peserta->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('participation_id must be unique', function () {
    $person = LPesertaMappingFactory_makePerson();
    $event = LPesertaMappingFactory_makeEvent();
    $participation = LPesertaMappingFactory_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);
    LPesertaMappingFactory_makeMapping([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participation_id' => $participation->id,
    ]);

    expect(fn () => LPesertaMappingFactory_makeMapping([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participation_id' => $participation->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('different peserta can map to different participations', function () {
    $pesertaA = LPesertaMappingFactory_makePeserta(['nama' => 'Peserta A']);
    $pesertaB = LPesertaMappingFactory_makePeserta(['nama' => 'Peserta B']);
    $personA = LPesertaMappingFactory_makePerson(['nama' => 'Person A']);
    $personB = LPesertaMappingFactory_makePerson(['nama' => 'Person B']);
    $event = LPesertaMappingFactory_makeEvent();

    $mappingA = LPesertaMappingFactory_makeMapping([
        'peserta_id' => $pesertaA->id,
        'person_id' => $personA->id,
        'event_id' => $event->id,
    ]);
    $mappingB = LPesertaMappingFactory_makeMapping([
        'peserta_id' => $pesertaB->id,
        'person_id' => $personB->id,
        'event_id' => $event->id,
    ]);

    expect($mappingA->exists)->toBeTrue()
        ->and($mappingB->exists)->toBeTrue()
        ->and(LegacyPesertaMapping::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// RestrictOnDelete — all four FK parents
// ---------------------------------------------------------------------------

test('deleting mapped peserta is prevented', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect(fn () => $mapping->peserta->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(LegacyPesertaMapping::count())->toBe(1);
});

test('deleting mapped person is prevented', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect(fn () => $mapping->person->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(LegacyPesertaMapping::count())->toBe(1);
});

test('deleting mapped participation is prevented', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect(fn () => $mapping->participation->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(LegacyPesertaMapping::count())->toBe(1);
});

test('deleting mapped event is prevented', function () {
    $mapping = LPesertaMappingFactory_makeMapping();

    expect(fn () => $mapping->event->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(LegacyPesertaMapping::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Cascade-free guarantee
// ---------------------------------------------------------------------------

test('deleting unmapped entity is unaffected', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    $person = LPesertaMappingFactory_makePerson();
    $event = LPesertaMappingFactory_makeEvent();
    $participation = LPesertaMappingFactory_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect($peserta->delete())->toBeTrue();
    expect($participation->delete())->toBeTrue();
    expect($person->delete())->toBeTrue();
    expect($event->delete())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Existing foundations remain unaffected
// ---------------------------------------------------------------------------

test('existing peserta-only architecture is unchanged with mapping', function () {
    $peserta = LPesertaMappingFactory_makePeserta();

    expect($peserta->exists)->toBeTrue()
        ->and(peserta::count())->toBe(1)
        ->and(LegacyPesertaMapping::count())->toBe(0);
});
