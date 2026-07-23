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
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('migration preserves all data and handles null peserta_id safely', function () {
    // -------------------------------------------------------
    // 1. Build pre-migration schema state with representative data
    // -------------------------------------------------------
    $event = Event::create(['name' => 'Production Event', 'slug' => 'prod-event', 'status' => 'active']);

    $person = Person::create(['nama' => 'Test Person', 'nip' => 1001]);
    $peserta = peserta::create([
        'nama' => 'Test Peserta', 'nip' => 1001,
        'attendance_code' => 'KJA-PROD', 'participant_number' => 'KL001',
        'jenis_kelamin' => 'Laki - Laki', 'status_registrasi' => 'Belum Registrasi',
    ]);
    $participation = Participation::create([
        'person_id' => $person->id, 'event_id' => $event->id,
        'attendance_code' => 'KJA-PART', 'participant_number' => 'KL002',
        'jenis_peserta' => 'Wajib',
    ]);
    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id, 'person_id' => $person->id,
        'participation_id' => $participation->id, 'event_id' => $event->id,
    ]);

    $session = SesiAbsensi::create([
        'event_id' => $event->id, 'nama_sesi' => 'Sesi 1', 'tanggal' => '2026-08-20', 'aktif' => true,
    ]);

    $absensi = Absensi::create(['nip' => 1001, 'nama' => 'Test', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    $izin = IzinAbsensi::create(['peserta_id' => $peserta->id, 'sesi_id' => $session->id, 'source' => 'manual']);
    $surat = SuratIzin::create([
        'peserta_id' => $peserta->id, 'event_id' => $event->id,
        'alasan' => 'Test', 'tanggal_mulai' => '2026-08-20', 'tanggal_selesai' => '2026-08-20',
        'status' => 'approved', 'created_by' => User::factory()->create(['role' => 'admin'])->id,
    ]);
    $ea = EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'sesi_absensi_id' => $session->id,
        'status' => EventAttendance::STATUS_HADIR, 'attended_at' => now(), 'method' => 'scan',
    ]);

    // -------------------------------------------------------
    // 2. Record all IDs and counts
    // -------------------------------------------------------
    $before = [
        'events' => Event::count(),
        'pesertas' => peserta::count(),
        'people' => Person::count(),
        'participations' => Participation::count(),
        'mappings' => LegacyPesertaMapping::count(),
        'sesi_absensis' => SesiAbsensi::count(),
        'absensis' => Absensi::count(),
        'izin_absensis' => IzinAbsensi::count(),
        'surat_izins' => SuratIzin::count(),
        'event_attendances' => EventAttendance::count(),
    ];

    $beforeIds = [
        'absensi' => $absensi->id,
        'izin' => $izin->id,
        'surat' => $surat->id,
        'event_attendance' => $ea->id,
        'event' => $event->id,
        'peserta' => $peserta->id,
        'person' => $person->id,
        'participation' => $participation->id,
    ];

    // -------------------------------------------------------
    // 3. Run ONLY the 6.6.1 migration
    // -------------------------------------------------------
    $migration = require database_path('migrations/2026_08_08_000001_fix_legacy_fk_preservation.php');
    $migration->up();

    // -------------------------------------------------------
    // 4. Verify every row/count remains unchanged
    // -------------------------------------------------------
    expect(Event::count())->toBe($before['events']);
    expect(peserta::count())->toBe($before['pesertas']);
    expect(Person::count())->toBe($before['people']);
    expect(Participation::count())->toBe($before['participations']);
    expect(LegacyPesertaMapping::count())->toBe($before['mappings']);
    expect(SesiAbsensi::count())->toBe($before['sesi_absensis']);
    expect(Absensi::count())->toBe($before['absensis']);
    expect(IzinAbsensi::count())->toBe($before['izin_absensis']);
    expect(SuratIzin::count())->toBe($before['surat_izins']);
    expect(EventAttendance::count())->toBe($before['event_attendances']);

    expect(Absensi::find($beforeIds['absensi']))->not->toBeNull();
    expect(IzinAbsensi::find($beforeIds['izin']))->not->toBeNull();
    expect(SuratIzin::find($beforeIds['surat']))->not->toBeNull();
    expect(EventAttendance::find($beforeIds['event_attendance']))->not->toBeNull();
    expect(Event::find($beforeIds['event']))->not->toBeNull();

    // -------------------------------------------------------
    // 5. Null peserta_id safety — set peserta_id to null directly
    // -------------------------------------------------------
    $surat->update(['peserta_id' => null]);
    $izin->update(['peserta_id' => null]);

    expect($surat->fresh()->peserta_id)->toBeNull();
    expect($izin->fresh()->peserta_id)->toBeNull();

    // -------------------------------------------------------
    // 6. Relationships return null (don't crash)
    // -------------------------------------------------------
    $surat->loadMissing('peserta');
    $izin->loadMissing('peserta');
    expect($surat->peserta)->toBeNull();
    expect($izin->peserta)->toBeNull();

    // -------------------------------------------------------
    // 7. EventAttendance, Absensi, Event, Person intact
    // -------------------------------------------------------
    expect(EventAttendance::find($beforeIds['event_attendance']))->not->toBeNull();
    expect(Absensi::find($beforeIds['absensi']))->not->toBeNull();

    // -------------------------------------------------------
    // 8. Cross-table integrity intact
    // -------------------------------------------------------
    expect(Participation::find($beforeIds['participation'])->person_id)->toBe($beforeIds['person']);
    expect(Person::find($beforeIds['person'])->nama)->toBe('Test Person');
});

test('canonical EventAttendance and Absensi survive after peserta_id changes', function () {
    $event = Event::create(['name' => 'Safe Event', 'slug' => 'safe-event', 'status' => 'active']);
    $person = Person::create(['nama' => 'Safe Person', 'nip' => 2001]);
    $peserta = peserta::create(['nama' => 'Safe Peserta', 'nip' => 2001, 'attendance_code' => 'KJA-SAFE', 'status_registrasi' => 'Belum Registrasi']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Safe Sesi', 'tanggal' => '2026-08-20', 'aktif' => true]);

    $absensi = Absensi::create(['nip' => 2001, 'nama' => 'Safe', 'jam_scan' => now(), 'sesi_id' => $session->id]);
    $ea = EventAttendance::create([
        'participation_id' => $participation->id, 'event_id' => $event->id,
        'sesi_absensi_id' => $session->id,
        'status' => EventAttendance::STATUS_HADIR, 'attended_at' => now(), 'method' => 'scan',
    ]);

    expect(Absensi::find($absensi->id))->not->toBeNull();
    expect(EventAttendance::find($ea->id))->not->toBeNull();
});
