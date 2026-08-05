<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use App\Services\Pengajian\DesaAccessService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function s4_user(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function s4_event(): Event
{
    return Event::create([
        'name' => 'S4 Test Event '.str()->random(6),
        'slug' => 's4-event-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'pengajian',
    ]);
}

function s4_desa(): desa
{
    return desa::create(['desa_asal' => 'S4 Desa '.str()->random(6)]);
}

// ---------------------------------------------------------------------------
// A. Pengajian Admin routes — Route access
// ---------------------------------------------------------------------------

test('guest cannot access pengajian admin routes', function () {
    $event = s4_event();
    $this->get(route('pengajian.admin.access', ['event' => $event]))->assertRedirect('/login');
    $this->get(route('pengajian.admin.manual-entry', ['event' => $event]))->assertRedirect('/login');
    $this->get(route('pengajian.import-massal', ['event' => $event]))->assertRedirect('/login');
    $this->get(route('koreksi.data'))->assertRedirect('/login');
});

test('admin can access pengajian admin routes', function () {
    $event = s4_event();
    app(ActiveEventContext::class)->set($event);
    $user = s4_user('admin');
    $this->actingAs($user);

    $this->get(route('pengajian.admin.access', ['event' => $event]))->assertOk();
    $this->get(route('pengajian.admin.manual-entry', ['event' => $event]))->assertOk();
    $this->get(route('pengajian.import-massal', ['event' => $event]))->assertOk();
    $this->get(route('koreksi.data'))->assertOk();
});

test('sekretariat can access pengajian admin routes', function () {
    $event = s4_event();
    $user = s4_user('sekretariat');
    grantEventRoleToUser($user, $event, 'sekretariat');
    $this->actingAs($user);

    $this->get(route('pengajian.admin.access', ['event' => $event]))->assertOk();
});

test('unauthorized roles cannot access pengajian admin routes', function () {
    $event = s4_event();
    app(ActiveEventContext::class)->set($event);
    foreach (['ketua_event', 'pj_divisi', 'operator_registrasi', 'operator_scan', 'juri', 'viewer'] as $role) {
        $user = s4_user($role);
        $this->actingAs($user);
        $this->get(route('pengajian.admin.access', ['event' => $event]))->assertForbidden("Role {$role} should be denied");
    }
});

test('null role cannot access pengajian admin routes', function () {
    $event = s4_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get(route('pengajian.admin.access', ['event' => $event]))->assertForbidden();
    $this->get(route('pengajian.admin.manual-entry', ['event' => $event]))->assertForbidden();
    $this->get(route('pengajian.import-massal', ['event' => $event]))->assertForbidden();
    $this->get(route('koreksi.data'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// B. Pengajian Report — Route access
// ---------------------------------------------------------------------------

test('viewer can access pengajian report', function () {
    $event = s4_event();
    $user = s4_user('viewer');
    grantEventRoleToUser($user, $event, 'viewer');
    $this->actingAs($user);

    $this->get(route('pengajian.report', ['event' => $event]))->assertOk();
});

test('null role cannot access pengajian report', function () {
    $event = s4_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get(route('pengajian.report', ['event' => $event]))->assertForbidden();
});

// ---------------------------------------------------------------------------
// C. Token Management — AccessIndex mutations
// ---------------------------------------------------------------------------

test('unauthorized role cannot create access grant', function () {
    $event = s4_event();
    $desa = s4_desa();
    app(ActiveEventContext::class)->set($event);
    $user = s4_user('operator_scan');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Pengajian\Admin\AccessIndex::class)
        ->set('showCreateForm', true)
        ->set('desaId', (string) $desa->id)
        ->set('validFrom', now()->format('Y-m-d\TH:i'))
        ->set('validUntil', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertForbidden();
});

test('admin can create access grant', function () {
    $event = s4_event();
    $desa = s4_desa();
    app(ActiveEventContext::class)->set($event);
    $user = s4_user('admin');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Pengajian\Admin\AccessIndex::class)
        ->set('showCreateForm', true)
        ->set('desaId', (string) $desa->id)
        ->set('validFrom', now()->format('Y-m-d\TH:i'))
        ->set('validUntil', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('create')
        ->assertDispatched('pengajian-raw-token-created');

    $this->assertDatabaseHas('desa_access_grants', [
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);
});

test('unauthorized role cannot revoke access grant', function () {
    $event = s4_event();
    $desa = s4_desa();
    app(ActiveEventContext::class)->set($event);
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa, now(), now()->addDay(),
    );
    $grant = $result['grant'];

    $user = s4_user('viewer');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Pengajian\Admin\AccessIndex::class)
        ->call('revoke', $grant->id)
        ->assertForbidden();

    expect($grant->fresh()->revoked_at)->toBeNull();
});

test('unauthorized role cannot delete access grant', function () {
    $event = s4_event();
    $desa = s4_desa();
    app(ActiveEventContext::class)->set($event);
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa, now(), now()->addDay(),
    );
    $grant = $result['grant'];
    app(DesaAccessService::class)->revokeGrant($grant);

    $user = s4_user('pj_divisi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Pengajian\Admin\AccessIndex::class)
        ->set('deleteGrantId', $grant->id)
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('desa_access_grants', ['id' => $grant->id]);
});

// ---------------------------------------------------------------------------
// D. Import — mutation protection
// ---------------------------------------------------------------------------

test('unauthorized role cannot execute import', function () {
    $event = s4_event();
    app(ActiveEventContext::class)->set($event);
    $user = s4_user('ketua_event');
    $this->actingAs($user);

    // preview is read-only, executeImport is the mutation
    Livewire::test(\App\Livewire\Pengajian\Admin\ImportMassal::class)
        ->call('executeImport')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// E. Admin Manual Entry — mutation protection
// ---------------------------------------------------------------------------

test('unauthorized role cannot submit admin manual entry', function () {
    $event = s4_event();
    $desa = s4_desa();
    $desa2 = s4_desa();
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'S4 Kel', 'desa_id' => $desa->id]);
    $user = s4_user('operator_scan');
    $this->actingAs($user);

    app(\App\Support\ActiveEventContext::class)->set($event);

    Livewire::test(\App\Livewire\Pengajian\Admin\ManualEntry::class)
        ->set('desaId', (string) $desa->id)
        ->set('nama', 'Hacker Person')
        ->set('jenisKelamin', 'L')
        ->set('tanggalLahir', '2000-01-01')
        ->set('kelompokId', (string) $kelompok->id)
        ->call('submit')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// F. Identity Correction — mutation protection
// ---------------------------------------------------------------------------

test('unauthorized role cannot approve identity correction', function () {
    $event = s4_event();
    $desa = s4_desa();
    $person = Person::create(['nama' => 'Correction Person', 'desa_id' => $desa->id, 'jenis_kelamin' => 'L']);
    $request = \App\Models\IdentityCorrectionRequest::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'requested_name' => 'Updated Name',
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $user = s4_user('viewer');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Pengajian\IdentityCorrectionReview::class)
        ->call('approve', $request->id)
        ->assertForbidden();

    expect($request->fresh()->status)->toBe('pending');
});

// ---------------------------------------------------------------------------
// G. Public Pengajian token flow — REGRESSION, must NOT be broken
// ---------------------------------------------------------------------------

test('pengajian enter token route is public', function () {
    $event = s4_event();
    $this->get(route('pengajian.enter-token', ['event' => $event]))->assertOk();
});

test('pengajian desa dashboard redirects without token session', function () {
    $event = s4_event();
    $this->get(route('pengajian.desa', ['event' => $event]))->assertRedirect(route('pengajian.enter-token', ['event' => $event], absolute: false));
});

test('valid token establishes pengajian session', function () {
    $event = s4_event();
    $desa = s4_desa();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa, now(), now()->addDay(),
    );

    Livewire::test(\App\Livewire\Pengajian\EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit')
        ->assertRedirect(route('pengajian.desa', ['event' => $event], absolute: false));

    expect(session('pengajian_access.event_id'))->toBe($event->id);
    expect(session('pengajian_access.desa_id'))->toBe($desa->id);
});

test('revoked token is rejected', function () {
    $event = s4_event();
    $desa = s4_desa();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa, now(), now()->addDay(),
    );
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::test(\App\Livewire\Pengajian\EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit')
        ->assertHasErrors('token');
});

// ---------------------------------------------------------------------------
// H. S1-S3 Regression — Master Data and Event/CAI protection intact
// ---------------------------------------------------------------------------

test('master data still protected after S4', function () {
    $this->actingAs(s4_user('operator_scan'));
    $this->get('/master-data')->assertForbidden();
});

test('event cai protection still enforced after S4', function () {
    $event = s4_event();
    $this->actingAs(s4_user('operator_scan'));
    $this->get(route('database', ['event' => $event]))->assertForbidden();
    // operator_scan can access /absensi (has manage-attendance)
    // but cannot access /sesi-absensi (needs manage-sessions)
    $this->get(route('sesi.absensi', ['event' => $event]))->assertForbidden();
});

// ---------------------------------------------------------------------------
// PGM.25J — Download Template Import Person
// ---------------------------------------------------------------------------

test('import template route requires auth', function () {
    $event = s4_event();
    $this->get(route('pengajian.import-massal.template', ['event' => $event], absolute: false))
        ->assertRedirect(route('login', absolute: false));
});

test('import template route requires manage-pengajian', function () {
    $event = s4_event();
    $this->actingAs(s4_user('operator_scan'));
    $this->get(route('pengajian.import-massal.template', ['event' => $event], absolute: false))
        ->assertForbidden();
});

test('import template download succeeds for authorized user', function () {
    $event = s4_event();
    $this->actingAs(s4_user('admin'));

    $response = $this->get(route('pengajian.import-massal.template', ['event' => $event], absolute: false));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->assertHeader('Content-Disposition', 'attachment; filename=template_import_person.xlsx');
});

test('import template returns BinaryFileResponse not rendered HTML', function () {
    $event = s4_event();
    $this->actingAs(s4_user('admin'));

    $response = $this->get(route('pengajian.import-massal.template', ['event' => $event], absolute: false));

    $contentType = $response->headers->get('Content-Type');
    expect($contentType)->toContain('vnd.openxmlformats-officedocument');
    expect($contentType)->not->toContain('text/html');
    expect($contentType)->not->toContain('application/xml');
});

test('import template file has correct filename', function () {
    $event = s4_event();
    $this->actingAs(s4_user('admin'));

    $response = $this->get(route('pengajian.import-massal.template', ['event' => $event], absolute: false));

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain('template_import_person.xlsx');
});
