<?php

use App\Console\Commands\AttendanceParity;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\Absensi;
use App\Models\EventAttendance;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('attendance:parity command boots without option conflict', function () {
    $this->artisan('attendance:parity')->assertSuccessful();
});

test('attendance:parity --event works', function () {
    $event = Event::create(['name' => 'Parity Test', 'slug' => 'parity-test', 'status' => 'active']);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi', 'tanggal' => '2026-08-12', 'aktif' => true]);
    $person = Person::create(['nama' => 'Test']);
    $peserta = peserta::create(['nama' => 'Legacy', 'attendance_code' => 'KJA-PARITY', 'status_registrasi' => 'Belum Registrasi']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'legacy_nip' => 9001]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id]);
    Absensi::create(['nip' => 9001, 'nama' => 'Test', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    EventAttendance::create([
        'participation_id' => $participation->id, 'sesi_absensi_id' => $session->id,
        'event_id' => $event->id, 'status' => EventAttendance::STATUS_HADIR,
        'attended_at' => now(), 'method' => 'scan',
    ]);

    $this->artisan('attendance:parity', ['--event' => $event->id])->assertSuccessful();
});

test('attendance:parity does not mutate data', function () {
    $event = Event::create(['name' => 'NoMutate', 'slug' => 'no-mutate', 'status' => 'active']);

    $this->artisan('attendance:parity')->assertSuccessful();

    expect(Event::find($event->id)->name)->toBe('NoMutate');
});
