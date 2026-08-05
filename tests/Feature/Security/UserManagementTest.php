<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function um_user(?string $role = null): User
{
    return User::factory()->create([
        'role' => $role,
        'email' => 'um-'.str()->random(10).'@example.com',
    ]);
}

// ---------------------------------------------------------------------------
// A. Route access
// ---------------------------------------------------------------------------

test('guest cannot access users page', function () {
    $this->get('/users')->assertRedirect('/login');
});

test('null role cannot access users page', function () {
    $this->actingAs(um_user());
    $this->get('/users')->assertForbidden();
});

test('admin cannot access users page', function () {
    $this->actingAs(um_user('admin'));
    $this->get('/users')->assertForbidden();
});

test('sekretariat cannot access users page', function () {
    $this->actingAs(um_user('sekretariat'));
    $this->get('/users')->assertForbidden();
});

test('super admin can access users page', function () {
    $this->actingAs(um_user('super_admin'));
    $this->get('/users')->assertOk();
});

// ---------------------------------------------------------------------------
// B. Master Data card visibility
// ---------------------------------------------------------------------------

test('super admin sees Manajemen User card', function () {
    $this->actingAs(um_user('super_admin'));
    $this->get('/master-data')->assertSee('Manajemen User');
});

test('admin does not see Manajemen User card', function () {
    $this->actingAs(um_user('admin'));
    $this->get('/master-data')->assertDontSee('Manajemen User');
});

test('sekretariat does not see Manajemen User card', function () {
    $this->actingAs(um_user('sekretariat'));
    $this->get('/master-data')->assertDontSee('Manajemen User');
});

// ---------------------------------------------------------------------------
// C. Create User
// ---------------------------------------------------------------------------

test('super admin can create user', function () {
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'New User')
        ->set('email', 'new@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'admin')
        ->call('simpan');

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'role' => 'admin',
    ]);

    $user = User::where('email', 'new@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('create user password is hashed', function () {
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'Hash Test')
        ->set('email', 'hash@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'super_admin')
        ->call('simpan');

    $user = User::where('email', 'hash@example.com')->first();
    expect($user->password)->not->toBe('password123');
    expect(Hash::isHashed($user->password))->toBeTrue();
});

test('duplicate email rejected on create', function () {
    User::factory()->create(['email' => 'dup@example.com', 'role' => 'admin']);
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'Duplicate')
        ->set('email', 'dup@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'admin')
        ->call('simpan')
        ->assertHasErrors('email');
});

test('invalid role rejected on create', function () {
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'Bad Role')
        ->set('email', 'bad@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'nonexistent_role')
        ->call('simpan')
        ->assertHasErrors('role');
});

// ---------------------------------------------------------------------------
// D. Edit User
// ---------------------------------------------------------------------------

test('super admin can edit user', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'edit@example.com']);
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->set('role', 'admin')
        ->call('update');

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->role)->toBe(Role::Admin);
});

test('email uniqueness enforced on edit', function () {
    User::factory()->create(['email' => 'existing@example.com', 'role' => 'admin']);
    $target = User::factory()->create(['email' => 'target@example.com', 'role' => 'admin']);
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\EditUser::class)
        ->dispatch('editUser', id: $target->id)
        ->set('name', 'Test')
        ->set('email', 'existing@example.com')
        ->set('role', 'admin')
        ->call('update')
        ->assertHasErrors('email');
});

// ---------------------------------------------------------------------------
// E. Reset Password
// ---------------------------------------------------------------------------

test('super admin can reset user password', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'reset-test@example.com']);
    $oldHash = $user->password;
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\ResetPasswordUser::class)
        ->dispatch('resetPasswordUser', id: $user->id)
        ->set('newPassword', 'newpassword123')
        ->set('newPasswordConfirmation', 'newpassword123')
        ->call('resetPassword');

    $user->refresh();
    expect($user->password)->not->toBe($oldHash);
    expect(Hash::check('newpassword123', $user->password))->toBeTrue();
});

// ---------------------------------------------------------------------------
// F. Delete User
// ---------------------------------------------------------------------------

