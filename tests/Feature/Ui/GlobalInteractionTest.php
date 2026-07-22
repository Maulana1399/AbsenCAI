<?php

use App\Livewire\Event\EditStatus;
use App\Livewire\Event\EventRoleManager;
use App\Livewire\Event\CommitteeManagement;
use App\Livewire\MasterData\User\IndexUser;
use App\Livewire\MasterData\User\EditUser;
use App\Livewire\MasterData\User\ResetPasswordUser;
use App\Livewire\MasterData\User\DeleteUser;
use App\Models\User;
use App\Enums\Role;
use Livewire\Livewire;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($this->user);
});

// ---------------------------------------------------------------------------
// Modal close controls
// ---------------------------------------------------------------------------

test('edit event modal has batal close button', function () {
    Livewire::test(EditStatus::class)
        ->assertSee('Batal');
});

test('event role modal has tutup close button', function () {
    Livewire::test(EventRoleManager::class)
        ->assertSee('Tutup');
});

test('committee management modal has tutup close button', function () {
    Livewire::test(CommitteeManagement::class)
        ->assertSee('Tutup');
});

// ---------------------------------------------------------------------------
// User Management — action visibility (runtime, not source grep)
// ---------------------------------------------------------------------------

test('super admin sees edit and reset action labels for every user', function () {
    User::factory()->create(['name' => 'Target User', 'role' => Role::Admin]);

    Livewire::test(IndexUser::class)
        ->assertSee('Target User')
        ->assertSee('Edit')
        ->assertSee('Reset');
});

test('super admin sees delete action only for other users', function () {
    $other = User::factory()->create(['name' => 'Other User', 'role' => Role::Admin]);

    $html = Livewire::test(IndexUser::class)->html();
    expect($html)->toContain('data-testid="delete-user-' . $other->id . '"');
    expect($html)->not->toContain('data-testid="delete-user-' . $this->user->id . '"');
});

test('super admin does not see delete action for own record', function () {
    $other = User::factory()->create(['name' => 'Other User', 'role' => Role::Admin]);

    $html = Livewire::test(IndexUser::class)->html();
    expect($html)->not->toContain('data-testid="delete-user-' . $this->user->id . '"');
});

test('super admin edit action triggers edit user modal', function () {
    $target = User::factory()->create(['name' => 'Editable User', 'role' => Role::Admin]);

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $target->id)
        ->assertSet('name', 'Editable User')
        ->assertSee('Simpan');
});

test('super admin reset password action triggers reset modal', function () {
    $target = User::factory()->create(['name' => 'Resettable User', 'role' => Role::Admin]);

    Livewire::test(ResetPasswordUser::class)
        ->dispatch('resetPasswordUser', id: $target->id)
        ->assertSet('userName', 'Resettable User')
        ->assertSee('Reset Password');
});

test('super admin delete action triggers delete modal for other user', function () {
    $target = User::factory()->create(['name' => 'Deletable User', 'role' => Role::Admin]);

    Livewire::test(DeleteUser::class)
        ->dispatch('deleteUser', id: $target->id)
        ->assertSet('userName', 'Deletable User')
        ->assertSee('Hapus User')
        ->assertSee('Batal');
});

test('unauthorized role cannot access user management page', function () {
    $restricted = User::factory()->create(['role' => Role::Viewer]);
    $this->actingAs($restricted);

    Livewire::test(IndexUser::class)
        ->assertForbidden();
});

test('index user page has responsive mobile aksi dropdown', function () {
    User::factory()->create(['name' => 'Dropdown User', 'role' => Role::Admin]);

    Livewire::test(IndexUser::class)
        ->assertSee('Aksi');
});

test('mobile dropdown contains edit action', function () {
    $target = User::factory()->create(['name' => 'Menu User', 'role' => Role::Admin]);

    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $target->id)
        ->assertSet('name', 'Menu User');
});

test('mobile dropdown contains reset password action', function () {
    $target = User::factory()->create(['name' => 'Reset User', 'role' => Role::Admin]);

    Livewire::test(ResetPasswordUser::class)
        ->dispatch('resetPasswordUser', id: $target->id)
        ->assertSet('userName', 'Reset User');
});

test('mobile dropdown hides hapus for own user', function () {
    $other = User::factory()->create(['name' => 'Other User', 'role' => Role::Admin]);

    $html = Livewire::test(IndexUser::class)->html();
    expect($html)->not->toContain('data-testid="delete-user-' . $this->user->id . '"');
    expect($html)->toContain('data-testid="delete-user-' . $other->id . '"');
});

test('user management row hover uses correct dark mode shade', function () {
    $view = Blade::render('
        @php
            $users = collect([\App\Models\User::factory()->make(["id" => 1, "name" => "Test"])]);
        @endphp
        <table>
            <tbody>
                @foreach ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                        <td>{{ $user->name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    ');
    expect($view)->toContain('hover:bg-zinc-50');
    expect($view)->toContain('dark:hover:bg-zinc-900/50');
    expect($view)->not->toContain('dark:hover:bg-zinc-800/50');
});

test('user management table has visible desktop action column on lg+', function () {
    User::factory()->create(['name' => 'Desktop User', 'role' => Role::Admin]);

    Livewire::test(IndexUser::class)
        ->assertSee('Desktop User')
        ->assertSee('Edit')
        ->assertSee('Reset');
});

// ---------------------------------------------------------------------------
// App logo icon
// ---------------------------------------------------------------------------

test('app-logo-icon component renders visible svg', function () {
    $view = Blade::render('<x-app-logo-icon class="size-9" />');
    expect($view)->toContain('<svg');
    expect($view)->toContain('fill="currentColor"');
    expect($view)->toContain('viewBox="0 0 40 42"');
});

test('app-logo-icon is not empty', function () {
    $view = Blade::render('<x-app-logo-icon class="size-9" />');
    expect(trim($view))->not->toBeEmpty();
});

test('app logo icon accepts and forwards class attributes', function () {
    $view = Blade::render('<x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />');
    expect($view)->toContain('<svg');
    expect($view)->toContain('class="size-9 fill-current text-black dark:text-white"');
});
