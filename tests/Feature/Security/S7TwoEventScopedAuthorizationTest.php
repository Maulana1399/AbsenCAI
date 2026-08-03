<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use App\Models\SesiAbsensi;
use App\Services\Activity\EventCommitteeService;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function s7e_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'S7E Event '.str()->random(6),
        'slug' => 's7e-event-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ], $overrides));
}

function s7e_person(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'S7E Person '.str()->random(6),
    ], $overrides));
}

function s7e_user(string $role, ?int $personId = null): User
{
    $attrs = ['role' => $role];
    if ($personId !== null) {
        $attrs['person_id'] = $personId;
    }
    return User::factory()->create($attrs);
}

function s7e_assignPersonToEvent(Person $person, Event $event, string $roleCode = 'ketua_event'): void
{
    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Panitia',
        'code' => $roleCode,
        'scope' => 'event',
    ]);

    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($event);
}

function s7e_ketuaEventAssignedTo(Event $event): User
{
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);
    s7e_assignPersonToEvent($person, $event);
    return $user;
}

// ---------------------------------------------------------------------------
// A. EventAccessService
// ---------------------------------------------------------------------------

test('user without person is not assigned to any event', function () {
    $event = s7e_event();
    $user = s7e_user('ketua_event');

    $service = app(EventAccessService::class);
    expect($service->isUserAssignedToEvent($user, $event->id))->toBeFalse();
    expect($service->getAssignedEventIds($user))->toBe([]);
});

test('user with person but no assignment is not assigned', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);

    $service = app(EventAccessService::class);
    expect($service->isUserAssignedToEvent($user, $event->id))->toBeFalse();
    expect($service->getAssignedEventIds($user))->toBe([]);
});

test('user with assignment to event A is assigned to event A', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);
    s7e_assignPersonToEvent($person, $event);

    $service = app(EventAccessService::class);
    expect($service->isUserAssignedToEvent($user, $event->id))->toBeTrue();
    expect($service->getAssignedEventIds($user))->toBe([$event->id]);
});

test('assignment to event A does not grant event B', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);
    s7e_assignPersonToEvent($person, $eventA);

    $service = app(EventAccessService::class);
    expect($service->isUserAssignedToEvent($user, $eventA->id))->toBeTrue();
    expect($service->isUserAssignedToEvent($user, $eventB->id))->toBeFalse();
});

test('event role code does not affect assignment authorization', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Sie Acara',
        'code' => 'pj_divisi',
        'scope' => 'event',
    ]);

    // Simulasikan role legacy dengan code arbitrary (bukan dari matriks sistem):
    // assignment authorization TIDAK bergantung pada nilai code.
    \Illuminate\Support\Facades\DB::table('event_roles')
        ->where('id', $role->id)
        ->update(['code' => 'sie_acara']);

    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $service = app(EventAccessService::class);
    expect($service->isUserAssignedToEvent($user, $event->id))->toBeTrue();
});

// ---------------------------------------------------------------------------
// B. Ketua Event Gates — Assigned active event
// ---------------------------------------------------------------------------

$ketuaEventAbilities = [
    'view-dashboard',
    'manage-registration',
    'manage-participants',
    'manage-attendance',
    'manage-sessions',
    'manage-secretariat',
    'view-reports',
];

foreach ($ketuaEventAbilities as $ability) {
    test("KetuaEvent can {$ability} when assigned to active event", function () use ($ability) {
        $event = s7e_event();
        $user = s7e_ketuaEventAssignedTo($event);
        app(ActiveEventContext::class)->set($event);

        expect(Gate::forUser($user)->allows($ability))->toBeTrue();
    });

    test("KetuaEvent cannot {$ability} when not assigned to active event", function () use ($ability) {
        $eventA = s7e_event();
        $eventB = s7e_event();
        $user = s7e_ketuaEventAssignedTo($eventA);
        app(ActiveEventContext::class)->set($eventB);

        expect(Gate::forUser($user)->allows($ability))->toBeFalse();
    });
}

// ---------------------------------------------------------------------------
// C. Ketua Event Gates — Edge cases
// ---------------------------------------------------------------------------

test('KetuaEvent cannot access abilities with no active event', function () {
    $event = s7e_event();
    $user = s7e_ketuaEventAssignedTo($event);
    app(ActiveEventContext::class)->clear();

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeFalse();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeFalse();
});

test('KetuaEvent cannot access abilities with no linked person', function () {
    $event = s7e_event();
    $user = s7e_user('ketua_event');
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeFalse();
});

test('KetuaEvent cannot access abilities with linked person but no assignment', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// D. Regression — All other roles remain global
// ---------------------------------------------------------------------------

test('SuperAdmin remains global for all abilities', function () {
    $event = s7e_event();
    $user = s7e_user('super_admin');
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
});

test('Admin remains global for all abilities', function () {
    $event = s7e_event();
    $user = s7e_user('admin');
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
});

test('Sekretariat remains global for its existing abilities', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('sekretariat', $person->id);
    s7e_assignPersonToEvent($person, $event, 'sekretariat');

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
});

