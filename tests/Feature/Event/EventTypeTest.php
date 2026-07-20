<?php

use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function et_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Event Type Test',
        'slug' => 'event-type-'.str()->random(6),
        'event_type' => 'cai',
        'status' => 'active',
    ], $overrides));
}

function et_user(): User
{
    return User::factory()->create();
}

// ---------------------------------------------------------------------------
// Event Type — Model methods
// ---------------------------------------------------------------------------

test('event defaults to cai type', function () {
    $event = et_event();

    expect($event->event_type)->toBe('cai')
        ->and($event->isCai())->toBeTrue()
        ->and($event->isPengajian())->toBeFalse();
});

test('event can be created as pengajian type', function () {
    $event = et_event(['event_type' => 'pengajian']);

    expect($event->event_type)->toBe('pengajian')
        ->and($event->isPengajian())->toBeTrue()
        ->and($event->isCai())->toBeFalse();
});

test('cai scope returns only cai events', function () {
    et_event(['slug' => 'cai-1', 'event_type' => 'cai']);
    et_event(['slug' => 'cai-2', 'event_type' => 'cai']);
    et_event(['slug' => 'pengajian-1', 'event_type' => 'pengajian']);

    expect(Event::cai()->count())->toBe(2);
});

test('pengajian scope returns only pengajian events', function () {
    et_event(['slug' => 'cai-1', 'event_type' => 'cai']);
    et_event(['slug' => 'pengajian-1', 'event_type' => 'pengajian']);
    et_event(['slug' => 'pengajian-2', 'event_type' => 'pengajian']);

    expect(Event::pengajian()->count())->toBe(2);
});

test('event can switch between cai and pengajian', function () {
    $event = et_event(['event_type' => 'cai']);

    expect($event->isCai())->toBeTrue();

    $event->update(['event_type' => 'pengajian']);

    expect($event->fresh()->isPengajian())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Event Type — Contextual Menu (ActiveEventContext)
// ---------------------------------------------------------------------------

test('active event context exposes event type for cai event', function () {
    $event = et_event(['event_type' => 'cai']);
    $context = app(ActiveEventContext::class);
    $context->set($event);

    expect($context->currentEventType())->toBe('cai')
        ->and($context->isCurrentCai())->toBeTrue()
        ->and($context->isCurrentPengajian())->toBeFalse();
});

test('active event context exposes event type for pengajian event', function () {
    $event = et_event(['event_type' => 'pengajian']);
    $context = app(ActiveEventContext::class);
    $context->set($event);

    expect($context->currentEventType())->toBe('pengajian')
        ->and($context->isCurrentPengajian())->toBeTrue()
        ->and($context->isCurrentCai())->toBeFalse();
});

test('active event context returns null for event type when no event set', function () {
    $context = app(ActiveEventContext::class);

    expect($context->currentEventType())->toBeNull()
        ->and($context->isCurrentCai())->toBeFalse()
        ->and($context->isCurrentPengajian())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Event Type — Event Switcher
// ---------------------------------------------------------------------------

test('event switcher shows event type label for cai events', function () {
    $event = et_event(['name' => 'CAI Event', 'slug' => 'cai-event', 'event_type' => 'cai']);
    $user = et_user();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSee('CAI Event');
});

test('event switcher shows event type label for pengajian events', function () {
    $event = et_event(['name' => 'Pengajian Event', 'slug' => 'pengajian-event', 'event_type' => 'pengajian']);
    $user = et_user();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSee('Pengajian Event');
});

// ---------------------------------------------------------------------------
// Event Type — Authorization remains enforced even when menu hidden
// ---------------------------------------------------------------------------

test('pengajian admin routes remain accessible even when CAI event is active', function () {
    $event = et_event(['event_type' => 'cai']);
    $user = et_user();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    $this->get(route('pengajian.report'))->assertOk();
    $this->get(route('pengajian.admin.access'))->assertOk();
    $this->get(route('pengajian.admin.manual-entry'))->assertOk();
});

test('cai admin routes remain accessible even when Pengajian event is active', function () {
    $event = et_event(['event_type' => 'pengajian']);
    $user = et_user();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('database'))->assertOk();
    $this->get(route('registrasi.peserta'))->assertOk();
    $this->get(route('sesi.absensi'))->assertOk();
});

// ---------------------------------------------------------------------------
// Event Type — Event creation via Livewire
// ---------------------------------------------------------------------------

test('event can be created with pengajian type via Livewire form', function () {
    $user = et_user();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'Pengajian Test')
        ->set('newSlug', 'pengajian-test')
        ->set('newEventType', 'pengajian')
        ->call('create');

    $this->assertDatabaseHas('events', [
        'name' => 'Pengajian Test',
        'slug' => 'pengajian-test',
        'event_type' => 'pengajian',
        'status' => 'active',
    ]);
});

test('event can be created with cai type via Livewire form', function () {
    $user = et_user();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'CAI Test')
        ->set('newSlug', 'cai-test')
        ->set('newEventType', 'cai')
        ->call('create');

    $this->assertDatabaseHas('events', [
        'name' => 'CAI Test',
        'slug' => 'cai-test',
        'event_type' => 'cai',
        'status' => 'active',
    ]);
});

test('sidebar does not 500 when no active event', function () {
    $user = et_user();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});

test('sidebar does not 500 when cai event is active', function () {
    $event = et_event(['event_type' => 'cai']);
    $user = et_user();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    $this->get(route('dashboard'))->assertOk();
});

test('sidebar does not 500 when pengajian event is active', function () {
    $event = et_event(['event_type' => 'pengajian']);
    $user = et_user();
    $this->actingAs($user);

    app(ActiveEventContext::class)->set($event);

    $this->get(route('dashboard'))->assertOk();
});
