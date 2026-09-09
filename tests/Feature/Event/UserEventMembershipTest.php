<?php

use App\Enums\Role;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Event\EventSwitcher;
use App\Livewire\Settings\Profile;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use App\Services\Event\EventAccessService;
use App\Services\Event\EventPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function uem_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'UEM Event '.str()->random(6),
        'slug' => 'uem-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ], $overrides));
}

function uem_guest(string $email): User
{
    return User::factory()->create([
        'role' => Role::Guest,
        'email' => $email,
        'person_id' => null,
    ]);
}

function uem_role(Event $event, string $code, string $name): EventRole
{
    return EventRole::create([
        'event_id' => $event->id,
        'name' => $name,
        'code' => $code,
    ]);
}

function uem_assignUser(User $user, Event $event, EventRole $role): EventCommitteeAssignment
{
    return app(EventCommitteeService::class)->assignUser([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'event_role_id' => $role->id,
    ]);
}

function uem_assignPerson(Person $person, Event $event, EventRole $role): EventCommitteeAssignment
{
    return app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);
}

// ---------------------------------------------------------------------------
// User without Person — database + account state
// ---------------------------------------------------------------------------

test('event_committee_assignments has nullable user_id and nullable person_id', function () {
    expect(Schema::getColumnListing('event_committee_assignments'))->toContain('user_id')
        ->and(Schema::getColumnType('event_committee_assignments', 'user_id'))->toBe('integer')
        ->and(Schema::getColumnType('event_committee_assignments', 'person_id'))->toBe('integer');
});

test('user can exist without a Person (person_id NULL)', function () {
    $user = uem_guest('pj@example.com');

    expect($user->person_id)->toBeNull()
        ->and($user->role)->toBe(Role::Guest)
        ->and($user->hasPerson())->toBeFalse()
        ->and(User::find($user->id))->not->toBeNull();
});

test('guest can have an event membership without a Person', function () {
    $event = uem_event();
    $guest = uem_guest('pj@example.com');
    $role = uem_role($event, 'guest', 'Guest');

    $assignment = uem_assignUser($guest, $event, $role);

    expect($assignment->user_id)->toBe($guest->id)
        ->and($assignment->person_id)->toBeNull();
});

