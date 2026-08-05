<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ActiveEvent_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event',
        'slug' => 'test-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function ActiveEvent_makeUser(): User
{
    return User::factory()->create(['role' => Role::Admin]);
}

function ActiveEvent_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function ActiveEvent_makeParticipation(Person $person, Event $event, array $overrides = []): Participation
{
    return Participation::create(array_merge([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Basic context behavior
// ---------------------------------------------------------------------------

test('active event returns null when no event set', function () {
    $context = app(ActiveEventContext::class);

    expect($context->current())->toBeNull()
        ->and($context->id())->toBeNull()
        ->and($context->hasActiveEvent())->toBeFalse();
});

test('active event can set and retrieve an event', function () {
    $event = ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);

    expect($context->current()->is($event))->toBeTrue()
        ->and($context->id())->toBe($event->id)
        ->and($context->hasActiveEvent())->toBeTrue();
});

test('active event can switch to a different event', function () {
    $eventA = ActiveEvent_makeEvent(['slug' => 'event-a', 'name' => 'A']);
    $eventB = ActiveEvent_makeEvent(['slug' => 'event-b', 'name' => 'B']);
    $context = app(ActiveEventContext::class);

    $context->set($eventA);
    expect($context->id())->toBe($eventA->id);

    $result = $context->switchTo($eventB->id);
    expect($result->is($eventB))->toBeTrue()
        ->and($context->id())->toBe($eventB->id);
});

test('active event cannot set an archived event', function () {
    $event = ActiveEvent_makeEvent();
    $event->update(['status' => 'archived']);
    $context = app(ActiveEventContext::class);

    $context->set($event->fresh());

    expect($context->hasActiveEvent())->toBeFalse()
        ->and($context->id())->toBeNull();
});

test('active event switchTo returns null for invalid event id', function () {
    $context = app(ActiveEventContext::class);

    $result = $context->switchTo(99999);

    expect($result)->toBeNull()
        ->and($context->hasActiveEvent())->toBeFalse();
});

test('active event switchTo returns null for archived event', function () {
    $event = ActiveEvent_makeEvent();
    $event->update(['status' => 'archived']);
    $context = app(ActiveEventContext::class);

    $result = $context->switchTo($event->id);

    expect($result)->toBeNull();
});

test('active event can be cleared', function () {
    $event = ActiveEvent_makeEvent();
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

    $event = ActiveEvent_makeEvent();
    $context1->set($event);

    expect($context2->id())->toBe($event->id);
});

// ---------------------------------------------------------------------------
// requireCurrent
// ---------------------------------------------------------------------------

test('requireCurrent returns current event when set', function () {
    $event = ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);

    expect($context->requireCurrent()->is($event))->toBeTrue();
});

test('requireCurrent throws when no events exist', function () {
    $context = app(ActiveEventContext::class);

    expect(fn () => $context->requireCurrent())
        ->toThrow(\RuntimeException::class, 'No active event available.');
});

test('requireCurrent throws when no event explicitly selected even if active events exist', function () {
    ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    expect(fn () => $context->requireCurrent())
        ->toThrow(\RuntimeException::class, 'No active event available.');
});

// ---------------------------------------------------------------------------
// containsExplicitSession
// ---------------------------------------------------------------------------

test('containsExplicitSession returns false when no session set', function () {
    $context = app(ActiveEventContext::class);

    expect($context->containsExplicitSession())->toBeFalse();
});

test('containsExplicitSession returns true after set', function () {
    $event = ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);

    expect($context->containsExplicitSession())->toBeTrue();
});

test('containsExplicitSession returns false after clear', function () {
    $event = ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);
    $context->clear();

    expect($context->containsExplicitSession())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Stale session handling
// ---------------------------------------------------------------------------

test('stale session id for deleted event is cleared', function () {
    $event = ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);
    expect($context->hasActiveEvent())->toBeTrue();

    $event->delete();

    $fresh = app(ActiveEventContext::class);

    expect($fresh->hasActiveEvent())->toBeFalse()
        ->and($fresh->id())->toBeNull();
});

test('stale session id for archived event is cleared', function () {
    $event = ActiveEvent_makeEvent();
    $context = app(ActiveEventContext::class);

    $context->set($event);
    expect($context->hasActiveEvent())->toBeTrue();

    $event->update(['status' => 'archived']);

    $fresh = app(ActiveEventContext::class);

    expect($fresh->hasActiveEvent())->toBeFalse()
        ->and($fresh->id())->toBeNull();
});

test('stale session does not fall back to other active event', function () {
    $target = ActiveEvent_makeEvent(['name' => 'Target', 'slug' => 'target']);
    ActiveEvent_makeEvent(['name' => 'Other', 'slug' => 'other']);
    $context = app(ActiveEventContext::class);

    $context->set($target);
    $target->update(['status' => 'archived']);

    $fresh = app(ActiveEventContext::class);

    expect($fresh->hasActiveEvent())->toBeFalse()
        ->and($fresh->id())->toBeNull();
});

// ---------------------------------------------------------------------------
// Inactive event policy
// ---------------------------------------------------------------------------

test('archived event cannot become active context via set', function () {
    $event = ActiveEvent_makeEvent();
    $event->update(['status' => 'archived']);
    $context = app(ActiveEventContext::class);

    $context->set($event->fresh());

    expect($context->hasActiveEvent())->toBeFalse();
});

test('archived event cannot become active context via switchTo', function () {
    $event = ActiveEvent_makeEvent();
    $event->update(['status' => 'archived']);
    $context = app(ActiveEventContext::class);

    $result = $context->switchTo($event->id);

    expect($result)->toBeNull();
});

// ---------------------------------------------------------------------------
// Participation isolation
// ---------------------------------------------------------------------------

test('switching active event does not delete participation records', function () {
    $eventA = ActiveEvent_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ActiveEvent_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);
    $person = ActiveEvent_makePerson();

    ActiveEvent_makeParticipation($person, $eventA);
    ActiveEvent_makeParticipation($person, $eventB);

    $context = app(ActiveEventContext::class);
    $context->set($eventA);

    expect(Participation::count())->toBe(2);

    $context->switchTo($eventB->id);

    expect(Participation::count())->toBe(2);
});

