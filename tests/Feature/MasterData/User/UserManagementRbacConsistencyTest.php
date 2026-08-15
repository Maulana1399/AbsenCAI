<?php

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Livewire\MasterData\User\CreateUser;
use App\Livewire\MasterData\User\EditUser;
use App\Livewire\MasterData\User\IndexUser;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Services\User\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function umr_superAdmin(): User
{
    return User::factory()->create(['role' => 'super_admin', 'email' => 'umr-admin-'.str()->random(8).'@example.com']);
}

function umr_event(): Event
{
    return Event::create(['name' => 'UMR Event '.str()->random(6), 'slug' => 'umr-'.str()->random(6), 'status' => 'active', 'event_type' => 'cai']);
}

function umr_person(): Person
{
    return Person::create(['nama' => 'UMR Person '.str()->random(6)]);
}

function umr_assign(Person $person, Event $event, string $code, string $name): EventRole
{
    $role = EventRole::create(['event_id' => $event->id, 'name' => $name, 'code' => $code]);
    EventCommitteeAssignment::create([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    return $role;
}

// ---------------------------------------------------------------------------
// 1. Role dropdown berisi role akun (platform + event-scoped account)
// ---------------------------------------------------------------------------

test('create user dropdown contains account roles (platform + guest/event chair)', function () {
    $component = Livewire::test(CreateUser::class);
    $html = $component->html();

    expect($html)->toContain('value="super_admin"');
    expect($html)->toContain('value="admin"');
    expect($html)->toContain('value="event_chair"');
    expect($html)->toContain('value="guest"');

    // Role event (EventRole) tidak boleh muncul
    foreach (['ketua_event', 'sekretariat', 'pj_divisi', 'operator_registrasi', 'operator_scan', 'juri', 'viewer'] as $eventRole) {
        expect($html)->not->toContain('value="'.$eventRole.'"');
    }
});

test('edit user dropdown contains account roles (platform + guest/event chair)', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'edit-dropdown@example.com']);
    $this->actingAs(umr_superAdmin());

    $component = Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id);

    $html = $component->html();
    expect($html)->toContain('value="super_admin"');
    expect($html)->toContain('value="admin"');
    expect($html)->toContain('value="event_chair"');
    expect($html)->toContain('value="guest"');

    foreach (['ketua_event', 'sekretariat', 'pj_divisi', 'operator_registrasi', 'operator_scan', 'juri', 'viewer'] as $eventRole) {
        expect($html)->not->toContain('value="'.$eventRole.'"');
    }
});

test('Role::platformCases returns only SuperAdmin and Admin', function () {
    expect(Role::platformValues())->toBe(['super_admin', 'admin']);
});

// ---------------------------------------------------------------------------
// 2. Person read-only
// ---------------------------------------------------------------------------

test('edit user shows person as read-only', function () {
    $person = umr_person();
    $user = User::factory()->create(['role' => 'admin', 'email' => 'readonly@example.com', 'person_id' => $person->id]);
    $this->actingAs(umr_superAdmin());

    $component = Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id);

    expect($component->get('person_id'))->toBe($person->id);
    expect($component->get('selectedPersonNama'))->toBe($person->nama);

    // Tidak ada tombol ganti person / search person di modal edit
    $html = $component->html();
    expect($html)->not->toContain('removePerson');
    expect($html)->not->toContain('selectPerson');
});

test('cannot change person through update', function () {
    $personA = umr_person();
    $personB = umr_person();
    $user = User::factory()->create(['role' => 'admin', 'email' => 'person-locked@example.com', 'person_id' => $personA->id]);
    $this->actingAs(umr_superAdmin());

    // Coba paksa ganti person via update — harus tetap person A
    Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id)
        ->set('person_id', $personB->id)
        ->set('selectedPersonNama', $personB->nama)
        ->set('name', 'Person Locked Updated')
        ->call('update');

    $user->refresh();
    expect($user->person_id)->toBe($personA->id);
});

// ---------------------------------------------------------------------------
// 3. Header person
// ---------------------------------------------------------------------------

test('edit modal header shows person name', function () {
    $person = umr_person();
    $user = User::factory()->create(['role' => 'admin', 'email' => 'header@example.com', 'person_id' => $person->id]);
    $this->actingAs(umr_superAdmin());

    $component = Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id);

    $component->assertSee('Person: '.$person->nama);
});

test('edit modal header shows em dash when no person', function () {
    $user = User::factory()->create(['role' => 'admin', 'email' => 'no-person@example.com']);
    $this->actingAs(umr_superAdmin());

    $component = Livewire::test(EditUser::class)
        ->dispatch('editUser', id: $user->id);

    $component->assertSee('Person: —');
});

// ---------------------------------------------------------------------------
// 4. User list — Platform Role & Event Role columns
// ---------------------------------------------------------------------------

test('user list shows platform role from users.role', function () {
    $admin = User::factory()->create(['role' => 'admin', 'email' => 'plat-admin@example.com']);
    $this->actingAs(umr_superAdmin());

    $component = Livewire::test(IndexUser::class);

    $component->assertSee('Platform Role');
    $html = $component->html();
    expect($html)->toContain($admin->name);
    // label platform role tampil
    expect($html)->toContain('Admin');
});

