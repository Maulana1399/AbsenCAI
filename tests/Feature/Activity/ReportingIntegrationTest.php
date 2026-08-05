<?php

use App\Exports\ActivityRegistrationExport;
use App\Exports\PesertaExport;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityGroup;
use App\Models\ActivityRegistration;
use App\Models\CategoryDefinition;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\Rundown;
use App\Models\Venue;
use App\Services\Activity\ActivityRegistrationService;
use App\Services\Activity\ActivityScheduleService;
use App\Services\Activity\EventCommitteeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function S3_9E_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge(['name' => 'Test Event', 'slug' => 'test-event-'.str()->random(6), 'status' => 'active'], $overrides));
}

function S3_9E_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge(['nama' => 'Test Person'], $overrides));
}

function S3_9E_makeParticipation(array $overrides = []): Participation
{
    $person = $overrides['person_id'] ?? S3_9E_makePerson()->id;
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return Participation::create(array_merge(['person_id' => $person, 'event_id' => $event, 'jenis_peserta' => 'Wajib'], $overrides));
}

function S3_9E_makeGroup(array $overrides = []): ActivityGroup
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return ActivityGroup::create(array_merge(['event_id' => $event, 'name' => 'Group Test'], $overrides));
}

function S3_9E_makeCategory(array $overrides = []): CategoryDefinition
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return CategoryDefinition::create(array_merge(['event_id' => $event, 'name' => 'SMA', 'is_active' => true], $overrides));
}

function S3_9E_makeActivity(array $overrides = []): Activity
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return Activity::create(array_merge(['event_id' => $event, 'name' => 'Activity Test', 'status' => 'active', 'requires_category' => false], $overrides));
}

function S3_9E_makeVenue(array $overrides = []): Venue
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return app(ActivityScheduleService::class)->createVenue(array_merge(['event_id' => $event, 'name' => 'Venue Test'], $overrides));
}

function S3_9E_makeRundown(array $overrides = []): Rundown
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return app(ActivityScheduleService::class)->createRundown(array_merge(['event_id' => $event, 'name' => 'Rundown Test'], $overrides));
}

function S3_9E_makeRole(array $overrides = []): EventRole
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return app(EventCommitteeService::class)->createRole(array_merge(['event_id' => $event, 'name' => 'Ketua Panitia', 'code' => 'ketua_event', 'scope' => 'event'], $overrides));
}

function S3_9E_makeAssignment(array $overrides = []): EventCommitteeAssignment
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;

    return app(EventCommitteeService::class)->assign(array_merge(['event_id' => $event, 'person_id' => S3_9E_makePerson()->id, 'event_role_id' => S3_9E_makeRole(['event_id' => $event])->id], $overrides));
}

function S3_9E_makeLegacyMapping(array $overrides = []): LegacyPesertaMapping
{
    $event = $overrides['event_id'] ?? S3_9E_makeEvent()->id;
    $person = $overrides['person_id'] ?? S3_9E_makePerson()->id;
    $participation = $overrides['participation_id'] ?? S3_9E_makeParticipation(['event_id' => $event, 'person_id' => $person])->id;

    $mapping = LegacyPesertaMapping::create(array_merge([
        'peserta_id' => $overrides['peserta_id'] ?? peserta::create(['nama' => 'Legacy Peserta', 'nip' => random_int(1000, 9999), 'status_registrasi' => 'Belum Registrasi'])->id,
        'person_id' => $person,
        'migrated_at' => now(),
    ], $overrides));
    LegacyParticipationMapping::create(array_merge([
        'peserta_id' => $mapping->peserta_id,
        'person_id' => $person,
        'participation_id' => $participation,
        'event_id' => $event,
        'migrated_at' => now(),
    ], $overrides));

    return $mapping;
}

