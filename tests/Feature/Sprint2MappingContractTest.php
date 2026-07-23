<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function sprint2MappingEvent(string $suffix): Event
{
    return Event::create([
        'name' => 'S2 Event ' . $suffix,
        'slug' => 's2-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function sprint2MappingPesertaPerson(int $nip): array
{
    $desa = \App\Models\desa::create(['desa_asal' => 'S2 Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'S2 Kelompok', 'desa_id' => $desa->id]);
    $regu = \App\Models\regu::create(['regu' => 'S2 Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $p = peserta::create(['nama' => 'S2 Person', 'nip' => $nip, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $person = Person::create(['nama' => 'S2 Person', 'nip' => $nip, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);

    return [
        'desa' => $desa,
        'kelompok' => $kelompok,
        'regu' => $regu,
        'peserta' => $p,
        'person' => $person,
    ];
}

// ===========================================================================
// 1. LegacyPesertaMapping can exist without Participation
// ===========================================================================

test('LegacyPesertaMapping can exist without any Participation', function () {
    $f = sprint2MappingPesertaPerson(50001);
    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $f['peserta']->id,
        'person_id' => $f['person']->id,
        'migrated_at' => now(),
    ]);

    expect($mapping->id)->not->toBeNull()
        ->and($mapping->peserta_id)->toBe($f['peserta']->id)
        ->and($mapping->person_id)->toBe($f['person']->id)
        ->and($mapping->peserta->id)->toBe($f['peserta']->id)
        ->and($mapping->person->id)->toBe($f['person']->id);
});

// ===========================================================================
// 2. LegacyPesertaMapping only represents peserta↔Person
// ===========================================================================

test('LegacyPesertaMapping can be created with only peserta↔Person', function () {
    $f = sprint2MappingPesertaPerson(50002);
    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $f['peserta']->id,
        'person_id' => $f['person']->id,
        'migrated_at' => now(),
    ]);

    expect($mapping->id)->not->toBeNull()
        ->and($mapping->peserta_id)->toBe($f['peserta']->id)
        ->and($mapping->person_id)->toBe($f['person']->id)
        ->and($mapping->peserta->id)->toBe($f['peserta']->id)
        ->and($mapping->person->id)->toBe($f['person']->id);
});

// ===========================================================================
// 3. LegacyParticipationMapping resolves Participation per Event
// ===========================================================================

test('LegacyParticipationMapping resolves Participation per Event', function () {
    $f = sprint2MappingPesertaPerson(50003);
    $eventA = sprint2MappingEvent('a');
    $eventB = sprint2MappingEvent('b');

    $partA = Participation::create(['person_id' => $f['person']->id, 'event_id' => $eventA->id, 'participant_number' => 'S2-A01', 'attendance_code' => 'KJA-S2A01', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $f['person']->id, 'event_id' => $eventB->id, 'participant_number' => 'S2-B01', 'attendance_code' => 'KJA-S2B01', 'jenis_peserta' => 'Wajib']);

    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $partB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    $resolver = app(LegacyParticipationResolver::class);
    expect($resolver->resolveByPesertaAndEvent($f['peserta']->id, $eventA->id)?->id)->toBe($partA->id)
        ->and($resolver->resolveByPesertaAndEvent($f['peserta']->id, $eventB->id)?->id)->toBe($partB->id)
        ->and($resolver->resolveByPersonAndEvent($f['person']->id, $eventA->id)?->id)->toBe($partA->id)
        ->and($resolver->resolveByPersonAndEvent($f['person']->id, $eventB->id)?->id)->toBe($partB->id);
});

// ===========================================================================
// 4. Person with two Participations in two Events resolve correctly
// ===========================================================================

test('Person with two Participations in two Events both resolve correctly', function () {
    $f = sprint2MappingPesertaPerson(50004);
    $eventA = sprint2MappingEvent('c');
    $eventB = sprint2MappingEvent('d');

    $partA = Participation::create(['person_id' => $f['person']->id, 'event_id' => $eventA->id, 'participant_number' => 'S2-C01', 'attendance_code' => 'KJA-S2C01', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $f['person']->id, 'event_id' => $eventB->id, 'participant_number' => 'S2-D01', 'attendance_code' => 'KJA-S2D01', 'jenis_peserta' => 'Wajib']);

    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $partB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    $resolver = app(LegacyParticipationResolver::class);

    $resolvedA = $resolver->resolveByPesertaAndEvent($f['peserta']->id, $eventA->id);
    $resolvedB = $resolver->resolveByPesertaAndEvent($f['peserta']->id, $eventB->id);

    expect($resolvedA->id)->toBe($partA->id)
        ->and($resolvedB->id)->toBe($partB->id)
        ->and($resolvedA->event_id)->toBe($eventA->id)
        ->and($resolvedB->event_id)->toBe($eventB->id);
});

// ===========================================================================
// 5. Remove Participation Event A does not remove Participation Event B
// ===========================================================================

test('removing Event A participation does not affect Event B participation', function () {
    $f = sprint2MappingPesertaPerson(50005);
    $eventA = sprint2MappingEvent('e');
    $eventB = sprint2MappingEvent('f');

    $partA = Participation::create(['person_id' => $f['person']->id, 'event_id' => $eventA->id, 'participant_number' => 'S2-E01', 'attendance_code' => 'KJA-S2E01', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $f['person']->id, 'event_id' => $eventB->id, 'participant_number' => 'S2-F01', 'attendance_code' => 'KJA-S2F01', 'jenis_peserta' => 'Wajib']);

    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $partB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    LegacyParticipationMapping::where('participation_id', $partA->id)->delete();
    $partA->delete();

    expect(Participation::find($partB->id))->not->toBeNull()
        ->and(LegacyParticipationMapping::where('event_id', $eventB->id)->count())->toBe(1)
        ->and(LegacyParticipationMapping::where('event_id', $eventA->id)->count())->toBe(0);
});

// ===========================================================================
// 6. Remove last Participation does not remove Person
// ===========================================================================

test('removing last Participation does not delete Person', function () {
    $f = sprint2MappingPesertaPerson(50006);
    $event = sprint2MappingEvent('g');

    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-G01', 'attendance_code' => 'KJA-S2G01', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    LegacyParticipationMapping::where('participation_id', $part->id)->delete();
    $part->delete();

    expect(Person::find($f['person']->id))->not->toBeNull();
});

// ===========================================================================
// 7. Remove last Participation does not remove legacy peserta
// ===========================================================================

test('removing last Participation does not delete legacy peserta', function () {
    $f = sprint2MappingPesertaPerson(50007);
    $event = sprint2MappingEvent('h');

    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-H01', 'attendance_code' => 'KJA-S2H01', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    LegacyParticipationMapping::where('participation_id', $part->id)->delete();
    $part->delete();

    expect(peserta::find($f['peserta']->id))->not->toBeNull();
});

// ===========================================================================
// 8. Remove last Participation does not remove LegacyPesertaMapping
// ===========================================================================

test('removing last Participation does not delete LegacyPesertaMapping', function () {
    $f = sprint2MappingPesertaPerson(50008);
    $event = sprint2MappingEvent('i');

    $mapping = LegacyPesertaMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'migrated_at' => now()]);
    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-I01', 'attendance_code' => 'KJA-S2I01', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    LegacyParticipationMapping::where('participation_id', $part->id)->delete();
    $part->delete();

    expect(LegacyPesertaMapping::find($mapping->id))->not->toBeNull();
});

// ===========================================================================
// 9. LegacyParticipationMapping deleted when Participation removed
// ===========================================================================

test('LegacyParticipationMapping is deleted when associated Participation is removed', function () {
    $f = sprint2MappingPesertaPerson(50009);
    $event = sprint2MappingEvent('j');

    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-J01', 'attendance_code' => 'KJA-S2J01', 'jenis_peserta' => 'Wajib']);
    $bridge = LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $bridge->delete();
    $part->delete();

    expect(LegacyParticipationMapping::find($bridge->id))->toBeNull()
        ->and(Participation::find($part->id))->toBeNull();
});

// ===========================================================================
// 10. Registration creates both mappings with correct responsibilities
// ===========================================================================

test('new registration creates LegacyPesertaMapping (peserta↔Person) and LegacyParticipationMapping (event bridge)', function () {
    $event = sprint2MappingEvent('k');
    app(ActiveEventContext::class)->set($event);

    $desa = \App\Models\desa::create(['desa_asal' => 'S2 Reg Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'S2 Reg Kelompok', 'desa_id' => $desa->id]);
    $regu = \App\Models\regu::create(['regu' => 'S2 Reg Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $result = app(RegistrationService::class)->createParticipant([
        'nama' => 'S2 Registration',
        'nip' => 50010,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    $pesertaMapping = LegacyPesertaMapping::where('peserta_id', $result->id)->first();
    $participationMapping = LegacyParticipationMapping::where('peserta_id', $result->id)->first();

    expect($pesertaMapping)->not->toBeNull()
        ->and($pesertaMapping->person_id)->not->toBeNull()
        ->and($participationMapping)->not->toBeNull()
        ->and($participationMapping->participation_id)->not->toBeNull()
        ->and($participationMapping->event_id)->toBe($event->id);
});

// ===========================================================================
// 11. Participant replacement updates LegacyPesertaMapping (peserta↔Person only)
// ===========================================================================

test('participant replacement updates LegacyPesertaMapping person pointer', function () {
    $f = sprint2MappingPesertaPerson(50011);
    $event = sprint2MappingEvent('l');

    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-L01', 'attendance_code' => 'KJA-S2L01', 'jenis_peserta' => 'Wajib']);
    $pesertaMapping = LegacyPesertaMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $newPerson = Person::create(['nama' => 'S2 New', 'nip' => 50012, 'jenis_kelamin' => 'L', 'desa_id' => $f['desa']->id, 'kelompok_id' => $f['kelompok']->id]);
    $newParticipation = Participation::create(['person_id' => $newPerson->id, 'event_id' => $event->id, 'participant_number' => 'S2-L02', 'attendance_code' => 'KJA-S2L02', 'jenis_peserta' => 'Wajib']);

    $pesertaMapping->update(['person_id' => $newPerson->id]);
    LegacyParticipationMapping::where('peserta_id', $f['peserta']->id)->delete();
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $newPerson->id, 'participation_id' => $newParticipation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $pesertaMapping->refresh();
    expect($pesertaMapping->person_id)->toBe($newPerson->id)
        ->and($pesertaMapping->peserta_id)->toBe($f['peserta']->id);
});

// ===========================================================================
// 12. Attendance identity resolution still works (via LegacyParticipationMapping)
// ===========================================================================

test('attendance identity resolution works through LegacyParticipationMapping', function () {
    $f = sprint2MappingPesertaPerson(50013);
    $event = sprint2MappingEvent('m');

    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-M01', 'attendance_code' => 'KJA-S2M01', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $resolver = app(LegacyParticipationResolver::class);
    $resolved = $resolver->resolveByPesertaAndEvent($f['peserta']->id, $event->id);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($part->id);

    $person = $resolver->resolvePersonByPesertaId($f['peserta']->id);
    expect($person)->not->toBeNull()
        ->and($person->id)->toBe($f['person']->id);
});

// ===========================================================================
// 13. Design C diagnostic remains zero on valid fixtures
// ===========================================================================

test('Design C diagnostic reports zero problems for valid Sprint 2 fixtures', function () {
    $f = sprint2MappingPesertaPerson(50014);
    $event = sprint2MappingEvent('n');

    $part = Participation::create(['person_id' => $f['person']->id, 'event_id' => $event->id, 'participant_number' => 'S2-N01', 'attendance_code' => 'KJA-S2N01', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $f['peserta']->id, 'person_id' => $f['person']->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $this->artisan('diagnose:design-c')
        ->expectsOutputToContain('problem_total: 0')
        ->assertExitCode(0);
});
