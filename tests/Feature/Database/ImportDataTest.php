<?php

use App\Livewire\Database\Desa\ImportDesa;
use App\Livewire\Database\Kelompok\ImportKelompok;
use App\Livewire\Database\Peserta\ImportPeserta;
use App\Livewire\Database\Regu\ImportRegu;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('import desa uploads csv through livewire', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', UploadedFile::fake()->createWithContent('desa.csv', "desa\nDesa Import\n"))
        ->call('import')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('desas', [
        'desa_asal' => 'Desa Import',
    ]);
});

test('import kelompok uploads csv through livewire', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);

    Livewire::test(ImportKelompok::class)
        ->set('file', UploadedFile::fake()->createWithContent('kelompok.csv', "kelompok,desa\nKelompok Import,{$desa->desa_asal}\n"))
        ->call('import')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('kelompoks', [
        'kelompok_asal' => 'Kelompok Import',
        'desa_id' => $desa->id,
    ]);
});

test('import regu uploads csv through livewire', function () {
    Livewire::test(ImportRegu::class)
        ->set('file', UploadedFile::fake()->createWithContent('regu.csv', "regu\nRegu Import\n"))
        ->call('import')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('regus', [
        'regu' => 'Regu Import',
    ]);
});

test('import peserta uploads csv through livewire', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    $kelompok = kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'Regu Perempuan', 'jenis_kelamin' => 'Perempuan']);

    Livewire::test(ImportPeserta::class)
        ->set('file', UploadedFile::fake()->createWithContent('peserta.csv', "nama,jenis_kelamin,kelompok,desa\nPeserta Import,Perempuan,{$kelompok->kelompok_asal},{$desa->desa_asal}\n"))
        ->call('import')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pesertas', [
        'nama' => 'Peserta Import',
        'nip' => 1,
        'regu_id' => $regu->id,
        'kelompok_id' => $kelompok->id,
        'desa_id' => $desa->id,
    ]);
});
