<?php

use App\Enums\Role;
use App\Livewire\Dashboard\Scan;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Support\ActiveEventContext;
use Livewire\Livewire;

beforeEach(function () {
    config(['features.attendance_legacy_write' => true]);
});

function scanTest_makeEvent(): Event
{
    return Event::where('slug', 'cai-operational')->first() ?? Event::create([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ]);
}

function scanTest_makeMappedLegacyPeserta(array $overrides, Event $event): array
{
    $participant = peserta::create(array_merge([
        'jenis_kelamin' => 'Laki - Laki',
    ], $overrides));
    $person = Person::create(['nama' => $participant->nama, 'nip' => $participant->nip, 'jenis_kelamin' => 'L']);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'attendance_code' => $participant->attendance_code, 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id, 'legacy_nip' => $participant->nip, 'legacy_participant_number' => $participant->participant_number, 'legacy_attendance_code' => $participant->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $participant->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    return [$participant, $person, $participation];
}

it('Scan orchestration handles successful attendance scan', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant] = scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Livewire', 'nip' => 3001, 'participant_number' => 'PN-3001', 'attendance_code' => 'KJA-LWSCAN1'], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Livewire', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->call('scanPeserta', 'kja-lwscan1');
    $response->assertSet('message', 'Absensi berhasil!');
    $response->assertSet('nama', 'Peserta Livewire');
    $response->assertNotSet('jam_scan', null);
    $this->assertDatabaseHas('event_attendances', ['sesi_absensi_id' => $session->id, 'event_id' => $event->id]);
});

it('Scan orchestration preserves duplicate attendance prevention', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant, , $participation] = scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Duplicate Livewire', 'nip' => 3002, 'participant_number' => 'PN-3002', 'attendance_code' => 'KJA-LWDUP1'], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Duplicate Livewire', 'tanggal' => '2026-07-15', 'aktif' => true]);
    EventAttendance::create(['participation_id' => $participation->id, 'sesi_absensi_id' => $session->id, 'event_id' => $event->id, 'status' => 'hadir', 'attended_at' => '2026-07-15 08:00:00', 'method' => 'scan']);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->call('scanPeserta', 'KJA-LWDUP1');
    $response->assertSet('message', 'Peserta sudah absen pada sesi ini');
    $response->assertSet('nama', 'Peserta Duplicate Livewire');
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->where('participation_id', $participation->id)->count())->toBe(1);
});

it('Scan orchestration handles missing active session', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Tanpa Sesi', 'nip' => 3003, 'participant_number' => 'PN-3003', 'attendance_code' => 'KJA-LWNOS1'], $event);
    $response = Livewire::test(Scan::class)->set('sesi_id', null)->call('scanPeserta', 'KJA-LWNOS1');
    $response->assertSet('message', 'Pilih sesi absensi terlebih dahulu');
    $response->assertSet('nama', null);
    $response->assertSet('jam_scan', null);
});

it('Scan orchestration handles invalid identifier', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Invalid Livewire', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->call('scanPeserta', 'unknown-identifier');
    $response->assertSet('message', 'Data peserta tidak ditemukan!');
    $response->assertSet('nama', null);
    $response->assertSet('jam_scan', null);
});

it('participant_number is not treated as an attendance scan identifier', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant] = scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Number Only', 'nip' => 3004, 'participant_number' => 'PN-3004', 'attendance_code' => 'KJA-LWPN01'], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Participant Number', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->call('scanPeserta', $participant->participant_number);
    $response->assertSet('message', 'Data peserta tidak ditemukan!');
    $response->assertSet('nama', null);
    $response->assertSet('jam_scan', null);
    expect(EventAttendance::count())->toBe(0);
});

it('manual attendance flow records success through AttendanceService', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant] = scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Manual', 'nip' => 4001, 'participant_number' => 'PN-4001', 'attendance_code' => 'KJA-MANUAL1'], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Manual', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->set('manualSearch', 'Peserta Manual')->call('selectManualParticipant', $participant->id)->call('manualAttend');
    $response->assertSet('message', 'Absensi berhasil!');
    $response->assertSet('nama', 'Peserta Manual');
    $response->assertNotSet('jam_scan', null);
    $this->assertDatabaseHas('event_attendances', ['sesi_absensi_id' => $session->id, 'event_id' => $event->id]);
});

it('manual attendance flow preserves duplicate prevention', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant, , $participation] = scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Manual Duplicate', 'nip' => 4002, 'participant_number' => 'PN-4002', 'attendance_code' => 'KJA-MANUAL2'], $event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Manual Duplicate', 'tanggal' => '2026-07-15', 'aktif' => true]);
    EventAttendance::create(['participation_id' => $participation->id, 'sesi_absensi_id' => $session->id, 'event_id' => $event->id, 'status' => 'hadir', 'attended_at' => '2026-07-15 08:00:00', 'method' => 'scan']);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->set('manualSearch', 'Peserta Manual Duplicate')->call('selectManualParticipant', $participant->id)->call('manualAttend');
    $response->assertSet('message', 'Peserta sudah absen pada sesi ini');
    $response->assertSet('nama', 'Peserta Manual Duplicate');
    expect(EventAttendance::where('sesi_absensi_id', $session->id)->where('participation_id', $participation->id)->count())->toBe(1);
});

it('manual attendance flow handles missing session', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    [$participant] = scanTest_makeMappedLegacyPeserta(['nama' => 'Peserta Manual No Session', 'nip' => 4003, 'participant_number' => 'PN-4003', 'attendance_code' => 'KJA-MANUAL3'], $event);
    $response = Livewire::test(Scan::class)->set('sesi_id', null)->set('manualSearch', 'Peserta Manual No Session')->call('selectManualParticipant', $participant->id)->call('manualAttend');
    $response->assertSet('message', 'Sesi absensi tidak valid atau bukan milik event ini.');
    $response->assertSet('nama', null);
    $response->assertSet('jam_scan', null);
});

it('manual attendance flow handles invalid participant selection', function () {
    $this->actingAs($user = User::factory()->create(['role' => Role::Admin]));
    $event = scanTest_makeEvent();
    app(ActiveEventContext::class)->set($event);
    $session = SesiAbsensi::create(['event_id' => $event->id, 'nama_sesi' => 'Sesi Manual Invalid', 'tanggal' => '2026-07-15', 'aktif' => true]);
    $response = Livewire::test(Scan::class)->set('sesi_id', $session->id)->set('manualSearch', 'Tidak Ada')->call('manualAttend');
    $response->assertSet('message', 'Pilih peserta terlebih dahulu');
    $response->assertSet('nama', null);
    $response->assertSet('jam_scan', null);
    expect(EventAttendance::count())->toBe(0);
});