test('PjDivisi existing abilities unchanged', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('pj_divisi', $person->id);
    s7e_assignPersonToEvent($person, $event, 'pj_divisi');

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-sessions'))->toBeTrue();
});

test('OperatorRegistrasi unchanged', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('operator_registrasi', $person->id);
    s7e_assignPersonToEvent($person, $event, 'operator_registrasi');

    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-dashboard'))->toBeTrue();
});

test('OperatorScan unchanged', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('operator_scan', $person->id);
    s7e_assignPersonToEvent($person, $event, 'operator_scan');

    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-dashboard'))->toBeTrue();
});

test('Viewer unchanged', function () {
    $event = s7e_event();
    $person = s7e_person();
    $user = s7e_user('viewer', $person->id);
    s7e_assignPersonToEvent($person, $event, 'viewer');

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-registration'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// E. EventSwitcher — Visibility
// ---------------------------------------------------------------------------

test('KetuaEvent sees only assigned active events in switcher', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    $events = $component->get('events');
    expect($events)->toHaveCount(1)
        ->and($events->first()->id)->toBe($eventA->id);
});

test('KetuaEvent does not see unassigned events in switcher', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    $eventIds = $component->get('events')->pluck('id')->toArray();
    expect($eventIds)->not->toContain($eventB->id);
});

test('KetuaEvent with no person sees no events in switcher', function () {
    s7e_event();
    $user = s7e_user('ketua_event');

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    expect($component->get('events'))->toHaveCount(0);
});

test('KetuaEvent with no assignment sees no events in switcher', function () {
    s7e_event();
    $person = s7e_person();
    $user = s7e_user('ketua_event', $person->id);

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    expect($component->get('events'))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// F. EventSwitcher — Enforcement
// ---------------------------------------------------------------------------

test('KetuaEvent can switch to assigned event', function () {
    $event = s7e_event();
    $user = s7e_ketuaEventAssignedTo($event);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $event->id)
        ->assertSet('currentEventId', $event->id);

    expect(app(ActiveEventContext::class)->id())->toBe($event->id);
});

test('KetuaEvent cannot switch to unassigned event', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    app(ActiveEventContext::class)->set($eventA);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertForbidden();

    expect(app(ActiveEventContext::class)->id())->toBe($eventA->id);
});

test('denied switch does not mutate active event context', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    app(ActiveEventContext::class)->set($eventA);

    $this->actingAs($user);
    try {
        Livewire::test(\App\Livewire\Event\EventSwitcher::class)
            ->call('switchTo', $eventB->id);
    } catch (AuthorizationException $e) {
    }

    expect(app(ActiveEventContext::class)->id())->toBe($eventA->id);
});

test('Admin still sees all events in switcher', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_user('admin');

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    expect($component->get('events'))->toHaveCount(2);
});

test('Admin can switch to any active event', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_user('admin');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertSet('currentEventId', $eventB->id);
});

test('SuperAdmin still sees all events in switcher', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_user('super_admin');

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    expect($component->get('events'))->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// G. Edge case — Stale session pointing to unassigned event
// ---------------------------------------------------------------------------

test('KetuaEvent with stale session to unassigned event has gates denied', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    app(ActiveEventContext::class)->set($eventB);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeFalse();
});

test('KetuaEvent with stale session sees only assigned event in switcher', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    app(ActiveEventContext::class)->set($eventB);

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);

    $events = $component->get('events');
    expect($events)->toHaveCount(1)
        ->and($events->first()->id)->toBe($eventA->id);
});

test('KetuaEvent can switch from stale unassigned event to assigned event', function () {
    $eventA = s7e_event();
    $eventB = s7e_event();
    $user = s7e_ketuaEventAssignedTo($eventA);

    app(ActiveEventContext::class)->set($eventB);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $eventA->id)
        ->assertSet('currentEventId', $eventA->id);

    expect(app(ActiveEventContext::class)->id())->toBe($eventA->id);
});

// ---------------------------------------------------------------------------
// H. Public Pengajian routes unchanged
// ---------------------------------------------------------------------------

test('pengajian enter token remains publicly accessible', function () {
    $this->get(route('pengajian.enter-token', ['event' => s7e_event(['event_type' => 'pengajian'])]))->assertOk();
});

test('pengajian desa redirects without session', function () {
    $event = s7e_event(['event_type' => 'pengajian']);
    $this->get(route('pengajian.desa', ['event' => $event]))->assertRedirect(route('pengajian.enter-token', ['event' => $event]));
});

test('pengajian hadir nonce route remains publicly accessible', function () {
    $event = s7e_event();
    $desa = \App\Models\desa::create(['desa_asal' => 'Test Desa']);
    $service = app(\App\Services\Pengajian\DesaAccessService::class);
    $grant = $service->createGrant($event, $desa, now()->subDay(), now()->addDay());
    $nonce = $grant['grant']->fresh()->nonce;

    $this->get(route('pengajian.hadir', ['nonce' => $nonce]))->assertOk();
});
