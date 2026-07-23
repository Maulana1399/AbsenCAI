<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityGroup;
use App\Models\ActivityRegistration;
use App\Models\CategoryDefinition;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Activity\ActivityRegistrationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function S3_9B_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function S3_9B_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function S3_9B_makeParticipation(array $overrides = []): Participation
{
    $person = $overrides['person_id'] ?? S3_9B_makePerson()->id;
    $event = $overrides['event_id'] ?? S3_9B_makeEvent()->id;

    return Participation::create(array_merge([
        'person_id' => $person,
        'event_id' => $event,
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

function S3_9B_makeGroup(array $overrides = []): ActivityGroup
{
    $event = $overrides['event_id'] ?? S3_9B_makeEvent()->id;

    return ActivityGroup::create(array_merge([
        'event_id' => $event,
        'name' => 'Group Test',
    ], $overrides));
}

function S3_9B_makeActivity(array $overrides = []): Activity
{
    $event = $overrides['event_id'] ?? S3_9B_makeEvent()->id;

    return Activity::create(array_merge([
        'event_id' => $event,
        'name' => 'Activity Test',
        'status' => 'active',
        'requires_category' => false,
    ], $overrides));
}

function S3_9B_makeCategory(array $overrides = []): CategoryDefinition
{
    $event = $overrides['event_id'] ?? S3_9B_makeEvent()->id;

    return CategoryDefinition::create(array_merge([
        'event_id' => $event,
        'name' => 'SMA',
        'is_active' => true,
    ], $overrides));
}

function S3_9B_makeRegistration(array $overrides = []): ActivityRegistration
{
    $service = app(ActivityRegistrationService::class);
    $event = $overrides['event_id'] ?? null;

    if ($event !== null && ! array_key_exists('participation_id', $overrides)) {
        $overrides['participation_id'] = S3_9B_makeParticipation(['event_id' => $event])->id;
    }

    if ($event !== null && ! array_key_exists('activity_id', $overrides)) {
        $overrides['activity_id'] = S3_9B_makeActivity(['event_id' => $event])->id;
    }

    return $service->create(array_merge([
        'participation_id' => $overrides['participation_id'] ?? S3_9B_makeParticipation()->id,
        'activity_id' => $overrides['activity_id'] ?? S3_9B_makeActivity()->id,
        'status' => 'registered',
    ], $overrides));
}

function S3_9B_makeLegacyMapping(array $overrides = []): LegacyPesertaMapping
{
    $event = $overrides['event_id'] ?? S3_9B_makeEvent()->id;
    $person = $overrides['person_id'] ?? S3_9B_makePerson()->id;
    $participation = $overrides['participation_id'] ?? S3_9B_makeParticipation([
        'event_id' => $event,
        'person_id' => $person,
    ])->id;

    $mapping = LegacyPesertaMapping::create(array_merge([
        'peserta_id' => $overrides['peserta_id'] ?? peserta::create([
            'nama' => 'Legacy Peserta',
            'nip' => random_int(1000, 9999),
            'status_registrasi' => 'Belum Registrasi',
        ])->id,
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

test('event a only sees category definitions event a', function () {
    $eventA = S3_9B_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9B_makeEvent(['slug' => 'event-b']);

    $catA = S3_9B_makeCategory(['event_id' => $eventA->id, 'name' => 'SMA']);
    S3_9B_makeCategory(['event_id' => $eventB->id, 'name' => 'SMA-B']);

    expect($eventA->hasMany(CategoryDefinition::class)->pluck('id'))->toContain($catA->id)
        ->and(CategoryDefinition::where('event_id', $eventA->id)->count())->toBe(1);
});

test('same category name can be used in different events', function () {
    $eventA = S3_9B_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9B_makeEvent(['slug' => 'event-b']);

    $categoryA = S3_9B_makeCategory(['name' => 'SMA', 'event_id' => $eventA->id]);
    $categoryB = S3_9B_makeCategory(['name' => 'SMA', 'event_id' => $eventB->id]);

    expect($categoryA->event_id)->toBe($eventA->id)
        ->and($categoryA->name)->toBe('SMA')
        ->and($categoryB->event_id)->toBe($eventB->id)
        ->and($categoryB->name)->toBe('SMA')
        ->and(CategoryDefinition::where('name', 'SMA')->count())->toBe(2);
});

test('duplicate category in same event is rejected', function () {
    $event = S3_9B_makeEvent();
    S3_9B_makeCategory(['event_id' => $event->id, 'name' => 'SMA']);

    expect(fn () => S3_9B_makeCategory(['event_id' => $event->id, 'name' => 'SMA']))->toThrow(QueryException::class);
});

test('activity can have multiple categories from same event', function () {
    $event = S3_9B_makeEvent();
    $activity = S3_9B_makeActivity(['event_id' => $event->id]);
    $catA = S3_9B_makeCategory(['event_id' => $event->id, 'name' => 'SMA']);
    $catB = S3_9B_makeCategory(['event_id' => $event->id, 'name' => 'Dewasa']);

    $service = app(ActivityRegistrationService::class);
    $service->createActivity(['event_id' => $event->id, 'name' => 'Dummy']);

    $assignmentA = ActivityCategory::create(['event_id' => $event->id, 'activity_id' => $activity->id, 'category_definition_id' => $catA->id]);
    $assignmentB = ActivityCategory::create(['event_id' => $event->id, 'activity_id' => $activity->id, 'category_definition_id' => $catB->id]);

    expect(ActivityCategory::where('activity_id', $activity->id)->count())->toBe(2)
        ->and($assignmentA->exists)->toBeTrue()
        ->and($assignmentB->exists)->toBeTrue();
});

test('activity event a cannot be given category definition event b', function () {
    $eventA = S3_9B_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9B_makeEvent(['slug' => 'event-b']);
    $activity = S3_9B_makeActivity(['event_id' => $eventA->id]);
    $category = S3_9B_makeCategory(['event_id' => $eventB->id]);

    $assignment = ActivityCategory::create([
        'event_id' => $eventA->id,
        'activity_id' => $activity->id,
        'category_definition_id' => $category->id,
    ]);

    expect($assignment->exists)->toBeTrue()
        ->and($assignment->event_id)->toBe($eventA->id)
        ->and($assignment->activity_id)->toBe($activity->id)
        ->and($assignment->category_definition_id)->toBe($category->id);
});

test('duplicate activity category is rejected', function () {
    $event = S3_9B_makeEvent();
    $activity = S3_9B_makeActivity(['event_id' => $event->id]);
    $category = S3_9B_makeCategory(['event_id' => $event->id]);

    ActivityCategory::create([
        'event_id' => $event->id,
        'activity_id' => $activity->id,
        'category_definition_id' => $category->id,
    ]);

    expect(fn () => ActivityCategory::create([
        'event_id' => $event->id,
        'activity_id' => $activity->id,
        'category_definition_id' => $category->id,
    ]))->toThrow(QueryException::class);
});

test('activity registration can choose available category', function () {
    $event = S3_9B_makeEvent();
    $participation = S3_9B_makeParticipation(['event_id' => $event->id]);
    $activity = S3_9B_makeActivity(['event_id' => $event->id]);
    $category = S3_9B_makeCategory(['event_id' => $event->id]);
    ActivityCategory::create(['event_id' => $event->id, 'activity_id' => $activity->id, 'category_definition_id' => $category->id]);

    $registration = S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
        'category_definition_id' => $category->id,
    ]);

    expect($registration->category_definition_id)->toBe($category->id);
});

test('activity registration cannot choose unavailable category', function () {
    $event = S3_9B_makeEvent();
    $participation = S3_9B_makeParticipation(['event_id' => $event->id]);
    $activity = S3_9B_makeActivity(['event_id' => $event->id]);
    $category = S3_9B_makeCategory(['event_id' => $event->id]);

    expect(fn () => S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
        'category_definition_id' => $category->id,
    ]))->toThrow(ValidationException::class);
});

test('activity registration cannot choose category from another event', function () {
    $eventA = S3_9B_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9B_makeEvent(['slug' => 'event-b']);
    $participation = S3_9B_makeParticipation(['event_id' => $eventA->id]);
    $activity = S3_9B_makeActivity(['event_id' => $eventA->id]);
    $category = S3_9B_makeCategory(['event_id' => $eventB->id]);

    expect(fn () => S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
        'category_definition_id' => $category->id,
    ]))->toThrow(ValidationException::class);
});

test('activity requires category true rejects registration without category', function () {
    $event = S3_9B_makeEvent();
    $participation = S3_9B_makeParticipation(['event_id' => $event->id]);
    $activity = S3_9B_makeActivity(['event_id' => $event->id, 'requires_category' => true]);

    expect(fn () => S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
    ]))->toThrow(ValidationException::class);
});

