<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ===========================================================================
// 1. Model Contract — LegacyPesertaMapping final Sprint 3 contract
// ===========================================================================

test('LegacyPesertaMapping fillable hanya berisi active contract fields', function () {
    $fillable = (new LegacyPesertaMapping)->getFillable();

    expect(in_array('peserta_id', $fillable))->toBeTrue()
        ->and(in_array('person_id', $fillable))->toBeTrue()
        ->and(in_array('participation_id', $fillable))->toBeFalse()
        ->and(in_array('event_id', $fillable))->toBeFalse()
        ->and(in_array('backfill_batch_id', $fillable))->toBeFalse();
});

test('LegacyPesertaMapping tidak memiliki participation() atau event() relationships', function () {
    expect(method_exists(new LegacyPesertaMapping, 'participation'))->toBeFalse()
        ->and(method_exists(new LegacyPesertaMapping, 'event'))->toBeFalse();
});

test('LegacyPesertaMapping masih memiliki peserta() dan person() relationships', function () {
    expect(method_exists(new LegacyPesertaMapping, 'peserta'))->toBeTrue()
        ->and(method_exists(new LegacyPesertaMapping, 'person'))->toBeTrue();
});

// ===========================================================================
// 2. Model Contract — Participation has legacyParticipationMapping()
// ===========================================================================

test('Participation memiliki legacyParticipationMapping() relationship', function () {
    expect(method_exists(new Participation, 'legacyParticipationMapping'))->toBeTrue();
});

test('Participation tidak lagi memiliki legacyPesertaMapping() relationship', function () {
    expect(method_exists(new Participation, 'legacyPesertaMapping'))->toBeFalse();
});

// ===========================================================================
// 3. Schema — deprecated columns removed
// ===========================================================================

test('legacy_peserta_mappings table tidak memiliki deprecated columns', function () {
    $columns = Schema::getColumnListing('legacy_peserta_mappings');

    expect(in_array('participation_id', $columns))->toBeFalse()
        ->and(in_array('event_id', $columns))->toBeFalse()
        ->and(in_array('backfill_batch_id', $columns))->toBeFalse();
});

test('legacy_peserta_mappings table masih memiliki active contract columns', function () {
    $columns = Schema::getColumnListing('legacy_peserta_mappings');

    expect(in_array('id', $columns))->toBeTrue()
        ->and(in_array('peserta_id', $columns))->toBeTrue()
        ->and(in_array('person_id', $columns))->toBeTrue()
        ->and(in_array('legacy_nip', $columns))->toBeTrue()
        ->and(in_array('legacy_participant_number', $columns))->toBeTrue()
        ->and(in_array('legacy_attendance_code', $columns))->toBeTrue()
        ->and(in_array('migrated_at', $columns))->toBeTrue();
});

// ===========================================================================
// 4. Creation — LegacyPesertaMapping without deprecated fields
// ===========================================================================

test('LegacyPesertaMapping dibuat tanpa participation_id, event_id, backfill_batch_id', function () {
    $peserta = peserta::create(['nama' => 'Test', 'nip' => 60001, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'status_registrasi' => 'Belum Registrasi']);
    $person = Person::create(['nama' => 'Test Person', 'nip' => 60001, 'jenis_kelamin' => 'L']);

    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'legacy_nip' => 60001,
        'legacy_participant_number' => 'SP3-001',
        'legacy_attendance_code' => 'SP3-ATT-001',
        'migrated_at' => now(),
    ]);

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->peserta_id)->toBe($peserta->id)
        ->and($mapping->person_id)->toBe($person->id)
        ->and($mapping->peserta->id)->toBe($peserta->id)
        ->and($mapping->person->id)->toBe($person->id);
});

// ===========================================================================
// 5. LegacyParticipationMapping sebagai satu-satunya bridge event-specific
// ===========================================================================

test('LegacyParticipationMapping menghubungkan peserta ↔ Participation ↔ Event', function () {
    $event = Event::create(['name' => 'SP3 Event', 'slug' => 'sp3-event', 'status' => 'active', 'event_type' => 'cai']);
    $peserta = peserta::create(['nama' => 'SP3 Peserta', 'nip' => 60002, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'status_registrasi' => 'Belum Registrasi']);
    $person = Person::create(['nama' => 'SP3 Person', 'nip' => 60002, 'jenis_kelamin' => 'L']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'SP3-001', 'attendance_code' => 'SP3-ATT', 'jenis_peserta' => 'Wajib']);

    LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'migrated_at' => now()]);
    $bridge = LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    expect($bridge->peserta->id)->toBe($peserta->id)
        ->and($bridge->person->id)->toBe($person->id)
        ->and($bridge->participation->id)->toBe($participation->id)
        ->and($bridge->event->id)->toBe($event->id);

    // Participation has inverse relationship
    $loaded = Participation::with('legacyParticipationMapping')->find($participation->id);
    expect($loaded->legacyParticipationMapping)->not->toBeNull()
        ->and($loaded->legacyParticipationMapping->id)->toBe($bridge->id);
});
