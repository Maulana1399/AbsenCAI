<?php

use App\Enums\Role;
use App\Imports\PesertaImport;
use App\Livewire\Database\Peserta\TambahPeserta;
use App\Livewire\Registrasi\SelfRegister;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Models\User;
use App\Services\Placement\PlacementService;
use App\Support\ActiveEventContext;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->user);
    app(ActiveEventContext::class)->set(Event::create(['name' => 'Default Event', 'slug' => 'default-event', 'status' => 'active']));
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
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    peserta::create([
        'nama' => 'Peserta 2',
        'nip' => 11,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    peserta::create([
        'nama' => 'Peserta 3',
        'nip' => 12,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    peserta::create([
        'nama' => 'Peserta 4',
        'nip' => 13,
        'jenis_kelamin' => 'Perempuan',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);
});

test('auto placement picks next nip and least filled regu by gender with event scope', function () {
    $event = Event::create(['name' => 'AP Event', 'slug' => 'ap-event', 'status' => 'active']);

    $p1 = Person::create(['nama' => 'AP M1', 'nip' => 100, 'jenis_kelamin' => 'L']);
    Participation::create(['person_id' => $p1->id, 'event_id' => $event->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-APM1', 'jenis_peserta' => 'Wajib', 'regu_id' => $this->reguMaleA->id]);
    $p2 = Person::create(['nama' => 'AP F1', 'nip' => 200, 'jenis_kelamin' => 'P']);
    Participation::create(['person_id' => $p2->id, 'event_id' => $event->id, 'participant_number' => 'KP001', 'attendance_code' => 'KJA-APF1', 'jenis_peserta' => 'Wajib', 'regu_id' => $this->reguFemaleA->id]);

    $placementMale = PlacementService::autoPlacement('Laki - Laki', $event->id);
    $placementFemale = PlacementService::autoPlacement('Perempuan', $event->id);

    expect($placementMale['regu_id'])->toBe($this->reguMaleB->id);
    expect($placementMale['regu_nama'])->toBe('Grup Merah Laki');

    expect($placementFemale['regu_id'])->toBe($this->reguFemaleB->id);
    expect($placementFemale['regu_nama'])->toBe('Grup Merah Perempuan');
});

test('self register uses automatic nip and least filled regu', function () {
    Livewire::test(SelfRegister::class)
        ->set('nama', 'Peserta Baru')
        ->set('tanggal_lahir', '1995-05-10')
        ->set('jenis_kelamin', 'Perempuan')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('register')
        ->assertRedirect(route('register.success', absolute: false));

    $this->assertDatabaseHas('pesertas', [
        'nama' => 'Peserta Baru',
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    $newPart = Participation::whereHas('person', fn ($q) => $q->where('nama', 'Peserta Baru'))->first();
    expect($newPart)->not->toBeNull();
    expect($newPart->regu_id)->toBe($this->reguFemaleA->id);
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
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $newPart = Participation::whereHas('person', fn ($q) => $q->where('nama', 'Peserta Database'))->first();
    expect($newPart)->not->toBeNull();
    expect($newPart->regu_id)->toBe($this->reguMaleA->id);
});

test('import peserta uses automatic nip and least filled regu', function () {
    $model = (new PesertaImport)->model([
        'nama' => 'Peserta Import',
        'jenis_kelamin' => 'Perempuan',
        'kelompok' => 'Kelompok A',
        'desa' => 'Desa A',
    ]);

    expect($model->nip)->toBeNull()
        ->and($model->participant_number)->toBe('KP001')
        ->and($model->attendance_code)->toStartWith('KJA-')
        ->and($model->regu_id)->toBeNull()
        ->and($model->status_registrasi)->toBe(peserta::STATUS_BELUM_REGISTRASI);

    $newPart = Participation::whereHas('person', fn ($q) => $q->where('nama', 'Peserta Import'))->first();
    expect($newPart)->not->toBeNull();
    expect($newPart->regu_id)->toBe($this->reguFemaleA->id);
});