test('user list shows event role from active assignment', function () {
    $event = umr_event();
    $person = umr_person();
    umr_assign($person, $event, 'ketua_fosda', 'Ketua Fosda');
    $user = User::factory()->create(['role' => null, 'email' => null, 'username' => 'umr-user-'.str()->random(6), 'person_id' => $person->id]);
    $this->actingAs(umr_superAdmin());

    $component = Livewire::test(IndexUser::class);

    $component->assertSee('Event Role');
    $html = $component->html();
    expect($html)->toContain('Ketua Fosda');
});

test('user list shows multiple event roles as first (+N)', function () {
    $event = umr_event();
    $person = umr_person();
    umr_assign($person, $event, 'ketua_fosda', 'Ketua Fosda');
    umr_assign($person, $event, 'operator_scan', 'Operator Scan');
    $user = User::factory()->create(['role' => null, 'email' => null, 'username' => 'umr-user-'.str()->random(6), 'person_id' => $person->id]);
    $this->actingAs(umr_superAdmin());

    $html = Livewire::test(IndexUser::class)->html();
    expect($html)->toContain('Ketua Fosda (+1)');
});

// ---------------------------------------------------------------------------
// 5. Delete user — hanya menghapus akun login
// ---------------------------------------------------------------------------

test('delete user does not delete person', function () {
    $person = umr_person();
    $user = User::factory()->create(['role' => 'admin', 'email' => 'del-person@example.com', 'person_id' => $person->id]);
    $this->actingAs(umr_superAdmin());

    app(UserManagementService::class)->delete($user, umr_superAdmin());

    expect(User::find($user->id))->toBeNull();
    expect(Person::find($person->id))->not->toBeNull();
});

test('delete user does not delete assignment', function () {
    $event = umr_event();
    $person = umr_person();
    $role = umr_assign($person, $event, 'ketua_fosda', 'Ketua Fosda');
    $user = User::factory()->create(['role' => 'admin', 'email' => 'del-assignment@example.com', 'person_id' => $person->id]);

    $assignment = EventCommitteeAssignment::where('event_id', $event->id)->where('person_id', $person->id)->first();

    $this->actingAs(umr_superAdmin());
    app(UserManagementService::class)->delete($user, umr_superAdmin());

    expect(EventCommitteeAssignment::find($assignment->id))->not->toBeNull();
    expect(EventRole::find($role->id))->not->toBeNull();
});

test('delete user does not delete attendance', function () {
    $event = umr_event();
    $person = umr_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $sesi = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi', 'tanggal' => '2026-08-01', 'aktif' => true]);
    $attendance = EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $sesi->id,
        'event_id' => $event->id,
        'status' => 'hadir',
        'attended_at' => now(),
        'method' => 'manual',
    ]);
    $user = User::factory()->create(['role' => 'admin', 'email' => 'del-attendance@example.com', 'person_id' => $person->id]);

    $this->actingAs(umr_superAdmin());
    app(UserManagementService::class)->delete($user, umr_superAdmin());

    expect(EventAttendance::find($attendance->id))->not->toBeNull();
});

test('delete user does not delete participation', function () {
    $event = umr_event();
    $person = umr_person();
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $user = User::factory()->create(['role' => 'admin', 'email' => 'del-participation@example.com', 'person_id' => $person->id]);

    $this->actingAs(umr_superAdmin());
    app(UserManagementService::class)->delete($user, umr_superAdmin());

    expect(Participation::find($participation->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// 6. Status user — Aktifkan / Nonaktifkan
// ---------------------------------------------------------------------------

test('toggle active deactivates user', function () {
    $target = User::factory()->create(['role' => 'admin', 'email' => 'toggle-off@example.com']);
    $this->actingAs(umr_superAdmin());

    Livewire::test(IndexUser::class)
        ->call('toggleActive', $target->id);

    expect($target->fresh()->is_active)->toBeFalse();
});

test('toggle active reactivates user', function () {
    $target = User::factory()->create(['role' => 'admin', 'email' => 'toggle-on@example.com', 'is_active' => false]);
    $this->actingAs(umr_superAdmin());

    Livewire::test(IndexUser::class)
        ->call('toggleActive', $target->id);

    expect($target->fresh()->is_active)->toBeTrue();
});

test('cannot deactivate own account', function () {
    $admin = umr_superAdmin();
    $this->actingAs($admin);

    Livewire::test(IndexUser::class)
        ->call('toggleActive', $admin->id);

    expect($admin->fresh()->is_active)->toBeTrue();
});

// ---------------------------------------------------------------------------
// 7. Login status
// ---------------------------------------------------------------------------

test('inactive user cannot login', function () {
    $user = User::factory()->create(['email' => 'inactive@example.com', 'is_active' => false, 'password' => Hash::make('password')]);

    Livewire::test(Login::class)
        ->set('email', 'inactive@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');
});

test('active user can login', function () {
    $user = User::factory()->create(['email' => 'active@example.com', 'is_active' => true, 'password' => Hash::make('password')]);

    Livewire::test(Login::class)
        ->set('email', 'active@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();
});
