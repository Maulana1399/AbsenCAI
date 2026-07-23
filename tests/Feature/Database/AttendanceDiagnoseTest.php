<?php

use App\Models\Absensi;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ad_event(): Event
{
    return Event::create(['name' => 'AD Event ' . str()->random(6), 'slug' => 'ad-' . str()->random(6), 'status' => 'active']);
}

function ad_session(Event $event): SesiAbsensi
{
    return SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'AD Sesi', 'tanggal' => '2026-08-13', 'aktif' => true]);
}

// ---------------------------------------------------------------------------
// A. Diagnostic identifies cause correctly
// ---------------------------------------------------------------------------

test('diagnose NO_PESERTA when NIP not in pesertas', function () {
    $event = ad_event();
    $session = ad_session($event);
    Absensi::create(['nip' => 99999, 'nama' => 'Ghost', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $this->artisan('attendance:diagnose', ['--event' => $event->id])
        ->assertSuccessful()
        ->expectsOutputToContain('NO_PESERTA');
});

test('diagnose NO_MAPPING when peserta has no mapping', function () {
    $event = ad_event();
    $session = ad_session($event);
    peserta::create(['nama' => 'No Map', 'nip' => 88888, 'attendance_code' => 'KJA-NOMAP', 'status_registrasi' => 'Belum Registrasi']);
    Absensi::create(['nip' => 88888, 'nama' => 'No Map', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $this->artisan('attendance:diagnose', ['--event' => $event->id])
        ->assertSuccessful()
        ->expectsOutputToContain('NO_MAPPING');
});

test('diagnose NO_EVENT when session has no event_id', function () {
    $session = SesiAbsensi::create(['nama_sesi' => 'Orphan Sesi', 'tanggal' => '2026-08-13', 'aktif' => true]);
    Absensi::create(['nip' => 77777, 'nama' => 'Orphan', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $this->artisan('attendance:diagnose')
        ->assertSuccessful()
        ->expectsOutputToContain('NO_EVENT');
});

test('valid mapped attendance not reported as unmappable', function () {
    $event = ad_event();
    $session = ad_session($event);
    $person = Person::create(['nama' => 'Valid', 'nip' => 66666]);
    $p = peserta::create(['nama' => 'Valid Peserta', 'nip' => 66666, 'attendance_code' => 'KJA-VALID', 'status_registrasi' => 'Belum Registrasi']);
    $part = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $p->id, 'person_id' => $person->id]);
    LegacyParticipationMapping::create(['peserta_id' => $p->id, 'person_id' => $person->id, 'participation_id' => $part->id, 'event_id' => $event->id]);
    Absensi::create(['nip' => 66666, 'nama' => 'Valid', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $this->artisan('attendance:diagnose', ['--event' => $event->id])
        ->assertSuccessful()
        ->expectsOutputToContain('No unmappable records');
});

test('diagnose is read-only does not mutate data', function () {
    $event = ad_event();
    $session = ad_session($event);

    $this->artisan('attendance:diagnose', ['--event' => $event->id])
        ->assertSuccessful();

    expect(Event::find($event->id)->name)->toBe($event->name);
});

test('diagnose cross-event isolation', function () {
    $eventA = ad_event();
    $eventB = ad_event();
    $sessionB = ad_session($eventB);
    peserta::create(['nama' => 'Cross', 'nip' => 55555, 'attendance_code' => 'KJA-CROSS', 'status_registrasi' => 'Belum Registrasi']);
    Absensi::create(['nip' => 55555, 'nama' => 'Cross', 'jam_scan' => now(), 'sesi_id' => $sessionB->id]);

    $this->artisan('attendance:diagnose', ['--event' => $eventA->id])
        ->assertSuccessful()
        ->expectsOutputToContain('No unmappable records');
});

test('diagnose respects --limit option', function () {
    $event = ad_event();
    $session = ad_session($event);
    peserta::create(['nama' => 'Limit A', 'nip' => 11111, 'attendance_code' => 'KJA-LIMIT1', 'status_registrasi' => 'Belum Registrasi']);
    peserta::create(['nama' => 'Limit B', 'nip' => 22222, 'attendance_code' => 'KJA-LIMIT2', 'status_registrasi' => 'Belum Registrasi']);
    Absensi::create(['nip' => 11111, 'nama' => 'Limit A', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    Absensi::create(['nip' => 22222, 'nama' => 'Limit B', 'jam_scan' => now(), 'sesi_id' => $session->id]);

    $this->artisan('attendance:diagnose', ['--event' => $event->id, '--limit' => 1])
        ->assertSuccessful()
        ->expectsOutputToContain('NO_MAPPING');
});
