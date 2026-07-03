<?php

use App\Livewire\Database\Peserta\TambahPeserta;
use App\Imports\PesertaImport;
use App\Livewire\Registrasi\SelfRegister;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use Livewire\Livewire;

beforeEach(function () {
    $this->desa = desa::create(['desa_asal' => 'Desa A']);
    $this->kelompok = kelompok::create([
        'kelompok_asal' => 'Kelompok A',
        'desa_id' => $this->desa->id,
    ]);

    $this->reguMaleA = regu::create(['regu' => 'Grup Biru Laki', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguMaleB = regu::create(['regu' => 'Grup Merah Laki', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguFemaleA = regu::create(['regu' => 'Grup Biru Perempuan', 'jenis_kelamin' => 'Perempuan']);
    $this->reguFemaleB = regu::create(['regu' => 'Grup Merah Perempuan', 'jenis_kelamin' => 'Perempuan']);

    peserta::create([
        'nama' => 'Peserta 1',
        'nip' => 10,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->reguMaleA->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    peserta::create([
        'nama' => 'Peserta 2',
        'nip' => 11,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->reguMaleA->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    peserta::create([
        'nama' => 'Peserta 3',
        'nip' => 12,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->reguMaleB->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    peserta::create([
        'nama' => 'Peserta 4',
        'nip' => 13,
        'jenis_kelamin' => 'Perempuan',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->reguFemaleA->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);
});

test('auto placement picks next nip and least filled regu by gender', function () {
    $placementMale = peserta::autoPlacement('Laki - Laki');
    $placementFemale = peserta::autoPlacement('Perempuan');

    expect($placementMale['nip'])->toBe('14');
    expect($placementMale['regu_id'])->toBe($this->reguMaleB->id);
    expect($placementMale['regu_nama'])->toBe('Grup Merah Laki');

    expect($placementFemale['nip'])->toBe('14');
    expect($placementFemale['regu_id'])->toBe($this->reguFemaleB->id);
    expect($placementFemale['regu_nama'])->toBe('Grup Merah Perempuan');
});

test('self register uses automatic nip and least filled regu', function () {
    Livewire::test(SelfRegister::class)
        ->set('nama', 'Peserta Baru')
        ->set('jenis_kelamin', 'Perempuan')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('register')
        ->assertRedirect(route('register.success', absolute: false));

    $this->assertDatabaseHas('pesertas', [
        'nama' => 'Peserta Baru',
        'nip' => 14,
        'regu_id' => $this->reguFemaleB->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);
});

test('database peserta form uses automatic nip and least filled regu', function () {
    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Peserta Database')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    $this->assertDatabaseHas('pesertas', [
        'nama' => 'Peserta Database',
        'nip' => 14,
        'regu_id' => $this->reguMaleB->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);
});

test('import peserta uses automatic nip and least filled regu', function () {
    $model = (new PesertaImport())->model([
        'nama' => 'Peserta Import',
        'jenis_kelamin' => 'Perempuan',
        'kelompok' => 'Kelompok A',
        'desa' => 'Desa A',
    ]);

    expect($model->nip)->toBe('14');
    expect($model->regu_id)->toBe($this->reguFemaleB->id);
    expect($model->status_registrasi)->toBe(peserta::STATUS_BELUM_REGISTRASI);
});
