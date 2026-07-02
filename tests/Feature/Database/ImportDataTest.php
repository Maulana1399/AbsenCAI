<?php

use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('import desa uploads csv', function () {
    $this->post(route('import.desa'), [
        'file' => UploadedFile::fake()->createWithContent('desa.csv', "desa\nDesa Import\n"),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('desas', [
        'desa_asal' => 'Desa Import',
    ]);
});

test('import kelompok uploads csv', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);

    $this->post(route('import.kelompok'), [
        'file' => UploadedFile::fake()->createWithContent('kelompok.csv', "kelompok,desa\nKelompok Import,{$desa->desa_asal}\n"),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('kelompoks', [
        'kelompok_asal' => 'Kelompok Import',
        'desa_id' => $desa->id,
    ]);
});

test('import regu uploads csv', function () {
    $this->post(route('import.regu'), [
        'file' => UploadedFile::fake()->createWithContent('regu.csv', "regu\nRegu Import\n"),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('regus', [
        'regu' => 'Regu Import',
    ]);
});

test('import peserta uploads csv', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    $kelompok = kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'Regu Perempuan', 'jenis_kelamin' => 'Perempuan']);

    $this->post(route('import.peserta'), [
        'file' => UploadedFile::fake()->createWithContent('peserta.csv', "nama,jenis_kelamin,kelompok,desa\nPeserta Import,Perempuan,{$kelompok->kelompok_asal},{$desa->desa_asal}\n"),
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('pesertas', [
        'nama' => 'Peserta Import',
        'nip' => 1,
        'regu_id' => $regu->id,
        'kelompok_id' => $kelompok->id,
        'desa_id' => $desa->id,
    ]);
});
