<?php

use App\Models\User;
use App\Enums\Role;
use App\Models\Event;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function branding_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event ' . str()->random(6),
        'slug' => 'test-' . str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function branding_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test ' . str()->random(4),
    ], $overrides));
}

function branding_grant(Event $event, desa $desa, User $user): void
{
    app(DesaAccessService::class)->createGrant(
        event: $event,
        desa: $desa,
        validFrom: Carbon::now()->subHour(),
        validUntil: Carbon::now()->addHour(),
        createdBy: $user,
    );
}

// ---------------------------------------------------------------------------
// Welcome / Landing Page (Bug #1)
// ---------------------------------------------------------------------------

test('welcome page uses KJA Event Manager branding', function () {
    $response = $this->get('/');

    $response->assertStatus(200);

    $response->assertSee('KJA Event Manager');
    $response->assertSee('Platform Manajemen Event Multi-Event');
    $response->assertSee('Login Admin');
    $response->assertDontSee('CINTA ALAM');
    $response->assertDontSee('INDONESIA 2025');
    $response->assertDontSee('Platform Registrasi & Absensi Peserta');
});

test('welcome page title uses KJA Event Manager', function () {
    $response = $this->get('/');

    $response->assertSeeInOrder([
        'KJA Event Manager',
        config('kjam.mvp_name'),
    ]);
});

test('authenticated user sees platform dashboard instead of welcome page', function () {
    $user = User::factory()->create(['role' => Role::Admin]);
    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Selamat datang');
    $response->assertSee('Kelola Event');
    $response->assertSee('Event Saya');
});

// ---------------------------------------------------------------------------
// Login Page (Bug #2)
// ---------------------------------------------------------------------------

test('login page uses KJA Event Manager branding', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);

    $response->assertSee('KJA Event Manager');
    $response->assertSee('Administrator');
    $response->assertDontSee('Cinta Alam Indonesia');
    $response->assertDontSee('Administrator Login');
    $response->assertDontSee('Dashboard Registrasi & Absensi');
});

// ---------------------------------------------------------------------------
// Global Navigation — Logo links to home (Bug #6)
// ---------------------------------------------------------------------------

test('logo link in authenticated layout navigates to home', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
});

test('home route does not auto-select CAI event', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    Event::create([
        'name' => 'CAI Event',
        'slug' => 'cai-event',
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $this->actingAs($user);

    $context = app(ActiveEventContext::class);
    expect($context->current())->toBeNull();
});

// ---------------------------------------------------------------------------
// Event-Aware Sidebar (Bug #4)
// ---------------------------------------------------------------------------

test('Pengajian menu is NOT visible in CAI event context', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $event = branding_event(['event_type' => 'cai']);

    $context = app(ActiveEventContext::class);
    $context->set($event);

    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertDontSee('Pengajian');
    $response->assertDontSee('Akses Desa');
});

test('Pengajian menu is visible in Pengajian event context', function () {
    $user = User::factory()->create(['role' => Role::Admin]);

    $event = branding_event(['event_type' => 'pengajian']);
    $desa = branding_desa();

    branding_grant($event, $desa, $user);

    $context = app(ActiveEventContext::class);
    $context->set($event);

    $this->actingAs($user);

    $response = $this->get(route('pengajian.report'));
    $response->assertStatus(200);

    $response->assertSee('Pengajian');
    $response->assertSee('Regional Report');
});
