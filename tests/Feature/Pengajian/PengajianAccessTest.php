<?php

use App\Livewire\Pengajian\DesaDashboard;
use App\Livewire\Pengajian\EnterToken;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use Carbon\Carbon;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm3_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Desa Juli 2026',
        'slug' => 'pengajian-desa-juli-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm3_makeDesa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test '.str()->random(4),
    ], $overrides));
}

function pgm3_makeUser(): User
{
    return User::factory()->create();
}

function pgm3_createValidGrant(Event $event, desa $desa, ?User $user = null): array
{
    $now = Carbon::now();
    return app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
        $user
    );
}

// ---------------------------------------------------------------------------
// Route accessibility
// ---------------------------------------------------------------------------

test('/pengajian dapat diakses tanpa Laravel auth', function () {
    $response = $this->get(route('pengajian.enter-token'));

    $response->assertStatus(200);
});

// ---------------------------------------------------------------------------
// Token entry - validation
// ---------------------------------------------------------------------------

test('valid token establishes Pengajian scoped session and redirects', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    Livewire::test(EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit')
        ->assertRedirect(route('pengajian.desa'));

    expect(session()->has('pengajian_access'))->toBeTrue();
    expect(session('pengajian_access.event_id'))->toBe($event->id);
    expect(session('pengajian_access.desa_id'))->toBe($desa->id);
});

test('wrong token ditolak', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    pgm3_createValidGrant($event, $desa);

    Livewire::test(EnterToken::class)
        ->set('token', 'kja-dgt-wrongtoken1234567890abcdefghijklmnop')
        ->call('submit')
        ->assertHasErrors('token');

    expect(session()->has('pengajian_access'))->toBeFalse();
});

test('expired token ditolak', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $now = Carbon::now();

    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subDays(2),
        $now->copy()->subDay(),
    );

    Livewire::test(EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit')
        ->assertHasErrors('token');

    expect(session()->has('pengajian_access'))->toBeFalse();
});

test('not yet valid token ditolak', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $now = Carbon::now();

    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->addDay(),
        $now->copy()->addDays(2),
    );

    Livewire::test(EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit')
        ->assertHasErrors('token');

    expect(session()->has('pengajian_access'))->toBeFalse();
});

test('revoked token ditolak', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);
    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::test(EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit')
        ->assertHasErrors('token');

    expect(session()->has('pengajian_access'))->toBeFalse();
});

test('raw token tidak tersimpan di session', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    Livewire::test(EnterToken::class)
        ->set('token', $result['raw_token'])
        ->call('submit');

    $sessionData = session('pengajian_access');
    expect($sessionData)->toHaveKeys(['grant_id', 'event_id', 'desa_id']);
    expect($sessionData)->not->toHaveKey('raw_token');
    expect($sessionData)->not->toHaveKey('token');
});

// ---------------------------------------------------------------------------
// Dashboard access validation
// ---------------------------------------------------------------------------

test('/pengajian/desa tanpa session redirects ke enter token', function () {
    $response = $this->get(route('pengajian.desa'));

    $response->assertRedirect(route('pengajian.enter-token'));
});

test('valid scoped session dapat membuka dashboard', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    // Simulate valid session
    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertSet('eventName', $event->name)
        ->assertSet('desaName', $desa->desa_asal);
});