test('assign requires exactly one of user_id or person_id', function () {
    $event = uem_event();
    $role = uem_role($event, 'guest', 'Guest');

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'event_role_id' => $role->id,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    $user = uem_guest('both@example.com');
    $person = Person::create(['nama' => 'Both Person']);

    expect(fn () => app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

// ---------------------------------------------------------------------------
// Guest authentication lifecycle (without Person)
// ---------------------------------------------------------------------------

test('guest can login without a Person', function () {
    $guest = uem_guest('pj@example.com');
    $guest->update(['password' => Hash::make('password')]);

    Livewire::test(Login::class)
        ->set('email', 'pj@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($guest);
});

test('guest can logout without a Person', function () {
    $guest = uem_guest('pj@example.com');
    $this->actingAs($guest);

    $this->post('/logout');

    $this->assertGuest();
});

test('guest can confirm password without a Person', function () {
    $guest = uem_guest('pj@example.com');
    $guest->update(['password' => Hash::make('password')]);
    $this->actingAs($guest);

    Livewire::test(ConfirmPassword::class)
        ->set('password', 'password')
        ->call('confirmPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));
});

test('guest profile does not crash without a Person', function () {
    $guest = uem_guest('pj@example.com');
    $guest->update(['password' => Hash::make('password')]);
    $this->actingAs($guest);

    Livewire::test(Profile::class)
        ->assertSet('email', 'pj@example.com')
        ->set('name', 'Guest PJ')
        ->set('email', 'pj@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($guest->fresh()->name)->toBe('Guest PJ');
});

test('committee account without email can still confirm password and save profile', function () {
    $person = Person::create(['nama' => 'No Email Person']);
    $user = User::create([
        'person_id' => $person->id,
        'name' => 'No Email',
        'username' => 'no.email',
        'password' => Hash::make('password'),
        'email' => null,
        'is_active' => true,
    ]);
    $this->actingAs($user);

    Livewire::test(ConfirmPassword::class)
        ->set('password', 'password')
        ->call('confirmPassword')
        ->assertHasNoErrors();

    Livewire::test(Profile::class)
        ->assertSet('email', '')
        ->set('name', 'No Email Updated')
        ->set('email', '')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('No Email Updated')
        ->and($user->fresh()->email)->toBeNull();
});

// ---------------------------------------------------------------------------
// User-based event access
// ---------------------------------------------------------------------------

test('user with membership can access the event', function () {
    $event = uem_event();
    $guest = uem_guest('member@example.com');
    $role = uem_role($event, 'guest', 'Guest');
    uem_assignUser($guest, $event, $role);
    $this->actingAs($guest);

    $this->get(route('events.dashboard', $event, false))->assertOk();
});

test('user without membership cannot access the event', function () {
    $event = uem_event();
    $guest = uem_guest('nonmember@example.com');
    $this->actingAs($guest);

    $this->get(route('events.dashboard', $event, false))->assertForbidden();
});

test('user without membership and without any access gets empty event list', function () {
    uem_event();
    $guest = uem_guest('lone@example.com');
    $this->actingAs($guest);

    $this->get('/dashboard')->assertOk();

    expect(app(EventAccessService::class)->getAssignedEventIds($guest))->toBe([]);
});

test('event access service resolves user-based membership', function () {
    $event = uem_event();
    $guest = uem_guest('svc@example.com');
    $role = uem_role($event, 'guest', 'Guest');
    uem_assignUser($guest, $event, $role);

    $service = app(EventAccessService::class);

    expect($service->isUserAssignedToEvent($guest, $event->id))->toBeTrue()
        ->and($service->getAssignedEventIds($guest))->toBe([$event->id]);
});

// ---------------------------------------------------------------------------
// Guest — only assigned event
// ---------------------------------------------------------------------------

test('guest can access assigned event', function () {
    $eventA = uem_event();
    $guest = uem_guest('guest-a@example.com');
    $role = uem_role($eventA, 'guest', 'Guest');
    uem_assignUser($guest, $eventA, $role);
    $this->actingAs($guest);

    $this->get(route('events.dashboard', $eventA, false))->assertOk();
});

test('guest cannot access unassigned event through direct URL', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $guest = uem_guest('guest-b@example.com');
    $role = uem_role($eventA, 'guest', 'Guest');
    uem_assignUser($guest, $eventA, $role);
    $this->actingAs($guest);

    $this->get(route('events.dashboard', $eventA, false))->assertOk();
    $this->get(route('events.dashboard', $eventB, false))->assertForbidden();
});

test('guest cannot switch to unassigned event via EventSwitcher', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $guest = uem_guest('guest-switch@example.com');
    $role = uem_role($eventA, 'guest', 'Guest');
    uem_assignUser($guest, $eventA, $role);
    $this->actingAs($guest);

    Livewire::test(EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Event Chair — only assigned event
// ---------------------------------------------------------------------------

test('event chair can access assigned event', function () {
    $event = uem_event();
    $chair = User::factory()->create([
        'role' => Role::EventChair,
        'email' => 'chair@example.com',
        'person_id' => null,
    ]);
    $role = uem_role($event, 'event_chair', 'Event Chair');
    uem_assignUser($chair, $event, $role);
    $this->actingAs($chair);

    $this->get(route('events.dashboard', $event, false))->assertOk();
});

test('event chair cannot access another event through direct URL', function () {
    $eventA = uem_event();
    $eventB = uem_event(['event_type' => 'pengajian']);
    $chair = User::factory()->create([
        'role' => Role::EventChair,
        'email' => 'chair-isolated@example.com',
        'person_id' => null,
    ]);
    $role = uem_role($eventA, 'event_chair', 'Event Chair');
    uem_assignUser($chair, $eventA, $role);
    $this->actingAs($chair);

    $this->get(route('events.dashboard', $eventA, false))->assertOk();
    $this->get(route('events.dashboard', $eventB, false))->assertForbidden();
    $this->get(route('events.dashboard', $eventB, false))->assertForbidden();
});

test('event chair cannot access another event through Livewire', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $chair = User::factory()->create([
        'role' => Role::EventChair,
        'email' => 'chair-livewire@example.com',
        'person_id' => null,
    ]);
    $role = uem_role($eventA, 'event_chair', 'Event Chair');
    uem_assignUser($chair, $eventA, $role);
    $this->actingAs($chair);

    Livewire::test(EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertForbidden();
});

test('event chair without membership has no event access', function () {
    $event = uem_event();
    $chair = User::factory()->create([
        'role' => Role::EventChair,
        'email' => 'chair-empty@example.com',
        'person_id' => null,
    ]);
    $this->actingAs($chair);

    $this->get(route('events.dashboard', $event, false))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Event Switcher — only authorized events appear
// ---------------------------------------------------------------------------

test('event switcher shows only events the user is assigned to', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $guest = uem_guest('switcher@example.com');
    $role = uem_role($eventA, 'guest', 'Guest');
    uem_assignUser($guest, $eventA, $role);
    $this->actingAs($guest);

    $component = Livewire::test(EventSwitcher::class);

    expect($component->get('events')->pluck('id')->all())->toBe([$eventA->id]);
});

test('platform user still sees all active events in switcher', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin);

    $component = Livewire::test(EventSwitcher::class);

    expect($component->get('events')->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Person-based access compatibility (Design C preserved)
// ---------------------------------------------------------------------------

test('person-based event access still works', function () {
    $event = uem_event();
    $person = Person::create(['nama' => 'Person Member']);
    $user = User::factory()->create(['role' => null, 'person_id' => $person->id]);
    $role = uem_role($event, 'viewer', 'Viewer');
    uem_assignPerson($person, $event, $role);
    $this->actingAs($user);

    $this->get(route('events.dashboard', $event, false))->assertOk();
});

test('permission service resolves both user and person memberships', function () {
    $event = uem_event();
    $person = Person::create(['nama' => 'Person Member']);
    $personUser = User::factory()->create(['role' => null, 'person_id' => $person->id]);
    $guest = uem_guest('permsvc@example.com');

    $viewerRole = uem_role($event, 'viewer', 'Viewer');
    $guestRole = uem_role($event, 'guest', 'Guest');

    uem_assignPerson($person, $event, $viewerRole);
    uem_assignUser($guest, $event, $guestRole);

    $service = app(EventPermissionService::class);

    expect($service->allows($personUser, 'view-dashboard', $event))->toBeTrue()
        ->and($service->allows($personUser, 'view-reports', $event))->toBeTrue()
        ->and($service->allows($guest, 'view-dashboard', $event))->toBeTrue()
        ->and($service->allows($guest, 'view-reports', $event))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Platform roles preserved
// ---------------------------------------------------------------------------

test('super admin can access any event', function () {
    $event = uem_event();
    $super = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($super);

    $this->get(route('events.dashboard', $event, false))->assertOk();
});

test('admin can access any event without membership', function () {
    $event = uem_event();
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin);

    $this->get(route('events.dashboard', $event, false))->assertOk();
});

// ---------------------------------------------------------------------------
// Cross-event authorization
// ---------------------------------------------------------------------------

test('event A user cannot access event B route', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $user = uem_guest('cross@example.com');
    $role = uem_role($eventA, 'guest', 'Guest');
    uem_assignUser($user, $eventA, $role);
    $this->actingAs($user);

    $this->get(route('events.dashboard', $eventB, false))->assertForbidden();
    $this->get(route('absensi', ['event' => $eventB], false))->assertForbidden();
});

test('event A user cannot access event B dashboard through direct route', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $user = uem_guest('cross-livewire@example.com');
    $role = uem_role($eventA, 'guest', 'Guest');
    uem_assignUser($user, $eventA, $role);
    $this->actingAs($user);

    $this->get(route('events.dashboard', $eventB, false))->assertForbidden();

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Guest creation paths
// ---------------------------------------------------------------------------

test('createGuestAndAssign creates a guest without person and assigns membership', function () {
    $event = uem_event();
    $role = uem_role($event, 'guest', 'Guest');

    $result = app(EventCommitteeService::class)->createGuestAndAssign([
        'event_id' => $event->id,
        'event_role_id' => $role->id,
        'name' => 'PJ',
        'email' => 'pj-create@example.com',
    ]);

    expect($result['user_created'])->toBeTrue()
        ->and($result['user']->person_id)->toBeNull()
        ->and($result['user']->role)->toBe(Role::Guest)
        ->and($result['assignment']->user_id)->toBe($result['user']->id)
        ->and($result['assignment']->person_id)->toBeNull();

    expect(Hash::check($result['plain_password'], $result['user']->password))->toBeTrue();
});

test('createGuestAndAssign reuses existing account for same email', function () {
    $event = uem_event();
    $role = uem_role($event, 'guest', 'Guest');
    $existing = uem_guest('pj-reuse@example.com');
    $before = User::count();

    $result = app(EventCommitteeService::class)->createGuestAndAssign([
        'event_id' => $event->id,
        'event_role_id' => $role->id,
        'name' => 'PJ Reuse',
        'email' => 'pj-reuse@example.com',
    ]);

    expect($result['user_created'])->toBeFalse()
        ->and($result['user']->id)->toBe($existing->id)
        ->and(User::count())->toBe($before)
        ->and($result['assignment']->user_id)->toBe($existing->id);
});

test('public registration creates a Guest account without person and without event access', function () {
    $event = uem_event();

    $response = Livewire::test(\App\Livewire\Auth\Register::class)
        ->set('name', 'Public Guest')
        ->set('email', 'public-guest@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response->assertHasNoErrors();

    $user = User::where('email', 'public-guest@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Guest)
        ->and($user->person_id)->toBeNull()
        ->and(app(EventAccessService::class)->getAssignedEventIds($user))->toBe([]);
});

test('event role codes guest and event_chair get default permissions', function () {
    $event = uem_event();

    $guestRole = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Guest',
        'code' => 'guest',
    ]);

    $chairRole = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Event Chair',
        'code' => 'event_chair',
    ]);

    expect($guestRole->permissions)->toBe(['view-dashboard'])
        ->and($chairRole->permissions)->toContain('view-dashboard')
        ->and($chairRole->permissions)->toContain('manage-participants')
        ->and($chairRole->permissions)->toContain('view-reports');
});

test('committee management can create and assign a guest without a person', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = uem_event();
    $role = uem_role($event, 'guest', 'Guest');

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->call('load', $event->id)
        ->set('showGuestForm', true)
        ->set('guestName', 'PJ Tamu')
        ->set('guestEmail', 'pj-tamu@example.com')
        ->set('guestEventRoleId', $role->id)
        ->call('createGuest')
        ->assertHasNoErrors();

    $user = User::where('email', 'pj-tamu@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->person_id)->toBeNull()
        ->and($user->role)->toBe(Role::Guest);

    expect(EventCommitteeAssignment::where('event_id', $event->id)
        ->where('user_id', $user->id)
        ->where('event_role_id', $role->id)
        ->exists())->toBeTrue();
});

test('committee management guest flow requires an event role', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = uem_event();
    uem_role($event, 'guest', 'Guest');

    $this->actingAs($admin);

    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->call('load', $event->id)
        ->set('guestEmail', 'no-role@example.com')
        ->call('createGuest')
        ->assertHasErrors(['guestEventRoleId']);

    expect(User::where('email', 'no-role@example.com')->exists())->toBeFalse();
});

test('super admin can create a guest account via user management without a person', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($admin);

    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'Guest PJ')
        ->set('email', 'guest-create@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'guest')
        ->call('simpan');

    $user = User::where('email', 'guest-create@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(Role::Guest)
        ->and($user->person_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// Participation / Design C untouched
// ---------------------------------------------------------------------------

test('person can still participate in multiple events (Design C preserved)', function () {
    $eventA = uem_event();
    $eventB = uem_event();
    $person = Person::create(['nama' => 'Multi Participant']);

    Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'jenis_peserta' => 'Wajib']);
    Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'jenis_peserta' => 'Wajib']);

    expect(Participation::where('person_id', $person->id)->count())->toBe(2)
        ->and($person->participations()->count())->toBe(2);
});
