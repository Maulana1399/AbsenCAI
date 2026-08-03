<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function sm_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Sidebar Mode Event',
        'slug' => 'sm-'.substr(md5(uniqid()), 0, 8),
        'event_type' => 'cai',
        'status' => 'active',
    ], $overrides));
}

function sm_super_admin(): User
{
    return User::factory()->create(['role' => Role::SuperAdmin]);
}

// ---------------------------------------------------------------------------
// Platform Mode (no active event)
// ---------------------------------------------------------------------------

test('sidebar shows platform mode when no event exists', function () {
    $this->actingAs(sm_super_admin());

    $this->get(route('settings.profile'))
        ->assertOk()
        ->assertSee('Kelola Event')
        ->assertDontSee('Scan Absensi')
        ->assertDontSee('Sesi Absensi')
        ->assertDontSee('Self Register')
        ->assertDontSee('Registrasi Ulang')
        ->assertDontSee('QR & Label')
        ->assertDontSee('Surat Izin')
        ->assertDontSee('Activity Log')
        ->assertDontSee('Rekap Absensi');
});

test('sidebar stays in platform mode when events exist but none is selected', function () {
    $this->actingAs(sm_super_admin());

    sm_event(['name' => 'Existing Unselected CAI', 'event_type' => 'cai']);

    expect(app(ActiveEventContext::class)->id())->toBeNull();

    $this->get(route('settings.profile'))
        ->assertOk()
        ->assertSee('Kelola Event')
        ->assertDontSee('Scan Absensi')
        ->assertDontSee('Sesi Absensi')
        ->assertDontSee('QR & Label')
        ->assertDontSee('Surat Izin')
        ->assertDontSee('Activity Log')
        ->assertDontSee('/absensi')
        ->assertDontSee(route('qr-label.index'))
        ->assertDontSee(route('surat-izin'))
        ->assertDontSee(route('activity-log.index'));
});

test('platform mode dashboard item points to the platform dashboard', function () {
    $this->actingAs(sm_super_admin());

    $this->get(route('settings.profile'))
        ->assertOk()
        ->assertSee('href="'.route('dashboard').'"', false);
});

// ---------------------------------------------------------------------------
// Event Mode (active event)
// ---------------------------------------------------------------------------

test('sidebar shows cai event menus when a cai event is active', function () {
    $user = sm_super_admin();
    $this->actingAs($user);

    $event = sm_event(['name' => 'Active CAI Event', 'event_type' => 'cai']);
    app(ActiveEventContext::class)->set($event);

    $this->get(route('settings.profile'))
        ->assertOk()
        ->assertSee('Scan Absensi')
        ->assertSee('Sesi Absensi')
        ->assertSee('Registrasi Peserta')
        ->assertSee('Self Register')
        ->assertSee('Registrasi Ulang')
        ->assertSee('Daftar Peserta')
        ->assertSee('Regu')
        ->assertSee('Rekap Peserta')
        ->assertSee('Rekap Absensi')
        ->assertSee('QR & Label')
        ->assertSee('Surat Izin')
        ->assertSee('Activity Log')
        ->assertSee('Kelola Event');
});

test('sidebar shows pengajian menus when a pengajian event is active', function () {
    $this->actingAs(sm_super_admin());

    $event = sm_event(['name' => 'Active Pengajian Event', 'event_type' => 'pengajian']);
    app(ActiveEventContext::class)->set($event);

    $this->get(route('settings.profile'))
        ->assertOk()
        ->assertSee('Regional Report')
        ->assertSee('Daftar Peserta')
        ->assertSee('Import Massal')
        ->assertDontSee('Scan Absensi')
        ->assertDontSee('QR & Label');
});

// ---------------------------------------------------------------------------
// Event Switcher — no auto-fallback
// ---------------------------------------------------------------------------

test('event switcher does not auto-select an event when none is active', function () {
    sm_event(['name' => 'Unselected CAI', 'event_type' => 'cai']);

    Livewire::actingAs(sm_super_admin())
        ->test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSet('currentEventId', null)
        ->assertSet('currentEventName', null)
        ->assertSee('Pilih Event...');
});

test('event switcher lists existing events even without an active selection', function () {
    sm_event(['name' => 'Listed CAI Event', 'event_type' => 'cai']);

    Livewire::actingAs(sm_super_admin())
        ->test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSee('Listed CAI Event')
        ->assertSet('currentEventId', null);
});

// ---------------------------------------------------------------------------
// Event creation — auto-activate
// ---------------------------------------------------------------------------

test('creating the first event auto-selects it and redirects to its dashboard', function () {
    $user = sm_super_admin();
    $this->actingAs($user);

    $slug = 'auto-active-'.substr(md5(uniqid()), 0, 6);

    $test = Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'Auto Active Event')
        ->set('newSlug', $slug)
        ->set('newEventType', 'cai')
        ->call('create');

    $event = Event::where('slug', $slug)->firstOrFail();

    expect(app(ActiveEventContext::class)->id())->toBe($event->id);

    $test->assertRedirect(route('events.dashboard', $event));
});

test('creating an event does not override an already active event', function () {
    $user = sm_super_admin();
    $this->actingAs($user);

    $existing = sm_event(['name' => 'Existing Active Event']);
    app(ActiveEventContext::class)->set($existing);

    $slug = 'second-'.substr(md5(uniqid()), 0, 6);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'Second Event')
        ->set('newSlug', $slug)
        ->set('newEventType', 'cai')
        ->call('create');

    expect(app(ActiveEventContext::class)->id())->toBe($existing->id);
});
