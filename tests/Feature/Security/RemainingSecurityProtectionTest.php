<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SuratIzin;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function s5_user(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function s5_event(): Event
{
    return Event::create([
        'name' => 'S5 Test Event ' . str()->random(6),
        'slug' => 's5-event-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

// ---------------------------------------------------------------------------
// A. QR Print Direct Routes
// ---------------------------------------------------------------------------

test('guest cannot access qr print routes', function () {
    $this->get(route('qr-label.print.filtered'))->assertRedirect('/login');
    $this->get(route('qr-label.print.a4'))->assertRedirect('/login');
});

test('unauthorized role cannot access qr print routes', function () {
    $event = s5_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s5_user('operator_scan'));

    $this->get(route('qr-label.print.filtered'))->assertForbidden();
    $this->get(route('qr-label.print.a4'))->assertForbidden();
});

test('authorized role can access qr print filtered route', function () {
    $event = s5_event();
    app(ActiveEventContext::class)->set($event);
    $person = Person::create(['nama' => 'Qr Print Person', 'nip' => 6001, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id, 'event_id' => $event->id,
        'attendance_code' => 'KJA-QRPRINT', 'participant_number' => 'KL601', 'jenis_peserta' => 'Wajib',
    ]);
    $this->actingAs(s5_user('admin'));

    $this->get(route('qr-label.print.filtered'))->assertOk();
});

// ---------------------------------------------------------------------------
// B. Surat Izin Print Route
// ---------------------------------------------------------------------------

test('unauthorized role cannot access surat izin print route', function () {
    $event = s5_event();
    app(ActiveEventContext::class)->set($event);
    $peserta = peserta::create(['nama' => 'Print Test', 'nip' => 6002, 'jenis_kelamin' => 'Laki - Laki']);
    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id, 'alasan' => 'Test alasan panjang',
        'tanggal_mulai' => '2026-07-20', 'tanggal_selesai' => '2026-07-22',
        'status' => 'approved', 'created_by' => s5_user('admin')->id,
    ]);
    $this->actingAs(s5_user('operator_scan'));

    $this->get(route('surat-izin.print', $surat->id))->assertForbidden();
});

test('authorized role can access surat izin print for approved surat', function () {
    $event = s5_event();
    $user = s5_user('sekretariat');
    grantEventRoleToUser($user, $event, 'sekretariat');

    $peserta = peserta::create(['nama' => 'Print Test 2', 'nip' => 6003, 'jenis_kelamin' => 'Laki - Laki']);
    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id, 'alasan' => 'Test alasan panjang',
        'tanggal_mulai' => '2026-07-20', 'tanggal_selesai' => '2026-07-22',
        'status' => 'approved', 'created_by' => s5_user('admin')->id,
    ]);
    $this->actingAs($user);

    $this->get(route('surat-izin.print', $surat->id))->assertOk();
});

// ---------------------------------------------------------------------------
// C. Import Routes
// ---------------------------------------------------------------------------

test('unauthorized role cannot import peserta', function () {
    $this->actingAs(s5_user('operator_scan'));

    $this->post(route('import.peserta'))->assertForbidden();
});

test('unauthorized role cannot import regu', function () {
    $this->actingAs(s5_user('viewer'));

    $this->post(route('import.regu'))->assertForbidden();
});

test('authorized role can access import peserta route', function () {
    $this->actingAs(s5_user('admin'));

    $response = $this->post(route('import.peserta'), [
        'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('peserta.csv', "nama,jenis_kelamin,kelompok,desa\nTest,Laki - Laki,Kel A,Desa A\n"),
    ]);
    expect($response->status())->not->toBe(403);
});

// ---------------------------------------------------------------------------
// D. Registrasi Self — unauthorized mutation blocked
// ---------------------------------------------------------------------------

test('unauthorized role cannot use self register', function () {
    $this->actingAs(s5_user('viewer'));

    Livewire::test(\App\Livewire\Registrasi\SelfRegister::class)
        ->set('nama', 'Hacker Self')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('desa_id', '1')
        ->set('kelompok_id', '1')
        ->call('register')
        ->assertForbidden();
});

test('operator registrasi can use self register', function () {
    $event = s5_event();
    $user = s5_user('operator_registrasi');
    grantEventRoleToUser($user, $event, 'operator_registrasi');

    $desa = \App\Models\desa::create(['desa_asal' => 'S5 Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'S5 Kelompok', 'desa_id' => $desa->id]);
    $regu = \App\Models\regu::create(['regu' => 'S5 Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Registrasi\SelfRegister::class)
        ->set('nama', 'Self Register Test')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('desa_id', (string) $desa->id)
        ->set('kelompok_id', (string) $kelompok->id)
        ->call('register');

    $this->assertDatabaseHas('pesertas', ['nama' => 'Self Register Test']);
});

// ---------------------------------------------------------------------------
// E. Activity Log — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access activity log', function () {
    $this->actingAs(s5_user('operator_scan'));
    $this->get('/activity-log')->assertForbidden();
});

test('null role cannot access activity log', function () {
    $this->actingAs(User::factory()->create(['role' => null]));
    $this->get('/activity-log')->assertForbidden();
});

test('sekretariat can access activity log', function () {
    $event = s5_event();
    $user = s5_user('sekretariat');
    grantEventRoleToUser($user, $event, 'sekretariat');
    $this->actingAs($user);
    $this->get('/activity-log')->assertOk();
});

// ---------------------------------------------------------------------------
// F. Report routes — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access report routes', function () {
    $event = s5_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs(s5_user('operator_scan'));
    $this->get('/rekap-peserta')->assertForbidden();
    $this->get('/rekap-absensi')->assertForbidden();
});

