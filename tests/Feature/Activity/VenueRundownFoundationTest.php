<?php

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\ActivityGroup;
use App\Models\CategoryDefinition;
use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\Rundown;
use App\Models\RundownItem;
use App\Models\Venue;
use App\Models\peserta;
use App\Services\Activity\ActivityRegistrationService;
use App\Services\Activity\ActivityScheduleService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function S3_9C_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function S3_9C_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function S3_9C_makeParticipation(array $overrides = []): Participation
{
    $person = $overrides['person_id'] ?? S3_9C_makePerson()->id;
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;

    return Participation::create(array_merge([
        'person_id' => $person,
        'event_id' => $event,
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

function S3_9C_makeActivity(array $overrides = []): Activity
{
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;

    return Activity::create(array_merge([
        'event_id' => $event,
        'name' => 'Activity Test',
        'status' => 'active',
        'requires_category' => false,
    ], $overrides));
}

function S3_9C_makeCategory(array $overrides = []): CategoryDefinition
{
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;

    return CategoryDefinition::create(array_merge([
        'event_id' => $event,
        'name' => 'SMA',
        'is_active' => true,
    ], $overrides));
}

function S3_9C_makeGroup(array $overrides = []): ActivityGroup
{
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;

    return ActivityGroup::create(array_merge([
        'event_id' => $event,
        'name' => 'Group Test',
    ], $overrides));
}

function S3_9C_makeVenue(array $overrides = []): Venue
{
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;

    return app(ActivityScheduleService::class)->createVenue(array_merge([
        'event_id' => $event,
        'name' => 'Venue Test',
    ], $overrides));
}

function S3_9C_makeRundown(array $overrides = []): Rundown
{
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;

    return app(ActivityScheduleService::class)->createRundown(array_merge([
        'event_id' => $event,
        'name' => 'Rundown Test',
    ], $overrides));
}

function S3_9C_makeRundownItem(array $overrides = []): RundownItem
{
    $service = app(ActivityScheduleService::class);
    $event = $overrides['event_id'] ?? null;

    if ($event !== null && ! array_key_exists('rundown_id', $overrides)) {
        $overrides['rundown_id'] = S3_9C_makeRundown(['event_id' => $event])->id;
    }

    if ($event !== null && ! array_key_exists('activity_id', $overrides)) {
        $overrides['activity_id'] = S3_9C_makeActivity(['event_id' => $event])->id;
    }

    return $service->createRundownItem(array_merge([
        'rundown_id' => $overrides['rundown_id'] ?? S3_9C_makeRundown()->id,
        'activity_id' => $overrides['activity_id'] ?? S3_9C_makeActivity()->id,
        'starts_at' => $overrides['starts_at'] ?? now(),
    ], $overrides));
}

function S3_9C_makeLegacyMapping(array $overrides = []): LegacyPesertaMapping
{
    $event = $overrides['event_id'] ?? S3_9C_makeEvent()->id;
    $person = $overrides['person_id'] ?? S3_9C_makePerson()->id;
    $participation = $overrides['participation_id'] ?? S3_9C_makeParticipation([
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

test('event a only sees venue event a', function () {
    $eventA = S3_9C_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9C_makeEvent(['slug' => 'event-b']);

    $venueA = S3_9C_makeVenue(['event_id' => $eventA->id, 'name' => 'Venue A']);
    S3_9C_makeVenue(['event_id' => $eventB->id, 'name' => 'Venue B']);

    expect(Venue::where('event_id', $eventA->id)->count())->toBe(1)
        ->and(Venue::where('event_id', $eventA->id)->first()->id)->toBe($venueA->id);
});

test('event a only sees rundown event a', function () {
    $eventA = S3_9C_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9C_makeEvent(['slug' => 'event-b']);

    $rundownA = S3_9C_makeRundown(['event_id' => $eventA->id, 'name' => 'Rundown A']);
    S3_9C_makeRundown(['event_id' => $eventB->id, 'name' => 'Rundown B']);

    expect(Rundown::where('event_id', $eventA->id)->count())->toBe(1)
        ->and(Rundown::where('event_id', $eventA->id)->first()->id)->toBe($rundownA->id);
});

test('rundown item can link activity and venue in same event', function () {
    $event = S3_9C_makeEvent();
    $rundown = S3_9C_makeRundown(['event_id' => $event->id]);
    $activity = S3_9C_makeActivity(['event_id' => $event->id]);
    $venue = S3_9C_makeVenue(['event_id' => $event->id]);

    $item = S3_9C_makeRundownItem([
        'rundown_id' => $rundown->id,
        'activity_id' => $activity->id,
        'venue_id' => $venue->id,
        'starts_at' => now(),
    ]);

    expect($item->event_id)->toBe($event->id)
        ->and($item->rundown_id)->toBe($rundown->id)
        ->and($item->activity_id)->toBe($activity->id)
        ->and($item->venue_id)->toBe($venue->id);
});

test('rundown item cannot link activity from other event', function () {
    $eventA = S3_9C_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9C_makeEvent(['slug' => 'event-b']);
    $rundown = S3_9C_makeRundown(['event_id' => $eventA->id]);
    $activity = S3_9C_makeActivity(['event_id' => $eventB->id]);

    expect(fn () => S3_9C_makeRundownItem([
        'rundown_id' => $rundown->id,
        'activity_id' => $activity->id,
        'starts_at' => now(),
    ]))->toThrow(ValidationException::class);
});

test('rundown item cannot link venue from other event', function () {
    $eventA = S3_9C_makeEvent(['slug' => 'event-a']);
    $eventB = S3_9C_makeEvent(['slug' => 'event-b']);
    $rundown = S3_9C_makeRundown(['event_id' => $eventA->id]);
    $activity = S3_9C_makeActivity(['event_id' => $eventA->id]);
    $venue = S3_9C_makeVenue(['event_id' => $eventB->id]);

    expect(fn () => S3_9C_makeRundownItem([
        'rundown_id' => $rundown->id,
        'activity_id' => $activity->id,
        'venue_id' => $venue->id,
        'starts_at' => now(),
    ]))->toThrow(ValidationException::class);
});

test('parallel rundown items can exist at same time on different venues', function () {
    $event = S3_9C_makeEvent();
    $rundown = S3_9C_makeRundown(['event_id' => $event->id]);
    $activityA = S3_9C_makeActivity(['event_id' => $event->id, 'name' => 'A']);
    $activityB = S3_9C_makeActivity(['event_id' => $event->id, 'name' => 'B']);
    $venueA = S3_9C_makeVenue(['event_id' => $event->id, 'name' => 'Venue A']);
    $venueB = S3_9C_makeVenue(['event_id' => $event->id, 'name' => 'Venue B']);
    $time = now();

    $itemA = S3_9C_makeRundownItem([
        'rundown_id' => $rundown->id,
        'activity_id' => $activityA->id,
        'venue_id' => $venueA->id,
        'starts_at' => $time,
    ]);
    $itemB = S3_9C_makeRundownItem([
        'rundown_id' => $rundown->id,
        'activity_id' => $activityB->id,
        'venue_id' => $venueB->id,
        'starts_at' => $time,
    ]);

    expect($itemA->exists)->toBeTrue()
        ->and($itemB->exists)->toBeTrue()
        ->and(RundownItem::count())->toBe(2);
});

test('legacy peserta mapping remains intact with venue and rundown domain', function () {
    $mapping = S3_9C_makeLegacyMapping();

    expect($mapping->exists)->toBeTrue()
        ->and($mapping->person)->not->toBeNull()
        ->and($mapping->participation)->not->toBeNull()
        ->and($mapping->event)->not->toBeNull();
});
