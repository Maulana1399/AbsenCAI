<?php

use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Rekap\Absensi\RekapAbsensi;
use App\Models\Absensi;
use App\Models\Event;
use App\Models\IzinAbsensi;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use App\Support\ActiveEventContext;
use Livewire\Livewire;

beforeEach(function () {
    $this->desa = desa::create(['desa_asal' => 'Desa A']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);
    $this->regu = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Laki - Laki']);

    $this->eventA = Event::create(['name' => 'Event A', 'slug' => 'event-a', 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Event B', 'slug' => 'event-b', 'status' => 'active']);

    $this->personA = Person::create(['nama' => 'Shared Person', 'nip' => 6001, 'jenis_kelamin' => 'L']);
    $this->personB = Person::create(['nama' => 'Other Person', 'nip' => 6002, 'jenis_kelamin' => 'L']);

    $this->pesertaA = peserta::create(['nama' => 'Peserta Legacy A', 'nip' => 6001, 'attendance_code' => 'KJA-HADIR1', 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id]);
    $this->pesertaB = peserta::create(['nama' => 'Peserta Legacy B', 'nip' => 6002, 'attendance_code' => 'KJA-IZIN1', 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id]);

    $this->participationA = Participation::create(['person_id' => $this->personA->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL601', 'attendance_code' => 'KJA-A601', 'jenis_peserta' => 'Wajib']);
    $this->participationB = Participation::create(['person_id' => $this->personA->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL602', 'attendance_code' => 'KJA-B602', 'jenis_peserta' => 'Wajib']);
    $this->participationC = Participation::create(['person_id' => $this->personB->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL603', 'attendance_code' => 'KJA-A603', 'jenis_peserta' => 'Wajib']);

    LegacyPesertaMapping::create(['peserta_id' => $this->pesertaA->id, 'person_id' => $this->personA->id, 'participation_id' => $this->participationA->id, 'event_id' => $this->eventA->id]);
    LegacyPesertaMapping::create(['peserta_id' => $this->pesertaB->id, 'person_id' => $this->personB->id, 'participation_id' => $this->participationC->id, 'event_id' => $this->eventA->id]);

    $this->sessionA = SesiAbsensi::create(['event_id' => $this->eventA->id, 'nama_sesi' => 'Sesi A', 'tanggal' => '2026-07-16', 'aktif' => true]);
    $this->sessionB = SesiAbsensi::create(['event_id' => $this->eventB->id, 'nama_sesi' => 'Sesi B', 'tanggal' => '2026-07-16', 'aktif' => true]);

    Absensi::create(['nip' => $this->pesertaA->nip, 'nama' => $this->pesertaA->nama, 'jam_scan' => '2026-07-16 08:00:00', 'sesi_id' => $this->sessionA->id]);
    Absensi::create(['nip' => $this->pesertaB->nip, 'nama' => $this->pesertaB->nama, 'jam_scan' => '2026-07-16 08:05:00', 'sesi_id' => $this->sessionB->id]);

    IzinAbsensi::create(['peserta_id' => $this->pesertaB->id, 'sesi_id' => $this->sessionA->id, 'status' => 'izin', 'source' => 'manual']);
});

it('rekap absensi shows event a data only', function () {
    app(ActiveEventContext::class)->set($this->eventA);

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->sessionA->id)
        ->assertSee('Total Peserta')
        ->assertSee('2')
        ->assertSee('Peserta Legacy A')
        ->assertSee('Peserta Legacy B')
        ->assertDontSee('Other Person');
});

it('switching active event changes rekap dataset', function () {
    app(ActiveEventContext::class)->set($this->eventA);

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->sessionA->id)
        ->assertSee('Peserta Legacy A')
        ->assertDontSee('Other Person');

    app(ActiveEventContext::class)->set($this->eventB);

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->sessionB->id)
        ->assertSee('Total Peserta')
        ->assertSee('1')
        ->assertSee('Shared Person')
        ->assertDontSee('Peserta Legacy B');
});

it('same person across events remains isolated', function () {
    app(ActiveEventContext::class)->set($this->eventA);

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->sessionA->id)
        ->assertSee('Peserta Legacy A')
        ->assertDontSee('KJA-B602');
});

it('sesi from other events do not leak attendance', function () {
    app(ActiveEventContext::class)->set($this->eventA);

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->sessionB->id)
        ->assertSet('sesi_id', '')
        ->assertSee('Pilih sesi absensi untuk melihat rekap.');
});

it('legacy compatibility still works for mapped peserta', function () {
    app(ActiveEventContext::class)->set($this->eventA);

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->sessionA->id)
        ->assertSee('Peserta Legacy A')
        ->assertSee('Peserta Legacy B');
});

it('dashboard counts active event participants', function () {
    $event = Event::create(['name' => 'Dashboard Event', 'slug' => 'dashboard-event', 'status' => 'active']);
    app(ActiveEventContext::class)->set($event);

    $person = Person::create(['nama' => 'Dashboard Person', 'nip' => 7001, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'KL701', 'attendance_code' => 'KJA-DASH701', 'jenis_peserta' => 'Wajib']);

    Livewire::test(Dashboard::class)
        ->assertSet('totalPeserta', 1)
        ->assertSee('Total Peserta');
});

it('dashboard excludes participations from other events', function () {
    $eventA = Event::create(['name' => 'Dashboard Event A', 'slug' => 'dashboard-event-a', 'status' => 'active']);
    $eventB = Event::create(['name' => 'Dashboard Event B', 'slug' => 'dashboard-event-b', 'status' => 'active']);

    $person = Person::create(['nama' => 'Shared Person', 'nip' => 7002, 'jenis_kelamin' => 'P']);
    Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KP701', 'attendance_code' => 'KJA-DASHA1', 'jenis_peserta' => 'Wajib']);
    Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KP702', 'attendance_code' => 'KJA-DASHB1', 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($eventA);
    Livewire::test(Dashboard::class)->assertSet('totalPeserta', 1);

    app(ActiveEventContext::class)->set($eventB);
    Livewire::test(Dashboard::class)->assertSet('totalPeserta', 1);
});

it('dashboard counts remain safe without active event', function () {
    app(ActiveEventContext::class)->clear();

    Livewire::test(Dashboard::class)
        ->assertSee('Total Peserta')
        ->assertSee('0')
        ->assertSee('Hadir')
        ->assertSee('Izin')
        ->assertSee('Alfa');
});
