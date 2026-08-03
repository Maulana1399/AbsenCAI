<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use App\Services\Activity\EventCommitteeService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function rbac_user(array $overrides = []): User
{
    return User::factory()->create($overrides);
}

// ---------------------------------------------------------------------------
// Role Enum
// ---------------------------------------------------------------------------

test('Role enum has all expected values', function () {
    expect(Role::values())->toBe([
        'super_admin',
        'admin',
        'ketua_event',
        'sekretariat',
        'pj_divisi',
        'operator_registrasi',
        'operator_scan',
        'juri',
        'viewer',
    ]);
});

test('Role enum labels are not empty', function () {
    foreach (Role::cases() as $role) {
        expect($role->label())->not->toBeEmpty();
    }
});

test('invalid role string does not resolve to enum', function () {
    expect(Role::tryFrom('invalid_role'))->toBeNull();
});

// ---------------------------------------------------------------------------
// User Model — role column
// ---------------------------------------------------------------------------

test('user can have null role', function () {
    $user = rbac_user(['role' => null]);
    expect($user->role)->toBeNull();
});

test('user role casts to Role enum', function () {
    $user = rbac_user(['role' => 'admin']);
    expect($user->role)->toBeInstanceOf(Role::class);
    expect($user->role)->toBe(Role::Admin);
});

test('hasRole returns true for matching role', function () {
    $user = rbac_user(['role' => 'super_admin']);
    expect($user->hasRole(Role::SuperAdmin))->toBeTrue();
});

test('hasRole returns false for non-matching role', function () {
    $user = rbac_user(['role' => 'admin']);
    expect($user->hasRole(Role::SuperAdmin))->toBeFalse();
});

test('hasRole returns false for null role', function () {
    $user = rbac_user(['role' => null]);
    expect($user->hasRole(Role::Admin))->toBeFalse();
});

test('hasAnyRole returns true when user has one of the roles', function () {
    $user = rbac_user(['role' => 'sekretariat']);
    expect($user->hasAnyRole(Role::Admin, Role::Sekretariat))->toBeTrue();
});

test('hasAnyRole returns false when user has none of the roles', function () {
    $user = rbac_user(['role' => 'viewer']);
    expect($user->hasAnyRole(Role::Admin, Role::Sekretariat))->toBeFalse();
});