test('viewer can access report routes', function () {
    $event = s5_event();
    $user = s5_user('viewer');
    grantEventRoleToUser($user, $event, 'viewer');
    $this->actingAs($user);
    $this->get('/rekap-peserta')->assertOk();
    $this->get('/rekap-absensi')->assertOk();
});

// ---------------------------------------------------------------------------
// G. Surat Izin route — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access surat izin page', function () {
    $this->actingAs(s5_user('operator_scan'));
    $this->get('/surat-izin')->assertForbidden();
});

test('unauthorized role cannot use surat izin mutations', function () {
    $peserta = peserta::create(['nama' => 'SI Test', 'nip' => 7001, 'jenis_kelamin' => 'Laki - Laki']);
    $this->actingAs(s5_user('viewer'));

    Livewire::test(\App\Livewire\SuratIzin\Create::class)
        ->set('selectedPesertaId', $peserta->id)
        ->set('alasan', 'Test alasan panjang')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-22')
        ->call('saveDraft')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// H. Registration routes — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access registration routes', function () {
    $this->actingAs(s5_user('viewer'));
    $this->get('/registrasi')->assertForbidden();
    $this->get('/registrasi/ulang')->assertForbidden();
});

test('operator registrasi can access registration routes', function () {
    $event = s5_event();
    $user = s5_user('operator_registrasi');
    grantEventRoleToUser($user, $event, 'operator_registrasi');
    $this->actingAs($user);
    $this->get('/registrasi')->assertOk();
    $this->get('/registrasi/ulang')->assertOk();
});

// ---------------------------------------------------------------------------
// I. Database route — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access database route', function () {
    $this->actingAs(s5_user('operator_scan'));
    $this->get('/database')->assertForbidden();
});

test('authorized role can access database route', function () {
    $this->actingAs(s5_user('admin'));
    $this->get('/database')->assertOk();
});

// ---------------------------------------------------------------------------
// J. Dashboard route — unauthorized access
// ---------------------------------------------------------------------------

test('operator registrasi can access platform dashboard', function () {
    $this->actingAs(s5_user('operator_registrasi'));
    $this->get('/dashboard')->assertOk();
});

test('pj divisi can access dashboard', function () {
    $this->actingAs(s5_user('pj_divisi'));
    $this->get('/dashboard')->assertOk();
});

// ---------------------------------------------------------------------------
// K. Attendance routes — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access attendance route', function () {
    $this->actingAs(s5_user('viewer'));
    $this->get('/absensi')->assertForbidden();
});

test('operator scan can access attendance route', function () {
    $event = s5_event();
    $user = s5_user('operator_scan');
    grantEventRoleToUser($user, $event, 'operator_scan');
    $this->actingAs($user);
    $this->get('/absensi')->assertOk();
});

// ---------------------------------------------------------------------------
// L. Sessions route — unauthorized access
// ---------------------------------------------------------------------------

test('unauthorized role cannot access sessions route', function () {
    $this->actingAs(s5_user('operator_scan'));
    $this->get('/sesi-absensi')->assertForbidden();
});

// ---------------------------------------------------------------------------
// M. Public Pengajian regression
// ---------------------------------------------------------------------------

test('pengajian enter token remains public', function () {
    $this->get(route('pengajian.enter-token'))->assertOk();
});

test('pengajian desa redirects without session', function () {
    $this->get(route('pengajian.desa'))->assertRedirect(route('pengajian.enter-token', absolute: false));
});

test('pengajian self attendance is publicly accessible', function () {
    $response = $this->get(route('pengajian.hadir', ['nonce' => 'invalid-nonce']));
    expect($response->status())->not->toBe(403);
});

// ---------------------------------------------------------------------------
// N. S1-S4 Regression
// ---------------------------------------------------------------------------

test('master data still protected', function () {
    $this->actingAs(s5_user('operator_scan'));
    $this->get('/master-data')->assertForbidden();
});

test('event management mutations still protected', function () {
    $this->actingAs(s5_user('viewer'));

    Livewire::test(\App\Livewire\Event\Index::class)
        ->set('showCreateForm', true)
        ->set('newName', 'S5 Hack')
        ->set('newSlug', 's5-hack')
        ->call('create')
        ->assertForbidden();
});

test('user management still super admin only', function () {
    $this->actingAs(s5_user('admin'));
    $this->get('/users')->assertForbidden();
});

// ---------------------------------------------------------------------------
// O. Role workflow regression
// ---------------------------------------------------------------------------

test('operator registrasi workflow works', function () {
    $event = s5_event();
    $user = s5_user('operator_registrasi');
    grantEventRoleToUser($user, $event, 'operator_registrasi');
    $this->actingAs($user);
    $this->get('/registrasi')->assertOk();
    $this->get('/registrasi/ulang')->assertOk();
});

test('operator scan workflow works', function () {
    $event = s5_event();
    $user = s5_user('operator_scan');
    grantEventRoleToUser($user, $event, 'operator_scan');
    $this->actingAs($user);
    $this->get('/absensi')->assertOk();
});

test('viewer workflow works', function () {
    $event = s5_event();
    $user = s5_user('viewer');
    grantEventRoleToUser($user, $event, 'viewer');
    $this->actingAs($user);
    $this->get('/rekap-peserta')->assertOk();
    $this->get('/rekap-absensi')->assertOk();
    $this->get(route('pengajian.report'))->assertOk();
});

test('super admin bypass works', function () {
    $this->actingAs(s5_user('super_admin'));
    $this->get('/master-data')->assertOk();
    $this->get('/users')->assertOk();
    $this->get('/dashboard')->assertOk();
});
