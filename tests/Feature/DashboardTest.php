<?php

use App\Livewire\Event\Dashboard as EventDashboard;
use App\Models\Event;
use App\Models\SesiAbsensi;
use App\Models\Participation;
use App\Models\Person;
use App\Models\EventAttendance;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\peserta;
use App\Models\regu;
use App\Models\User;
use App\Enums\Role;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function ds_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'CAI Test',
        'slug' => 'cai-test-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ], $overrides));
}

function ds_sesi(Event $event, array $overrides = []): SesiAbsensi
{
    return SesiAbsensi::create(array_merge([
        'event_id' => $event->id,
        'nama_sesi' => 'Sesi ' . str()->random(4),
        'tanggal' => now()->format('Y-m-d'),
        'aktif' => true,
    ], $overrides));
}

function ds_desa(): desa
{
    return desa::create(['desa_asal' => 'Desa ' . str()->random(4)]);
}

function ds_regu(string $gender = 'L'): regu
{
    return regu::create([
        'regu' => 'Regu ' . str()->random(4),
        'jenis_kelamin' => $gender === 'L' ? 'Laki - Laki' : 'Perempuan',
    ]);
}

function ds_kelompok(desa $desa): kelompok
{
    return kelompok::create([
        'kelompok_asal' => 'Kelompok ' . str()->random(4),
        'desa_id' => $desa->id,
    ]);
}

function ds_legacyPeserta(Person $person, desa $desa, kelompok $kelompok, regu $regu): peserta
{
    $p = peserta::create([
        'nama' => $person->nama,
        'jenis_kelamin' => $person->jenis_kelamin,
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
    ]);

    \App\Models\LegacyPesertaMapping::create([
        'peserta_id' => $p->id,
        'person_id' => $person->id,
        'legacy_nip' => $person->id,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $p->id,
        'person_id' => $person->id,
        'participation_id' => $person->participations()->first()?->id,
        'event_id' => $person->participations()->first()?->event_id,
    ]);

    return $p;
}

function ds_participation(Event $event, Person $person): Participation
{
    return Participation::create([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'participant_number' => 'KL' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
        'attendance_code' => 'KJA-' . str()->random(8),
    ]);
}

function ds_person(desa $desa, string $gender = 'L'): Person
{
    return Person::create([
        'nama' => 'Person ' . str()->random(6),
        'jenis_kelamin' => $gender,
        'desa_id' => $desa->id,
        'nip' => $gender === 'L' ? (string) random_int(1001, 1999) : (string) random_int(2001, 2999),
    ]);
}

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));

    $this->get('/dashboard')->assertStatus(200);
});

// ---------------------------------------------------------------------------
// Bug #3 — Belum Absen Statistics
// ---------------------------------------------------------------------------

test('dashboard shows Alfa count when session is active and no one attended', function () {
    $event = ds_event();
    $sesi = ds_sesi($event);
    $desa = ds_desa();
    $regu = ds_regu();
    $kel = ds_kelompok($desa);
    $person = ds_person($desa);
    $participation = ds_participation($event, $person);
    ds_legacyPeserta($person, $desa, $kel, $regu);

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    Livewire::actingAs($user)
        ->test(EventDashboard::class, ['event' => $event])
        ->assertViewHas('presenterData', fn($d) => $d['totalPeserta'] === 1)
        ->assertViewHas('presenterData', fn($d) => $d['belumAbsenCount'] === 1)
        ->assertViewHas('presenterData', fn($d) => $d['sudahAbsenCount'] === 0)
        ->assertViewHas('presenterData', fn($d) => $d['izinCount'] === 0);
});

test('dashboard Alfa count decreases when participant attends', function () {
    $event = ds_event();
    $sesi = ds_sesi($event);
    $desa = ds_desa();
    $regu = ds_regu();
    $kel = ds_kelompok($desa);
    $person1 = ds_person($desa);
    $person2 = ds_person($desa);
    $part1 = ds_participation($event, $person1);
    ds_participation($event, $person2);
    ds_legacyPeserta($person1, $desa, $kel, $regu);
    ds_legacyPeserta($person2, $desa, $kel, $regu);

    EventAttendance::create([
        'participation_id' => $part1->id,
        'sesi_absensi_id' => $sesi->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    Livewire::actingAs($user)
        ->test(EventDashboard::class, ['event' => $event])
        ->assertViewHas('presenterData', fn($d) => $d['totalPeserta'] === 2)
        ->assertViewHas('presenterData', fn($d) => $d['sudahAbsenCount'] === 1)
        ->assertViewHas('presenterData', fn($d) => $d['belumAbsenCount'] === 1);
});

test('dashboard Alfa shows 0 when all participants attended', function () {
    $event = ds_event();
    $sesi = ds_sesi($event);
    $desa = ds_desa();
    $regu = ds_regu();
    $kel = ds_kelompok($desa);
    $person = ds_person($desa);
    $part = ds_participation($event, $person);
    ds_legacyPeserta($person, $desa, $kel, $regu);

    EventAttendance::create([
        'participation_id' => $part->id,
        'sesi_absensi_id' => $sesi->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    Livewire::actingAs($user)
        ->test(EventDashboard::class, ['event' => $event])
        ->assertViewHas('presenterData', fn($d) => $d['belumAbsenCount'] === 0)
        ->assertViewHas('presenterData', fn($d) => $d['sudahAbsenCount'] === 1);
});

test('dashboard Belum Absen table shows correct names', function () {
    $event = ds_event();
    $sesi = ds_sesi($event);
    $desa = ds_desa();
    $regu = ds_regu();
    $kel = ds_kelompok($desa);
    $person = ds_person($desa);
    $p = ds_participation($event, $person);
    ds_legacyPeserta($person, $desa, $kel, $regu);

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    Livewire::actingAs($user)
        ->test(EventDashboard::class, ['event' => $event])
        ->assertSee($person->nama);
});

test('dashboard attendance from other session does not affect Alfa count', function () {
    $event = ds_event();
    $sesiAktif = ds_sesi($event, ['aktif' => true]);
    $sesiLain = ds_sesi($event, ['nama_sesi' => 'Sesi Lain', 'aktif' => false]);
    $desa = ds_desa();
    $regu = ds_regu();
    $kel = ds_kelompok($desa);
    $person = ds_person($desa);
    $participation = ds_participation($event, $person);
    ds_legacyPeserta($person, $desa, $kel, $regu);

    // Attend in the OTHER session (not active one)
    EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $sesiLain->id,
        'event_id' => $event->id,
        'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    // Person should still show as belum absen because they're not in the active session
    Livewire::actingAs($user)
        ->test(EventDashboard::class, ['event' => $event])
        ->assertViewHas('presenterData', fn($d) => $d['belumAbsenCount'] === 1)
        ->assertViewHas('presenterData', fn($d) => $d['sudahAbsenCount'] === 0);
});