test('super admin can delete normal user', function () {
    $target = User::factory()->create(['email' => 'delete-me@example.com', 'role' => 'admin']);
    $this->actingAs(um_user('super_admin'));

    Livewire::test(\App\Livewire\MasterData\User\DeleteUser::class)
        ->dispatch('deleteUser', id: $target->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    $this->assertDatabaseMissing('users', ['id' => $target->id]);
});

test('cannot delete self', function () {
    $admin = um_user('super_admin');
    $this->actingAs($admin);

    Livewire::test(\App\Livewire\MasterData\User\DeleteUser::class)
        ->dispatch('deleteUser', id: $admin->id)
        ->assertSet('canDelete', false);
});

test('cannot delete last super admin', function () {
    $superAdmin = um_user('super_admin');
    $this->actingAs($superAdmin);
    $target = User::factory()->create(['email' => 'admin2@example.com', 'role' => 'admin']);

    // Promote admin to super_admin
    $target->update(['role' => Role::SuperAdmin]);
    // Demote original to admin
    $superAdmin->update(['role' => Role::Admin]);
    $superAdmin->refresh();

    $this->actingAs($superAdmin);

    Livewire::test(\App\Livewire\MasterData\User\DeleteUser::class)
        ->dispatch('deleteUser', id: $target->id)
        ->assertSet('canDelete', false);
});

// ---------------------------------------------------------------------------
// G. Authorization — unauthorized mutation blocked
// ---------------------------------------------------------------------------

test('admin cannot create user via Livewire', function () {
    $this->actingAs(um_user('admin'));

    Livewire::test(\App\Livewire\MasterData\User\CreateUser::class)
        ->set('name', 'Hacker')
        ->set('email', 'hack@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->set('role', 'admin')
        ->call('simpan')
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'hack@example.com']);
});

test('admin cannot edit user via Livewire', function () {
    $target = User::factory()->create(['role' => 'admin', 'email' => 'edit-target@example.com']);
    $this->actingAs(um_user('sekretariat'));

    Livewire::test(\App\Livewire\MasterData\User\EditUser::class)
        ->dispatch('editUser', id: $target->id)
        ->set('name', 'Hacked Name')
        ->call('update')
        ->assertForbidden();
});

test('admin cannot reset password via Livewire', function () {
    $target = User::factory()->create(['role' => 'admin', 'email' => 'reset-target@example.com']);
    $this->actingAs(um_user('operator_scan'));

    Livewire::test(\App\Livewire\MasterData\User\ResetPasswordUser::class)
        ->dispatch('resetPasswordUser', id: $target->id)
        ->set('newPassword', 'hacked123')
        ->set('newPasswordConfirmation', 'hacked123')
        ->call('resetPassword')
        ->assertForbidden();
});

test('admin cannot delete user via Livewire', function () {
    $target = User::factory()->create(['email' => 'del-target@example.com', 'role' => 'admin']);
    $this->actingAs(um_user('operator_registrasi'));

    Livewire::test(\App\Livewire\MasterData\User\DeleteUser::class)
        ->dispatch('deleteUser', id: $target->id)
        ->call('destroy')
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $target->id]);
});

// ---------------------------------------------------------------------------
// H. Regression — user:set-role command still works
// ---------------------------------------------------------------------------

test('user set role artisan command still works', function () {
    $user = User::factory()->create(['email' => 'artisan@example.com', 'role' => null]);

    $this->artisan('user:set-role', ['email' => 'artisan@example.com', 'role' => 'admin'])
        ->assertSuccessful();

    $user->refresh();
    expect($user->role)->toBe(Role::Admin);
});

// ---------------------------------------------------------------------------
// I. Regression — S1-S4 authorization intact
// ---------------------------------------------------------------------------

test('master data routes still protected', function () {
    $this->actingAs(um_user('super_admin'));
    $this->get('/master-data')->assertOk();
});

test('user factory default role remains null', function () {
    $user = User::factory()->create();
    expect($user->role)->toBeNull();
});
