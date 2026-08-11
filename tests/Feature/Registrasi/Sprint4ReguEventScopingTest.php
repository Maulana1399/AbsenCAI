<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────
// 1. participations.regu_id schema exists
// ──────────────────────────────────────────────
test('participations regu_id column exists and is nullable', function () {
    $event = Event::create(['name' => 'Schema Test', 'slug' => 'schema-test', 'status' => 'active']);
    $person = Person::create(['nama' => 'Schema Person', 'nip' => 8001, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-SCHEMA-001',
        'jenis_peserta' => 'Wajib',
    ]);

    expect(Schema::hasColumn('participations', 'regu_id'))->toBeTrue();
    expect($participation->regu_id)->toBeNull();
});

// ──────────────────────────────────────────────
// 2. Participation belongsTo Regu
// ──────────────────────────────────────────────
test('participation belongs to regu', function () {
    $reguA = regu::create(['regu' => 'Regu Test 1', 'jenis_kelamin' => 'Laki - Laki']);
    $event = Event::create(['name' => 'Rel Test', 'slug' => 'rel-test', 'status' => 'active']);
    $person = Person::create(['nama' => 'Rel Person', 'nip' => 8002, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL002',
        'attendance_code' => 'KJA-REL-001',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $reguA->id,
    ]);

    expect($participation->regu)->toBeInstanceOf(regu::class);
    expect($participation->regu->id)->toBe($reguA->id);
    expect($participation->regu->regu)->toBe('Regu Test 1');
});

