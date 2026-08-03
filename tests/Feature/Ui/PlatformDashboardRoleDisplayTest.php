<?php

use App\Livewire\Dashboard\PlatformDashboard;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function pdu_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Dashboard Event '.str()->random(6),
        'slug' => 'dash-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ], $overrides));
}

function pdu_user(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => null,
    ], $overrides));
}

function pdu_assign(User $user, Event $event, string $code, string $name): EventRole
{
    if ($user->person_id === null) {
        $person = Person::create(['nama' => 'Dashboard Person '.str()->random(6)]);
        $user->forceFill(['person_id' => $person->id])->save();
    }

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => $name,
        'code' => $code,
    ]);

    EventCommitteeAssignment::create([
        'event_id' => $event->id,
        'person_id' => $user->person_id,
        'event_role_id' => $role->id,
    ]);

    return $role;
}

// ---------------------------------------------------------------------------
// Dashboard role display — data dari EventAssignment (bukan users.role)
// ---------------------------------------------------------------------------

test('dashboard shows EventRole.name for assigned user', function () {
    $event = pdu_event();
    $user = pdu_user();
    pdu_assign($user, $event, 'ketua_fosda', 'Ketua Fosda');

    $this->actingAs($user);

    $component = Livewire::test(PlatformDashboard::class);

    $events = $component->get('events');
    expect($events)->toHaveCount(1);

    $roleLabel = $component->instance()->roleLabelForEvent($events->first(), $user);
    expect($roleLabel)->toBe('Ketua Fosda');

    $component->assertSee('Ketua Fosda');
});

test('dashboard shows "Tidak ada peran" for user without assignment', function () {
    $event = pdu_event();
    $user = pdu_user();

    $this->actingAs($user);

    $component = Livewire::test(PlatformDashboard::class);

    // User tanpa person_id / assignment tidak melihat event ini sama sekali.
    $events = $component->get('events');
    expect($events)->toHaveCount(0);

    $component->assertSee('Tidak ada peran');
});

test('dashboard shows multiple assignments without error', function () {
    $event = pdu_event();
    $user = pdu_user();

    pdu_assign($user, $event, 'ketua_fosda', 'Ketua Fosda');
    pdu_assign($user, $event, 'operator_scan', 'Operator Scan');

    $this->actingAs($user);

    $component = Livewire::test(PlatformDashboard::class);

    $events = $component->get('events');
    expect($events)->toHaveCount(1);

    $roleLabel = $component->instance()->roleLabelForEvent($events->first(), $user);
    expect($roleLabel)->toBe('Ketua Fosda (+1)');

    $component->assertSee('Ketua Fosda (+1)');
});

test('dashboard reads event roles from EventAssignment, not users.role', function () {
    $event = pdu_event();
    $user = pdu_user();

    // user.role = null; hak akses event hanya dari assignment.
    pdu_assign($user, $event, 'sekretariat', 'Sekretariat');

    $this->actingAs($user);

    $component = Livewire::test(PlatformDashboard::class);

    expect($component->get('events'))->toHaveCount(1);

    $roleNames = $component->instance()->roleNamesForEvent($event, $user);
    expect($roleNames)->toBe(['Sekretariat']);
});

// ---------------------------------------------------------------------------
// Platform users tetap memakai users.role
// ---------------------------------------------------------------------------

test('platform admin keeps users.role label on dashboard', function () {
    $event = pdu_event();
    $user = pdu_user(['role' => 'admin']);

    $this->actingAs($user);

    $component = Livewire::test(PlatformDashboard::class);

    expect($component->get('events'))->toHaveCount(1);

    $roleNames = $component->instance()->roleNamesForEvent($event, $user);
    expect($roleNames)->toBe(['Admin']);
});

// ---------------------------------------------------------------------------
// Role Manager — template menghasilkan code yang benar
// ---------------------------------------------------------------------------

test('role manager template options map to valid codes', function () {
    $component = Livewire::test(\App\Livewire\Event\EventRoleManager::class);

    $options = $component->get('templateOptions');

    expect($options)->toHaveKeys([
        'ketua_event',
        'sekretariat',
        'operator_registrasi',
        'operator_scan',
        'pj_divisi',
        'juri',
        'viewer',
    ]);

    foreach (array_keys($options) as $code) {
        expect(\App\Support\EventRolePermissionDefaults::isKnownCode($code))->toBeTrue();
    }
});

test('role manager create sets code from template', function () {
    $event = pdu_event();
    $user = pdu_user(['role' => 'admin']);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Sekretaris Baru')
        ->set('newCode', 'sekretariat')
        ->call('create');

    $role = EventRole::where('event_id', $event->id)->where('name', 'Sekretaris Baru')->first();
    expect($role)->not->toBeNull();
    expect($role->code)->toBe('sekretariat');
    expect($role->permissions)->toContain('view-reports');
});

test('role manager rejects unknown code via template validation', function () {
    $event = pdu_event();
    $user = pdu_user(['role' => 'admin']);

    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Role Aneh')
        ->set('newCode', 'bukan_kode_valid')
        ->call('create')
        ->assertHasErrors('newCode');

    expect(EventRole::where('event_id', $event->id)->where('name', 'Role Aneh')->exists())->toBeFalse();
});
