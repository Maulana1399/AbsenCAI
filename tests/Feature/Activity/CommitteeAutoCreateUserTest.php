<?php

use App\Livewire\Auth\Login;
use App\Livewire\Event\CommitteeManagement;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function acu_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Auto Create Event',
        'slug' => 'acu-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function acu_person(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Ada Saya',
        'tanggal_lahir' => '1999-09-13',
    ], $overrides));
}

function acu_role(int $eventId, array $overrides = []): EventRole
{
    return app(EventCommitteeService::class)->createRole(array_merge([
        'event_id' => $eventId,
        'name' => 'Ketua Panitia',
        'code' => 'ketua_event',
        'scope' => 'event',
    ], $overrides));
}

function acu_openForm(User $admin, Event $event, Person $person, EventRole $role)
{
    return Livewire::actingAs($admin)
        ->test(CommitteeManagement::class)
        ->call('load', $event->id)
        ->set('newPersonId', $person->id)
        ->set('selectedPersonNama', $person->nama)
        ->set('newEventRoleId', $role->id);
}

// ---------------------------------------------------------------------------
// Bug fix: event role validation no longer fires when a role is selected
// ---------------------------------------------------------------------------

test('event role validation passes when a role is selected (bug regression)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    $role = acu_role($event->id);

    acu_openForm($admin, $event, $person, $role)
        ->assertSet('newEventRoleId', $role->id)
        ->call('create')
        ->assertHasNoErrors();

    expect(EventCommitteeAssignment::where('event_id', $event->id)
        ->where('person_id', $person->id)
        ->where('event_role_id', $role->id)
        ->exists())->toBeTrue();
});

test('event role remains required when not selected', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    acu_role($event->id);

    Livewire::actingAs($admin)
        ->test(CommitteeManagement::class)
        ->call('load', $event->id)
        ->set('newPersonId', $person->id)
        ->call('create')
        ->assertHasErrors(['newEventRoleId' => 'required']);

    expect(EventCommitteeAssignment::where('event_id', $event->id)->count())->toBe(0);
});

test('event role select uses live model binding (browser sync regression)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    acu_role($event->id);

    $html = Livewire::actingAs($admin)
        ->test(CommitteeManagement::class)
        ->call('load', $event->id)
        ->html();

    expect($html)->toContain('wire:model.live="newEventRoleId"');
    expect($html)->toContain('data-flux-select-native');
});

test('event role property defaults to empty string not null (browser auto-select regression)', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $t = Livewire::actingAs($admin)->test(CommitteeManagement::class);

    $value = $t->get('newEventRoleId');
    expect($value)->toBe('');
    $t->assertSet('newEventRoleId', '');
});

test('event role select renders selectable placeholder without disabled (browser auto-select regression)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    acu_role($event->id);

    $html = Livewire::actingAs($admin)
        ->test(CommitteeManagement::class)
        ->call('load', $event->id)
        ->html();

    expect($html)->toContain('<option value="">Pilih role...</option>')
        ->and($html)->toContain('wire:key="event-role-select-')
        ->and($html)->not->toContain('<option value="" disabled')
        ->and($html)->not->toContain('class="placeholder"');
});

test('selecting the first role option still submits, resets to empty string, and auto-creates user (browser auto-select regression)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    $firstRole = acu_role($event->id, ['name' => 'Ketua Fosda']);

    expect(User::count())->toBe(1);

    $t = Livewire::actingAs($admin)
        ->test(CommitteeManagement::class)
        ->call('load', $event->id)
        ->set('newPersonId', $person->id)
        ->set('selectedPersonNama', $person->nama)
        ->set('newEventRoleId', (string) $firstRole->id)
        ->call('create')
        ->assertHasNoErrors();

    expect(EventCommitteeAssignment::where('event_id', $event->id)
        ->where('person_id', $person->id)
        ->where('event_role_id', $firstRole->id)
        ->exists())->toBeTrue();

    $user = User::where('person_id', $person->id)->first();
    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('ada.saya');

    $t->assertSet('newEventRoleId', '');
});

// ---------------------------------------------------------------------------
// Auto-create user on committee assignment
// ---------------------------------------------------------------------------

test('assigning a person without a user auto-creates their login account', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    $role = acu_role($event->id);

    expect(User::count())->toBe(1);

    acu_openForm($admin, $event, $person, $role)
        ->call('create')
        ->assertHasNoErrors();

    expect(EventCommitteeAssignment::where('event_id', $event->id)
        ->where('person_id', $person->id)
        ->where('event_role_id', $role->id)
        ->exists())->toBeTrue();

    $user = User::where('person_id', $person->id)->first();
    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('ada.saya')
        ->and($user->is_active)->toBeTrue()
        ->and($user->email)->toBeNull()
        ->and($user->name)->toBe('Ada Saya');

    expect(Hash::check('13091999', $user->password))->toBeTrue();
});

