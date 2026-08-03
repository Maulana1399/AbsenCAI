<?php

use App\Livewire\Event\EventRoleManager;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ercrud_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Event Role CRUD Event '.str()->random(6),
        'slug' => 'ercrud-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function ercrud_role(Event $event, array $overrides = []): EventRole
{
    return EventRole::create(array_merge([
        'event_id' => $event->id,
        'name' => 'Role '.str()->random(6),
        'code' => 'ketua_event',
    ], $overrides));
}

function ercrud_assign(Event $event, EventRole $role, ?Person $person = null): EventCommitteeAssignment
{
    $person = $person ?? Person::create(['nama' => 'Panitia '.str()->random(6)]);

    return EventCommitteeAssignment::create([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);
}

function ercrud_admin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

// ---------------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------------

test('role without assignment can be deleted', function () {
    $event = ercrud_event();
    $role = ercrud_role($event);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('delete', $role->id)
        ->assertSee('Role berhasil dihapus.');

    expect(EventRole::find($role->id))->toBeNull();
});

test('role with assignment is refused deletion', function () {
    $event = ercrud_event();
    $role = ercrud_role($event);
    ercrud_assign($event, $role);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('delete', $role->id)
        ->assertSee('Role masih digunakan oleh 1 panitia.');

    expect(EventRole::find($role->id))->not->toBeNull();
});

test('assignment stays intact after refused deletion', function () {
    $event = ercrud_event();
    $role = ercrud_role($event);
    $assignment = ercrud_assign($event, $role);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('delete', $role->id);

    expect(EventCommitteeAssignment::find($assignment->id))->not->toBeNull();
    expect(EventCommitteeAssignment::where('event_role_id', $role->id)->count())->toBe(1);
});

test('delete role in one event does not affect another event', function () {
    $eventA = ercrud_event();
    $eventB = ercrud_event();
    $roleA = ercrud_role($eventA, ['name' => 'Role A']);
    $roleB = ercrud_role($eventB, ['name' => 'Role B']);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $eventA->id)
        ->call('delete', $roleA->id);

    expect(EventRole::find($roleA->id))->toBeNull();
    expect(EventRole::find($roleB->id))->not->toBeNull();
});

test('unauthorized role cannot delete event role', function () {
    $event = ercrud_event();
    $role = ercrud_role($event);
    $user = User::factory()->create(['role' => 'operator_scan']);
    $this->actingAs($user);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('delete', $role->id)
        ->assertForbidden();

    expect(EventRole::find($role->id))->not->toBeNull();
});

test('delete non-existent role flashes error', function () {
    $event = ercrud_event();
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('delete', 99999)
        ->assertSee('Role tidak ditemukan.');
});

// ---------------------------------------------------------------------------
// EDIT
// ---------------------------------------------------------------------------

test('edit role name succeeds', function () {
    $event = ercrud_event();
    $role = ercrud_role($event, ['name' => 'Ketua Lama']);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('edit', $role->id)
        ->set('editName', 'Ketua Panitia Baru')
        ->call('update')
        ->assertSee('Role berhasil diperbarui.');

    expect($role->fresh()->name)->toBe('Ketua Panitia Baru');
});

test('edit role description succeeds', function () {
    $event = ercrud_event();
    $role = ercrud_role($event);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('edit', $role->id)
        ->set('editDescription', 'Deskripsi baru')
        ->call('update');

    expect($role->fresh()->description)->toBe('Deskripsi baru');
});

test('edit does not change code', function () {
    $event = ercrud_event();
    $role = ercrud_role($event, ['name' => 'Kode Role', 'code' => 'ketua_fosda']);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('edit', $role->id)
        ->assertSet('editCode', 'ketua_fosda')
        ->set('editName', 'Ketua Panitia')
        ->call('update');

    expect($role->fresh()->code)->toBe('ketua_fosda');
});

