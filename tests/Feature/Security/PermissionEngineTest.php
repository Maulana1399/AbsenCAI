<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\EventRole;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use App\Services\Event\EventPermissionService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function perm_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'PE Event '.str()->random(6),
        'slug' => 'pe-event-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ], $overrides));
}

function perm_person(): Person
{
    return Person::create(['nama' => 'PE Person '.str()->random(6)]);
}

// ---------------------------------------------------------------------------
// A. Default permission matrix
// ---------------------------------------------------------------------------

test('event role auto-fills permissions by code', function () {
    $event = perm_event();

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Ketua Panitia',
        'code' => 'ketua_event',
    ]);

    expect($role->permissions)->toBe([
        'view-dashboard',
        'manage-registration',
        'manage-participants',
        'manage-attendance',
        'manage-sessions',
        'manage-secretariat',
        'view-reports',
    ]);
});

test('permissions come from code, not role name', function () {
    $event = perm_event();

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Operator Registrasi',
        'code' => 'viewer',
    ]);

    expect($role->permissions)->toBe(['view-dashboard', 'view-reports']);
});

test('unknown role code throws explicit exception', function () {
    $event = perm_event();

    expect(fn () => EventRole::create([
        'event_id' => $event->id,
        'name' => 'Sie Acara',
        'code' => 'sie_acara',
    ]))->toThrow(\App\Exceptions\UnknownEventRoleCodeException::class);
});

test('missing role code throws explicit exception', function () {
    $event = perm_event();

    expect(fn () => EventRole::create([
        'event_id' => $event->id,
        'name' => 'Tanpa Kode',
    ]))->toThrow(\App\Exceptions\UnknownEventRoleCodeException::class);
});

test('explicit permissions are preserved', function () {
    $event = perm_event();

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Custom Role',
        'code' => 'viewer',
        'permissions' => ['view-reports'],
    ]);

    expect($role->permissions)->toBe(['view-reports']);
});

// ---------------------------------------------------------------------------
// B. EventPermissionService
// ---------------------------------------------------------------------------

test('permission service grants permission from active event assignment', function () {
    $event = perm_event();
    $person = perm_person();
    $user = User::factory()->create(['role' => null, 'person_id' => $person->id]);

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Operator Scan',
        'code' => 'operator_scan',
    ]);
    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($event);

    $service = app(EventPermissionService::class);
    expect($service->allows($user, 'manage-attendance'))->toBeTrue();
    expect($service->allows($user, 'manage-registration'))->toBeFalse();
    expect($service->permissionsFor($user))->toBe(['manage-attendance']);
});

test('permission service requires active event and assignment', function () {
    $event = perm_event();
    $person = perm_person();
    $user = User::factory()->create(['role' => null, 'person_id' => $person->id]);

    $service = app(EventPermissionService::class);
    expect($service->allows($user, 'manage-attendance'))->toBeFalse();

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Operator Scan',
        'code' => 'operator_scan',
    ]);
    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($event);
    $eventB = perm_event();
    app(ActiveEventContext::class)->set($eventB);

    expect($service->allows($user, 'manage-attendance'))->toBeFalse();
});

test('permission service requires linked person', function () {
    $event = perm_event();
    $user = User::factory()->create(['role' => null]);

    app(ActiveEventContext::class)->set($event);

    $service = app(EventPermissionService::class);
    expect($service->allows($user, 'manage-attendance'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// C. Regression — auto-create account with event assignment gets permissions
// ---------------------------------------------------------------------------

test('auto-created user with event assignment can access features per event role permission', function () {
    $event = perm_event();
    $person = perm_person();

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Operator Registrasi',
        'code' => 'operator_registrasi',
    ]);

    $result = app(EventCommitteeService::class)->assignAndEnsureUser([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $user = $result['user'];
    expect($user->role)->toBeNull();

    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();

    $this->actingAs($user);
    $this->get(route('registrasi.peserta', ['event' => $event]))->assertOk();
    $this->get(route('absensi', ['event' => $event]))->assertForbidden();
});

test('auto-created user with ketua event role gets dashboard and management', function () {
    $event = perm_event();
    $person = perm_person();

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Ketua Panitia',
        'code' => 'ketua_event',
    ]);

    $result = app(EventCommitteeService::class)->assignAndEnsureUser([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $user = $result['user'];
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-import'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-events'))->toBeTrue();

    $this->actingAs($user);
    $this->get(route('events.dashboard', $event, false))->assertOk();
});

// ---------------------------------------------------------------------------
// D. Platform roles compatibility
// ---------------------------------------------------------------------------

test('admin bypasses event abilities without assignment', function () {
    $event = perm_event();
    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-users'))->toBeTrue();
});

test('super admin bypasses all abilities', function () {
    $event = perm_event();
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-users'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// E. Visibility — only assigned events
// ---------------------------------------------------------------------------

test('auto-created user sees only assigned event in switcher', function () {
    $eventA = perm_event();
    $eventB = perm_event();
    $person = perm_person();

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $eventA->id,
        'name' => 'Viewer',
        'code' => 'viewer',
    ]);
    $result = app(EventCommitteeService::class)->assignAndEnsureUser([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $user = $result['user'];
    $this->actingAs($user);

    $component = Livewire::test(\App\Livewire\Event\EventSwitcher::class);
    $eventIds = $component->get('events')->pluck('id')->toArray();

    expect($eventIds)->toBe([$eventA->id]);
});

// ---------------------------------------------------------------------------
// DIAGNOSTIC: Full chain trace — "Tidak ada peran" + "1 event aktif" + 403
// ---------------------------------------------------------------------------

test('DIAG 1: unknown code cannot be created → no silent empty permissions', function () {
    $event = perm_event();

    // Role dengan nama "Panitia" dan code yang TIDAK dikenal:
    // kini dilarang dibuat — harus memakai code sistem yang valid.
    expect(fn () => EventRole::create([
        'event_id' => $event->id,
        'name' => 'Panitia',
        'code' => null,
    ]))->toThrow(\App\Exceptions\UnknownEventRoleCodeException::class);

    expect(EventRole::count())->toBe(0, 'Role tidak jadi dibuat (fail-closed)');
});

test('DIAG 2: same user/event with code=ketua_event → permissions OK → 200', function () {
    $event = perm_event();
    $person = perm_person();
    $user = User::factory()->create([
        'role' => null,
        'person_id' => $person->id,
    ]);

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Ketua',
        'code' => 'ketua_event',
    ]);

    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($event);

    $this->actingAs($user);
    $response = $this->get(route('events.dashboard', $event, false));
    expect($response->status())->toBe(200, 'Control: same scenario with code → should pass');
});

test('DIAG 3: user with assignment + code-driven permissions → 200', function () {
    $event = perm_event();
    $person = perm_person();
    $user = User::factory()->create([
        'role' => null,
        'person_id' => $person->id,
    ]);

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Sekretaris Acara',
        'code' => 'sekretariat',
    ]);

    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($event);

    $this->actingAs($user);
    $response = $this->get(route('events.dashboard', $event, false));
    expect($response->status())->toBe(200, 'Code-driven: "sekretariat" → permissions populated');
});

// ---------------------------------------------------------------------------
// F. EventSwitcher / visibility
// ---------------------------------------------------------------------------

test('auto-created user cannot switch to unassigned event', function () {
    $eventA = perm_event();
    $eventB = perm_event();
    $person = perm_person();

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $eventA->id,
        'name' => 'Viewer',
        'code' => 'viewer',
    ]);
    $result = app(EventCommitteeService::class)->assignAndEnsureUser([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $user = $result['user'];
    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertForbidden();
});