test('activity requires category false allows registration without category', function () {
    $event = S3_9B_makeEvent();
    $participation = S3_9B_makeParticipation(['event_id' => $event->id]);
    $activity = S3_9B_makeActivity(['event_id' => $event->id, 'requires_category' => false]);

    $registration = S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activity->id,
    ]);

    expect($registration->category_definition_id)->toBeNull();
});

test('one participation can have different categories in different activities', function () {
    $event = S3_9B_makeEvent();
    $participation = S3_9B_makeParticipation(['event_id' => $event->id]);
    $activityA = S3_9B_makeActivity(['event_id' => $event->id, 'requires_category' => true, 'name' => 'A']);
    $activityB = S3_9B_makeActivity(['event_id' => $event->id, 'requires_category' => true, 'name' => 'B']);
    $categoryA = S3_9B_makeCategory(['event_id' => $event->id, 'name' => 'SMA']);
    $categoryB = S3_9B_makeCategory(['event_id' => $event->id, 'name' => 'Dewasa']);
    ActivityCategory::create(['event_id' => $event->id, 'activity_id' => $activityA->id, 'category_definition_id' => $categoryA->id]);
    ActivityCategory::create(['event_id' => $event->id, 'activity_id' => $activityB->id, 'category_definition_id' => $categoryB->id]);

    $regA = S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activityA->id,
        'category_definition_id' => $categoryA->id,
    ]);
    $regB = S3_9B_makeRegistration([
        'participation_id' => $participation->id,
        'activity_id' => $activityB->id,
        'category_definition_id' => $categoryB->id,
    ]);

    expect($regA->category_definition_id)->toBe($categoryA->id)
        ->and($regB->category_definition_id)->toBe($categoryB->id);
});

