<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityGroup;
use App\Models\CategoryDefinition;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\Rundown;
use App\Models\RundownItem;
use App\Models\Venue;
use App\Models\peserta;
use App\Models\LegacyParticipationMapping;
use App\Services\Activity\EventCommitteeService;
use App\Services\Activity\ActivityRegistrationService;
use App\Services\Activity\ActivityScheduleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function S3_9D_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function S3_9D_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function S3_9D_makeParticipation(array $overrides = []): Participation
{
    $person = $overrides['person_id'] ?? S3_9D_makePerson()->id;
    $event = $overrides['event_id'] ?? S3_9D_makeEvent()->id;

    return Participation::create(array_merge([
        'person_id' => $person,
        'event_id' => $event,
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

function S3_9D_makeRole(array $overrides = []): EventRole
{
    $event = $overrides['event_id'] ?? S3_9D_makeEvent()->id;

    return app(EventCommitteeService::class)->createRole(array_merge([
        'event_id' => $event,
        'name' => 'Ketua Panitia',
        'code' => 'ketua_event',
        'scope' => 'event',
    ], $overrides));
}

function S3_9D_makeGroup(array $overrides = []): ActivityGroup
{
    $event = $overrides['event_id'] ?? S3_9D_makeEvent()->id;
    return ActivityGroup::create(array_merge(['event_id' => $event, 'name' => 'Group Test'], $overrides));
}

function S3_9D_makeActivity(array $overrides = []): Activity
{
    $event = $overrides['event_id'] ?? S3_9D_makeEvent()->id;
    return Activity::create(array_merge(['event_id' => $event, 'name' => 'Activity Test', 'status' => 'active', 'requires_category' => false], $overrides));
}

function S3_9D_makeVenue(array $overrides = []): Venue
{
    $event = $overrides['event_id'] ?? S3_9D_makeEvent()->id;
    return app(ActivityScheduleService::class)->createVenue(array_merge(['event_id' => $event, 'name' => 'Venue Test'], $overrides));
}

function S3_9D_makeAssignment(array $overrides = []): EventCommitteeAssignment
{
    $service = app(EventCommitteeService::class);
    $event = $overrides['event_id'] ?? null;
    if ($event !== null && ! array_key_exists('event_role_id', $overrides)) {
        $overrides['event_role_id'] = S3_9D_makeRole(['event_id' => $event])->id;
    }
    if ($event !== null && ! array_key_exists('person_id', $overrides)) {
        $overrides['person_id'] = S3_9D_makePerson()->id;
    }
    return $service->assign(array_merge(['event_id' => $event ?? S3_9D_makeEvent()->id, 'person_id' => $overrides['person_id'] ?? S3_9D_makePerson()->id, 'event_role_id' => $overrides['event_role_id'] ?? S3_9D_makeRole()->id], $overrides));
}

test('event role is event scoped', function () {
    $event = S3_9D_makeEvent();
    $role = S3_9D_makeRole(['event_id' => $event->id, 'name' => 'Sekretaris']);

    expect($role->event_id)->toBe($event->id)
        ->and($event->eventRoles()->count())->toBe(1);
});

test('same role name can be used in different events', function () {
    $roleA = S3_9D_makeRole(['event_id' => S3_9D_makeEvent(['slug' => 'event-a'])->id, 'name' => 'Ketua Panitia']);
    $roleB = S3_9D_makeRole(['event_id' => S3_9D_makeEvent(['slug' => 'event-b'])->id, 'name' => 'Ketua Panitia']);

    expect($roleA->name)->toBe('Ketua Panitia')
        ->and($roleB->name)->toBe('Ketua Panitia');
});

test('duplicate role name in same event is rejected', function () {
    $event = S3_9D_makeEvent();
    S3_9D_makeRole(['event_id' => $event->id, 'name' => 'Ketua Panitia']);
    expect(fn () => S3_9D_makeRole(['event_id' => $event->id, 'name' => 'Ketua Panitia']))->toThrow(QueryException::class);
});

test('person can be committee without participation', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $event->id]);

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    expect($assignment->exists)->toBeTrue()
        ->and($assignment->participation_id)->toBeNull();
});

test('person can be committee with their own participation', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $participation = S3_9D_makeParticipation(['event_id' => $event->id, 'person_id' => $person->id]);
    $role = S3_9D_makeRole(['event_id' => $event->id]);

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_role_id' => $role->id,
    ]);

    expect($assignment->participation_id)->toBe($participation->id);
});

test('participation from other event is rejected', function () {
    $eventA = S3_9D_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9D_makeEvent(['slug' => 'event-b']);
    $person = S3_9D_makePerson();
    $participation = S3_9D_makeParticipation(['event_id' => $eventB->id, 'person_id' => $person->id]);
    $role = S3_9D_makeRole(['event_id' => $eventA->id]);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_role_id' => $role->id,
    ]))->toThrow(ValidationException::class);
});

test('participation owned by other person is rejected', function () {
    $event = S3_9D_makeEvent();
    $personA = S3_9D_makePerson(['nama' => 'A']);
    $personB = S3_9D_makePerson(['nama' => 'B']);
    $participation = S3_9D_makeParticipation(['event_id' => $event->id, 'person_id' => $personB->id]);
    $role = S3_9D_makeRole(['event_id' => $event->id]);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $personA->id,
        'participation_id' => $participation->id,
        'event_role_id' => $role->id,
    ]))->toThrow(ValidationException::class);
});

