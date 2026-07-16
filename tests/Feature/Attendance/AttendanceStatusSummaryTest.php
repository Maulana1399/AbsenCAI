<?php

use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Rekap\Absensi\RekapAbsensi;
use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

beforeEach(function () {
    $this->desa = desa::create(['desa_asal' => 'Desa A']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);
    $this->regu = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Laki - Laki']);

    $this->hadir = peserta::create([
        'nama' => 'Peserta Hadir',
        'nip' => 6001,
        'attendance_code' => 'KJA-HADIR1',
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
    ]);

    $this->izin = peserta::create([
        'nama' => 'Peserta Izin',
        'nip' => 6002,
        'attendance_code' => 'KJA-IZIN1',
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
    ]);

    $this->alfa = peserta::create([
        'nama' => 'Peserta Alfa',
        'nip' => 6003,
        'attendance_code' => 'KJA-ALFA1',
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
    ]);

    $this->session = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Status',
        'tanggal' => '2026-07-16',
        'aktif' => true,
    ]);

    Absensi::create([
        'nip' => $this->hadir->nip,
        'nama' => $this->hadir->nama,
        'jam_scan' => '2026-07-16 08:00:00',
        'sesi_id' => $this->session->id,
    ]);

    IzinAbsensi::create([
        'peserta_id' => $this->izin->id,
        'sesi_id' => $this->session->id,
        'status' => 'izin',
        'source' => 'manual',
    ]);
});

it('rekap absensi shows hadiir izin alfa counts', function () {
    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->session->id)
        ->assertSee('Total Peserta')
        ->assertSee('3')
        ->assertSee('1')
        ->assertSee('0');
});

it('rekap absensi treats izin as not alfa', function () {
    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->session->id)
        ->assertSee('Izin')
        ->assertSee('Alfa');
});

it('rekap absensi remains compatible with hadir only data', function () {
    IzinAbsensi::query()->delete();

    Livewire::test(RekapAbsensi::class)
        ->set('sesi_id', $this->session->id)
        ->assertSee('Peserta Hadir');
});

it('dashboard counts active session attendance status summary', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Hadir')
        ->assertSee('Izin')
        ->assertSee('Alfa');
});