// ──────────────────────────────────────────────
// 3. regu hasMany participations
// ──────────────────────────────────────────────
test('regu has many participations', function () {
    $reguA = regu::create(['regu' => 'Regu PHM', 'jenis_kelamin' => 'Laki - Laki']);
    $event = Event::create(['name' => 'PHM Event', 'slug' => 'phm-event', 'status' => 'active']);
    $personA = Person::create(['nama' => 'PHM A', 'nip' => 8101, 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'PHM B', 'nip' => 8102, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $personA->id, 'event_id' => $event->id, 'participant_number' => 'KL010', 'attendance_code' => 'KJA-PHM1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);
    Participation::create(['person_id' => $personB->id, 'event_id' => $event->id, 'participant_number' => 'KL011', 'attendance_code' => 'KJA-PHM2', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);

    $reguA->refresh();
    expect($reguA->participations)->toHaveCount(2);
});

// ──────────────────────────────────────────────
// 4. FK nullOnDelete — deleting regu nullifies participations.regu_id
// ──────────────────────────────────────────────
test('deleting regu nullifies participation regu_id', function () {
    $reguA = regu::create(['regu' => 'Regu NullTest', 'jenis_kelamin' => 'Laki - Laki']);
    $event = Event::create(['name' => 'Null Event', 'slug' => 'null-event', 'status' => 'active']);
    $person = Person::create(['nama' => 'Null Person', 'nip' => 8201, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL020',
        'attendance_code' => 'KJA-NULL-001',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $reguA->id,
    ]);

    $reguA->delete();

    $participation->refresh();
    expect($participation->regu_id)->toBeNull();
});

// ──────────────────────────────────────────────
// 5. registration writes Participation.regu_id only (dual-write retired per Sprint 7/8A)
// ──────────────────────────────────────────────
test('create participant writes regu_id to participation only (dual-write retired)', function () {
    $reguA = regu::create(['regu' => 'Regu Dual', 'jenis_kelamin' => 'Laki - Laki']);
    $event = Event::create(['name' => 'Dual Event', 'slug' => 'dual-event', 'status' => 'active']);
    app(\App\Support\ActiveEventContext::class)->set($event);

    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Dual']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Dual', 'desa_id' => $desa->id]);

    app(RegistrationService::class)->createParticipant([
        'nama' => 'Dual Peserta',
        'nip' => 3001,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $reguA->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $pesertaRecord = peserta::where('nama', 'Dual Peserta')->first();
    expect($pesertaRecord)->not->toBeNull();
    expect($pesertaRecord->regu_id)->toBeNull();

    $participationRecord = Participation::where('person_id', $pesertaRecord->legacyPesertaMapping->person_id)->first();
    expect($participationRecord)->not->toBeNull();
    expect($participationRecord->regu_id)->toBe($reguA->id);
});

// ──────────────────────────────────────────────
// 6. placement Event A only counts Event A
// ──────────────────────────────────────────────
test('least filled regu is scoped to event when eventId is given', function () {
    $reguA = regu::create(['regu' => 'Event A Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Event B Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $eventA = Event::create(['name' => 'EA', 'slug' => 'ea', 'status' => 'active']);
    $eventB = Event::create(['name' => 'EB', 'slug' => 'eb', 'status' => 'active']);

    // Person di Event A pakai Regu A
    $personA = Person::create(['nama' => 'EA Person', 'nip' => 9001, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'KL100', 'attendance_code' => 'KJA-EA1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);

    // Person di Event B pakai Regu B
    $personB = Person::create(['nama' => 'EB Person', 'nip' => 9002, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'KL101', 'attendance_code' => 'KJA-EB1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguB->id]);

    // Paling sedikit di Event A adalah Regu B (0 participation di Event A)
    expect(PlacementService::leastFilledRegu($eventA->id, 'Laki - Laki')?->id)->toBe($reguB->id);

    // Paling sedikit di Event B adalah Regu A (0 participation di Event B)
    expect(PlacementService::leastFilledRegu($eventB->id, 'Laki - Laki')?->id)->toBe($reguA->id);
});

// ──────────────────────────────────────────────
// 7. Person sama bisa punya regu berbeda di dua Event
// ──────────────────────────────────────────────
test('same person can have different regu in different events', function () {
    $reguA = regu::create(['regu' => 'Regu Event A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Regu Event B', 'jenis_kelamin' => 'Laki - Laki']);

    $eventA = Event::create(['name' => 'Multi Event A', 'slug' => 'multi-a', 'status' => 'active']);
    $eventB = Event::create(['name' => 'Multi Event B', 'slug' => 'multi-b', 'status' => 'active']);

    $person = Person::create(['nama' => 'Multi Person', 'nip' => 9101, 'jenis_kelamin' => 'L']);

    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KL200', 'attendance_code' => 'KJA-MA1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KL201', 'attendance_code' => 'KJA-MB1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguB->id]);

    expect((int) $partA->regu_id)->toBe($reguA->id);
    expect((int) $partB->regu_id)->toBe($reguB->id);
    expect($partA->regu_id)->not->toBe($partB->regu_id);
});

// ──────────────────────────────────────────────
// 8. join second Event does not change first Event's Participation
// ──────────────────────────────────────────────
test('joining second event does not change first event participation regu', function () {
    $reguA = regu::create(['regu' => 'Regu Event A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Regu Event B', 'jenis_kelamin' => 'Laki - Laki']);

    $eventA = Event::create(['name' => 'Join Event A', 'slug' => 'join-a', 'status' => 'active']);
    $eventB = Event::create(['name' => 'Join Event B', 'slug' => 'join-b', 'status' => 'active']);

    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Join']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Join', 'desa_id' => $desa->id]);

    // Person A + legacy peserta with reguA
    $pesertaRecord = peserta::create(['nama' => 'Join Person', 'nip' => 4001, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $person = Person::create(['nama' => 'Join Person', 'nip' => 4001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id]);

    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KL300', 'attendance_code' => 'KJA-JA1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);

    // Join Event B — should NOT change partA's regu_id
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KL301', 'attendance_code' => 'KJA-JB1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguB->id]);

    $partA->refresh();
    expect((int) $partA->regu_id)->toBe($reguA->id);
    expect((int) $partB->regu_id)->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 9. NULL regu_id valid
// ──────────────────────────────────────────────
test('participation with null regu_id is valid', function () {
    $event = Event::create(['name' => 'Null Regu Event', 'slug' => 'null-regu', 'status' => 'active']);
    $person = Person::create(['nama' => 'Null Regu Person', 'nip' => 8301, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL030',
        'attendance_code' => 'KJA-NULLREGU',
        'jenis_peserta' => 'Wajib',
    ]);

    expect($participation->regu_id)->toBeNull();
    expect($participation->regu)->toBeNull();
});

// ──────────────────────────────────────────────
// 10. least filled regu uses participations scoped by event (retired legacy peserta fallback)
// ──────────────────────────────────────────────
test('least filled regu uses participations scoped by event (retired legacy peserta fallback)', function () {
    $reguA = regu::create(['regu' => 'Event Scoped Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Event Scoped Regu B', 'jenis_kelamin' => 'Laki - Laki']);
    $event = Event::create(['name' => 'Event Scoped Test', 'slug' => 'event-scoped-test', 'status' => 'active']);

    $person = Person::create(['nama' => 'Event Scoped Person', 'nip' => 5001, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL501', 'attendance_code' => 'KJA-ES1', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);

    $result = PlacementService::leastFilledRegu($event->id, 'Laki - Laki');
    expect($result)->toBeInstanceOf(regu::class);
    expect($result->id)->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 11. autoPlacement with eventId uses participation count
// ──────────────────────────────────────────────
test('auto placement with event id uses participation count', function () {
    $reguA = regu::create(['regu' => 'Auto Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Auto Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    $event = Event::create(['name' => 'Auto Event', 'slug' => 'auto-event', 'status' => 'active']);
    $person = Person::create(['nama' => 'Auto Person', 'nip' => 9201, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL400', 'attendance_code' => 'KJA-AUTO', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);

    // reguA has 1 participation, reguB has 0 — so autoPlacement picks reguB
    $placement = PlacementService::autoPlacement('Laki - Laki', $event->id);
    expect($placement['regu_id'])->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 12. EditPeserta regu_id writes to participation only (dual-write retired)
// ──────────────────────────────────────────────
test('edit peserta writes regu_id to participation only (dual-write retired)', function () {
    $reguA = regu::create(['regu' => 'Edit Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Edit Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    $event = Event::create(['name' => 'Edit Event', 'slug' => 'edit-event', 'status' => 'active']);
    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Edit']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Edit', 'desa_id' => $desa->id]);

    $person = Person::create(['nama' => 'Edit Person', 'nip' => 6001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $pesertaRecord = peserta::create(['nama' => 'Edit Person', 'nip' => 6001, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL500', 'attendance_code' => 'KJA-EDIT', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    // Simulate EditPeserta::update() — write regu_id to participation only
    $participation->update(['jenis_peserta' => 'Kiriman', 'regu_id' => $reguB->id]);

    $participation->refresh();

    expect((int) $participation->regu_id)->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 13. Ulang updatePeserta writes regu_id to participation only (dual-write retired)
// ──────────────────────────────────────────────
test('ulang update peserta writes regu_id to participation only (dual-write retired)', function () {
    $reguA = regu::create(['regu' => 'Ulang Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Ulang Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    $event = Event::create(['name' => 'Ulang Event', 'slug' => 'ulang-event', 'status' => 'active']);
    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Ulang']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Ulang', 'desa_id' => $desa->id]);

    $person = Person::create(['nama' => 'Ulang Person', 'nip' => 7001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $pesertaRecord = peserta::create(['nama' => 'Ulang Person', 'nip' => 7001, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL600', 'attendance_code' => 'KJA-ULANG', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    // Simulate Ulang::updatePeserta() — write regu_id to participation only
    $participation->update(['jenis_peserta' => 'Kiriman', 'regu_id' => $reguB->id]);

    $participation->refresh();

    expect((int) $participation->regu_id)->toBe($reguB->id);
});