test('participants export remains event scoped', function () {
    $eventA = S3_9E_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9E_makeEvent(['slug' => 'event-b']);
    S3_9E_makeParticipation(['event_id' => $eventA->id]);
    S3_9E_makeParticipation(['event_id' => $eventB->id]);

    $export = new PesertaExport($eventA->id);
    expect($export->collection()->count())->toBe(1);
});

test('activity registration report is event scoped', function () {
    $eventA = S3_9E_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9E_makeEvent(['slug' => 'event-b']);
    $partA = S3_9E_makeParticipation(['event_id' => $eventA->id]);
    $partB = S3_9E_makeParticipation(['event_id' => $eventB->id]);
    $activityA = S3_9E_makeActivity(['event_id' => $eventA->id]);
    $activityB = S3_9E_makeActivity(['event_id' => $eventB->id]);
    app(ActivityRegistrationService::class)->create(['participation_id' => $partA->id, 'activity_id' => $activityA->id]);
    app(ActivityRegistrationService::class)->create(['participation_id' => $partB->id, 'activity_id' => $activityB->id]);

    $export = new ActivityRegistrationExport($eventA->id);
    expect($export->collection()->count())->toBe(1);
});

test('activity registration report filters by group activity and category safely', function () {
    $event = S3_9E_makeEvent();
    $group = S3_9E_makeGroup(['event_id' => $event->id]);
    $category = S3_9E_makeCategory(['event_id' => $event->id]);
    $activity = S3_9E_makeActivity(['event_id' => $event->id, 'activity_group_id' => $group->id, 'requires_category' => true]);
    ActivityCategory::create(['event_id' => $event->id, 'activity_id' => $activity->id, 'category_definition_id' => $category->id]);
    $registration = app(ActivityRegistrationService::class)->create(['participation_id' => S3_9E_makeParticipation(['event_id' => $event->id])->id, 'activity_id' => $activity->id, 'category_definition_id' => $category->id]);

    $export = new ActivityRegistrationExport($event->id, $group->id, $activity->id, $category->id);
    expect($export->collection()->pluck('Activity')->contains($activity->name))->toBeTrue()
        ->and($registration->event_id)->toBe($event->id);
});

test('one participation multiple activities produces multiple registration rows', function () {
    $event = S3_9E_makeEvent();
    $participation = S3_9E_makeParticipation(['event_id' => $event->id]);
    $activityA = S3_9E_makeActivity(['event_id' => $event->id, 'name' => 'A']);
    $activityB = S3_9E_makeActivity(['event_id' => $event->id, 'name' => 'B']);
    app(ActivityRegistrationService::class)->create(['participation_id' => $participation->id, 'activity_id' => $activityA->id]);
    app(ActivityRegistrationService::class)->create(['participation_id' => $participation->id, 'activity_id' => $activityB->id]);

    expect(ActivityRegistration::where('event_id', $event->id)->count())->toBe(2)
        ->and(Participation::where('event_id', $event->id)->count())->toBe(1);
});

test('committee query remains event scoped', function () {
    $eventA = S3_9E_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9E_makeEvent(['slug' => 'event-b']);
    S3_9E_makeAssignment(['event_id' => $eventA->id]);
    S3_9E_makeAssignment(['event_id' => $eventB->id]);

    expect(EventCommitteeAssignment::where('event_id', $eventA->id)->count())->toBe(1);
});

test('rundown query remains event scoped', function () {
    $eventA = S3_9E_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9E_makeEvent(['slug' => 'event-b']);
    S3_9E_makeRundown(['event_id' => $eventA->id]);
    S3_9E_makeRundown(['event_id' => $eventB->id]);

    expect(Rundown::where('event_id', $eventA->id)->count())->toBe(1);
});

test('legacy peserta mapping remains intact with reporting integration', function () {
    $mapping = S3_9E_makeLegacyMapping();

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->person)->not->toBeNull();

    $participationMapping = LegacyParticipationMapping::where('peserta_id', $mapping->peserta_id)->first();
    expect($participationMapping)->not->toBeNull()
        ->and($participationMapping->participation)->not->toBeNull();
});
