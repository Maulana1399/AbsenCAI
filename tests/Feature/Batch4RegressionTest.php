<?php

use App\Enums\Role;
use App\Livewire\Pengajian\EnterToken;
use App\Livewire\Pengajian\Admin\AccessIndex;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\User;
use App\Models\desa;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Bug #11 — /pengajian HTTP 500 Regression
// ---------------------------------------------------------------------------

test('GET /pengajian returns 200 not 500', function () {
    $event = Event::create([
        'name' => 'Regression Event',
        'slug' => 'regression-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'pengajian',
    ]);

    $response = $this->get(route('pengajian.enter-token', ['event' => $event]));

    $response->assertStatus(200);
});

test('EnterToken component renders without error', function () {
    Livewire::test(EnterToken::class)
        ->assertStatus(200);
});

test('EnterToken component has token input field', function () {
    Livewire::test(EnterToken::class)
        ->assertSee('Token Akses');
});

// ---------------------------------------------------------------------------
// Bug #10 — Dark Mode Runtime Verification
// ---------------------------------------------------------------------------

test('Access Desa page has proper dark mode classes', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertSee('Akses Desa')
        ->assertSeeHtml('dark:text-white');
});

test('Event management page has proper dark mode classes', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertStatus(200)
        ->assertSeeHtml('dark:text-white');
});

// ---------------------------------------------------------------------------
// Access Desa — Event Isolation
// ---------------------------------------------------------------------------

test('AccessIndex only shows grants for active event', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    // Create two events
    $eventA = Event::create([
        'name' => 'Event A',
        'slug' => 'event-a',
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $eventB = Event::create([
        'name' => 'Event B',
        'slug' => 'event-b',
        'status' => 'active',
        'event_type' => 'pengajian',
    ]);

    $desa = desa::create(['desa_asal' => 'Test Desa']);

    // Create grants for both events
    $grantA = DesaAccessGrant::create([
        'event_id' => $eventA->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash_a',
        'token_prefix' => 'prefix_a',
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'nonce' => 'nonce_a',
        'nonce_expires_at' => now()->addDay(),
        'created_by' => $user->id,
    ]);

    $grantB = DesaAccessGrant::create([
        'event_id' => $eventB->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash_b',
        'token_prefix' => 'prefix_b',
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'nonce' => 'nonce_b',
        'nonce_expires_at' => now()->addDay(),
        'created_by' => $user->id,
    ]);

    // Set active event to Event A
    app(ActiveEventContext::class)->set($eventA);

    // AccessIndex should only show grants for Event A
    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->assertSee($eventA->name)
        ->assertDontSee($eventB->name);
});

test('cross-event grant mutation is rejected', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    // Create two events
    $eventA = Event::create([
        'name' => 'Event A',
        'slug' => 'event-a',
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $eventB = Event::create([
        'name' => 'Event B',
        'slug' => 'event-b',
        'status' => 'active',
        'event_type' => 'pengajian',
    ]);

    $desa = desa::create(['desa_asal' => 'Test Desa']);

    // Create grant for Event B
    $grantB = DesaAccessGrant::create([
        'event_id' => $eventB->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash_b',
        'token_prefix' => 'prefix_b',
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'nonce' => 'nonce_b',
        'nonce_expires_at' => now()->addDay(),
        'created_by' => $user->id,
    ]);

    // Set active event to Event A
    app(ActiveEventContext::class)->set($eventA);

    // Try to revoke grant from Event B while in Event A context
    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('revoke', $grantB->id)
        ->assertSee('Grant tidak berada dalam event aktif.');

    // Grant should still be active
    expect(DesaAccessGrant::find($grantB->id)->isRevoked())->toBeFalse();
});

test('cross-event grant delete is rejected', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    // Create two events
    $eventA = Event::create([
        'name' => 'Event A',
        'slug' => 'event-a',
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $eventB = Event::create([
        'name' => 'Event B',
        'slug' => 'event-b',
        'status' => 'active',
        'event_type' => 'pengajian',
    ]);

    $desa = desa::create(['desa_asal' => 'Test Desa']);

    // Create revoked grant for Event B
    $grantB = DesaAccessGrant::create([
        'event_id' => $eventB->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash_b',
        'token_prefix' => 'prefix_b',
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
        'nonce' => 'nonce_b',
        'nonce_expires_at' => now()->addDay(),
        'created_by' => $user->id,
        'revoked_at' => now(),
    ]);

    // Set active event to Event A
    app(ActiveEventContext::class)->set($eventA);

    // Try to delete grant from Event B while in Event A context
    Livewire::actingAs($user)
        ->test(AccessIndex::class)
        ->call('confirmDelete', $grantB->id)
        ->call('delete')
        ->assertSee('Grant tidak berada dalam event aktif.');

    // Grant should still exist
    expect(DesaAccessGrant::find($grantB->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Auth Layout — Login Page Regression
// ---------------------------------------------------------------------------

test('login page renders without error after simple.blade.php changes', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('login page has proper dark mode classes', function () {
    $response = $this->get('/login');

    $response->assertStatus(200)
        ->assertSeeHtml('dark:text-white');
});
