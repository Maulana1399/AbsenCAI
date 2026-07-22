<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function s3_user(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function s3_event(): Event
{
    return Event::create([
        'name' => 'S3 Test Event ' . str()->random(6),
        'slug' => 's3-event-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function s3_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'S3 Test Session',
        'tanggal' => '2026-07-21',
        'aktif' => true,
    ]);
}

// ---------------------------------------------------------------------------
// A. Event Management — Route access
// ---------------------------------------------------------------------------

test('guest cannot access events page', function () {
    $this->get('/events')->assertRedirect('/login');
});

test('super admin can access events page', function () {
    $this->actingAs(s3_user('super_admin'));
    $this->get('/events')->assertOk();
});

test('admin can access events page', function () {
    $this->actingAs(s3_user('admin'));
    $this->get('/events')->assertOk();
});

test('unauthorized role cannot access events page', function () {
    $this->actingAs(s3_user('operator_scan'));

    $this->get(route('events.index'))->assertForbidden();
});

test('unauthorized role cannot access events page mutation', function () {
    $event = s3_event();
    $user = s3_user('operator_scan');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'Hacked Event')
        ->set('newSlug', 'hacked-event')
        ->call('create')
        ->assertForbidden();
});

test('unauthorized role cannot archive event', function () {
    $event = s3_event();
    $user = s3_user('operator_registrasi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->call('archive', $event->id)
        ->assertForbidden();
});

test('unauthorized role cannot update event', function () {
    $event = s3_event();
    $user = s3_user('viewer');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\EditStatus::class)
        ->dispatch('editEvent', id: $event->id)
        ->set('editName', 'Hacked Name')
        ->call('update')
        ->assertForbidden();
});

test('admin can create event', function () {
    $user = s3_user('admin');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'Admin Event')
        ->set('newSlug', 'admin-event')
        ->call('create');

    $this->assertDatabaseHas('events', ['name' => 'Admin Event', 'slug' => 'admin-event']);
});

test('admin can archive event', function () {
    $event = s3_event();
    $user = s3_user('admin');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\Index::class)
        ->call('archive', $event->id);

    expect($event->fresh()->status)->toBe('archived');
});

test('admin can update event', function () {
    $event = s3_event();
    $user = s3_user('admin');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Event\EditStatus::class)
        ->dispatch('editEvent', id: $event->id)
        ->set('editName', 'Updated Name')
        ->call('update');

    expect($event->fresh()->name)->toBe('Updated Name');
});

// ---------------------------------------------------------------------------
// B. Registration — Route access
// ---------------------------------------------------------------------------

test('guest cannot access registration page', function () {
    $this->get('/registrasi')->assertRedirect('/login');
});

test('operator registrasi can access registration page', function () {
    $this->actingAs(s3_user('operator_registrasi'));
    $this->get('/registrasi')->assertOk();
});

test('unauthorized role cannot access registration page', function () {
    $this->actingAs(s3_user('operator_scan'));
    $this->get('/registrasi')->assertForbidden();
});

test('null role cannot access registration page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/registrasi')->assertForbidden();
});

test('operator registrasi can access re-registration page', function () {
    $this->actingAs(s3_user('operator_registrasi'));
    $this->get('/registrasi/ulang')->assertOk();
});

// ---------------------------------------------------------------------------
// C. Participant management — Route access
// ---------------------------------------------------------------------------

test('guest cannot access database page', function () {
    $this->get('/database')->assertRedirect('/login');
});

test('admin can access database page', function () {
    $this->actingAs(s3_user('admin'));
    $this->get('/database')->assertOk();
});

test('operator registrasi cannot access database page', function () {
    $this->actingAs(s3_user('operator_registrasi'));
    $this->get('/database')->assertForbidden();
});

test('null role cannot access database page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/database')->assertForbidden();
});