test('dashboard menampilkan Event yang benar', function () {
    $eventA = pgm3_makeEvent(['name' => 'Pengajian A', 'slug' => 'pengajian-a']);
    $eventB = pgm3_makeEvent(['name' => 'Pengajian B', 'slug' => 'pengajian-b']);
    $desa = pgm3_makeDesa();

    $resultA = pgm3_createValidGrant($eventA, $desa);
    pgm3_createValidGrant($eventB, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $resultA['grant']->id,
        'event_id' => $eventA->id,
        'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertSet('eventName', 'Pengajian A')
        ->assertSet('eventName', $eventA->name)
        ->assertSet('eventName', fn ($val) => $val !== 'Pengajian B');
});

test('dashboard menampilkan Desa yang benar', function () {
    $event = pgm3_makeEvent();
    $desaA = pgm3_makeDesa(['desa_asal' => 'Desa Alpha']);
    $desaB = pgm3_makeDesa(['desa_asal' => 'Desa Beta']);

    $resultA = pgm3_createValidGrant($event, $desaA);
    pgm3_createValidGrant($event, $desaB);

    session()->put('pengajian_access', [
        'grant_id' => $resultA['grant']->id,
        'event_id' => $event->id,
        'desa_id' => $desaA->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertSet('desaName', 'Desa Alpha')
        ->assertSet('desaName', $desaA->desa_asal)
        ->assertSet('desaName', fn ($val) => $val !== 'Desa Beta');
});

// ---------------------------------------------------------------------------
// Grant revocation after session established
// ---------------------------------------------------------------------------

test('revoked grant setelah session dibuat membatalkan access', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);

    app(DesaAccessService::class)->revokeGrant($result['grant']);

    Livewire::test(DesaDashboard::class)
        ->assertRedirect(route('pengajian.enter-token'));

    expect(session()->has('pengajian_access'))->toBeFalse();
});

test('expired grant setelah session dibuat membatalkan access', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $now = Carbon::now();

    // Create grant in the past (already expired)
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subDays(2),
        $now->copy()->subDay(),
    );

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertRedirect(route('pengajian.enter-token'));

    expect(session()->has('pengajian_access'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Session manipulation protection
// ---------------------------------------------------------------------------

test('manipulated desa_id session ditolak', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    // Session says desa_id = 999 but grant has the real desa_id
    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => $event->id,
        'desa_id' => 999,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertRedirect(route('pengajian.enter-token'));
});

test('manipulated event_id session ditolak', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    // Session says event_id = 999 but grant has the real event_id
    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => 999,
        'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertRedirect(route('pengajian.enter-token'));
});

test('invalid grant_id in session ditolak', function () {
    session()->put('pengajian_access', [
        'grant_id' => 99999,
        'event_id' => 1,
        'desa_id' => 1,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertRedirect(route('pengajian.enter-token'));
});

// ---------------------------------------------------------------------------
// Logout behavior
// ---------------------------------------------------------------------------

test('Pengajian logout hanya membersihkan Pengajian session', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);

    // Simulate Laravel auth is active
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(DesaDashboard::class)
        ->call('logout')
        ->assertRedirect(route('pengajian.enter-token'));

    expect(session()->has('pengajian_access'))->toBeFalse();
    // Laravel auth should still be active
    expect(auth()->check())->toBeTrue();
});

test('Laravel authenticated User tidak ikut logout saat Pengajian logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);

    expect(auth()->check())->toBeTrue();

    Livewire::test(DesaDashboard::class)
        ->call('logout');

    expect(auth()->check())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Token minimum length validation (UI layer)
// ---------------------------------------------------------------------------

test('token dengan panjang kurang dari 16 karakter ditolak oleh validasi form', function () {
    Livewire::test(EnterToken::class)
        ->set('token', 'short-token')
        ->call('submit')
        ->assertHasErrors('token');
});

// ---------------------------------------------------------------------------
// findGrantByToken (service layer, backstop for PGM.1)
// ---------------------------------------------------------------------------

test('findGrantByToken returns grant for valid token without needing event_id', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();

    $result = pgm3_createValidGrant($event, $desa);

    $grant = app(DesaAccessService::class)->findGrantByToken($result['raw_token']);

    expect($grant)->not->toBeNull();
    expect($grant->id)->toBe($result['grant']->id);
    expect($grant->event_id)->toBe($event->id);
    expect($grant->desa_id)->toBe($desa->id);
});

test('findGrantByToken returns null for wrong token', function () {
    $grant = app(DesaAccessService::class)->findGrantByToken('kja-dgt-invalidtoken1234567890abcdefghij');

    expect($grant)->toBeNull();
});

// ---------------------------------------------------------------------------
// Daftar Kehadiran – Filter
// ---------------------------------------------------------------------------

test('switch tab ke list memuat attendanceList', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    Person::create(['nama' => 'Jono', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Joni', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->assertSet('activeTab', 'attendance')
        ->set('activeTab', 'list')
        ->assertSet('attendanceList', fn ($list) => count($list) === 2);
});

test('setFilterStatus memfilter attendanceList', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $hadir = Person::create(['nama' => 'Jono Hadir', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Jono Belum', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($hadir, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('setFilterStatus', 'hadir')
        ->assertSet('filterStatus', 'hadir')
        ->assertSet('attendanceList', fn ($list) => count($list) === 1 && $list[0]['nama'] === 'Jono Hadir');
});

test('setFilterStatus kosong mengembalikan semua', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $hadir = Person::create(['nama' => 'Jono Hadir', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Jono Belum', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($hadir, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('setFilterStatus', 'hadir')
        ->call('setFilterStatus', '')
        ->assertSet('filterStatus', null)
        ->assertSet('attendanceList', fn ($list) => count($list) === 2);
});

test('setFilterMethod memfilter attendanceList', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $op = Person::create(['nama' => 'Jono Op', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    $selfPerson = Person::create(['nama' => 'Jono Self', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'tanggal_lahir' => '2000-01-15']);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($op, $grant);
    app(PengajianAttendanceService::class)->attendPersonPublicContext($selfPerson, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('setFilterMethod', 'operator')
        ->assertSet('filterMethod', 'operator')
        ->assertSet('attendanceList', fn ($list) => count($list) === 1 && $list[0]['method'] === 'operator');
});

test('setFilterMethod kosong mengembalikan semua', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $op = Person::create(['nama' => 'Jono Op', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    $selfPerson = Person::create(['nama' => 'Jono Self', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'tanggal_lahir' => '2000-01-15']);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($op, $grant);
    app(PengajianAttendanceService::class)->attendPersonPublicContext($selfPerson, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('setFilterMethod', 'operator')
        ->call('setFilterMethod', '')
        ->assertSet('filterMethod', null)
        ->assertSet('attendanceList', fn ($list) => count($list) === 2);
});

// ---------------------------------------------------------------------------
// Daftar Kehadiran – Search
// ---------------------------------------------------------------------------

test('searchList partial name memfilter attendanceList', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);

    Person::create(['nama' => 'Atta Halilintar', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Siti Aisyah', 'jenis_kelamin' => 'P', 'desa_id' => $desa->id]);

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('searchList', 'Atta')
        ->assertSet('listSearch', 'Atta')
        ->assertSet('attendanceList', fn ($list) =>
            count($list) === 1 && $list[0]['nama'] === 'Atta Halilintar'
        );
});

test('searchList empty string mengembalikan semua', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);

    Person::create(['nama' => 'Atta Halilintar', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('searchList', 'Atta')
        ->call('searchList', '')
        ->assertSet('listSearch', '')
        ->assertSet('attendanceList', fn ($list) => count($list) === 2);
});

test('searchList tanpa hasil mengembalikan array kosong', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);

    Person::create(['nama' => 'Atta Halilintar', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    session()->put('pengajian_access', [
        'grant_id' => $result['grant']->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('searchList', 'NonexistentXYZ')
        ->assertSet('listSearch', 'NonexistentXYZ')
        ->assertSet('attendanceList', []);
});

test('searchList + filterStatus kombinasi', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $hadir = Person::create(['nama' => 'Atta Hadir', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Atta Belum', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    Person::create(['nama' => 'Budi Lain', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($hadir, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('searchList', 'Atta')
        ->call('setFilterStatus', 'hadir')
        ->assertSet('listSearch', 'Atta')
        ->assertSet('filterStatus', 'hadir')
        ->assertSet('attendanceList', fn ($list) =>
            count($list) === 1 && $list[0]['nama'] === 'Atta Hadir'
        );
});

test('searchList + setFilterMethod kombinasi', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $op = Person::create(['nama' => 'Atta Operator', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    $selfPerson = Person::create(['nama' => 'Atta Self', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'tanggal_lahir' => '2000-01-15']);
    Person::create(['nama' => 'Budi Lain', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($op, $grant);
    app(PengajianAttendanceService::class)->attendPersonPublicContext($selfPerson, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->set('activeTab', 'list')
        ->call('searchList', 'Atta')
        ->call('setFilterMethod', 'operator')
        ->assertSet('listSearch', 'Atta')
        ->assertSet('filterMethod', 'operator')
        ->assertSet('attendanceList', fn ($list) =>
            count($list) === 1 && $list[0]['method'] === 'operator'
        );
});

// ---------------------------------------------------------------------------
// Operator Attendance – UX flow
// ---------------------------------------------------------------------------

test('confirmOperatorAttendance sukses mereset state UI', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $person = Person::create(['nama' => 'Jono', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    $component = Livewire::test(DesaDashboard::class);

    $component->call('selectPerson', $person->id)
        ->assertSet('showingConfirmation', true)
        ->assertSet('selectedPersonId', $person->id)
        ->assertSet('selectedPersonName', 'Jono');

    $component->call('confirmOperatorAttendance')
        ->assertSet('showingConfirmation', false)
        ->assertSet('selectedPersonId', null)
        ->assertSet('selectedPersonName', null)
        ->assertSet('query', '')
        ->assertSet('searchResults', [])
        ->assertSet('successMessage', 'Kehadiran berhasil dicatat.');
});

test('sequential operator attendance tidak perlu Batal manual', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $jono = Person::create(['nama' => 'Jono', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    $joni = Person::create(['nama' => 'Joni', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    $component = Livewire::test(DesaDashboard::class);

    // Attendance Jono
    $component->call('selectPerson', $jono->id)
        ->assertSet('showingConfirmation', true)
        ->call('confirmOperatorAttendance')
        ->assertSet('showingConfirmation', false)
        ->assertSet('selectedPersonId', null);

    // Verify Jono tercatat
    $component->assertSet('summary', fn ($s) => $s['sudah_hadir'] >= 1);

    // Attendance Joni — langsung tanpa Batal
    $component->call('selectPerson', $joni->id)
        ->assertSet('showingConfirmation', true)
        ->assertSet('selectedPersonId', $joni->id)
        ->call('confirmOperatorAttendance')
        ->assertSet('showingConfirmation', false)
        ->assertSet('selectedPersonId', null);

    // Verify keduanya tercatat
    $component->assertSet('summary', fn ($s) => $s['sudah_hadir'] >= 2);
});

test('confirmOperatorAttendance gagal tidak mereset selected person', function () {
    $event = pgm3_makeEvent();
    $desa = pgm3_makeDesa();
    $result = pgm3_createValidGrant($event, $desa);
    $grant = $result['grant'];

    $person = Person::create(['nama' => 'Jono', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);

    // Pre-attend to trigger duplicate error
    app(PengajianAttendanceService::class)->attendPersonOperatorContext($person, $grant);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id, 'event_id' => $event->id, 'desa_id' => $desa->id,
    ]);

    Livewire::test(DesaDashboard::class)
        ->call('selectPerson', $person->id)
        ->assertSet('showingConfirmation', true)
        ->assertSet('selectedPersonId', $person->id)
        ->call('confirmOperatorAttendance')
        ->assertSet('showingConfirmation', true)
        ->assertSet('selectedPersonId', $person->id)
        ->assertSet('errorMessage', 'Peserta sudah tercatat hadir.');
});
