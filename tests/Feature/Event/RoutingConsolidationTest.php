<?php

use App\Enums\Role;
use App\Livewire\Dashboard\PlatformDashboard;
use App\Livewire\Event\EventSwitcher;
use App\Livewire\Event\Index as EventIndex;
use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function rc_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Routing Event',
        'slug' => 'rc-'.str()->random(8),
        'event_type' => 'cai',
        'status' => 'active',
    ], $overrides));
}

function rc_super_admin(): User
{
    return User::factory()->create(['role' => Role::SuperAdmin]);
}

// ---------------------------------------------------------------------------
// 1. resolve.active-event middleware
// ---------------------------------------------------------------------------

test('resolve.active-event middleware sets the active event from the URL', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    app(ActiveEventContext::class)->clear();

    $this->get(route('events.dashboard', $event))->assertOk();

    expect(app(ActiveEventContext::class)->id())->toBe($event->id);
});

test('resolve.active-event middleware overrides a stale session event before authorization', function () {
    $eventA = rc_event();
    $eventB = rc_event();
    $user = User::factory()->create(['role' => null]);
    grantEventRoleToUser($user, $eventB, 'viewer');

    app(ActiveEventContext::class)->set($eventA);

    $this->actingAs($user);
    $this->get(route('events.dashboard', $eventB))->assertOk();

    expect(app(ActiveEventContext::class)->id())->toBe($eventB->id);
});

test('a stale session event does not leak access to another event', function () {
    $eventA = rc_event();
    $eventB = rc_event();
    $user = User::factory()->create(['role' => null]);
    grantEventRoleToUser($user, $eventA, 'viewer');

    $this->actingAs($user);
    $this->get(route('events.dashboard', $eventB))->assertForbidden();

    expect(app(ActiveEventContext::class)->id())->toBe($eventB->id);
});

test('gate view-dashboard evaluates the URL event instead of a stale session', function () {
    $eventA = rc_event();
    $eventB = rc_event();
    $user = User::factory()->create(['role' => null]);
    grantEventRoleToUser($user, $eventA, 'viewer');

    $this->actingAs($user);
    $this->get(route('events.dashboard', $eventB))->assertForbidden();
});

test('deep link to /events/{event}/dashboard works without a prior session', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    expect(app(ActiveEventContext::class)->id())->toBeNull();

    $this->get(route('events.dashboard', $event))->assertOk();

    expect(app(ActiveEventContext::class)->id())->toBe($event->id);
});

test('resolve.active-event middleware works for competition dashboard deep link', function () {
    $event = rc_event(['event_type' => 'competition']);
    $this->actingAs(rc_super_admin());

    $this->get(route('competition.dashboard', $event))->assertOk();

    expect(app(ActiveEventContext::class)->id())->toBe($event->id);
});

// ---------------------------------------------------------------------------
// 2. Dashboard route resolver
// ---------------------------------------------------------------------------

test('dashboardRoute returns events.dashboard for CAI events', function () {
    $event = rc_event(['event_type' => 'cai']);

    expect($event->dashboardRoute())->toBe(route('events.dashboard', $event, absolute: false));
});

test('dashboardRoute returns competition.dashboard for competition events', function () {
    $event = rc_event(['event_type' => 'competition']);

    expect($event->dashboardRoute())->toBe(route('competition.dashboard', $event, absolute: false));
});

test('dashboardRoute returns pengajian.report for pengajian events', function () {
    $event = rc_event(['event_type' => 'pengajian']);

    expect($event->dashboardRoute())->toBe(route('pengajian.report', ['event' => $event], absolute: false));
});

test('competition and pengajian routes are consolidated under events event prefix', function () {
    $content = file_get_contents(base_path('routes/web.php'));

    expect($content)->toContain("events/{event}/competition")
        ->and($content)->toContain("events/{event}/pengajian");
});

