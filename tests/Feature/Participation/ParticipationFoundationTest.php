<?php

use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ParticipationFoundation_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function ParticipationFoundation_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-' . str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function ParticipationFoundation_makeParticipation(array $overrides = []): Participation
{
    static $personCounter = 0;
    static $eventCounter = 0;
    $personCounter++;
    $eventCounter++;

    if (! array_key_exists('person_id', $overrides)) {
        $overrides['person_id'] = ParticipationFoundation_makePerson(['nama' => 'Person '.$personCounter])->id;
    }

    if (! array_key_exists('event_id', $overrides)) {
        $overrides['event_id'] = ParticipationFoundation_makeEvent(['name' => 'Event '.$eventCounter])->id;
    }

    return Participation::create(array_merge([
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Schema
// ---------------------------------------------------------------------------

test('participations table has expected columns', function () {
    $columns = Schema::getColumnListing('participations');
    $expected = ['id', 'person_id', 'event_id', 'participant_number', 'attendance_code', 'jenis_peserta', 'created_at', 'updated_at'];

    expect($columns)->toMatchArray($expected);
});

// ---------------------------------------------------------------------------
// Participation belongs to Person
// ---------------------------------------------------------------------------

test('participation belongs to person', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();
    $participation = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect($participation->person)->not->toBeNull()
        ->and($participation->person->id)->toBe($person->id)
        ->and($participation->person->nama)->toBe('Test Person');
});

// ---------------------------------------------------------------------------
// Participation belongs to Event
// ---------------------------------------------------------------------------

test('participation belongs to event', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();
    $participation = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect($participation->event)->not->toBeNull()
        ->and($participation->event->id)->toBe($event->id)
        ->and($participation->event->name)->toBe('Test Event');
});

// ---------------------------------------------------------------------------
// Person has many Participations
// ---------------------------------------------------------------------------

test('person has many participations', function () {
    $person = ParticipationFoundation_makePerson();
    $eventA = ParticipationFoundation_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ParticipationFoundation_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);

    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventA->id,
    ]);
    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
    ]);

    expect($person->participations->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Event has many Participations
// ---------------------------------------------------------------------------

test('event has many participations', function () {
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);
    $event = ParticipationFoundation_makeEvent();

    ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $event->id,
    ]);
    ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $event->id,
    ]);

    expect($event->participations->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Person has many Events (via participations)
// ---------------------------------------------------------------------------

test('person has many events via participations', function () {
    $person = ParticipationFoundation_makePerson();
    $eventA = ParticipationFoundation_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ParticipationFoundation_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);

    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventA->id,
    ]);
    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
    ]);

    $events = $person->events;
    expect($events->count())->toBe(2)
        ->and($events->pluck('id')->toArray())->toContain($eventA->id, $eventB->id);
});

// ---------------------------------------------------------------------------
// Event has many People (via participations)
// ---------------------------------------------------------------------------

test('event has many people via participations', function () {
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);
    $event = ParticipationFoundation_makeEvent();

    ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $event->id,
    ]);
    ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $event->id,
    ]);

    $people = $event->people;
    expect($people->count())->toBe(2)
        ->and($people->pluck('id')->toArray())->toContain($personA->id, $personB->id);
});

// ---------------------------------------------------------------------------
// Same person in different events
// ---------------------------------------------------------------------------

test('same person can participate in different events', function () {
    $person = ParticipationFoundation_makePerson();
    $eventA = ParticipationFoundation_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ParticipationFoundation_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);

    $partA = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventA->id,
    ]);
    $partB = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
    ]);

    expect($partA->exists)->toBeTrue()
        ->and($partB->exists)->toBeTrue()
        ->and(Participation::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Same person cannot participate twice in same event
// ---------------------------------------------------------------------------

test('same person cannot participate twice in same event', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect(fn () => ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

// ---------------------------------------------------------------------------
// Different people in same event
// ---------------------------------------------------------------------------

test('different people can participate in same event', function () {
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);
    $event = ParticipationFoundation_makeEvent();

    $partA = ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $event->id,
    ]);
    $partB = ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $event->id,
    ]);

    expect($partA->exists)->toBeTrue()
        ->and($partB->exists)->toBeTrue()
        ->and(Participation::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Participant number uniqueness (within event)
// ---------------------------------------------------------------------------

test('participant number must be unique within same event', function () {
    $event = ParticipationFoundation_makeEvent();
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);

    ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
    ]);

    expect(fn () => ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('same participant number allowed in different events', function () {
    $eventA = ParticipationFoundation_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ParticipationFoundation_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);

    $partA = ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $eventA->id,
        'participant_number' => 'KL001',
    ]);
    $partB = ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $eventB->id,
        'participant_number' => 'KL001',
    ]);

    expect($partA->exists)->toBeTrue()
        ->and($partB->exists)->toBeTrue();
});