test('context switching leaves participation data intact', function () {
    $person = ActiveEvent_makePerson(['nama' => 'Multi Event Person']);
    $eventA = ActiveEvent_makeEvent(['name' => 'CAI 2026', 'slug' => 'cai-2026']);
    $eventB = ActiveEvent_makeEvent(['name' => 'KJA 2026', 'slug' => 'kja-2026']);

    ActiveEvent_makeParticipation($person, $eventA, [
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-CAI001',
    ]);
    ActiveEvent_makeParticipation($person, $eventB, [
        'participant_number' => 'KL002',
        'attendance_code' => 'KJA-KJA001',
    ]);

    $context = app(ActiveEventContext::class);
    $context->set($eventA);

    expect(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(2);

    $context->switchTo($eventB->id);

    expect(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(2)
        ->and(Participation::where('event_id', $eventA->id)->count())->toBe(1)
        ->and(Participation::where('event_id', $eventB->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Legacy compatibility
// ---------------------------------------------------------------------------

test('context switching does not affect legacy peserta data', function () {
    $eventA = ActiveEvent_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ActiveEvent_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);

    \App\Models\peserta::create([
        'nama' => 'Legacy Peserta',
        'nip' => 5001,
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $context = app(ActiveEventContext::class);
    $context->set($eventA);

    expect(\App\Models\peserta::count())->toBe(1);

    $context->switchTo($eventB->id);

    expect(\App\Models\peserta::count())->toBe(1)
        ->and(\App\Models\peserta::first()->nama)->toBe('Legacy Peserta');
});

test('context switching does not change active sesi_absensi', function () {
    $eventA = ActiveEvent_makeEvent(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = ActiveEvent_makeEvent(['name' => 'Event B', 'slug' => 'event-b']);

    \App\Models\SesiAbsensi::create([
        'nama_sesi' => 'Global Sesi',
        'tanggal' => '2026-07-21',
        'aktif' => true,
    ]);

    $context = app(ActiveEventContext::class);
    $context->set($eventA);

    expect(\App\Models\SesiAbsensi::where('aktif', true)->count())->toBe(1);

    $context->switchTo($eventB->id);

    expect(\App\Models\SesiAbsensi::where('aktif', true)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// No-event state is safe
// ---------------------------------------------------------------------------

test('no-event state is safe for all legacy routes', function () {
    $user = ActiveEvent_makeUser();
    $this->actingAs($user);

    $event = ActiveEvent_makeEvent();

    $this->get('/dashboard')->assertStatus(200);
    $this->get(route('database', ['event' => $event]))->assertStatus(200);
    $this->get(route('sesi.absensi', ['event' => $event]))->assertStatus(200);
    $this->get(route('absensi', ['event' => $event]))->assertStatus(200);
    $this->get(route('surat-izin', ['event' => $event]))->assertStatus(200);
    $this->get(route('rekap.peserta', ['event' => $event]))->assertStatus(200);
    $this->get(route('rekap.absensi', ['event' => $event]))->assertStatus(200);
});

test('no active event — event switcher shows Pilih Event', function () {
    ActiveEvent_makeEvent();
    $user = ActiveEvent_makeUser();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSee('Pilih Event');
});
