<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Person;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\Participation;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function s2_user(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function s2_person(): Person
{
    return Person::create([
        'nama' => 'S2 Test Person ' . str()->random(6),
        'jenis_kelamin' => 'L',
    ]);
}

function s2_event(): Event
{
    return Event::create([
        'name' => 'S2 Test Event ' . str()->random(6),
        'slug' => 's2-event-' . str()->random(6),
        'status' => 'active',
    ]);
}

// ---------------------------------------------------------------------------
// Data providers for route access tests
// ---------------------------------------------------------------------------

$masterDataRoutes = [
    '/master-data',
    '/person',
    '/desa',
    '/kelompok',
];

$authorizedRoles = ['super_admin'];
$unauthorizedRoles = [
    'ketua_event', 'pj_divisi', 'operator_registrasi',
    'operator_scan', 'juri', 'viewer',
];

// ---------------------------------------------------------------------------
// A. Route Access — Guest
// ---------------------------------------------------------------------------

test('guest is redirected to login for master data routes', function () {
    $this->get('/master-data')->assertRedirect('/login');
    $this->get('/person')->assertRedirect('/login');
    $this->get('/desa')->assertRedirect('/login');
    $this->get('/kelompok')->assertRedirect('/login');
});

// ---------------------------------------------------------------------------
// A. Route Access — Authorized roles (200)
// ---------------------------------------------------------------------------

test('super_admin can access master data routes', function () {
    $user = s2_user('super_admin');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertOk();
    }
});