test('unauthorized role cannot create participant via Livewire', function () {
    $event = s3_event();
    app(ActiveEventContext::class)->set($event);
    $desa = \App\Models\desa::create(['desa_asal' => 'S3 Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'S3 Kelompok', 'desa_id' => $desa->id]);
    $regu = \App\Models\regu::create(['regu' => 'S3 Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $user = s3_user('operator_registrasi');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Peserta\TambahPeserta::class)
        ->set('nama', 'Hacker Participant')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('desa_id', $desa->id)
        ->set('kelompok_id', $kelompok->id)
        ->set('regu_id', $regu->id)
        ->call('simpan')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// D. Attendance — Route access
// ---------------------------------------------------------------------------

test('guest cannot access attendance page', function () {
    $this->get('/absensi')->assertRedirect('/login');
});

test('operator scan can access attendance page', function () {
    $this->actingAs(s3_user('operator_scan'));
    $this->get('/absensi')->assertOk();
});

test('pj divisi can access attendance page', function () {
    $this->actingAs(s3_user('pj_divisi'));
    $this->get('/absensi')->assertOk();
});

test('null role cannot access attendance page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/absensi')->assertForbidden();
});

test('unauthorized role cannot access attendance mutation', function () {
    $this->actingAs(s3_user('viewer'));
    $this->get('/absensi')->assertForbidden();
});

// ---------------------------------------------------------------------------
// E. Sessions — Route access
// ---------------------------------------------------------------------------

test('guest cannot access sessions page', function () {
    $this->get('/sesi-absensi')->assertRedirect('/login');
});

test('admin can access sessions page', function () {
    $this->actingAs(s3_user('admin'));
    $this->get('/sesi-absensi')->assertOk();
});

test('operator scan cannot access sessions page', function () {
    $this->actingAs(s3_user('operator_scan'));
    $this->get('/sesi-absensi')->assertForbidden();
});

test('null role cannot access sessions page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/sesi-absensi')->assertForbidden();
});

test('unauthorized role cannot create session via Livewire', function () {
    $event = s3_event();
    app(ActiveEventContext::class)->set($event);
    $user = s3_user('viewer');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Sesi\TambahSesi::class)
        ->set('nama_sesi', 'Hacked Session')
        ->set('tanggal', '2026-07-22')
        ->call('simpan')
        ->assertForbidden();
});

test('admin can create session', function () {
    $event = s3_event();
    app(ActiveEventContext::class)->set($event);
    $user = s3_user('admin');
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Sesi\TambahSesi::class)
        ->set('nama_sesi', 'Admin Session')
        ->set('tanggal', '2026-07-22')
        ->call('simpan');

    $this->assertDatabaseHas('sesi_absensis', ['nama_sesi' => 'Admin Session']);
});

// ---------------------------------------------------------------------------
// F. Reports — Route access
// ---------------------------------------------------------------------------

test('guest cannot access reports', function () {
    $this->get('/rekap-peserta')->assertRedirect('/login');
    $this->get('/rekap-absensi')->assertRedirect('/login');
});

test('viewer can access reports', function () {
    $this->actingAs(s3_user('viewer'));
    $this->get('/rekap-peserta')->assertOk();
    $this->get('/rekap-absensi')->assertOk();
});

test('null role cannot access reports', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/rekap-peserta')->assertForbidden();
    $this->get('/rekap-absensi')->assertForbidden();
});

// ---------------------------------------------------------------------------
// G. Surat Izin — Route access
// ---------------------------------------------------------------------------

test('guest cannot access surat izin page', function () {
    $this->get('/surat-izin')->assertRedirect('/login');
});

test('sekretariat can access surat izin page', function () {
    $this->actingAs(s3_user('sekretariat'));
    $this->get('/surat-izin')->assertOk();
});

test('null role cannot access surat izin page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/surat-izin')->assertForbidden();
});

// ---------------------------------------------------------------------------
// H. QR Label — Route access
// ---------------------------------------------------------------------------

test('guest cannot access qr label page', function () {
    $this->get('/qr-label')->assertRedirect('/login');
});

test('admin can access qr label page', function () {
    $this->actingAs(s3_user('admin'));
    $this->get('/qr-label')->assertOk();
});

test('null role cannot access qr label page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/qr-label')->assertForbidden();
});

// ---------------------------------------------------------------------------
// I. Activity Log — Route access
// ---------------------------------------------------------------------------

test('guest cannot access activity log page', function () {
    $this->get('/activity-log')->assertRedirect('/login');
});

test('sekretariat can access activity log page', function () {
    $this->actingAs(s3_user('sekretariat'));
    $this->get('/activity-log')->assertOk();
});

test('null role cannot access activity log page', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/activity-log')->assertForbidden();
});

// ---------------------------------------------------------------------------
// J. Dashboard — Route access
// ---------------------------------------------------------------------------

test('guest cannot access dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('admin can access dashboard', function () {
    $this->actingAs(s3_user('admin'));
    $this->get('/dashboard')->assertOk();
});

test('pj divisi can access dashboard', function () {
    $this->actingAs(s3_user('pj_divisi'));
    $this->get('/dashboard')->assertOk();
});

test('viewer can access dashboard', function () {
    $this->actingAs(s3_user('viewer'));
    $this->get('/dashboard')->assertOk();
});

test('null role cannot access dashboard', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/dashboard')->assertForbidden();
});

// ---------------------------------------------------------------------------
// K. Regression — S2 Master Data still protected
// ---------------------------------------------------------------------------

test('master data routes still protected after S3', function () {
    $this->actingAs(s3_user('operator_scan'));
    $this->get('/master-data')->assertForbidden();
    $this->get('/person')->assertForbidden();
    $this->get('/desa')->assertForbidden();
    $this->get('/kelompok')->assertForbidden();
});

// ---------------------------------------------------------------------------
// L. Regression — public Pengajian flow unchanged
// ---------------------------------------------------------------------------

test('pengajian public flow unchanged by S3', function () {
    $this->get(route('pengajian.enter-token'))->assertOk();
});

// ---------------------------------------------------------------------------
// M. Regression — Event switching available to all authenticated users
// ---------------------------------------------------------------------------

test('event switching is not protected by manage-events', function () {
    $event = s3_event();
    $user = s3_user('operator_scan');
    $this->actingAs($user);
    app(ActiveEventContext::class)->set($event);

    Livewire::test(\App\Livewire\Event\EventSwitcher::class)
        ->assertSee($event->name);
});