test('assigning a person who already has a user does not create a new user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    User::create([
        'person_id' => $person->id,
        'name' => $person->nama,
        'username' => 'ada.saya',
        'password' => Hash::make('password'),
        'email' => null,
        'is_active' => true,
    ]);
    $role = acu_role($event->id);

    $before = User::count();

    acu_openForm($admin, $event, $person, $role)
        ->call('create')
        ->assertHasNoErrors();

    expect(User::count())->toBe($before);
    expect(User::where('person_id', $person->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Username generation
// ---------------------------------------------------------------------------

test('username is derived from person name', function () {
    $service = app(EventCommitteeService::class);
    $person = acu_person(['nama' => 'Budi Santoso']);

    $result = $service->ensureUserForPerson($person);

    expect($result['created'])->toBeTrue();
    expect($result['user']->username)->toBe('budi.santoso');
});

test('username stays unique with numeric suffix on collision', function () {
    $service = app(EventCommitteeService::class);

    $a = acu_person(['nama' => 'Ada Saya']);
    $b = acu_person(['nama' => 'Ada Saya']);
    $c = acu_person(['nama' => 'Ada Saya']);

    $ra = $service->ensureUserForPerson($a);
    $rb = $service->ensureUserForPerson($b);
    $rc = $service->ensureUserForPerson($c);

    $usernames = [$ra['user']->username, $rb['user']->username, $rc['user']->username];

    expect($usernames)->toHaveCount(3);
    expect(array_unique($usernames))->toHaveCount(3);
    expect($ra['user']->username)->toBe('ada.saya');
    expect($rb['user']->username)->toBe('ada.saya2');
    expect($rc['user']->username)->toBe('ada.saya3');
});

// ---------------------------------------------------------------------------
// Password generation
// ---------------------------------------------------------------------------

test('password is generated from tanggal lahir as dmY', function () {
    $service = app(EventCommitteeService::class);
    $person = acu_person(['nama' => 'Tanggal Lahir', 'tanggal_lahir' => '1999-09-13']);

    $result = $service->ensureUserForPerson($person);

    expect($result['plain_password'])->toBe('13091999');
    expect(Hash::check('13091999', $result['user']->password))->toBeTrue();
});

test('password falls back to temporary 12345678 when person has no tanggal lahir', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($message) => str_contains($message, 'password sementara'));

    $service = app(EventCommitteeService::class);
    $person = acu_person(['nama' => 'No Birthday', 'tanggal_lahir' => null]);

    $result = $service->ensureUserForPerson($person);

    expect($result['plain_password'])->toBe('12345678');
    expect(Hash::check('12345678', $result['user']->password))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Service level: assignment + user result
// ---------------------------------------------------------------------------

test('assignAndEnsureUser creates assignment and returns user result', function () {
    $event = acu_event();
    $person = acu_person();
    $role = acu_role($event->id);

    $result = app(EventCommitteeService::class)->assignAndEnsureUser([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    expect($result['assignment'])->toBeInstanceOf(EventCommitteeAssignment::class)
        ->and($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->person_id)->toBe($person->id)
        ->and($result['user_created'])->toBeTrue();
});

test('assignAndEnsureUser returns existing user without recreating', function () {
    $event = acu_event();
    $person = acu_person();
    User::create([
        'person_id' => $person->id,
        'name' => $person->nama,
        'username' => 'ada.saya',
        'password' => Hash::make('password'),
        'email' => null,
        'is_active' => true,
    ]);
    $role = acu_role($event->id);

    $result = app(EventCommitteeService::class)->assignAndEnsureUser([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    expect($result['user_created'])->toBeFalse()
        ->and($result['plain_password'])->toBeNull()
        ->and($result['user']->username)->toBe('ada.saya');
});

// ---------------------------------------------------------------------------
// Flash message
// ---------------------------------------------------------------------------

test('flash message shows new account credentials when account was created', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    $role = acu_role($event->id);

    acu_openForm($admin, $event, $person, $role)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSee('Panitia berhasil ditambahkan.')
        ->assertSee('ada.saya')
        ->assertSee('13091999');
});

test('flash message notes existing account when user already exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $event = acu_event();
    $person = acu_person();
    User::create([
        'person_id' => $person->id,
        'name' => $person->nama,
        'username' => 'ada.saya',
        'password' => Hash::make('password'),
        'email' => null,
        'is_active' => true,
    ]);
    $role = acu_role($event->id);

    acu_openForm($admin, $event, $person, $role)
        ->call('create')
        ->assertHasNoErrors()
        ->assertSee('Panitia berhasil ditambahkan.')
        ->assertSee('Menggunakan akun login yang sudah ada.');
});

// ---------------------------------------------------------------------------
// Login with auto-created account
// ---------------------------------------------------------------------------

test('auto-created user can login with username and birthday password', function () {
    $person = acu_person(['nama' => 'Ada Saya', 'tanggal_lahir' => '1999-09-13']);
    $result = app(EventCommitteeService::class)->ensureUserForPerson($person);

    Livewire::test(Login::class)
        ->set('email', $result['user']->username)
        ->set('password', $result['plain_password'])
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('login with email still works', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));
});

test('inactive user cannot login', function () {
    $person = acu_person(['nama' => 'Non Aktif']);
    $result = app(EventCommitteeService::class)->ensureUserForPerson($person);
    $result['user']->update(['is_active' => false]);

    Livewire::test(Login::class)
        ->set('email', $result['user']->username)
        ->set('password', $result['plain_password'])
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

// ---------------------------------------------------------------------------
// Migration: users.username + users.is_active + nullable email
// ---------------------------------------------------------------------------

test('users table has username and is_active columns and nullable email', function () {
    $columns = Illuminate\Support\Facades\Schema::getColumnListing('users');
    expect($columns)->toContain('username')->toContain('is_active');

    $user = User::create([
        'name' => 'No Email',
        'email' => null,
        'username' => 'no.email',
        'password' => Hash::make('12345678'),
        'is_active' => true,
    ]);

    expect($user->email)->toBeNull()
        ->and($user->username)->toBe('no.email')
        ->and($user->is_active)->toBeTrue();
});

test('users username column is unique', function () {
    User::create([
        'name' => 'One',
        'email' => null,
        'username' => 'same.name',
        'password' => Hash::make('12345678'),
        'is_active' => true,
    ]);

    expect(fn () => User::create([
        'name' => 'Two',
        'email' => null,
        'username' => 'same.name',
        'password' => Hash::make('12345678'),
        'is_active' => true,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});
