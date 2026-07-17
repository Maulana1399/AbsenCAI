<?php

use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function EventFoundation_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-' . str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function EventFoundation_makeUser(): User
{
    return User::factory()->create();
}

function EventFoundation_makeSession(array $overrides = []): \App\Models\SesiAbsensi
{
    return \App\Models\SesiAbsensi::create(array_merge([
        'nama_sesi' => 'Sesi Test',
        'tanggal' => '2026-07-21',
        'aktif' => true,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Event creation & validation
// ---------------------------------------------------------------------------

test('event can be created with minimal fields', function () {
    $event = EventFoundation_makeEvent(['name' => 'Minimal Event']);

    expect($event->exists)->toBeTrue()
        ->and($event->name)->toBe('Minimal Event')
        ->and($event->slug)->not->toBeEmpty()
        ->and($event->status)->toBe('active')
        ->and($event->description)->toBeNull()
        ->and($event->start_date)->toBeNull()
        ->and($event->end_date)->toBeNull();
});

test('event requires a name', function () {
    expect(fn () => EventFoundation_makeEvent(['name' => null]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('event slug must be unique', function () {
    EventFoundation_makeEvent(['slug' => 'same-slug']);
    expect(fn () => EventFoundation_makeEvent(['slug' => 'same-slug']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('event auto-generates slug from name when not provided', function () {
    $event = Event::create(['name' => 'My Event Name']);

    expect($event->slug)->toBe('my-event-name');
});

test('event can have optional description and dates', function () {
    $event = EventFoundation_makeEvent([
        'description' => 'An event description',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-10',
    ]);

    expect($event->description)->toBe('An event description')
        ->and($event->start_date->format('Y-m-d'))->toBe('2026-08-01')
        ->and($event->end_date->format('Y-m-d'))->toBe('2026-08-10');
});

test('event status defaults to active', function () {
    $event = EventFoundation_makeEvent();

    expect($event->status)->toBe('active')
        ->and($event->isActive())->toBeTrue()
        ->and($event->isArchived())->toBeFalse();
});

test('event can be archived', function () {
    $event = EventFoundation_makeEvent();
    $event->update(['status' => 'archived']);

    expect($event->fresh()->isActive())->toBeFalse()
        ->and($event->fresh()->isArchived())->toBeTrue();
});

test('active scope only returns active events', function () {
    EventFoundation_makeEvent(['slug' => 'active-1']);
    EventFoundation_makeEvent(['slug' => 'active-2']);
    $archived = EventFoundation_makeEvent(['slug' => 'archived-1']);
    $archived->update(['status' => 'archived']);

    expect(Event::active()->count())->toBe(2)
        ->and(Event::count())->toBe(3);
});

// ---------------------------------------------------------------------------
// Legacy event bootstrap
// ---------------------------------------------------------------------------

test('legacy event seeder creates the CAI operational event', function () {
    $this->seed(\Database\Seeders\LegacyEventSeeder::class);

    $event = Event::where('slug', 'cai-operational')->first();

    expect($event)->not->toBeNull()
        ->and($event->name)->not->toBeEmpty()
        ->and($event->status)->toBe('active');
});

test('legacy event seeder is idempotent', function () {
    $this->seed(\Database\Seeders\LegacyEventSeeder::class);
    $this->seed(\Database\Seeders\LegacyEventSeeder::class);
    $this->seed(\Database\Seeders\LegacyEventSeeder::class);

    expect(Event::where('slug', 'cai-operational')->count())->toBe(1);
});

test('legacy event seeder works on fresh database', function () {
    $this->seed(\Database\Seeders\LegacyEventSeeder::class);

    expect(Event::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// ActiveEventContext
// ---------------------------------------------------------------------------

test('active event context returns null when no event set', function () {
    $context = app(ActiveEventContext::class);

    expect($context->current())->toBeNull()
        ->and($context->id())->toBeNull()
        ->and($context->hasActiveEvent())->toBeFalse();
});

test('active event context can set and retrieve an event', function () {
    $event = EventFoundation_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);

    expect($context->current()->is($event))->toBeTrue()
        ->and($context->id())->toBe($event->id)
        ->and($context->hasActiveEvent())->toBeTrue();
});

test('active event context can switch to a different event', function () {
    $eventA = EventFoundation_makeEvent(['slug' => 'event-a', 'name' => 'A']);
    $eventB = EventFoundation_makeEvent(['slug' => 'event-b', 'name' => 'B']);
    $context = app(ActiveEventContext::class);

    $context->set($eventA);
    expect($context->id())->toBe($eventA->id);

    $result = $context->switchTo($eventB->id);
    expect($result->is($eventB))->toBeTrue()
        ->and($context->id())->toBe($eventB->id);
});

test('active event context cannot set an archived event', function () {
    $event = EventFoundation_makeEvent();
    $event->update(['status' => 'archived']);
    $context = app(ActiveEventContext::class);

    $context->set($event->fresh());

    expect($context->hasActiveEvent())->toBeFalse()
        ->and($context->id())->toBeNull();
});

test('active event context switchTo returns null for invalid event id', function () {
    $context = app(ActiveEventContext::class);

    $result = $context->switchTo(99999);

    expect($result)->toBeNull()
        ->and($context->hasActiveEvent())->toBeFalse();
});

test('active event context switchTo returns null for archived event', function () {
    $event = EventFoundation_makeEvent();
    $event->update(['status' => 'archived']);
    $context = app(ActiveEventContext::class);

    $result = $context->switchTo($event->id);

    expect($result)->toBeNull();
});

test('active event context can be cleared', function () {
    $event = EventFoundation_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);
    expect($context->hasActiveEvent())->toBeTrue();

    $context->clear();

    expect($context->hasActiveEvent())->toBeFalse()
        ->and($context->id())->toBeNull()
        ->and($context->current())->toBeNull();
});

test('active event context is a singleton', function () {
    $context1 = app(ActiveEventContext::class);
    $context2 = app(ActiveEventContext::class);

    $event = EventFoundation_makeEvent();
    $context1->set($event);

    expect($context2->id())->toBe($event->id);
});

// ---------------------------------------------------------------------------
// Event UI — authenticated access
// ---------------------------------------------------------------------------

test('guest cannot access events index page', function () {
    $this->get('/events')->assertRedirect('/login');
});

test('authenticated user can access events index page', function () {
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    $this->get('/events')->assertStatus(200);
});

test('events index lists all events', function () {
    EventFoundation_makeEvent(['name' => 'Event Alpha', 'slug' => 'alpha']);
    EventFoundation_makeEvent(['name' => 'Event Beta', 'slug' => 'beta']);

    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->assertSee('Event Alpha')
        ->assertSee('Event Beta');
});

test('events can be created via Livewire form', function () {
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'New Test Event')
        ->set('newSlug', 'new-test-event')
        ->call('create');

    $this->assertDatabaseHas('events', [
        'name' => 'New Test Event',
        'slug' => 'new-test-event',
        'status' => 'active',
    ]);
});

test('event can be archived via Livewire', function () {
    $event = EventFoundation_makeEvent(['name' => 'Archivable', 'slug' => 'archivable']);
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->call('archive', $event->id);

    expect($event->fresh()->status)->toBe('archived');
});

test('sesi_absensis has nullable event_id column', function () {
    expect(Schema::hasColumn('sesi_absensis', 'event_id'))->toBeTrue();
});

test('existing session can exist without event_id', function () {
    $session = EventFoundation_makeSession();

    expect($session->event_id)->toBeNull();
});

test('session can belong to an event', function () {
    $event = EventFoundation_makeEvent();
    $session = EventFoundation_makeSession([
        'event_id' => $event->id,
    ]);

    expect($session->event->is($event))->toBeTrue();
});

test('event has many sessions', function () {
    $event = EventFoundation_makeEvent();
    EventFoundation_makeSession(['event_id' => $event->id]);
    EventFoundation_makeSession(['event_id' => $event->id, 'nama_sesi' => 'Sesi 2']);

    expect($event->sesiAbsensis)->toHaveCount(2);
});

test('deleting event with referenced session is prevented', function () {
    $event = EventFoundation_makeEvent();
    EventFoundation_makeSession(['event_id' => $event->id]);

    expect(fn () => $event->delete())->toThrow(\Illuminate\Database\QueryException::class);
});

test('deleting unreferenced event remains possible', function () {
    $event = EventFoundation_makeEvent();

    expect($event->delete())->toBeTrue();
    expect(\App\Models\Event::find($event->id))->toBeNull();
});

test('deleting session does not delete its event', function () {
    $event = EventFoundation_makeEvent();
    $session = EventFoundation_makeSession(['event_id' => $event->id]);

    expect($session->delete())->toBeTrue();
    expect(\App\Models\Event::find($event->id))->not->toBeNull();
});

test('legacy CAI event cannot be archived', function () {
    $this->seed(\Database\Seeders\LegacyEventSeeder::class);
    $legacy = Event::where('slug', 'cai-operational')->first();
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->call('archive', $legacy->id);

    expect($legacy->fresh()->status)->toBe('active');
});

test('event switcher shows current event name', function () {
    $event = EventFoundation_makeEvent(['name' => 'Active Event', 'slug' => 'active-event']);
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSee('Active Event');
});

test('event switcher can switch events', function () {
    $eventA = EventFoundation_makeEvent(['name' => 'Switch A', 'slug' => 'switch-a']);
    $eventB = EventFoundation_makeEvent(['name' => 'Switch B', 'slug' => 'switch-b']);
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertSet('currentEventId', $eventB->id)
        ->assertSet('currentEventName', 'Switch B');
});

// ---------------------------------------------------------------------------
// Backward compatibility — existing app still works
// ---------------------------------------------------------------------------

test('existing application works without selecting an event', function () {
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    $this->get('/dashboard')->assertStatus(200);
    $this->get('/database')->assertStatus(200);
    $this->get('/sesi-absensi')->assertStatus(200);
    $this->get('/absensi')->assertStatus(200);
    $this->get('/surat-izin')->assertStatus(200);
    $this->get('/rekap-peserta')->assertStatus(200);
    $this->get('/rekap-absensi')->assertStatus(200);
});

test('no existing identifier contracts change', function () {
    $user = EventFoundation_makeUser();
    $this->actingAs($user);

    $this->get('/sesi-absensi')->assertStatus(200);
    $this->get('/database')->assertStatus(200);
    $this->get('/desa')->assertStatus(200);
    $this->get('/kelompok')->assertStatus(200);
    $this->get('/regu')->assertStatus(200);
    $this->get('/qr-label')->assertStatus(200);
});
