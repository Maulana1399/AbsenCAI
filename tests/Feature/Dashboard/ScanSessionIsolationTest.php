<?php

use App\Livewire\Dashboard\Scan;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ss_event(): Event
{
    return Event::create(['name' => 'SS Event '.str()->random(6), 'slug' => 'ss-'.str()->random(6), 'status' => 'active']);
}

function ss_session(Event $event, bool $aktif = true): SesiAbsensi
{
    return SesiAbsensi::create([
        'event_id' => $event->id,
        'nama_sesi' => 'SS Sesi '.str()->random(6),
        'tanggal' => '2026-08-21',
        'aktif' => $aktif,
    ]);
}

function ss_admin(): User
{
    return User::factory()->create(['role' => 'super_admin']);
}

// ---------------------------------------------------------------------------
// A. DaftarSesi hanya milik active event
// ---------------------------------------------------------------------------

test('daftarSesi only contains sessions from active event', function () {
    $eventA = ss_event();
    $eventB = ss_event();
    $sessionA = ss_session($eventA);
    $sessionB = ss_session($eventB);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(ss_admin());

    Livewire::test(Scan::class)
        ->assertSet('sesi_id', $sessionA->id);

    $daftar = Livewire::test(Scan::class)->get('daftarSesi');
    expect($daftar)->toHaveCount(1)
        ->and($daftar->first()->id)->toBe($sessionA->id);
});

test('daftarSesi empty when no active event', function () {
    $event = ss_event();
    ss_session($event);

    Livewire::test(Scan::class)
        ->assertSet('daftarSesi', collect());
});

// ---------------------------------------------------------------------------
// B. Manual search Event A tidak menampilkan participant Event B
// ---------------------------------------------------------------------------

test('manual search Event A does not show Event B participants', function () {
    $eventA = ss_event();
    $eventB = ss_event();
    $personA = Person::create(['nama' => 'Alice Event A', 'nip' => 10001]);
    $personB = Person::create(['nama' => 'Bob Event B', 'nip' => 10002]);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(ss_admin());

    Livewire::test(Scan::class)
        ->set('manualSearch', 'Alice')
        ->assertSet('manualResults', fn ($r) => count($r) === 1 && $r[0]['nama'] === 'Alice Event A');
});

// ---------------------------------------------------------------------------
// C. Direct selection participant Event B from Event A is rejected
// ---------------------------------------------------------------------------

test('selecting Event B participant from Event A is rejected', function () {
    $eventA = ss_event();
    $eventB = ss_event();
    $personB = Person::create(['nama' => 'Bob Event B', 'nip' => 10003]);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(ss_admin());

    // Try to select via canonical (will fail because no search found Bob)
    Livewire::test(Scan::class)
        ->call('selectManualParticipant', $partB->id, 'canonical')
        ->assertSet('selectedManualParticipantId', null);
});

// ---------------------------------------------------------------------------
// D. Canonical-only Participation (no legacy mapping) can manual attend
// ---------------------------------------------------------------------------

test('canonical-only participation can manual attend', function () {
    $event = ss_event();
    $session = ss_session($event);
    $person = Person::create(['nama' => 'Canonical Only', 'nip' => 20001]);
    $part = Participation::create([
        'person_id' => $person->id, 'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
        'attendance_code' => 'KJA-CANON-'.str()->random(6),
        'participant_number' => 'KL'.random_int(100, 999),
    ]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(ss_admin());

    Livewire::test(Scan::class)
        ->call('selectManualParticipant', $part->id, 'canonical')
        ->assertSet('selectedManualParticipantId', $part->id)
        ->set('sesi_id', $session->id)
        ->call('manualAttend')
        ->assertSet('message', 'Absensi berhasil!');

    expect(\App\Models\EventAttendance::where('participation_id', $part->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// E. Duplicate nama with different Participation both appear
// ---------------------------------------------------------------------------

test('duplicate names with different participations both appear in search', function () {
    $event = ss_event();
    $personA = Person::create(['nama' => 'John Doe', 'nip' => 30001]);
    $personB = Person::create(['nama' => 'John Doe', 'nip' => 30002]);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(ss_admin());

    Livewire::test(Scan::class)
        ->set('manualSearch', 'John Doe')
        ->assertSet('manualResults', fn ($r) => count($r) === 2);
});

// ---------------------------------------------------------------------------
// F. Manipulated session ID from Event B rejected when active is Event A
// ---------------------------------------------------------------------------

test('manipulated session ID from Event B rejected when active is Event A', function () {
    $eventA = ss_event();
    $eventB = ss_event();
    $sessionB = ss_session($eventB);
    $personA = Person::create(['nama' => 'Alice', 'nip' => 40001]);
    $pesertaA = peserta::create(['nama' => 'Alice Peserta', 'nip' => 40001, 'attendance_code' => 'KJA-SS-'.str()->random(6), 'status_registrasi' => 'Belum Registrasi']);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaA->id, 'person_id' => $personA->id, 'legacy_nip' => $pesertaA->nip, 'legacy_attendance_code' => $pesertaA->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $pesertaA->id, 'person_id' => $personA->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(ss_admin());

    Livewire::test(Scan::class)
        ->call('selectManualParticipant', $partA->id, 'canonical')
        ->set('sesi_id', $sessionB->id)
        ->call('manualAttend')
        ->assertSet('message', 'Sesi absensi tidak valid atau bukan milik event ini.');
});

// ---------------------------------------------------------------------------
// G. Manual attend with session from same event succeeds
// ---------------------------------------------------------------------------

test('manual attend with correct session succeeds', function () {
    $event = ss_event();
    $session = ss_session($event);
    $person = Person::create(['nama' => 'Valid Attend', 'nip' => 50001]);
    $peserta = peserta::create(['nama' => 'Valid Peserta', 'nip' => 50001, 'attendance_code' => 'KJA-SS-VALID', 'status_registrasi' => 'Belum Registrasi']);
    $part = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'legacy_nip' => $peserta->nip, 'legacy_attendance_code' => $peserta->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $part->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(ss_admin());

    Livewire::test(Scan::class)
        ->call('selectManualParticipant', $part->id, 'canonical')
        ->set('sesi_id', $session->id)
        ->call('manualAttend')
        ->assertSet('message', 'Absensi berhasil!');
});
