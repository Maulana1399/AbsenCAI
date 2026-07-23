<?php

use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Placement\PlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('generate participant number uses participation records first and falls back to legacy peserta', function () {
    $event = Event::create(['name' => 'Placement Event', 'slug' => 'placement-event', 'status' => 'active']);
    $person = Person::create(['nama' => 'Placement Person', 'nip' => 5001, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-PLAC001', 'jenis_peserta' => 'Wajib']);
    peserta::create(['nama' => 'Legacy Fallback', 'nip' => 1001, 'participant_number' => 'KL009', 'jenis_kelamin' => 'Laki - Laki']);

    expect(PlacementService::generateParticipantNumber($event->id, 'Laki - Laki'))->toBe('KL002')
        ->and(PlacementService::generateParticipantNumber($event->id, 'Perempuan'))->toBe('KP001');
});

test('generate participant number continues the next sequence for the same gender prefix', function () {
    peserta::create([
        'nama' => 'Peserta Laki 1',
        'nip' => 1001,
        'participant_number' => 'KL001',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    peserta::create([
        'nama' => 'Peserta Perempuan 1',
        'nip' => 2001,
        'participant_number' => 'KP009',
        'jenis_kelamin' => 'Perempuan',
    ]);

    $eventA = Event::create(['name' => 'Placement Event A', 'slug' => 'placement-event-a', 'status' => 'active']);
    $eventB = Event::create(['name' => 'Placement Event B', 'slug' => 'placement-event-b', 'status' => 'active']);
    $personA = Person::create(['nama' => 'Placement A', 'nip' => 5002, 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'Placement B', 'nip' => 6002, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-PLAC-A1', 'jenis_peserta' => 'Wajib']);
    Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-PLAC-B1', 'jenis_peserta' => 'Wajib']);

    expect(PlacementService::generateParticipantNumber($eventA->id, 'Laki - Laki'))->toBe('KL002')
        ->and(PlacementService::generateParticipantNumber($eventB->id, 'Laki - Laki'))->toBe('KL002')
        ->and(PlacementService::generateParticipantNumber($eventA->id, 'Laki laki'))->toBe('KL002')
        ->and(PlacementService::generateParticipantNumber($eventA->id, 'Laki - Laki '))->toBe('KL002');
});

test('generate participant number is isolated per event and ignores legacy peserta records from other events', function () {
    $eventA = Event::create(['name' => 'Placement Event A2', 'slug' => 'placement-event-a2', 'status' => 'active']);
    $eventB = Event::create(['name' => 'Placement Event B2', 'slug' => 'placement-event-b2', 'status' => 'active']);
    $personA = Person::create(['nama' => 'Placement A2', 'nip' => 7001, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'KL003', 'attendance_code' => 'KJA-PLAC-A2', 'jenis_peserta' => 'Wajib']);
    peserta::create(['nama' => 'Legacy Cross Event', 'nip' => 7002, 'participant_number' => 'KL999', 'jenis_kelamin' => 'Laki - Laki']);

    expect(PlacementService::generateParticipantNumber($eventB->id, 'Laki - Laki'))->toBe('KL001');
});

test('least filled regu picks the regu with the fewest participants for the selected gender', function () {
    $reguA = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Regu B', 'jenis_kelamin' => 'Laki - Laki']);
    $event = Event::create(['name' => 'Placement Event LFR', 'slug' => 'placement-lfr', 'status' => 'active']);

    $person1 = Person::create(['nama' => 'P1', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    $person2 = Person::create(['nama' => 'P2', 'nip' => 1002, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $person1->id, 'event_id' => $event->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-LFR01', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);
    Participation::create(['person_id' => $person2->id, 'event_id' => $event->id, 'participant_number' => 'KL002', 'attendance_code' => 'KJA-LFR02', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);

    expect(PlacementService::leastFilledRegu('Laki - Laki', $event->id)?->id)->toBe($reguB->id)
        ->and(PlacementService::leastFilledReguId('Laki - Laki', $event->id))->toBe($reguB->id)
        ->and(PlacementService::leastFilledReguName('Laki - Laki', $event->id))->toBe('Regu B');
});

test('auto placement uses legacy nip compatibility and least filled regu', function () {
    $reguA = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Perempuan']);
    $reguB = regu::create(['regu' => 'Regu B', 'jenis_kelamin' => 'Perempuan']);
    $event = Event::create(['name' => 'Placement Event AP', 'slug' => 'placement-ap', 'status' => 'active']);

    $person1 = Person::create(['nama' => 'P1', 'nip' => 2001, 'jenis_kelamin' => 'P']);
    // Legacy peserta needed for NIP generation (legacyNextNip queries peserta table)
    peserta::create(['nama' => 'P1', 'nip' => 2001, 'jenis_kelamin' => 'Perempuan']);
    Participation::create(['person_id' => $person1->id, 'event_id' => $event->id, 'participant_number' => 'KP001', 'attendance_code' => 'KJA-AP01', 'jenis_peserta' => 'Wajib', 'regu_id' => $reguA->id]);

    expect(PlacementService::autoPlacement('Perempuan', $event->id))->toBe([
        'nip' => '2002',
        'regu_id' => $reguB->id,
        'regu_nama' => 'Regu B',
    ]);
});
