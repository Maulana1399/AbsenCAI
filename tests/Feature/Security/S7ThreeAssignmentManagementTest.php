<?php

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use App\Services\Event\EventAccessService;
use App\Services\User\UserManagementService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function s73_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'S73 Event '.str()->random(6),
        'slug' => 's73-event-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function s73_person(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'S73 Person '.str()->random(6),
    ], $overrides));
}

function s73_user(?string $role = null, ?int $personId = null): User
{
    $attrs = ['email' => 's73-'.str()->random(10).'@example.com'];
    if ($role !== null) {
        $attrs['role'] = $role;
    }
    if ($personId !== null) {
        $attrs['person_id'] = $personId;
    }

    return User::factory()->create($attrs);
}

function s73_role(Event $event, array $overrides = []): EventRole
{
    return app(EventCommitteeService::class)->createRole(array_merge([
        'event_id' => $event->id,
        'name' => 'Role '.str()->random(6),
        'code' => 'ketua_event',
        'scope' => 'event',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// A. User ↔ Person — SuperAdmin can create/edit/link
// ---------------------------------------------------------------------------

test('SuperAdmin can create User linked to Person', function () {
    $person = s73_person();
    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    $user = $service->create([
        'name' => 'Linked User',
        'email' => 'linked@example.com',
        'password' => 'password123',
        'role' => 'ketua_event',
        'person_id' => $person->id,
    ]);

    expect($user->person_id)->toBe($person->id)
        ->and($user->person->id)->toBe($person->id);
});

test('SuperAdmin can create User without Person', function () {
    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    $user = $service->create([
        'name' => 'No Person User',
        'email' => 'noperson@example.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);

    expect($user->person_id)->toBeNull();
});

test('SuperAdmin can edit User and link Person', function () {
    $person = s73_person();
    $user = s73_user('admin');
    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    $updated = $service->update($user, [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'admin',
        'person_id' => $person->id,
    ]);

    expect($updated->person_id)->toBe($person->id);
});

test('SuperAdmin can change linked Person', function () {
    $personA = s73_person();
    $personB = s73_person();
    $user = s73_user('admin', $personA->id);
    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    $updated = $service->update($user, [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'admin',
        'person_id' => $personB->id,
    ]);

    expect($updated->person_id)->toBe($personB->id);
});

test('SuperAdmin can unlink Person', function () {
    $person = s73_person();
    $user = s73_user('admin', $person->id);
    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    $updated = $service->update($user, [
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'admin',
        'person_id' => null,
    ]);

    expect($updated->person_id)->toBeNull();
});

test('cannot link two Users to same Person', function () {
    $person = s73_person();
    s73_user('admin', $person->id);
    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    expect(fn () => $service->create([
        'name' => 'Duplicate',
        'email' => 'dup@example.com',
        'password' => 'password123',
        'role' => 'ketua_event',
        'person_id' => $person->id,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('manipulated duplicate person_id rejected server-side on update', function () {
    $person = s73_person();
    $userA = s73_user('admin', $person->id);
    $userB = s73_user('admin');

    $service = app(UserManagementService::class);
    expect(fn () => $service->update($userB, [
        'name' => $userB->name,
        'email' => $userB->email,
        'role' => 'admin',
        'person_id' => $person->id,
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('Person data unchanged after linking', function () {
    $person = s73_person(['nama' => 'Original Name']);
    $originalName = $person->nama;

    s73_user('ketua_event', $person->id);

    expect($person->fresh()->nama)->toBe($originalName);
});

test('no Participation or Assignment created automatically when linking Person', function () {
    $person = s73_person();
    s73_user('ketua_event', $person->id);

    expect(\App\Models\Participation::where('person_id', $person->id)->count())->toBe(0);
    expect(EventCommitteeAssignment::where('person_id', $person->id)->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// B. Committee Assignments — CRUD
// ---------------------------------------------------------------------------

test('Admin can view assignments for Event A', function () {
    $event = s73_event();
    $user = s73_user('admin');

    $this->actingAs($user);
    $component = Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id);

    expect($component->get('eventId'))->toBe($event->id);
});

test('Admin can create assignment using Person and EventRole from Event A', function () {
    $event = s73_event();
    $person = s73_person();
    $role = s73_role($event);
    $user = s73_user('admin');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id)
        ->set('newPersonId', $person->id)
        ->set('newEventRoleId', $role->id)
        ->call('create');

    expect(EventCommitteeAssignment::where('event_id', $event->id)->where('person_id', $person->id)->exists())->toBeTrue();
});

test('cannot create assignment with EventRole from another Event', function () {
    $eventA = s73_event();
    $eventB = s73_event();
    $person = s73_person();
    $roleB = s73_role($eventB);
    $user = s73_user('admin');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $eventA->id)
        ->set('newPersonId', $person->id)
        ->set('newEventRoleId', $roleB->id)
        ->call('create');

    expect(EventCommitteeAssignment::where('event_id', $eventA->id)->where('person_id', $person->id)->exists())->toBeFalse();
});

test('duplicate assignment follows existing uniqueness rules', function () {
    $event = s73_event();
    $person = s73_person();
    $role = s73_role($event);
    $user = s73_user('admin');

    $service = app(EventCommitteeService::class);
    $service->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id)
        ->set('newPersonId', $person->id)
        ->set('newEventRoleId', $role->id)
        ->call('create');

    expect(EventCommitteeAssignment::where('event_id', $event->id)
        ->where('person_id', $person->id)
        ->where('event_role_id', $role->id)
        ->count())->toBe(1);
});

test('can delete assignment from same Event', function () {
    $event = s73_event();
    $person = s73_person();
    $role = s73_role($event);
    $user = s73_user('admin');

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $event->id)
        ->call('delete', $assignment->id);

    expect(EventCommitteeAssignment::find($assignment->id))->toBeNull();
});

test('cannot delete assignment from another Event', function () {
    $eventA = s73_event();
    $eventB = s73_event();
    $person = s73_person();
    $roleB = s73_role($eventB);
    $user = s73_user('admin');

    $assignment = app(EventCommitteeService::class)->assign([
        'event_id' => $eventB->id,
        'person_id' => $person->id,
        'event_role_id' => $roleB->id,
    ]);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\CommitteeManagement::class)
        ->dispatch('manageCommittee', id: $eventA->id)
        ->call('delete', $assignment->id);

    expect(EventCommitteeAssignment::find($assignment->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// C. EventRole Management
// ---------------------------------------------------------------------------

test('Admin can create EventRole for current Event', function () {
    $event = s73_event();
    $user = s73_user('admin');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Sekretaris')
        ->set('newTemplate', 'sekretariat')
        ->call('create');

    expect(EventRole::where('event_id', $event->id)->where('name', 'Sekretaris')->exists())->toBeTrue();
});

test('cannot create EventRole with duplicate name in same Event', function () {
    $event = s73_event();
    s73_role($event, ['name' => 'Bendahara']);
    $user = s73_user('admin');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Bendahara')
        ->set('newTemplate', 'sekretariat')
        ->call('create');

    expect(EventRole::where('event_id', $event->id)->where('name', 'Bendahara')->count())->toBe(1);
});

test('unauthorized role cannot access EventRole management', function () {
    $event = s73_event();
    $user = s73_user('operator_scan');

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Event\EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Test')
        ->call('create')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// D. Authorization — User Management
// ---------------------------------------------------------------------------

test('Admin cannot access User Management', function () {
    $user = s73_user('admin');
    $this->actingAs($user);
    $this->get('/users')->assertForbidden();
});

test('SuperAdmin can manage User-Person link', function () {
    $person = s73_person();
    $admin = s73_user('super_admin');

    $this->actingAs($admin);
    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'New Person User')
        ->set('email', 'newp@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'admin')
        ->set('person_id', $person->id)
        ->call('simpan');

    expect(User::where('email', 'newp@example.com')->first()->person_id)->toBe($person->id);
});

test('unauthorized User mutation denied via Livewire', function () {
    $user = s73_user('operator_scan');
    $person = s73_person();

    $this->actingAs($user);
    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'Hacker')
        ->set('email', 'hacker@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'super_admin')
        ->call('simpan')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// E. End-to-End: KetuaEvent operational flow
// ---------------------------------------------------------------------------

test('complete KetuaEvent setup flow works end to end', function () {
    $eventA = s73_event();
    $eventB = s73_event();
    $person = s73_person();

    $admin = s73_user('super_admin');

    $service = app(UserManagementService::class);
    $ketuaUser = $service->create([
        'name' => 'Ketua Event User',
        'email' => 'ketua@example.com',
        'password' => 'password123',
        'role' => 'ketua_event',
        'person_id' => $person->id,
    ]);

    $role = s73_role($eventA, ['code' => 'ketua_event']);
    app(EventCommitteeService::class)->assign([
        'event_id' => $eventA->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($eventA);

    expect(Gate::forUser($ketuaUser)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($ketuaUser)->allows('manage-registration'))->toBeTrue();

    expect(app(EventAccessService::class)->isUserAssignedToEvent($ketuaUser, $eventA->id))->toBeTrue();
    expect(app(EventAccessService::class)->isUserAssignedToEvent($ketuaUser, $eventB->id))->toBeFalse();

    $this->actingAs($ketuaUser);
    $switcher = Livewire::test(\App\Livewire\Event\EventSwitcher::class);
    $eventIds = $switcher->get('events')->pluck('id')->toArray();
    expect($eventIds)->toContain($eventA->id)
        ->and($eventIds)->not->toContain($eventB->id);

    EventCommitteeAssignment::where('event_id', $eventA->id)->where('person_id', $person->id)->first()->delete();

    expect(Gate::forUser($ketuaUser)->allows('view-dashboard'))->toBeFalse();

    $switcher2 = Livewire::test(\App\Livewire\Event\EventSwitcher::class);
    expect($switcher2->get('events'))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// F. Regression
// ---------------------------------------------------------------------------

test('SuperAdmin remains global after S7.3', function () {
    $user = s73_user('super_admin');
    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
});

test('Admin remains global after S7.3', function () {
    $user = s73_user('admin');
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
});

test('existing User Management safety rules remain', function () {
    $superAdmin = s73_user('super_admin');
    $anotherSuper = s73_user('super_admin');
    $user = s73_user('admin');

    $service = app(UserManagementService::class);

    expect(fn () => $service->delete($superAdmin, $superAdmin))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('Person Master Data unchanged by S7.3', function () {
    $person = s73_person();
    expect($person->nama)->not->toBeNull();
});

test('public Pengajian unchanged by S7.3', function () {
    $this->get(route('pengajian.enter-token', ['event' => s73_event(['event_type' => 'pengajian'])]))->assertOk();
});

test('existing unchanged abilities remain correct', function () {
    $event = s73_event();
    $user = s73_user('sekretariat');
    grantEventRoleToUser($user, $event, 'sekretariat');
    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();

    $opReg = s73_user('operator_registrasi');
    grantEventRoleToUser($opReg, $event, 'operator_registrasi');
    expect(Gate::forUser($opReg)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($opReg)->denies('view-dashboard'))->toBeTrue();
});