test('competition routes generate event-prefixed urls', function () {
    $event = rc_event(['event_type' => 'competition']);

    expect(route('competition.dashboard', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/competition')
        ->and(route('competition.registration', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/competition/registration')
        ->and(route('competition.participants', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/competition/participants')
        ->and(route('competition.schedule.index', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/competition/schedules')
        ->and(route('competition.report.summary', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/competition/reports/summary')
        ->and(route('competition.viewer', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/competition/viewer');
});

test('pengajian routes generate event-prefixed urls except public hadir', function () {
    $event = rc_event(['event_type' => 'pengajian']);

    expect(route('pengajian.report', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/pengajian/report')
        ->and(route('pengajian.admin.access', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/pengajian/admin/access')
        ->and(route('pengajian.enter-token', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/pengajian')
        ->and(route('pengajian.desa', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/pengajian/desa')
        ->and(route('pengajian.hadir', ['nonce' => 'abc-123'], absolute: false))
        ->toBe('/pengajian/hadir/abc-123');
});

test('cai legacy module routes generate event-prefixed urls', function () {
    $event = rc_event();

    expect(route('absensi', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/absensi')
        ->and(route('database', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/database')
        ->and(route('registrasi.peserta', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/registrasi')
        ->and(route('rekap.peserta', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/rekap-peserta')
        ->and(route('qr-label.index', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/qr-label')
        ->and(route('surat-izin', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/surat-izin')
        ->and(route('activity-log.index', ['event' => $event], absolute: false))
        ->toBe('/events/'.$event->id.'/activity-log');
});

test('legacy cai root paths redirect to event-scoped routes', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    app(ActiveEventContext::class)->set($event);

    $this->get('/absensi')->assertRedirect('/events/'.$event->id.'/absensi');
    $this->get('/sesi-absensi')->assertRedirect('/events/'.$event->id.'/sesi-absensi');
    $this->get('/database')->assertRedirect('/events/'.$event->id.'/database');
    $this->get('/registrasi')->assertRedirect('/events/'.$event->id.'/registrasi');
    $this->get('/rekap')->assertRedirect('/events/'.$event->id.'/rekap-peserta');
    $this->get('/rekap-peserta')->assertRedirect('/events/'.$event->id.'/rekap-peserta');
    $this->get('/qr-label')->assertRedirect('/events/'.$event->id.'/qr-label');
    $this->get('/surat-izin')->assertRedirect('/events/'.$event->id.'/surat-izin');
    $this->get('/activity-log')->assertRedirect('/events/'.$event->id.'/activity-log');
});

test('legacy cai root paths still require authentication', function () {
    $this->get('/absensi')->assertRedirect('/login');
    $this->get('/database')->assertRedirect('/login');
    $this->get('/registrasi')->assertRedirect('/login');
    $this->get('/qr-label')->assertRedirect('/login');
    $this->get('/surat-izin')->assertRedirect('/login');
    $this->get('/activity-log')->assertRedirect('/login');
});

// ---------------------------------------------------------------------------
// 3. All redirects use the shared resolver
// ---------------------------------------------------------------------------

test('event switcher redirects via the shared dashboard route resolver', function () {
    $eventA = rc_event(['event_type' => 'cai']);
    $eventB = rc_event(['event_type' => 'competition']);
    $this->actingAs(rc_super_admin());

    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertRedirect($eventB->dashboardRoute());
});

test('event switcher redirects to pengajian.report via the shared resolver', function () {
    $eventA = rc_event(['event_type' => 'cai']);
    $eventB = rc_event(['event_type' => 'pengajian']);
    $this->actingAs(rc_super_admin());

    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(EventSwitcher::class)
        ->call('switchTo', $eventB->id)
        ->assertRedirect($eventB->dashboardRoute());
});

test('platform dashboard openEvent redirects via the shared resolver', function () {
    $event = rc_event(['event_type' => 'competition']);
    $this->actingAs(rc_super_admin());

    Livewire::test(PlatformDashboard::class)
        ->call('openEvent', $event->id)
        ->assertRedirect($event->dashboardRoute());
});

test('platform dashboard openQuickAccess scan redirects to absensi', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    Livewire::test(PlatformDashboard::class)
        ->call('openQuickAccess', 'scan', $event->id)
        ->assertRedirect(route('absensi', ['event' => $event], absolute: false));
});

test('platform dashboard openQuickAccess registrasi redirects to registrasi.peserta', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    Livewire::test(PlatformDashboard::class)
        ->call('openQuickAccess', 'registrasi', $event->id)
        ->assertRedirect(route('registrasi.peserta', ['event' => $event], absolute: false));
});

test('platform dashboard openQuickAccess cari redirects to database', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    Livewire::test(PlatformDashboard::class)
        ->call('openQuickAccess', 'cari', $event->id)
        ->assertRedirect(route('database', ['event' => $event], absolute: false));
});

test('platform dashboard openQuickAccess rejects unknown target', function () {
    $event = rc_event();
    $this->actingAs(rc_super_admin());

    Livewire::test(PlatformDashboard::class)
        ->call('openQuickAccess', 'unknown', $event->id)
        ->assertStatus(404);
});

test('event creation redirects via the shared resolver for competition events', function () {
    $user = rc_super_admin();
    $this->actingAs($user);

    $slug = 'comp-route-'.substr(md5(uniqid()), 0, 6);

    $test = Livewire::test(EventIndex::class)
        ->set('showCreateForm', true)
        ->set('newName', 'Competition Route Event')
        ->set('newSlug', $slug)
        ->set('newEventType', 'competition')
        ->call('create');

    $event = Event::where('slug', $slug)->firstOrFail();

    $test->assertRedirect($event->dashboardRoute());
});
