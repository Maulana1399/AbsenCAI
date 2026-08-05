<?php

use App\Enums\Role;
use App\Livewire\MasterData\User\EditUser;
use App\Models\User;
use App\Services\User\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function enu_superAdmin(): User
{
    return User::factory()->create([
        'role' => 'super_admin',
        'email' => 'enu-admin-'.str()->random(8).'@example.com',
    ]);
}

function enu_autoUser(array $overrides = []): User
{
    // Simulasi akun auto-create: email NULL, role NULL, login via username.
    return User::factory()->create(array_merge([
        'name' => 'Auto Create User',
        'email' => null,
        'username' => 'auto.user'.random_int(10, 9999),
        'role' => null,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Edit dengan email NULL — tidak boleh 500
// ---------------------------------------------------------------------------

test('edit user with NULL email does not 500', function () {
    $user = enu_autoUser();
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->assertSet('email', null)
        ->assertSet('name', $user->name);
});

test('edit modal opens normally for auto-created user', function () {
    $user = enu_autoUser();
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->assertSet('userId', $user->id)
        ->assertSet('role', '')
        ->assertOk();
});

// ---------------------------------------------------------------------------
// Save behavior — email NULL / kosong / baru
// ---------------------------------------------------------------------------

test('save user without email succeeds and keeps email NULL', function () {
    $user = enu_autoUser(['role' => 'admin']);
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('email', null)
        ->call('update');

    $user->refresh();
    expect($user->email)->toBeNull();
});

test('save with empty email string converts to NULL', function () {
    // User dengan email terisi, lalu di-edit menjadi kosong.
    $user = User::factory()->create([
        'role' => 'admin',
        'email' => 'will-be-cleared@example.com',
    ]);
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('email', '')
        ->call('update');

    $user->refresh();
    expect($user->email)->toBeNull();
});

test('save with new email works', function () {
    $user = enu_autoUser(['role' => 'admin']);
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('email', 'new-email@example.com')
        ->call('update');

    $user->refresh();
    expect($user->email)->toBe('new-email@example.com');
});

test('normal email account still works', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'email' => 'normal@example.com',
    ]);
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('name', 'Normal Updated')
        ->set('email', 'normal-updated@example.com')
        ->call('update');

    $user->refresh();
    expect($user->email)->toBe('normal-updated@example.com');
    expect($user->name)->toBe('Normal Updated');
});

// ---------------------------------------------------------------------------
// Username login & role platform tidak terpengaruh
// ---------------------------------------------------------------------------

test('editing email NULL user does not change username', function () {
    $user = enu_autoUser(['username' => 'keeper.username', 'role' => 'admin']);
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('email', 'has-username@example.com')
        ->call('update');

    $user->refresh();
    expect($user->username)->toBe('keeper.username');
    expect($user->email)->toBe('has-username@example.com');
});

test('platform role change still works for email NULL user', function () {
    $user = enu_autoUser(['role' => 'viewer']);
    $this->actingAs(enu_superAdmin());

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('role', 'admin')
        ->call('update');

    $user->refresh();
    expect($user->role)->toBe(Role::Admin);
});

// ---------------------------------------------------------------------------
// UserManagementService::update — email nullable tetap didukung
// ---------------------------------------------------------------------------

test('service update supports nullable email', function () {
    $user = enu_autoUser(['role' => 'admin']);
    $this->actingAs(enu_superAdmin());

    app(UserManagementService::class)->update($user, [
        'name' => 'Via Service',
        'email' => null,
        'role' => 'admin',
    ]);

    $user->refresh();
    expect($user->email)->toBeNull();
    expect($user->name)->toBe('Via Service');
});
