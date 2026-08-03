<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function bf_event(): Event
{
    return Event::create([
        'name' => 'Backfill Event '.str()->random(6),
        'slug' => 'bf-event-'.str()->random(6),
        'status' => 'active',
    ]);
}

test('migration backfills code and permissions for legacy roles', function () {
    $event = bf_event();

    // Simulasikan data legacy: role lama tanpa code, permissions NULL,
    // dibuat langsung ke tabel (bypass model EventRole).
    DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Ketua Panitia',
        'code' => null,
        'scope' => 'event',
        'is_active' => 1,
        'permissions' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Operator Scan',
        'code' => 'operator_scan',
        'scope' => 'event',
        'is_active' => 1,
        'permissions' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Role yang TIDAK bisa dipetakan dari nama → code tetap null (di-flag audit)
    DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Sie Konsumsi',
        'code' => null,
        'scope' => 'event',
        'is_active' => 1,
        'permissions' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Jalankan backfill migration
    $migration = include database_path('migrations/2026_08_20_000001_backfill_event_role_codes_and_permissions.php');
    $migration->up();

    // Ketua Panitia → code ketua_event + permissions terisi
    $ketua = DB::table('event_roles')->where('name', 'Ketua Panitia')->first();
    expect($ketua->code)->toBe('ketua_event');
    expect(json_decode($ketua->permissions, true))->toContain('view-dashboard');

    // Operator Scan → code tetap operator_scan + permissions terisi
    $scan = DB::table('event_roles')->where('name', 'Operator Scan')->first();
    expect($scan->code)->toBe('operator_scan');
    expect(json_decode($scan->permissions, true))->toBe(['manage-attendance']);

    // Sie Konsumsi → tidak bisa dipetakan → code null, permissions null
    $siekonsumsi = DB::table('event_roles')->where('name', 'Sie Konsumsi')->first();
    expect($siekonsumsi->code)->toBeNull();
    expect($siekonsumsi->permissions)->toBeNull();
});

test('migration does not overwrite existing permissions', function () {
    $event = bf_event();

    DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Ketua Kustom',
        'code' => 'ketua_event',
        'scope' => 'event',
        'is_active' => 1,
        'permissions' => json_encode(['view-reports']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = include database_path('migrations/2026_08_20_000001_backfill_event_role_codes_and_permissions.php');
    $migration->up();

    $role = DB::table('event_roles')->where('name', 'Ketua Kustom')->first();
    expect(json_decode($role->permissions, true))->toBe(['view-reports']);
});

test('migration avoids unique code collision within same event', function () {
    $event = bf_event();

    DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Ketua Panitia',
        'code' => null,
        'scope' => 'event',
        'is_active' => 1,
        'permissions' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Role kedua dengan nama yang sama-sama memetakan ke ketua_event
    DB::table('event_roles')->insert([
        'event_id' => $event->id,
        'name' => 'Ketua Fosda',
        'code' => null,
        'scope' => 'event',
        'is_active' => 1,
        'permissions' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = include database_path('migrations/2026_08_20_000001_backfill_event_role_codes_and_permissions.php');
    $migration->up();

    $ketuaPanitia = DB::table('event_roles')->where('name', 'Ketua Panitia')->first();
    expect($ketuaPanitia->code)->toBe('ketua_event');

    // Ketua Fosda → juga ketua_fosda (bukan collision dengan ketua_event)
    $ketuaFosda = DB::table('event_roles')->where('name', 'Ketua Fosda')->first();
    expect($ketuaFosda->code)->toBe('ketua_fosda');
});