test('hasAnyRole returns false for null role', function () {
    $user = rbac_user(['role' => null]);
    expect($user->hasAnyRole(Role::Admin, Role::Sekretariat))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Gate — Super Admin bypass
// ---------------------------------------------------------------------------

test('Super Admin bypasses all gates', function () {
    $user = rbac_user(['role' => 'super_admin']);

    $abilities = [
        'view-dashboard', 'view-master-data', 'manage-master-data',
        'manage-events', 'manage-registration', 'manage-participants',
        'manage-attendance', 'manage-sessions', 'manage-qr-labels',
        'manage-secretariat', 'manage-import', 'view-reports',
        'manage-pengajian', 'view-activity-log', 'manage-users',
    ];

    foreach ($abilities as $ability) {
        expect(Gate::forUser($user)->allows($ability))->toBeTrue("Super Admin should be able to {$ability}");
    }
});

// ---------------------------------------------------------------------------
// Gate — Admin
// ---------------------------------------------------------------------------

test('Admin has administrative permissions', function () {
    $user = rbac_user(['role' => 'admin']);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-events'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-qr-labels'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-import'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-pengajian'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-activity-log'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-users'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gate — Sekretariat
// ---------------------------------------------------------------------------

test('Sekretariat has operational permissions', function () {
    $event = Event::create(['name' => 'Sekretariat Event', 'slug' => 'sekretariat-event-'.str()->random(6), 'status' => 'active']);
    $user = rbac_user(['role' => 'sekretariat']);
    grantEventRoleToUser($user, $event, 'sekretariat');

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-events'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-qr-labels'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-import'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-pengajian'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-activity-log'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gate — Operator Registrasi
// ---------------------------------------------------------------------------

test('Operator Registrasi has registration permission only', function () {
    $event = Event::create(['name' => 'Operator Reg Event', 'slug' => 'operator-reg-event-'.str()->random(6), 'status' => 'active']);
    $user = rbac_user(['role' => 'operator_registrasi']);
    grantEventRoleToUser($user, $event, 'operator_registrasi');

    expect(Gate::forUser($user)->denies('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-events'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-import'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-reports'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gate — Operator Scan
// ---------------------------------------------------------------------------

test('Operator Scan has attendance permission only', function () {
    $event = Event::create(['name' => 'Operator Scan Event', 'slug' => 'operator-scan-event-'.str()->random(6), 'status' => 'active']);
    $user = rbac_user(['role' => 'operator_scan']);
    grantEventRoleToUser($user, $event, 'operator_scan');

    expect(Gate::forUser($user)->denies('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-qr-labels'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-import'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-reports'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gate — Viewer
// ---------------------------------------------------------------------------

test('Viewer has read-only permissions', function () {
    $event = Event::create(['name' => 'Viewer Event', 'slug' => 'viewer-event-'.str()->random(6), 'status' => 'active']);
    $user = rbac_user(['role' => 'viewer']);
    grantEventRoleToUser($user, $event, 'viewer');

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-events'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-pengajian'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gate — Null role
// ---------------------------------------------------------------------------

test('user with null role cannot access privileged gates', function () {
    $user = rbac_user(['role' => null]);

    $privilegedAbilities = [
        'view-dashboard', 'view-master-data', 'manage-master-data',
        'manage-events', 'manage-registration', 'manage-participants',
        'manage-attendance', 'manage-sessions', 'manage-qr-labels',
        'manage-secretariat', 'manage-import', 'view-reports',
        'manage-pengajian', 'view-activity-log', 'manage-users',
    ];

    foreach ($privilegedAbilities as $ability) {
        expect(Gate::forUser($user)->denies($ability))->toBeTrue("Null role should NOT be able to {$ability}");
    }
});

test('null role user can still authenticate and access allowed routes', function () {
    $user = rbac_user(['role' => null, 'email' => 'null@test.com']);

    $this->actingAs($user);
    $this->get('/regu')->assertOk();
    $this->get('/events')->assertForbidden();
});

// ---------------------------------------------------------------------------
// Gate — Unauthenticated user
// ---------------------------------------------------------------------------

test('guest cannot access any privileged gates', function () {
    $privilegedAbilities = [
        'view-dashboard', 'view-master-data', 'manage-master-data',
        'manage-events', 'manage-registration', 'manage-participants',
        'manage-attendance', 'manage-sessions', 'manage-qr-labels',
        'manage-secretariat', 'manage-import', 'view-reports',
        'manage-pengajian', 'view-activity-log', 'manage-users',
    ];

    foreach ($privilegedAbilities as $ability) {
        expect(Gate::denies($ability))->toBeTrue("Guest should NOT be able to {$ability}");
    }
});

// ---------------------------------------------------------------------------
// Gate — Ketua Event
// ---------------------------------------------------------------------------

test('Ketua Event has event-scoped permissions', function () {
    $event = Event::create(['name' => 'Ketua Event Test', 'slug' => 'ketua-event-'.str()->random(6), 'status' => 'active']);
    $person = Person::create(['nama' => 'Ketua Person']);
    $user = rbac_user(['role' => 'ketua_event', 'person_id' => $person->id]);

    $role = app(EventCommitteeService::class)->createRole([
        'event_id' => $event->id,
        'name' => 'Ketua Panitia',
        'code' => 'ketua_event',
        'scope' => 'event',
    ]);

    app(EventCommitteeService::class)->assign([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);

    app(ActiveEventContext::class)->set($event);

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-events'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-participants'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-secretariat'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view-reports'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-import'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Gate — PJ Divisi
// ---------------------------------------------------------------------------

test('PJ Divisi has monitoring permissions', function () {
    $event = Event::create(['name' => 'PJ Divisi Event', 'slug' => 'pj-divisi-event-'.str()->random(6), 'status' => 'active']);
    $user = rbac_user(['role' => 'pj_divisi']);
    grantEventRoleToUser($user, $event, 'pj_divisi');

    expect(Gate::forUser($user)->allows('view-dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage-attendance'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-master-data'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-registration'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-sessions'))->toBeTrue();
    expect(Gate::forUser($user)->denies('manage-import'))->toBeTrue();
    expect(Gate::forUser($user)->denies('view-reports'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Artisan command
// ---------------------------------------------------------------------------

test('user:set-role sets valid role successfully', function () {
    $user = rbac_user(['email' => 'test@example.com', 'role' => null]);

    $this->artisan('user:set-role', ['email' => 'test@example.com', 'role' => 'admin'])
        ->assertSuccessful();

    $user->refresh();
    expect($user->role)->toBe(Role::Admin);
});

test('user:set-role rejects invalid role', function () {
    rbac_user(['email' => 'test@example.com']);

    $this->artisan('user:set-role', ['email' => 'test@example.com', 'role' => 'super_god'])
        ->assertFailed();
});

test('user:set-role rejects unknown email', function () {
    $this->artisan('user:set-role', ['email' => 'nonexistent@example.com', 'role' => 'admin'])
        ->assertFailed();
});

test('user:set-role preserves existing user data', function () {
    $user = rbac_user([
        'email' => 'preserve@example.com',
        'name' => 'Original Name',
        'role' => null,
    ]);

    $this->artisan('user:set-role', ['email' => 'preserve@example.com', 'role' => 'sekretariat'])
        ->assertSuccessful();

    $user->refresh();
    expect($user->name)->toBe('Original Name');
    expect($user->email)->toBe('preserve@example.com');
    expect($user->role)->toBe(Role::Sekretariat);
});

// ---------------------------------------------------------------------------
// Regression — S2+S3 route protection
// ---------------------------------------------------------------------------

test('null role cannot access S3 operational routes', function () {
    $user = rbac_user(['role' => null]);
    $this->actingAs($user);

    $event = Event::create([
        'name' => 'Rbac Operational Event',
        'slug' => 'rbac-operational-'.str()->random(6),
        'status' => 'active',
    ]);

    $this->get('/dashboard')->assertOk('Null role should access platform dashboard');

    $protected = [
        route('sesi.absensi', ['event' => $event]),
        route('rekap.peserta', ['event' => $event]),
        route('rekap.absensi', ['event' => $event]),
        route('surat-izin', ['event' => $event]),
        route('activity-log.index', ['event' => $event]),
    ];

    foreach ($protected as $url) {
        $this->get($url)->assertForbidden("Null role should be denied {$url}");
    }
});

test('allowed routes remain accessible for null role after S3', function () {
    $user = rbac_user(['role' => null]);
    $this->actingAs($user);

    $this->get('/regu')->assertOk();
    $this->get('/events')->assertForbidden();
});

test('master data routes return 403 for null role after S2', function () {
    $user = rbac_user(['role' => null]);
    $this->actingAs($user);

    $this->get('/master-data')->assertForbidden();
    $this->get('/person')->assertForbidden();
    $this->get('/desa')->assertForbidden();
    $this->get('/kelompok')->assertForbidden();
});

test('guest behavior unchanged after S2', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/master-data')->assertRedirect('/login');
    $this->get('/person')->assertRedirect('/login');
});

test('Pengajian public flow unchanged in S1', function () {
    $event = Event::create([
        'name' => 'Rbac Pengajian Event',
        'slug' => 'rbac-pengajian-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'pengajian',
    ]);
    $this->get(route('pengajian.enter-token', ['event' => $event]))->assertOk();
});
