<?php

use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\ActivityRegistration;
use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Activity\ActivityRegistrationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function S3_9A_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function S3_9A_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function S3_9A_makeParticipation(array $overrides = []): Participation
{
    $person = $overrides['person_id'] ?? S3_9A_makePerson()->id;
    $event = $overrides['event_id'] ?? S3_9A_makeEvent()->id;

    return Participation::create(array_merge([
        'person_id' => $person,
        'event_id' => $event,
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

function S3_9A_makeActivityGroup(array $overrides = []): ActivityGroup
{
    $event = $overrides['event_id'] ?? S3_9A_makeEvent()->id;

    return ActivityGroup::create(array_merge([
        'event_id' => $event,
        'name' => 'Program Test',
    ], $overrides));
}

function S3_9A_makeActivity(array $overrides = []): Activity
{
    $event = $overrides['event_id'] ?? S3_9A_makeEvent()->id;

    return app(ActivityRegistrationService::class)->createActivity(array_merge([
        'event_id' => $event,
        'name' => 'Activity Test',
        'status' => 'active',
    ], $overrides));
}

function S3_9A_makeRegistration(array $overrides = []): ActivityRegistration
{
    $participation = $overrides['participation_id'] ?? S3_9A_makeParticipation()->id;
    $activity = $overrides['activity_id'] ?? S3_9A_makeActivity()->id;

    return app(ActivityRegistrationService::class)->create(array_merge([
        'participation_id' => $participation,
        'activity_id' => $activity,
        'status' => 'registered',
    ], $overrides));
}

function S3_9A_makeLegacyMapping(array $overrides = []): LegacyPesertaMapping
{
    $event = $overrides['event_id'] ?? S3_9A_makeEvent()->id;
    $person = $overrides['person_id'] ?? S3_9A_makePerson()->id;
    $participation = $overrides['participation_id'] ?? S3_9A_makeParticipation([
        'event_id' => $event,
        'person_id' => $person,
    ])->id;

    return LegacyPesertaMapping::create(array_merge([
        'peserta_id' => $overrides['peserta_id'] ?? peserta::create([
            'nama' => 'Legacy Peserta',
            'nip' => random_int(1000, 9999),
            'status_registrasi' => 'Belum Registrasi',
        ])->id,
        'person_id' => $person,
        'participation_id' => $participation,
        'event_id' => $event,
        'migrated_at' => now(),
    ], $overrides));
}

test('event a only sees activity group event a', function () {
    $eventA = S3_9A_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9A_makeEvent(['slug' => 'event-b']);

    $groupA = S3_9A_makeActivityGroup(['event_id' => $eventA->id, 'name' => 'Group A']);
    S3_9A_makeActivityGroup(['event_id' => $eventB->id, 'name' => 'Group B']);

    expect($eventA->activityGroups()->pluck('id'))->toContain($groupA->id)
        ->and($eventA->activityGroups()->count())->toBe(1);
});

test('event a only sees activity event a', function () {
    $eventA = S3_9A_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9A_makeEvent(['slug' => 'event-b']);

    $activityA = S3_9A_makeActivity(['event_id' => $eventA->id, 'name' => 'Activity A']);
    S3_9A_makeActivity(['event_id' => $eventB->id, 'name' => 'Activity B']);

    expect($eventA->activities()->pluck('id'))->toContain($activityA->id)
        ->and($eventA->activities()->count())->toBe(1);
});

test('same person across multiple events remains isolated', function () {
    $person = S3_9A_makePerson();
    $eventA = S3_9A_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9A_makeEvent(['slug' => 'event-b']);

    $partA = S3_9A_makeParticipation(['person_id' => $person->id, 'event_id' => $eventA->id]);
    $partB = S3_9A_makeParticipation(['person_id' => $person->id, 'event_id' => $eventB->id]);

    expect($partA->event_id)->toBe($eventA->id)
        ->and($partB->event_id)->toBe($eventB->id)
        ->and(Participation::count())->toBe(2);
});

test('one participation can join multiple activities in same event', function () {
    $event = S3_9A_makeEvent();
    $participation = S3_9A_makeParticipation(['event_id' => $event->id]);
    $activityA = S3_9A_makeActivity(['event_id' => $event->id, 'name' => 'Activity A']);
    $activityB = S3_9A_makeActivity(['event_id' => $event->id, 'name' => 'Activity B']);

    $regA = S3_9A_makeRegistration(['participation_id' => $participation->id, 'activity_id' => $activityA->id]);
    $regB = S3_9A_makeRegistration(['participation_id' => $participation->id, 'activity_id' => $activityB->id]);

    expect($regA->exists)->toBeTrue()
        ->and($regB->exists)->toBeTrue()
        ->and($participation->activityRegistrations()->count())->toBe(2);
});

test('participation event a cannot register to activity event b', function () {
    $eventA = S3_9A_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9A_makeEvent(['slug' => 'event-b']);
    $participation = S3_9A_makeParticipation(['event_id' => $eventA->id]);
    $activity = S3_9A_makeActivity(['event_id' => $eventB->id]);

    expect(fn () => S3_9A_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('activity event a cannot use activity group event b', function () {
    $eventA = S3_9A_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9A_makeEvent(['slug' => 'event-b']);
    $groupB = S3_9A_makeActivityGroup(['event_id' => $eventB->id]);

    expect(fn () => S3_9A_makeActivity([
        'event_id' => $eventA->id,
        'activity_group_id' => $groupB->id,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('duplicate activity registration is rejected', function () {
    $event = S3_9A_makeEvent();
    $participation = S3_9A_makeParticipation(['event_id' => $event->id]);
    $activity = S3_9A_makeActivity(['event_id' => $event->id]);

    S3_9A_makeRegistration(['participation_id' => $participation->id, 'activity_id' => $activity->id]);

    expect(fn () => S3_9A_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
    ]))->toThrow(QueryException::class);
});

test('activity registration stores correct event id', function () {
    $event = S3_9A_makeEvent();
    $participation = S3_9A_makeParticipation(['event_id' => $event->id]);
    $activity = S3_9A_makeActivity(['event_id' => $event->id]);

    $registration = S3_9A_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
    ]);

    expect($registration->event_id)->toBe($event->id);
});

test('legacy peserta mapping remains intact with activity domain', function () {
    $mapping = S3_9A_makeLegacyMapping();

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->person)->not->toBeNull()
        ->and($mapping->participation)->not->toBeNull()
        ->and($mapping->event)->not->toBeNull();
});

test('existing event participation behavior does not regress', function () {
    $event = S3_9A_makeEvent();
    $person = S3_9A_makePerson();
    $participation = S3_9A_makeParticipation(['person_id' => $person->id, 'event_id' => $event->id]);

    expect($event->participations()->count())->toBe(1)
        ->and($person->participations()->count())->toBe(1)
        ->and($participation->person->id)->toBe($person->id)
        ->and($participation->event->id)->toBe($event->id);
});
