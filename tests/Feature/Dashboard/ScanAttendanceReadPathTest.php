<?php

use App\Livewire\Dashboard\Scan;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Models\peserta;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function scan_test_event(string $suffix): Event
{
    return Event::create(['name' => 'Scan ' . $suffix, 'slug' => 'scan-' . $suffix . '-' . str()->random(6), 'status' => 'active']);
}

function scan_test_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Session ' . str()->random(4), 'tanggal' => '2026-08-22', 'aktif' => true]);
}

test('manual search returns active event participant and attendance can be recorded', function () {
    $event = scan_test_event('a');
    $session = scan_test_session($event);
    $person = Person::create(['nama' => 'Lana Manual', 'nip' => 82001, 'jenis_kelamin' => 'L']);
    $part = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KA100', 'attendance_code' => 'KJA-MANUAL-100', 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(User::factory()->create(['role' => 'super_admin']));

    Livewire::test(Scan::class)
        ->set('manualSearch', 'lana')
        ->assertSet('manualResults', fn ($r) => count($r) === 1 && $r[0]['id'] === $part->id)
        ->call('selectManualParticipant', $part->id, 'canonical')
        ->assertSet('selectedManualParticipantId', $part->id)
        ->set('sesi_id', $session->id)
        ->call('manualAttend')
        ->assertSet('message', 'Absensi berhasil!');

    expect(EventAttendance::where('participation_id', $part->id)->where('sesi_absensi_id', $session->id)->count())->toBe(1);
});

test('manual search and attendance stay scoped to active event', function () {
    $eventA = scan_test_event('scope-a');
    $eventB = scan_test_event('scope-b');
    $sessionA = scan_test_session($eventA);
    $sessionB = scan_test_session($eventB);

    $personA = Person::create(['nama' => 'Scoped Lana', 'nip' => 82002, 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'Scoped Lana', 'nip' => 82003, 'jenis_kelamin' => 'P']);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'KA101', 'attendance_code' => 'KJA-SCOPE-101', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'KB101', 'attendance_code' => 'KJA-SCOPE-201', 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($eventA);
    $this->actingAs(User::factory()->create(['role' => 'super_admin']));

    Livewire::test(Scan::class)
        ->set('manualSearch', 'scoped lana')
        ->assertSee('KA101')
        ->assertDontSee('KB101')
        ->call('selectManualParticipant', $partA->id, 'canonical')
        ->assertSet('selectedManualParticipantId', $partA->id)
        ->set('sesi_id', $sessionB->id)
        ->call('manualAttend')
        ->assertSet('message', 'Sesi absensi tidak valid atau bukan milik event ini.');

    expect(EventAttendance::where('participation_id', $partB->id)->count())->toBe(0);
    expect(EventAttendance::where('participation_id', $partA->id)->count())->toBe(0);
});

test('manual duplicate attendance remains blocked', function () {
    $event = scan_test_event('dup');
    $session = scan_test_session($event);
    $person = Person::create(['nama' => 'Dup Lana', 'nip' => 82004, 'jenis_kelamin' => 'L']);
    $part = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KA102', 'attendance_code' => 'KJA-DUP-102', 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($event);
    $this->actingAs(User::factory()->create(['role' => 'super_admin']));

    Livewire::test(Scan::class)
        ->call('selectManualParticipant', $part->id, 'canonical')
        ->set('sesi_id', $session->id)
        ->call('manualAttend')
        ->call('manualAttend')
        ->assertSet('message', 'Peserta sudah absen pada sesi ini');

    expect(EventAttendance::where('participation_id', $part->id)->count())->toBe(1);
});