test('admin cannot access master data routes', function () {
    $user = s2_user('admin');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('sekretariat cannot access master data routes', function () {
    $user = s2_user('sekretariat');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

// ---------------------------------------------------------------------------
// A. Route Access — Unauthorized roles (403)
// ---------------------------------------------------------------------------

test('ketua_event cannot access master data routes', function () {
    $user = s2_user('ketua_event');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('pj_divisi cannot access master data routes', function () {
    $user = s2_user('pj_divisi');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('operator_registrasi cannot access master data routes', function () {
    $user = s2_user('operator_registrasi');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('operator_scan cannot access master data routes', function () {
    $user = s2_user('operator_scan');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('juri cannot access master data routes', function () {
    $user = s2_user('juri');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('viewer cannot access master data routes', function () {
    $user = s2_user('viewer');
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

test('null role cannot access master data routes', function () {
    $user = User::factory()->create(['role' => null]);
    $this->actingAs($user);
    foreach (['/master-data', '/person', '/desa', '/kelompok'] as $route) {
        $this->get($route)->assertForbidden();
    }
});

// ---------------------------------------------------------------------------
// B. Sidebar visibility
// ---------------------------------------------------------------------------

test('super admin sees Master Data in sidebar', function () {
    $user = s2_user('super_admin');
    $this->actingAs($user);
    $response = $this->get('/dashboard');
    $response->assertSee('Master Data');
});

test('admin does not see Master Data in sidebar', function () {
    $user = s2_user('admin');
    $this->actingAs($user);
    $response = $this->get('/dashboard');
    $response->assertDontSee('Master Data');
});

test('sekretariat does not see Master Data in sidebar', function () {
    $user = s2_user('sekretariat');
    $this->actingAs($user);
    $response = $this->get('/dashboard');
    $response->assertDontSee('Master Data');
});

test('unauthorized roles do not see Master Data in sidebar', function () {
    foreach (['ketua_event', 'pj_divisi', 'operator_registrasi', 'operator_scan', 'juri', 'viewer'] as $role) {
        $user = s2_user($role);
        $this->actingAs($user);
        $response = $this->get('/dashboard');
        $response->assertDontSee('Master Data');
    }
});

test('null role does not see CAI operational menus in sidebar', function () {
    $user = User::factory()->create(['role' => null]);
    $this->actingAs($user);
    $response = $this->get('/dashboard');
    $response->assertDontSee('Kelola Event');
    $response->assertDontSee('Registrasi Ulang');
});

// ---------------------------------------------------------------------------
// C. Mutation protection — Person
// ---------------------------------------------------------------------------

test('unauthorized user cannot create person via Livewire', function () {
    $user = s2_user('operator_scan');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', 'Hacker Person')
        ->set('jenis_kelamin', 'L')
        ->call('simpan')
        ->assertForbidden();

    $this->assertDatabaseMissing('people', ['nama' => 'Hacker Person']);
});

test('unauthorized user cannot edit person via Livewire', function () {
    $person = s2_person();
    $user = s2_user('viewer');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Hacked Name')
        ->call('update')
        ->assertForbidden();

    $this->assertDatabaseMissing('people', ['id' => $person->id, 'nama' => 'Hacked Name']);
});

test('unauthorized user cannot delete person via Livewire', function () {
    $person = s2_person();
    $user = s2_user('pj_divisi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\DeletePerson::class)
        ->dispatch('deletePerson', id: $person->id)
        ->call('destroy')
        ->assertForbidden();

    $this->assertDatabaseHas('people', ['id' => $person->id]);
});

// ---------------------------------------------------------------------------
// C. Mutation protection — Desa
// ---------------------------------------------------------------------------

test('unauthorized user cannot create desa via Livewire', function () {
    $user = s2_user('operator_registrasi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Desa\TambahDesa::class)
        ->set('Desa', 'Hacker Desa')
        ->call('simpan')
        ->assertForbidden();

    $this->assertDatabaseMissing('desas', ['desa_asal' => 'Hacker Desa']);
});

test('unauthorized user cannot edit desa via Livewire', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Original Desa']);
    $user = s2_user('operator_scan');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Desa\EditDesa::class)
        ->dispatch('editDesa', id: $desa->id)
        ->set('desa', 'Hacked Desa')
        ->call('update')
        ->assertForbidden();

    $this->assertDatabaseHas('desas', ['id' => $desa->id, 'desa_asal' => 'Original Desa']);
});

test('unauthorized user cannot delete desa via Livewire', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Delete Desa']);
    $user = s2_user('juri');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Desa\HapusDesa::class)
        ->dispatch('HapusDesa', id: $desa->id)
        ->call('destroy')
        ->assertForbidden();

    $this->assertDatabaseHas('desas', ['id' => $desa->id]);
});

test('unauthorized user cannot import desa via Livewire', function () {
    $user = s2_user('ketua_event');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Desa\ImportDesa::class)
        ->call('import')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// C. Mutation protection — Kelompok
// ---------------------------------------------------------------------------

test('unauthorized user cannot create kelompok via Livewire', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Kelompok Test Desa']);
    $user = s2_user('viewer');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Kelompok\TambahKelompok::class)
        ->set('Kelompok', 'Hacker Kelompok')
        ->set('desa_id', $desa->id)
        ->call('simpan')
        ->assertForbidden();

    $this->assertDatabaseMissing('kelompoks', ['kelompok_asal' => 'Hacker Kelompok']);
});

test('unauthorized user cannot edit kelompok via Livewire', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Edit Kel Test Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Original Kel', 'desa_id' => $desa->id]);
    $user = s2_user('pj_divisi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Kelompok\EditKelompok::class)
        ->dispatch('editKelompok', id: $kelompok->id)
        ->set('kelompok', 'Hacked Kel')
        ->call('update')
        ->assertForbidden();

    $this->assertDatabaseHas('kelompoks', ['id' => $kelompok->id, 'kelompok_asal' => 'Original Kel']);
});

test('unauthorized user cannot delete kelompok via Livewire', function () {
    $desa = \App\Models\desa::create(['desa_asal' => 'Del Kel Test Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Del Kel', 'desa_id' => $desa->id]);
    $user = s2_user('operator_registrasi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Kelompok\HapusKelompok::class)
        ->dispatch('HapusKelompok', id: $kelompok->id)
        ->call('destroy')
        ->assertForbidden();

    $this->assertDatabaseHas('kelompoks', ['id' => $kelompok->id]);
});

test('unauthorized user cannot import kelompok via Livewire', function () {
    $user = s2_user('pj_divisi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Kelompok\ImportKelompok::class)
        ->call('import')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// D. Authorized mutations still work
// ---------------------------------------------------------------------------

test('admin cannot create person (master data super_admin only)', function () {
    $user = s2_user('admin');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', 'Admin Rejected Person')
        ->set('jenis_kelamin', 'L')
        ->call('simpan')
        ->assertForbidden();
});

test('sekretariat cannot edit desa (master data super_admin only)', function () {
    $user = s2_user('sekretariat');
    $desa = \App\Models\desa::create(['desa_asal' => 'Edit Me']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Desa\EditDesa::class)
        ->dispatch('editDesa', id: $desa->id)
        ->set('desa', 'Edited By Sekre')
        ->call('update')
        ->assertForbidden();
});

test('super_admin can delete kelompok', function () {
    $user = s2_user('super_admin');
    $desa = \App\Models\desa::create(['desa_asal' => 'Del Kel D']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'To Delete', 'desa_id' => $desa->id]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Kelompok\HapusKelompok::class)
        ->dispatch('HapusKelompok', id: $kelompok->id)
        ->call('destroy');

    $this->assertDatabaseMissing('kelompoks', ['id' => $kelompok->id]);
});

// ---------------------------------------------------------------------------
// E. Regression — Regu not affected
// ---------------------------------------------------------------------------

test('regu is not protected by master data gate', function () {
    $user = s2_user('ketua_event');
    $this->actingAs($user);

    $this->get('/regu')->assertOk();
});

test('regu is accessible by unauthorized master data roles', function () {
    foreach (['ketua_event', 'pj_divisi', 'operator_registrasi', 'operator_scan', 'juri', 'viewer'] as $role) {
        $user = s2_user($role);
        $this->actingAs($user);
        $this->get('/regu')->assertOk();
    }
});

test('null role can access regu', function () {
    $user = User::factory()->create(['role' => null]);
    $this->actingAs($user);

    $this->get('/regu')->assertOk();
});

// ---------------------------------------------------------------------------
// E. Regression — Person-Legacy sync
// ---------------------------------------------------------------------------

test('person legacy sync still works for super admin', function () {
    $user = s2_user('super_admin');
    $event = s2_event();
    $regu = \App\Models\regu::create(['regu' => 'Sync Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $person = Person::create(['nama' => 'Sync Source', 'jenis_kelamin' => 'L']);
    $peserta = \App\Models\peserta::create([
        'nama' => 'Sync Source', 'nip' => 9911, 'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => 'Belum Registrasi',
    ]);
    $participation = Participation::create([
        'person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib',
    ]);
    \App\Models\LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id, 'migrated_at' => now(),
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
        'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now(),
    ]);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Synced Name')
        ->call('update');

    $peserta->refresh();
    expect($peserta->nama)->toBe('Synced Name');
});

// ---------------------------------------------------------------------------
// E. Regression — ActiveEventContext
// ---------------------------------------------------------------------------

test('master data still works without active event for super admin', function () {
    $user = s2_user('super_admin');
    $this->actingAs($user);

    expect(app(ActiveEventContext::class)->current())->toBeNull();
    $this->get('/master-data')->assertOk();
    $this->get('/person')->assertOk();
    $this->get('/desa')->assertOk();
    $this->get('/kelompok')->assertOk();
});

test('visiting master data does not change ActiveEventContext for super admin', function () {
    $user = s2_user('super_admin');
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $this->get('/master-data')->assertOk();

    expect(app(ActiveEventContext::class)->id())->toBe($event->id);
});

test('admin cannot access master data without active event', function () {
    $user = s2_user('admin');
    $this->actingAs($user);

    expect(app(ActiveEventContext::class)->current())->toBeNull();
    $this->get('/master-data')->assertForbidden();
    $this->get('/person')->assertForbidden();
    $this->get('/desa')->assertForbidden();
    $this->get('/kelompok')->assertForbidden();
});

// ---------------------------------------------------------------------------
// E. Regression — Pengajian public flow unchanged
// ---------------------------------------------------------------------------

test('pengajian public flow unchanged by S2', function () {
    $this->get(route('pengajian.enter-token'))->assertOk();
});

// ---------------------------------------------------------------------------
// E. Regression — S1 Gates still work
// ---------------------------------------------------------------------------

test('S1 gate definitions still correct for master data', function () {
    $admin = s2_user('admin');
    expect(\Illuminate\Support\Facades\Gate::forUser($admin)->denies('view-master-data'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Gate::forUser($admin)->denies('manage-master-data'))->toBeTrue();

    $scan = s2_user('operator_scan');
    expect(\Illuminate\Support\Facades\Gate::forUser($scan)->denies('view-master-data'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Gate::forUser($scan)->denies('manage-master-data'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// F. S6 Sidebar visibility — CAI context
// ---------------------------------------------------------------------------

test('super admin sees all CAI sidebar menus', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('super_admin'));

    $response = $this->get('/dashboard');
    $response->assertSee('Dashboard');
    $response->assertSee('Scan Absensi');
    $response->assertSee('Sesi Absensi');
    $response->assertSee('Registrasi');
    $response->assertSee('Daftar Peserta');
    $response->assertSee('Laporan');
    $response->assertSee('QR & Label');
    $response->assertSee('Event');
    $response->assertSee('Surat Izin');
    $response->assertSee('Activity Log');
    $response->assertSee('Master Data');
});

test('admin sees all CAI sidebar menus without Master Data', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('admin'));

    $response = $this->get('/dashboard');
    $response->assertSee('Dashboard');
    $response->assertSee('Scan Absensi');
    $response->assertSee('Sesi Absensi');
    $response->assertSee('Registrasi');
    $response->assertSee('Daftar Peserta');
    $response->assertSee('Laporan');
    $response->assertSee('QR & Label');
    $response->assertSee('Event');
    $response->assertSee('Surat Izin');
    $response->assertSee('Activity Log');
    $response->assertDontSee('Master Data');
});

test('sekretariat sees all CAI sidebar menus without Master Data', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('sekretariat'));

    $response = $this->get('/dashboard');
    $response->assertSee('Dashboard');
    $response->assertSee('Daftar Peserta');
    $response->assertSee('Laporan');
    $response->assertSee('Surat Izin');
    $response->assertSee('Activity Log');
    $response->assertDontSee('Master Data');
});

test('operator registrasi only sees registration menus', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('operator_registrasi'));

    $response = $this->get('/registrasi');
    $response->assertSee('Registrasi');
    $response->assertDontSee('Scan Absensi');
    $response->assertDontSee('Daftar Peserta');
    $response->assertDontSee('Laporan');
    $response->assertDontSee('QR & Label');
    $response->assertDontSee('Master Data');
});

test('operator scan only sees attendance menus', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('operator_scan'));

    $response = $this->get('/absensi');
    $response->assertSee('Scan Absensi');
    $response->assertDontSee('Registrasi');
    $response->assertDontSee('Daftar Peserta');
    $response->assertDontSee('Laporan');
    $response->assertDontSee('QR & Label');
    $response->assertDontSee('Surat Izin');
    $response->assertDontSee('Master Data');
});

test('viewer only sees dashboard and reports', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('viewer'));

    $response = $this->get('/dashboard');
    $response->assertSee('Dashboard');
    $response->assertSee('Laporan');
    $response->assertDontSee('Registrasi');
    $response->assertDontSee('Scan Absensi');
    $response->assertDontSee('Sesi Absensi');
    $response->assertDontSee('Master Data');
});

test('null role does not see any CAI operational menus', function () {
    $event = s2_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(User::factory()->create(['role' => null]));

    $this->get('/events')->assertForbidden();
    $response = $this->get('/settings/profile');
    $response->assertDontSee('Dashboard');
    $response->assertDontSee('Master Data');
});

// ---------------------------------------------------------------------------
// G. S6 Sidebar visibility — Pengajian context
// ---------------------------------------------------------------------------

test('operator scan cannot access pengajian report route', function () {
    $event = s2_event(['event_type' => 'pengajian']);
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s2_user('operator_scan'));

    $this->get(route('pengajian.report'))->assertForbidden();
});
