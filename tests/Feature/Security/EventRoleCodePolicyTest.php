<?php

use App\Exceptions\UnknownEventRoleCodeException;
use App\Models\Event;
use App\Models\EventRole;
use App\Support\EventRolePermissionDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function erp_event(): Event
{
    return Event::create([
        'name' => 'ERP Event '.str()->random(6),
        'slug' => 'erp-event-'.str()->random(6),
        'status' => 'active',
    ]);
}

// ---------------------------------------------------------------------------
// EventRolePermissionDefaults — code-only resolver
// ---------------------------------------------------------------------------

test('resolve returns permissions by code only', function () {
    expect(EventRolePermissionDefaults::resolve('ketua_event'))->toBe([
        'view-dashboard',
        'manage-registration',
        'manage-participants',
        'manage-attendance',
        'manage-sessions',
        'manage-secretariat',
        'view-reports',
    ]);

    expect(EventRolePermissionDefaults::resolve('operator_registrasi'))->toBe(['manage-registration']);
});

test('resolve throws for null code', function () {
    expect(fn () => EventRolePermissionDefaults::resolve(null))
        ->toThrow(UnknownEventRoleCodeException::class);
});

test('resolve throws for unknown code', function () {
    expect(fn () => EventRolePermissionDefaults::resolve('sie_acara'))
        ->toThrow(UnknownEventRoleCodeException::class);
});

test('forCode is non-throwing and returns empty for unknown', function () {
    expect(EventRolePermissionDefaults::forCode('sie_acara'))->toBe([]);
    expect(EventRolePermissionDefaults::forCode('viewer'))->toBe(['view-dashboard', 'view-reports']);
});

test('isKnownCode and knownCodes reflect the system matrix', function () {
    expect(EventRolePermissionDefaults::isKnownCode('ketua_event'))->toBeTrue();
    expect(EventRolePermissionDefaults::isKnownCode('sie_acara'))->toBeFalse();
    expect(EventRolePermissionDefaults::knownCodes())->toContain('ketua_event');
    expect(EventRolePermissionDefaults::knownCodes())->toContain('sekretariat');
});

// ---------------------------------------------------------------------------
// EventRole model — code wajib
// ---------------------------------------------------------------------------

test('EventRole creation requires a known code', function () {
    $event = erp_event();

    expect(fn () => EventRole::create([
        'event_id' => $event->id,
        'name' => 'Panitia',
        'code' => null,
    ]))->toThrow(UnknownEventRoleCodeException::class);

    expect(fn () => EventRole::create([
        'event_id' => $event->id,
        'name' => 'Panitia',
        'code' => 'sie_acara',
    ]))->toThrow(UnknownEventRoleCodeException::class);

    expect(EventRole::count())->toBe(0);
});

test('EventRole with known code auto-fills permissions', function () {
    $event = erp_event();

    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Ketua',
        'code' => 'ketua_event',
    ]);

    expect($role->permissions)->toContain('view-dashboard');
    expect($role->permissions)->toContain('manage-secretariat');
});

// ---------------------------------------------------------------------------
// Audit command
// ---------------------------------------------------------------------------

test('event-roles:audit reports missing and unknown codes and empty permissions', function () {
    $event = erp_event();

    EventRole::create(['event_id' => $event->id, 'name' => 'Ketua', 'code' => 'ketua_event']);

    // Role legacy tanpa code (bypass model agar tidak throw)
    \Illuminate\Support\Facades\DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Bendahara',
        'code' => null,
        'permissions' => json_encode(['view-reports']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Role dengan code tidak dikenal (bypass model agar tidak throw)
    \Illuminate\Support\Facades\DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Sie Konsumsi',
        'code' => 'sie_konsumsi',
        'permissions' => json_encode(['view-reports']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Role tanpa permissions (code dikenal tapi kosong)
    \Illuminate\Support\Facades\DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Koordinator',
        'code' => 'viewer',
        'permissions' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('event-roles:audit')
        ->assertExitCode(1)
        ->expectsOutputToContain('ROLE TANPA CODE')
        ->expectsOutputToContain('Bendahara')
        ->expectsOutputToContain('CODE TIDAK DIKENAL')
        ->expectsOutputToContain('sie_konsumsi')
        ->expectsOutputToContain('ROLE TANPA PERMISSION')
        ->expectsOutputToContain('Koordinator');
});

test('event-roles:audit succeeds when all roles are valid', function () {
    $event = erp_event();

    EventRole::create(['event_id' => $event->id, 'name' => 'Ketua', 'code' => 'ketua_event']);
    EventRole::create(['event_id' => $event->id, 'name' => 'Sekretaris', 'code' => 'sekretariat']);

    $this->artisan('event-roles:audit')
        ->assertSuccessful()
        ->expectsOutputToContain('Semua event role valid.');
});

// ---------------------------------------------------------------------------
// suggestCodeFromName — legacy mapping (only for backfill/audit)
// ---------------------------------------------------------------------------

test('suggestCodeFromName maps legacy role names to codes', function () {
    expect(EventRolePermissionDefaults::suggestCodeFromName('Ketua Panitia'))->toBe('ketua_event');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Sekretaris Acara'))->toBe('sekretariat');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Operator Registrasi'))->toBe('operator_registrasi');
    expect(EventRolePermissionDefaults::suggestCodeFromName('PJ Divisi A'))->toBe('pj_divisi');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Viewer'))->toBe('viewer');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Juri'))->toBe('juri');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Ketua Fosda'))->toBe('ketua_fosda');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Super Admin'))->toBe('super_admin');
    expect(EventRolePermissionDefaults::suggestCodeFromName('Sie Acara'))->toBeNull();
});
