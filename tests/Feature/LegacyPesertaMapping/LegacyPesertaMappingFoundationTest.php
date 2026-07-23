<?php

use App\Models\LegacyPesertaMapping;
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

function LPesertaMappingFactory_makeMapping(array $overrides = []): LegacyPesertaMapping
{
    $pesertaId = $overrides['peserta_id'] ?? LPesertaMappingFactory_makePeserta()->id;
    $personId = $overrides['person_id'] ?? LPesertaMappingFactory_makePerson()->id;

    return LegacyPesertaMapping::create(array_merge([
        'peserta_id' => $pesertaId,
        'person_id' => $personId,
        'legacy_nip' => 5001,
        'legacy_participant_number' => 'LEGACY-001',
        'legacy_attendance_code' => 'LEGACY-ATT-001',
        'migrated_at' => now(),
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Schema — transitional (inert columns still physically present)
// ---------------------------------------------------------------------------

test('legacy_peserta_mappings table has expected columns', function () {
    $columns = Schema::getColumnListing('legacy_peserta_mappings');

    // Active contract columns
    expect(in_array('id', $columns))->toBeTrue();
    expect(in_array('peserta_id', $columns))->toBeTrue();
    expect(in_array('person_id', $columns))->toBeTrue();

    // Sprint 2 transitional: inert columns berikut masih ada secara fisik
    // tetapi BUKAN runtime contract — dijadwalkan removal Sprint 3.
    expect(in_array('participation_id', $columns))->toBeTrue();
    expect(in_array('event_id', $columns))->toBeTrue();
    expect(in_array('backfill_batch_id', $columns))->toBeTrue();

    // Snapshot / metadata columns
    expect(in_array('legacy_nip', $columns))->toBeTrue();
    expect(in_array('legacy_participant_number', $columns))->toBeTrue();
    expect(in_array('legacy_attendance_code', $columns))->toBeTrue();
    expect(in_array('migrated_at', $columns))->toBeTrue();
    expect(in_array('created_at', $columns))->toBeTrue();
    expect(in_array('updated_at', $columns))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Model creation
// ---------------------------------------------------------------------------

test('can create a mapping with active contract fields', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    $person = LPesertaMappingFactory_makePerson();

    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'legacy_nip' => 1234,
        'legacy_participant_number' => 'LEG-001',
        'legacy_attendance_code' => 'LEG-ATT-001',
        'migrated_at' => now(),
    ]);

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->legacy_nip)->toBe(1234)
        ->and($mapping->legacy_participant_number)->toBe('LEG-001')
        ->and($mapping->legacy_attendance_code)->toBe('LEG-ATT-001')
        ->and($mapping->migrated_at)->not->toBeNull();
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

// ---------------------------------------------------------------------------
// Inverse hasOne / hasMany relationships
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

// ---------------------------------------------------------------------------
// UNIQUE constraints
// ---------------------------------------------------------------------------

test('peserta_id must be unique', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    LPesertaMappingFactory_makeMapping(['peserta_id' => $peserta->id]);

    expect(fn () => LPesertaMappingFactory_makeMapping(['peserta_id' => $peserta->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// ---------------------------------------------------------------------------
// Mapping integrity
// ---------------------------------------------------------------------------

test('different peserta can have separate mappings', function () {
    $pesertaA = LPesertaMappingFactory_makePeserta(['nama' => 'Peserta A']);
    $pesertaB = LPesertaMappingFactory_makePeserta(['nama' => 'Peserta B']);
    $personA = LPesertaMappingFactory_makePerson(['nama' => 'Person A']);
    $personB = LPesertaMappingFactory_makePerson(['nama' => 'Person B']);

    $mappingA = LPesertaMappingFactory_makeMapping([
        'peserta_id' => $pesertaA->id,
        'person_id' => $personA->id,
    ]);
    $mappingB = LPesertaMappingFactory_makeMapping([
        'peserta_id' => $pesertaB->id,
        'person_id' => $personB->id,
    ]);

    expect($mappingA->exists)->toBeTrue()
        ->and($mappingB->exists)->toBeTrue()
        ->and(LegacyPesertaMapping::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// RestrictOnDelete — FK peserta_id and person_id only
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

// ---------------------------------------------------------------------------
// Cascade-free guarantee
// ---------------------------------------------------------------------------

test('deleting unmapped entity is unaffected', function () {
    $peserta = LPesertaMappingFactory_makePeserta();
    $person = LPesertaMappingFactory_makePerson();

    expect($peserta->delete())->toBeTrue();
    expect($person->delete())->toBeTrue();
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
