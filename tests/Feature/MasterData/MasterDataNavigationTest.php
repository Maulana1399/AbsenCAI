<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Event;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function md_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Master Data Test Event ' . str()->random(6),
        'slug' => 'md-test-' . str()->random(6),
        'event_type' => 'cai',
        'status' => 'active',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Route existence
// ---------------------------------------------------------------------------

test('master-data route resolves', function () {
    expect(route('master-data.index', [], false))->toBe('/master-data');
});

test('person index route resolves', function () {
    expect(route('person.index', [], false))->toBe('/person');
});

test('desa route resolves', function () {
    expect(route('desa', [], false))->toBe('/desa');
});

test('kelompok route resolves', function () {
    expect(route('kelompok', [], false))->toBe('/kelompok');
});

test('regu route still resolves for backward compatibility', function () {
    expect(route('regu', [], false))->toBe('/regu');
});

// ---------------------------------------------------------------------------
// Guest access restriction
// ---------------------------------------------------------------------------

test('guest cannot access master-data page', function () {
    $this->get('/master-data')->assertRedirect('/login');
});

test('guest cannot access person page', function () {
    $this->get('/person')->assertRedirect('/login');
});

test('guest cannot access desa page', function () {
    $this->get('/desa')->assertRedirect('/login');
});

test('guest cannot access kelompok page', function () {
    $this->get('/kelompok')->assertRedirect('/login');
});

test('guest cannot access regu page', function () {
    $this->get('/regu')->assertRedirect('/login');
});

// ---------------------------------------------------------------------------
// Authenticated access to Master Data landing page
// ---------------------------------------------------------------------------

test('authenticated user can access master-data page', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $this->get('/master-data')->assertOk();
});

test('master-data page renders without active event', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    expect(app(ActiveEventContext::class)->current())->toBeNull();
    $this->get('/master-data')->assertOk();
});

test('visiting master-data does not change ActiveEventContext', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $event = md_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $this->get('/master-data')->assertOk();

    $response = $this->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Master Data');
});

// ---------------------------------------------------------------------------
// Master Data landing page card content
// ---------------------------------------------------------------------------

test('master-data landing shows heading', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/master-data');
    $response->assertOk();
    $response->assertSee('Master Data');
});

test('master-data landing shows Person card', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/master-data');
    $response->assertOk();
    $response->assertSee('Person');
    $response->assertSee(route('person.index', [], false));
});

test('master-data landing shows Desa card', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/master-data');
    $response->assertOk();
    $response->assertSee('Desa');
    $response->assertSee(route('desa', [], false));
});

test('master-data landing shows Kelompok card', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/master-data');
    $response->assertOk();
    $response->assertSee('Kelompok');
    $response->assertSee(route('kelompok', [], false));
});

test('master-data landing does not show Regu as navigation card', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/master-data');
    $response->assertOk();
    $response->assertDontSee('Regu');
});

// ---------------------------------------------------------------------------
// Sidebar Master Data link visibility
// ---------------------------------------------------------------------------

test('sidebar shows Master Data link', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Master Data');
});

test('sidebar Master Data link visible when no active event', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Master Data');
});

test('sidebar Master Data link visible in CAI event context', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $event = md_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Master Data');
});

test('sidebar Master Data link visible in Pengajian event context', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $event = md_event(['event_type' => 'pengajian']);
    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Master Data');
});

// ---------------------------------------------------------------------------
// Backward compatibility: Regu still works but is NOT in Master Data
// ---------------------------------------------------------------------------

test('regu page still accessible for authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/regu')->assertOk();
});

test('regu page renders without active event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(app(ActiveEventContext::class)->current())->toBeNull();
    $this->get('/regu')->assertOk();
});

// ---------------------------------------------------------------------------
// Other Master Data pages remain accessible
// ---------------------------------------------------------------------------

test('person page still accessible', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $this->get('/person')->assertOk();
});

test('desa page still accessible', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $this->get('/desa')->assertOk();
});

test('kelompok page still accessible', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($user);

    $this->get('/kelompok')->assertOk();
});