test('event role from other event is rejected', function () {
    $eventA = S3_9D_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9D_makeEvent(['slug' => 'event-b']);
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $eventB->id]);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]))->toThrow(ValidationException::class);
});

test('activity group from other event is rejected', function () {
    $eventA = S3_9D_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9D_makeEvent(['slug' => 'event-b']);
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $eventA->id]);
    $group = S3_9D_makeGroup(['event_id' => $eventB->id]);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
        'activity_group_id' => $group->id,
    ]))->toThrow(ValidationException::class);
});

test('activity from other event is rejected', function () {
    $eventA = S3_9D_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9D_makeEvent(['slug' => 'event-b']);
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $eventA->id]);
    $activity = S3_9D_makeActivity(['event_id' => $eventB->id]);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
        'activity_id' => $activity->id,
    ]))->toThrow(ValidationException::class);
});

test('venue from other event is rejected', function () {
    $eventA = S3_9D_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9D_makeEvent(['slug' => 'event-b']);
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $eventA->id]);
    $venue = S3_9D_makeVenue(['event_id' => $eventB->id]);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
        'venue_id' => $venue->id,
    ]))->toThrow(ValidationException::class);
});

test('valid activity group assignment succeeds', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $event->id, 'scope' => 'activity_group']);
    $group = S3_9D_makeGroup(['event_id' => $event->id]);

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
        'activity_group_id' => $group->id,
    ]);

    expect($assignment->activity_group_id)->toBe($group->id);
});

test('valid activity assignment succeeds', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $event->id, 'scope' => 'activity']);
    $activity = S3_9D_makeActivity(['event_id' => $event->id]);

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
        'activity_id' => $activity->id,
    ]);

    expect($assignment->activity_id)->toBe($activity->id);
});

test('valid venue assignment succeeds', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $event->id, 'scope' => 'venue']);
    $venue = S3_9D_makeVenue(['event_id' => $event->id]);

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
        'venue_id' => $venue->id,
    ]);

    expect($assignment->venue_id)->toBe($venue->id);
});

test('person can have multiple committee roles in same event', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $roleA = S3_9D_makeRole(['event_id' => $event->id, 'name' => 'Ketua Panitia']);
    $roleB = S3_9D_makeRole(['event_id' => $event->id, 'name' => 'Sekretaris', 'code' => 'sekretariat']);

    $assignmentA = app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $person->id, 'event_role_id' => $roleA->id]);
    $assignmentB = app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $person->id, 'event_role_id' => $roleB->id]);

    expect($assignmentA->exists)->toBeTrue()
        ->and($assignmentB->exists)->toBeTrue();
});

test('same role can be given to multiple people', function () {
    $event = S3_9D_makeEvent();
    $role = S3_9D_makeRole(['event_id' => $event->id]);
    $personA = S3_9D_makePerson(['nama' => 'A']);
    $personB = S3_9D_makePerson(['nama' => 'B']);

    $a = app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $personA->id, 'event_role_id' => $role->id]);
    $b = app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $personB->id, 'event_role_id' => $role->id]);

    expect($a->exists)->toBeTrue()->and($b->exists)->toBeTrue();
});

test('duplicate identical committee assignment is prevented', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $event->id]);

    app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $person->id, 'event_role_id' => $role->id]);

    expect(fn () => app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $person->id, 'event_role_id' => $role->id]))->toThrow(ValidationException::class);
});

test('same person can be committee in multiple events', function () {
    $person = S3_9D_makePerson();
    $eventA = S3_9D_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9D_makeEvent(['slug' => 'event-b']);
    $roleA = S3_9D_makeRole(['event_id' => $eventA->id]);
    $roleB = S3_9D_makeRole(['event_id' => $eventB->id]);

    $assignmentA = app(EventCommitteeService::class)->assign(['event_id' => $eventA->id, 'person_id' => $person->id, 'event_role_id' => $roleA->id]);
    $assignmentB = app(EventCommitteeService::class)->assign(['event_id' => $eventB->id, 'person_id' => $person->id, 'event_role_id' => $roleB->id]);

    expect($assignmentA->event_id)->toBe($eventA->id)
        ->and($assignmentB->event_id)->toBe($eventB->id);
});

test('becoming committee does not create participation automatically', function () {
    $event = S3_9D_makeEvent();
    $person = S3_9D_makePerson();
    $role = S3_9D_makeRole(['event_id' => $event->id]);

    app(EventCommitteeService::class)->assign(['event_id' => $event->id, 'person_id' => $person->id, 'event_role_id' => $role->id]);

    expect($person->participations()->count())->toBe(0);
});

test('legacy peserta mapping remains intact with committee domain', function () {
    $peserta = peserta::create(['nama' => 'Legacy', 'nip' => random_int(1000, 9999), 'status_registrasi' => 'Belum Registrasi']);
    $person = S3_9D_makePerson();
    $event = S3_9D_makeEvent();
    $participation = S3_9D_makeParticipation(['event_id' => $event->id, 'person_id' => $person->id]);
    LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    expect(LegacyPesertaMapping::count())->toBe(1);
});