test('participant number can be null', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    $participation = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => null,
    ]);

    expect($participation->participant_number)->toBeNull();
});

// ---------------------------------------------------------------------------
// Attendance code uniqueness (global)
// ---------------------------------------------------------------------------

test('attendance code must be globally unique', function () {
    $eventA = ParticipationFoundation_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ParticipationFoundation_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);

    ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $eventA->id,
        'attendance_code' => 'KJA-UNIQUE01',
    ]);

    expect(fn () => ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $eventB->id,
        'attendance_code' => 'KJA-UNIQUE01',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

test('different participations have different attendance codes', function () {
    $event = ParticipationFoundation_makeEvent();
    $personA = ParticipationFoundation_makePerson(['nama' => 'Person A']);
    $personB = ParticipationFoundation_makePerson(['nama' => 'Person B']);

    $partA = ParticipationFoundation_makeParticipation([
        'person_id' => $personA->id,
        'event_id' => $event->id,
        'attendance_code' => 'KJA-AAAA',
    ]);
    $partB = ParticipationFoundation_makeParticipation([
        'person_id' => $personB->id,
        'event_id' => $event->id,
        'attendance_code' => 'KJA-BBBB',
    ]);

    expect($partA->attendance_code)->toBe('KJA-AAAA')
        ->and($partB->attendance_code)->toBe('KJA-BBBB');
});

test('attendance code can be null', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    $participation = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'attendance_code' => null,
    ]);

    expect($participation->attendance_code)->toBeNull();
});

// ---------------------------------------------------------------------------
// jenis_peserta default
// ---------------------------------------------------------------------------

test('jenis_peserta defaults to Wajib', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    $participation = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect($participation->jenis_peserta)->toBe('Wajib');
});

test('jenis_peserta can be set explicitly', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    $participation = ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Kiriman',
    ]);

    expect($participation->jenis_peserta)->toBe('Kiriman');
});

// ---------------------------------------------------------------------------
// Restrict delete — Person
// ---------------------------------------------------------------------------

test('deleting person with existing participation is prevented', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect(Participation::count())->toBe(1);

    expect(fn () => $person->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Restrict delete — Event
// ---------------------------------------------------------------------------

test('deleting event with existing participation is prevented', function () {
    $person = ParticipationFoundation_makePerson();
    $event = ParticipationFoundation_makeEvent();

    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $event->id,
    ]);

    expect(Participation::count())->toBe(1);

    expect(fn () => $event->delete())
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Multi Event identity contract
// ---------------------------------------------------------------------------

test('multi event identity contract — one person, two events, two participations', function () {
    $person = ParticipationFoundation_makePerson(['nama' => 'Multi Event Person']);
    $eventA = ParticipationFoundation_makeEvent(['name' => 'CAI 2026', 'slug' => 'cai-2026']);
    $eventB = ParticipationFoundation_makeEvent(['name' => 'KJA 2026', 'slug' => 'kja-2026']);

    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventA->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-CAI001',
    ]);
    ParticipationFoundation_makeParticipation([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-KJA001',
    ]);

    expect(Person::count())->toBe(1)
        ->and(Event::count())->toBe(2)
        ->and(Participation::count())->toBe(2)
        ->and($person->events->count())->toBe(2)
        ->and($eventA->people->first()->id)->toBe($person->id)
        ->and($eventB->people->first()->id)->toBe($person->id);
});

// ---------------------------------------------------------------------------
// Existing foundations remain unaffected
// ---------------------------------------------------------------------------

test('existing Person foundation remains unaffected', function () {
    $person = ParticipationFoundation_makePerson(['nama' => 'Person']);

    expect($person->nama)->toBe('Person')
        ->and($person->nip)->toBeNull()
        ->and($person->participations->count())->toBe(0);
});

test('existing Event foundation remains unaffected', function () {
    $event = ParticipationFoundation_makeEvent();

    expect($event->name)->not->toBeEmpty()
        ->and($event->isActive())->toBeTrue()
        ->and($event->participations->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// No runtime integration — peserta-only flow still works
// ---------------------------------------------------------------------------

test('existing peserta-only architecture is unchanged', function () {
    $peserta = \App\Models\peserta::create([
        'nama' => 'Legacy Participant',
        'nip' => 5001,
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => 'Belum Registrasi',
    ]);

    expect($peserta->exists)->toBeTrue()
        ->and(\App\Models\peserta::count())->toBe(1)
        ->and(Participation::count())->toBe(0);
});
