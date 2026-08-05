<?php

use App\Models\Event;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function s7_user(array $overrides = []): User
{
    return User::factory()->create($overrides);
}

function s7_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'S7 Event '.str()->random(6),
        'slug' => 's7-event-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ], $overrides));
}

function s7_person(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'S7 Person '.str()->random(6),
    ], $overrides));
}

function s7_session(Event $event, array $overrides = []): SesiAbsensi
{
    return SesiAbsensi::create(array_merge([
        'event_id' => $event->id,
        'nama_sesi' => 'S7 Session '.str()->random(6),
        'tanggal' => '2026-08-04',
        'aktif' => true,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// A. User ↔ Person Foundation
// ---------------------------------------------------------------------------

test('user can be created without person_id', function () {
    $user = s7_user(['role' => 'admin']);

    expect($user->person_id)->toBeNull()
        ->and($user->hasPerson())->toBeFalse()
        ->and($user->person)->toBeNull();
});

test('user can be linked to a person', function () {
    $person = s7_person();
    $user = s7_user(['role' => 'admin', 'person_id' => $person->id]);

    expect($user->person_id)->toBe($person->id)
        ->and($user->hasPerson())->toBeTrue()
        ->and($user->person)->not->toBeNull()
        ->and($user->person->id)->toBe($person->id)
        ->and($user->person->nama)->toBe($person->nama);
});

test('person->user resolves correctly', function () {
    $person = s7_person();
    $user = s7_user(['role' => 'admin', 'person_id' => $person->id]);

    expect($person->user)->not->toBeNull()
        ->and($person->user->id)->toBe($user->id)
        ->and($person->user->email)->toBe($user->email);
});

test('person without linked user returns null', function () {
    $person = s7_person();

    expect($person->user)->toBeNull();
});

test('two users cannot link to the same person', function () {
    $person = s7_person();
    s7_user(['role' => 'admin', 'person_id' => $person->id]);

    expect(fn () => s7_user(['role' => 'ketua_event', 'person_id' => $person->id]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('user without person_id can still login', function () {
    $user = s7_user(['role' => 'super_admin']);

    $this->actingAs($user);
    $this->get('/dashboard')->assertOk();
});

test('user with person_id linked can still login', function () {
    $person = s7_person();
    $user = s7_user(['role' => 'super_admin', 'person_id' => $person->id]);

    $this->actingAs($user);
    $this->get('/dashboard')->assertOk();
});

// ---------------------------------------------------------------------------
// B. DataSesi — Event scope
// ---------------------------------------------------------------------------

test('DataSesi only displays sessions from active event', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionA = s7_session($eventA);
    $sessionB = s7_session($eventB);

    app(ActiveEventContext::class)->set($eventA);

    $component = Livewire::test(\App\Livewire\Database\Sesi\DataSesi::class);

    $daftarSesi = $component->get('daftarSesi');
    expect($daftarSesi)->toHaveCount(1)
        ->and($daftarSesi->first()->id)->toBe($sessionA->id);
});

test('DataSesi shows no sessions when no active event', function () {
    $event = s7_event();
    s7_session($event);

    $component = Livewire::test(\App\Livewire\Database\Sesi\DataSesi::class);

    expect($component->get('daftarSesi'))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// C. HapusSesi — Event scope
// ---------------------------------------------------------------------------

test('HapusSesi can delete session from active event', function () {
    $event = s7_event();
    $session = s7_session($event);
    $user = s7_user(['role' => 'admin']);

    app(ActiveEventContext::class)->set($event);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Database\Sesi\HapusSesi::class)
        ->dispatch('HapusSesi', id: $session->id)
        ->assertSet('sesi_id', $session->id)
        ->call('delete');

    expect(SesiAbsensi::find($session->id))->toBeNull();
});

test('HapusSesi cannot delete session from another event', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionB = s7_session($eventB);

    app(ActiveEventContext::class)->set($eventA);

    $component = Livewire::test(\App\Livewire\Database\Sesi\HapusSesi::class)
        ->dispatch('HapusSesi', id: $sessionB->id);

    expect($component->get('sesi_id'))->toBeNull()
        ->and(SesiAbsensi::find($sessionB->id))->not->toBeNull();
});

test('HapusSesi cannot delete session from another event via direct state manipulation', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionB = s7_session($eventB);
    $user = s7_user(['role' => 'admin']);

    app(ActiveEventContext::class)->set($eventA);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Database\Sesi\HapusSesi::class)
        ->set('sesi_id', $sessionB->id)
        ->call('delete');

    expect(SesiAbsensi::find($sessionB->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// D. EditSesi — Event scope
// ---------------------------------------------------------------------------

test('EditSesi can load session from active event', function () {
    $event = s7_event();
    $session = s7_session($event);
    $user = s7_user(['role' => 'admin']);

    app(ActiveEventContext::class)->set($event);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Database\Sesi\EditSesi::class)
        ->dispatch('editSesi', id: $session->id)
        ->assertSet('sesi_id', $session->id)
        ->assertSet('nama_sesi', $session->nama_sesi);
});

test('EditSesi cannot load session from another event', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionB = s7_session($eventB);

    app(ActiveEventContext::class)->set($eventA);

    $component = Livewire::test(\App\Livewire\Database\Sesi\EditSesi::class)
        ->dispatch('editSesi', id: $sessionB->id);

    expect($component->get('sesi_id'))->toBeNull();
});

test('EditSesi update scoped to active event session', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionA = s7_session($eventA);
    $sessionB = s7_session($eventB);
    $user = s7_user(['role' => 'admin']);

    app(ActiveEventContext::class)->set($eventA);

    $this->actingAs($user);
    Livewire::test(\App\Livewire\Database\Sesi\EditSesi::class)
        ->set('sesi_id', $sessionB->id)
        ->set('nama_sesi', 'Manipulated Name')
        ->set('tanggal', '2026-08-05')
        ->call('update');

    expect($sessionB->fresh()->nama_sesi)->not->toBe('Manipulated Name');
});

// ---------------------------------------------------------------------------
// E. SuratIzinService — Session scope
// ---------------------------------------------------------------------------

test('SuratIzin approve scopes sessions to active event', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionA = s7_session($eventA, ['tanggal' => '2026-08-04']);
    $sessionB = s7_session($eventB, ['tanggal' => '2026-08-04']);

    app(ActiveEventContext::class)->set($eventA);

    $peserta = peserta::create([
        'nama' => 'Test Peserta',
        'nip' => 7777,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $user = s7_user(['role' => 'super_admin']);

    $service = app(SuratIzinService::class);
    $surat = $service->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan panjang untuk keperluan testing',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-04',
        'tanggal_selesai' => '2026-08-04',
    ], $user->id);

    $service->submit($surat);

    $result = $service->approve($surat->fresh(), $user);

    expect($result['sesi_found'])->toBe(1)
        ->and($result['created'])->toHaveCount(1)
        ->and($result['created'][0]->sesi_id)->toBe($sessionA->id);
});

test('SuratIzin approve excludes sessions from other events', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    s7_session($eventA, ['tanggal' => '2026-08-04']);
    $sessionB = s7_session($eventB, ['tanggal' => '2026-08-04']);

    app(ActiveEventContext::class)->set($eventB);

    $peserta = peserta::create([
        'nama' => 'Test Peserta',
        'nip' => 8888,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $user = s7_user(['role' => 'super_admin']);

    $service = app(SuratIzinService::class);
    $surat = $service->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan panjang untuk keperluan testing',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-04',
        'tanggal_selesai' => '2026-08-04',
    ], $user->id);

    $service->submit($surat);

    $result = $service->approve($surat->fresh(), $user);

    expect($result['sesi_found'])->toBe(1)
        ->and($result['created'])->toHaveCount(1)
        ->and($result['created'][0]->sesi_id)->toBe($sessionB->id);
});

test('SuratIzin approve with no active event falls back to all sessions', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    s7_session($eventA, ['tanggal' => '2026-08-04']);
    s7_session($eventB, ['tanggal' => '2026-08-04']);

    $peserta = peserta::create([
        'nama' => 'Test Peserta',
        'nip' => 9999,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $user = s7_user(['role' => 'super_admin']);

    $service = app(SuratIzinService::class);
    $surat = $service->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Test alasan panjang untuk keperluan testing',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-08-04',
        'tanggal_selesai' => '2026-08-04',
    ], $user->id);

    $service->submit($surat);

    $result = $service->approve($surat->fresh(), $user);

    expect($result['sesi_found'])->toBe(2);
});

// ---------------------------------------------------------------------------
// F. Regression — Admin/SuperAdmin authorized flows remain working
// ---------------------------------------------------------------------------

test('admin can still manage sessions after IDOR fixes', function () {
    $event = s7_event();
    $session = s7_session($event);
    $user = s7_user(['role' => 'admin']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Sesi\HapusSesi::class)
        ->dispatch('HapusSesi', id: $session->id)
        ->call('delete');

    expect(SesiAbsensi::find($session->id))->toBeNull();
});

test('super admin can still manage sessions after IDOR fixes', function () {
    $event = s7_event();
    $session = s7_session($event);
    $user = s7_user(['role' => 'super_admin']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Sesi\HapusSesi::class)
        ->dispatch('HapusSesi', id: $session->id)
        ->call('delete');

    expect(SesiAbsensi::find($session->id))->toBeNull();
});

test('DataSesi shows correct sessions for admin after scoping', function () {
    $eventA = s7_event();
    $eventB = s7_event();
    $sessionA = s7_session($eventA);
    s7_session($eventB);

    app(ActiveEventContext::class)->set($eventA);

    $component = Livewire::test(\App\Livewire\Database\Sesi\DataSesi::class);

    $daftarSesi = $component->get('daftarSesi');
    expect($daftarSesi)->toHaveCount(1)
        ->and($daftarSesi->first()->id)->toBe($sessionA->id);
});

test('EditSesi update still works for admin on own event session', function () {
    $event = s7_event();
    $session = s7_session($event);
    $user = s7_user(['role' => 'admin']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\Database\Sesi\EditSesi::class)
        ->dispatch('editSesi', id: $session->id)
        ->set('nama_sesi', 'Updated Session Name')
        ->set('tanggal', '2026-08-05')
        ->call('update');

    expect($session->fresh()->nama_sesi)->toBe('Updated Session Name');
});