test('edit does not change template or permissions', function () {
    $event = ercrud_event();
    $role = ercrud_role($event, ['name' => 'Fosda Role', 'code' => 'ketua_fosda']);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $permissionsBefore = $role->permissions;

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('edit', $role->id)
        ->set('editName', 'Ketua Panitia')
        ->call('update');

    expect($role->fresh()->permissions)->toBe($permissionsBefore);
});

test('edit shows code as read-only value', function () {
    $event = ercrud_event();
    $role = ercrud_role($event, ['code' => 'sekretariat']);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $component = Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('edit', $role->id);

    $html = $component->html();
    expect($html)->toContain('sekretariat');
    expect($html)->toContain('Sekretariat');
});

test('edit template cannot be changed (no template select in edit modal)', function () {
    $event = ercrud_event();
    $role = ercrud_role($event);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $component = Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->call('edit', $role->id);

    $html = $component->html();
    // Tidak ada wire:model select untuk template di modal edit:
    // editCode hanya ditampilkan read-only (bukan wire:model select).
    expect($html)->not->toContain('wire:model="editCode"');
    expect($html)->not->toContain('wire:model="editPermissions"');
});

// ---------------------------------------------------------------------------
// UI — assignment count display
// ---------------------------------------------------------------------------

test('role list shows assignment count', function () {
    $event = ercrud_event();
    $role = ercrud_role($event, ['name' => 'Fosda UI']);
    ercrud_assign($event, $role);
    ercrud_assign($event, $role);
    ercrud_assign($event, $role);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $component = Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id);

    $component->assertSee('Dipakai oleh 3 panitia');
});

test('role list shows not used label', function () {
    $event = ercrud_event();
    ercrud_role($event, ['name' => 'Unused Role']);
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $component = Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id);

    $component->assertSee('Belum digunakan');
});

// ---------------------------------------------------------------------------
// Template → Code sync (bug: code tidak pernah terisi)
// ---------------------------------------------------------------------------

test('create with template fills code automatically (no manual code)', function () {
    $event = ercrud_event();
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Ketua Panitia')
        ->set('newTemplate', 'ketua_event')
        ->call('create');

    $role = EventRole::where('event_id', $event->id)->where('name', 'Ketua Panitia')->first();
    expect($role)->not->toBeNull();
    expect($role->code)->toBe('ketua_event');
    expect($role->permissions)->toContain('view-dashboard');
});

test('template select renders live binding without disabled placeholder', function () {
    $event = ercrud_event();
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $html = Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->html();

    // Dropdown template terikat ke newTemplate (live), bukan newCode.
    expect($html)->toContain('wire:model.live="newTemplate"');
    expect($html)->not->toContain('wire:model="newCode"');

    // Opsi kosong selectable (bukan disabled) agar change event selalu terpantau.
    expect($html)->toContain('<option value="">Pilih template...</option>');

    // Code tampil read-only.
    expect($html)->toContain('Code diisi otomatis dari template permission dan tidak dapat diubah.');
});

test('create without template fails validation', function () {
    $event = ercrud_event();
    $admin = ercrud_admin();
    $this->actingAs($admin);

    Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id)
        ->set('newName', 'Role Tanpa Template')
        ->call('create')
        ->assertHasErrors('newTemplate');

    expect(EventRole::where('event_id', $event->id)->where('name', 'Role Tanpa Template')->exists())->toBeFalse();
});

test('code cannot be set independently of template', function () {
    $event = ercrud_event();
    $admin = ercrud_admin();
    $this->actingAs($admin);

    $component = Livewire::test(EventRoleManager::class)
        ->dispatch('manageEventRoles', id: $event->id);

    // Admin tidak mengetik code; hanya template yang menentukan code.
    $component->set('newTemplate', 'sekretariat');
    expect($component->get('newCode'))->toBe('sekretariat');

    // Tidak ada input manual code di form (read-only display).
    $html = $component->html();
    expect($html)->not->toContain('wire:model="newCode"');
});