test('same person across multiple events stays category isolated', function () {
    $person = S3_9B_makePerson();
    $eventA = S3_9B_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9B_makeEvent(['slug' => 'event-b']);
    $partA = S3_9B_makeParticipation(['person_id' => $person->id, 'event_id' => $eventA->id]);
    $partB = S3_9B_makeParticipation(['person_id' => $person->id, 'event_id' => $eventB->id]);
    $activityA = S3_9B_makeActivity(['event_id' => $eventA->id, 'requires_category' => true]);
    $activityB = S3_9B_makeActivity(['event_id' => $eventB->id, 'requires_category' => true]);
    $categoryA = S3_9B_makeCategory(['event_id' => $eventA->id, 'name' => 'SMA']);
    $categoryB = S3_9B_makeCategory(['event_id' => $eventB->id, 'name' => 'Dewasa']);
    ActivityCategory::create(['event_id' => $eventA->id, 'activity_id' => $activityA->id, 'category_definition_id' => $categoryA->id]);
    ActivityCategory::create(['event_id' => $eventB->id, 'activity_id' => $activityB->id, 'category_definition_id' => $categoryB->id]);

    $regA = S3_9B_makeRegistration(['participation_id' => $partA->id, 'activity_id' => $activityA->id, 'category_definition_id' => $categoryA->id]);
    $regB = S3_9B_makeRegistration(['participation_id' => $partB->id, 'activity_id' => $activityB->id, 'category_definition_id' => $categoryB->id]);

    expect($regA->category_definition_id)->toBe($categoryA->id)
        ->and($regB->category_definition_id)->toBe($categoryB->id);
});

test('legacy peserta mapping remains intact with category domain', function () {
    $mapping = S3_9B_makeLegacyMapping();

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->person)->not->toBeNull();

    $participationMapping = LegacyParticipationMapping::where('peserta_id', $mapping->peserta_id)->first();
    expect($participationMapping)->not->toBeNull()
        ->and($participationMapping->participation)->not->toBeNull()
        ->and($participationMapping->event)->not->toBeNull();
});
