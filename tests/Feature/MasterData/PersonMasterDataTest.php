<?php

use App\Enums\Role;
use App\Models\User;
use App\Models\Person;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\Participation;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pm_person(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person ' . str()->random(6),
        'jenis_kelamin' => 'L',
    ], $overrides));
}

function pm_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Person Test Event ' . str()->random(6),
        'slug' => 'pt-test-' . str()->random(6),
        'event_type' => 'cai',
        'status' => 'active',
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Route existence
// ---------------------------------------------------------------------------

test('Person index route resolves to /person', function () {
    expect(route('person.index', [], false))->toBe('/person');
});

// ---------------------------------------------------------------------------
// Guest access restriction
// ---------------------------------------------------------------------------

test('guest cannot access person page', function () {
    $this->get('/person')->assertRedirect('/login');
});

// ---------------------------------------------------------------------------
// Authenticated access
// ---------------------------------------------------------------------------

test('authenticated user can access person page', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $this->get('/person')->assertOk();
});

test('person page renders without active event', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    expect(app(ActiveEventContext::class)->current())->toBeNull();
    $this->get('/person')->assertOk();
});

test('person page does not change ActiveEventContext', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = pm_event();
    app(ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $this->get('/person')->assertOk();

    $response = $this->get('/dashboard');
    $response->assertOk();
    $response->assertSee('Master Data');
});

// ---------------------------------------------------------------------------
// Person accessible via Master Data landing page
// ---------------------------------------------------------------------------

test('master-data landing page shows Person card', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $response = $this->get('/master-data');
    $response->assertOk();
    $response->assertSee('Person');
    $response->assertSee(route('person.index', [], false));
});

test('Person page renders via Master Data context', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $this->get('/person')->assertOk();
    $this->get('/master-data')->assertOk();
    $this->get('/person')->assertOk();
});

// ---------------------------------------------------------------------------
// Person CRUD — Create
// ---------------------------------------------------------------------------

test('create person stores a new person without participation', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', 'Budi Santoso')
        ->set('jenis_kelamin', 'L')
        ->call('simpan');

    $this->assertDatabaseHas('people', [
        'nama' => 'Budi Santoso',
        'jenis_kelamin' => 'L',
    ]);

    $person = Person::where('nama', 'Budi Santoso')->first();
    expect($person)->not->toBeNull();
    expect($person->participations()->count())->toBe(0);
});

test('create person with desa and kelompok', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Test']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Test', 'desa_id' => $desa->id]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', 'Siti Aminah')
        ->set('jenis_kelamin', 'P')
        ->set('desa_id', (string) $desa->id)
        ->set('kelompok_id', (string) $kelompok->id)
        ->call('simpan');

    $this->assertDatabaseHas('people', [
        'nama' => 'Siti Aminah',
        'jenis_kelamin' => 'P',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
    ]);
});

test('create person requires nama', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', '')
        ->set('jenis_kelamin', 'L')
        ->call('simpan')
        ->assertHasErrors('nama');
});

test('create person requires jenis_kelamin', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', 'Test')
        ->set('jenis_kelamin', '')
        ->call('simpan')
        ->assertHasErrors('jenis_kelamin');
});

// ---------------------------------------------------------------------------
// Person CRUD — Index
// ---------------------------------------------------------------------------

test('person index shows list of people', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    pm_person(['nama' => 'Person Alpha']);
    pm_person(['nama' => 'Person Beta']);
    $this->actingAs($user);

    $response = $this->get('/person');
    $response->assertOk();
    $response->assertSee('Person Alpha');
    $response->assertSee('Person Beta');
});

test('person index search filters by name', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    pm_person(['nama' => 'Unique Name']);
    pm_person(['nama' => 'Other Name']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('search', 'Unique')
        ->assertSee('Unique Name')
        ->assertDontSee('Other Name');
});


test('person index shows empty state when no data', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $response = $this->get('/person');
    $response->assertOk();
    $response->assertSee('Belum ada data Person');
});

// ---------------------------------------------------------------------------
// Person CRUD — Edit
// ---------------------------------------------------------------------------

test('edit person updates the record', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $person = pm_person(['nama' => 'Old Name']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Updated Name')
        ->call('update');

    $this->assertDatabaseHas('people', [
        'id' => $person->id,
        'nama' => 'Updated Name',
    ]);

    $this->assertDatabaseMissing('people', [
        'id' => $person->id,
        'nama' => 'Old Name',
    ]);
});

test('edit person preserves relationships', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $desa = \App\Models\desa::create(['desa_asal' => 'Edit Desa']);
    $person = pm_person(['nama' => 'Edit Person', 'desa_id' => $desa->id]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Edited Person')
        ->call('update');

    $person->refresh();
    expect($person->desa_id)->toBe($desa->id);
});

// ---------------------------------------------------------------------------
// Person CRUD — Delete Safety
// ---------------------------------------------------------------------------

test('person without participations or mapping can be deleted', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $person = pm_person(['nama' => 'Deletable Person']);
    $this->actingAs($user);

    $component = Livewire::test(\App\Livewire\MasterData\Person\DeletePerson::class)
        ->dispatch('deletePerson', id: $person->id);

    $component->assertSet('canDelete', true);
    $component->assertSet('blockReason', null);

    $component->call('destroy');

    $this->assertDatabaseMissing('people', ['id' => $person->id]);
});

test('person with participation cannot be deleted', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = pm_event();
    $person = pm_person(['nama' => 'Protected Person']);
    $this->actingAs($user);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
    ]);

    $component = Livewire::test(\App\Livewire\MasterData\Person\DeletePerson::class)
        ->dispatch('deletePerson', id: $person->id);

    $component->assertSet('canDelete', false);
    $component->assertSet('blockReason', fn ($value) => str_contains($value, 'tidak dapat dihapus'));

    $component->call('destroy');

    $this->assertDatabaseHas('people', ['id' => $person->id]);
    $this->assertDatabaseHas('participations', ['id' => $participation->id]);
});

// ---------------------------------------------------------------------------
// Sync — PersonLegacySyncService
// ---------------------------------------------------------------------------

function pm_regu(): \App\Models\regu
{
    return \App\Models\regu::create([
        'regu' => 'Test Regu ' . str()->random(4),
        'jenis_kelamin' => 'Laki - Laki',
    ]);
}

function pm_mappedPerson(): array
{
    $regu = pm_regu();

    $event = Event::create([
        'name' => 'Sync Test Event',
        'slug' => 'sync-test-' . str()->random(6),
        'status' => 'active',
    ]);

    $person = Person::create([
        'nama' => 'Original Name',
        'jenis_kelamin' => 'L',
    ]);

    $peserta = \App\Models\peserta::create([
        'nama' => 'Original Name',
        'nip' => random_int(3000, 9999),
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => null,
        'kelompok_id' => null,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Wajib',
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-SYNCTEST01',
    ]);

    $mapping = \App\Models\LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'migrated_at' => now(),
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'migrated_at' => now(),
    ]);

    return [
        'person' => $person,
        'peserta' => $peserta,
        'participation' => $participation,
        'mapping' => $mapping,
        'event' => $event,
        'regu' => $regu,
    ];
}

test('edit mapped person syncs nama to legacy peserta', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $person = $setup['person'];
    $peserta = $setup['peserta'];
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Updated Sync Name')
        ->call('update');

    $peserta->refresh();
    expect($peserta->nama)->toBe('Updated Sync Name');
});

test('edit mapped person syncs desa to legacy peserta', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $desa = \App\Models\desa::create(['desa_asal' => 'Sync Desa']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test')
        ->set('desa_id', (string) $desa->id)
        ->call('update');

    $setup['peserta']->refresh();
    expect((int) $setup['peserta']->desa_id)->toBe($desa->id);
});

test('edit mapped person syncs kelompok to legacy peserta', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $desa = \App\Models\desa::create(['desa_asal' => 'Kel Sync Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Sync Kelompok', 'desa_id' => $desa->id]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test')
        ->set('desa_id', (string) $desa->id)
        ->set('kelompok_id', (string) $kelompok->id)
        ->call('update');

    $setup['peserta']->refresh();
    expect((int) $setup['peserta']->kelompok_id)->toBe($kelompok->id);
});

test('edit mapped person syncs jenis_kelamin L to Laki - Laki', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $setup['person']->update(['jenis_kelamin' => 'L']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test')
        ->set('jenis_kelamin', 'L')
        ->call('update');

    $setup['peserta']->refresh();
    expect($setup['peserta']->jenis_kelamin)->toBe('Laki - Laki');
});

test('edit mapped person syncs jenis_kelamin P to Perempuan', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $setup['person']->update(['jenis_kelamin' => 'P']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test')
        ->set('jenis_kelamin', 'P')
        ->call('update');

    $setup['peserta']->refresh();
    expect($setup['peserta']->jenis_kelamin)->toBe('Perempuan');
});

test('edit mapped person does NOT change participant_number', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $originalNumber = $setup['participation']->participant_number;
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test Updated')
        ->call('update');

    $setup['participation']->refresh();
    expect($setup['participation']->participant_number)->toBe($originalNumber);
});

test('edit mapped person does NOT change attendance_code', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $originalCode = $setup['participation']->attendance_code;
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test Updated')
        ->call('update');

    $setup['participation']->refresh();
    expect($setup['participation']->attendance_code)->toBe($originalCode);
});

test('edit mapped person does NOT change regu_id on peserta', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $originalReguId = $setup['peserta']->regu_id;
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test Updated')
        ->call('update');

    $setup['peserta']->refresh();
    expect($setup['peserta']->regu_id)->toBe($originalReguId);
});

test('edit mapped person does NOT create new Participation', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $participationCount = Participation::count();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Test Updated')
        ->call('update');

    expect(Participation::count())->toBe($participationCount);
});

test('edit standalone person does NOT create legacy peserta', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $person = pm_person(['nama' => 'Standalone Person']);
    $pesertaCount = \App\Models\peserta::count();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Updated Standalone')
        ->call('update');

    expect(\App\Models\peserta::count())->toBe($pesertaCount);
});

test('delete guard still works after sync implementation', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $this->actingAs($user);

    $component = Livewire::test(\App\Livewire\MasterData\Person\DeletePerson::class)
        ->dispatch('deletePerson', id: $setup['person']->id);

    $component->assertSet('canDelete', false);
    $component->assertSet('blockReason', fn ($value) => str_contains($value, 'tidak dapat dihapus'));

    $component->call('destroy');

    $this->assertDatabaseHas('people', ['id' => $setup['person']->id]);
});

// ---------------------------------------------------------------------------
// Identity field editing (NIP removed from EditPerson/CreatePerson)
// ---------------------------------------------------------------------------

test('edit mapped person syncs all identity fields', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $setup = pm_mappedPerson();
    $desa = \App\Models\desa::create(['desa_asal' => 'Identity Sync Desa']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $setup['person']->id)
        ->set('nama', 'Identity Sync Name')
        ->set('jenis_kelamin', 'P')
        ->set('desa_id', (string) $desa->id)
        ->set('tanggal_lahir', '2000-01-15')
        ->call('update');

    $setup['person']->refresh();
    $setup['peserta']->refresh();

    expect($setup['person']->nama)->toBe('Identity Sync Name');
    expect($setup['person']->jenis_kelamin)->toBe('P');
    expect((int) $setup['person']->desa_id)->toBe($desa->id);
    expect($setup['person']->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-15');

    expect($setup['peserta']->nama)->toBe('Identity Sync Name');
    expect($setup['peserta']->jenis_kelamin)->toBe('Perempuan');
    expect((int) $setup['peserta']->desa_id)->toBe($desa->id);
});

test('edit standalone person with identity fields does not create peserta', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $person = pm_person(['nama' => 'Standalone Identity']);
    $pesertaCount = \App\Models\peserta::count();
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\EditPerson::class)
        ->dispatch('editPerson', id: $person->id)
        ->set('nama', 'Updated Standalone Identity')
        ->set('jenis_kelamin', 'L')
        ->set('tanggal_lahir', '1999-12-01')
        ->call('update');

    $person->refresh();

    expect($person->nama)->toBe('Updated Standalone Identity');
    expect($person->jenis_kelamin)->toBe('L');
    expect($person->tanggal_lahir->format('Y-m-d'))->toBe('1999-12-01');

    expect(\App\Models\peserta::count())->toBe($pesertaCount);
});

test('create person with tanggal_lahir stores correctly', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\CreatePerson::class)
        ->set('nama', 'Birthday Person')
        ->set('jenis_kelamin', 'L')
        ->set('tanggal_lahir', '2005-06-20')
        ->call('simpan');

    $this->assertDatabaseHas('people', [
        'nama' => 'Birthday Person',
        'jenis_kelamin' => 'L',
    ]);

    $person = Person::where('nama', 'Birthday Person')->first();
    expect($person->tanggal_lahir->format('Y-m-d'))->toBe('2005-06-20');
});

// ---------------------------------------------------------------------------
// RegistrationService — kelompok_id sync
// ---------------------------------------------------------------------------

test('RegistrationService createParticipant sets kelompok_id on Person', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = pm_event();
    $regu = pm_regu();
    $desa = \App\Models\desa::create(['desa_asal' => 'Reg Test Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Reg Test Kelompok', 'desa_id' => $desa->id]);
    $nama = 'Reg Kelompok Test ' . str()->random(6);
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    app(\App\Services\Registration\RegistrationService::class)->createParticipant([
        'nama' => $nama,
        'nip' => 7777,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $this->assertDatabaseHas('people', [
        'nama' => $nama,
        'kelompok_id' => $kelompok->id,
    ]);
});

test('RegistrationService updateParticipant syncs kelompok_id to Person', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = pm_event();
    $regu = pm_regu();
    $desa = \App\Models\desa::create(['desa_asal' => 'Update Test Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Update Test Kelompok', 'desa_id' => $desa->id]);
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($user);

    $peserta = app(\App\Services\Registration\RegistrationService::class)->createParticipant([
        'nama' => 'Update Kelompok Test',
        'nip' => 7778,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desa->id,
        'kelompok_id' => null,
        'regu_id' => $regu->id,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $peserta = app(\App\Services\Registration\RegistrationService::class)->updateParticipant($peserta->id, [
        'nama' => 'Update Kelompok Test',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
    ]);

    $this->assertDatabaseHas('people', [
        'nama' => 'Update Kelompok Test',
        'kelompok_id' => $kelompok->id,
    ]);
});

// ---------------------------------------------------------------------------
// Existing Master Data remains accessible
// ---------------------------------------------------------------------------

test('desa page still accessible after person implementation', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $this->get('/desa')->assertOk();
});

test('kelompok page still accessible after person implementation', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $this->get('/kelompok')->assertOk();
});

test('regu page still accessible after person implementation', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    $this->get('/regu')->assertOk();
});

// ---------------------------------------------------------------------------
// PGM.25F — Master Data Person Filter
// ---------------------------------------------------------------------------

test('filter by desa shows only persons from that desa', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $desa = \App\Models\desa::create(['desa_asal' => 'Filter Desa']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('desaId', (string) $desa->id)
        ->assertSet('desaId', (string) $desa->id);
});

test('filter by kelompok shows only persons from that kelompok', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $desa = \App\Models\desa::create(['desa_asal' => 'Filter Kel Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Filter Kelompok', 'desa_id' => $desa->id]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('kelompokId', (string) $kelompok->id)
        ->assertSet('kelompokId', (string) $kelompok->id);
});

test('filter by jenis kelamin shows only persons of that gender', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('jenisKelamin', 'L')
        ->assertSet('jenisKelamin', 'L');
});

test('filter desa resets kelompok to all', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $desa = \App\Models\desa::create(['desa_asal' => 'Reset Desa']);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('kelompokId', '999')
        ->set('desaId', (string) $desa->id)
        ->assertSet('kelompokId', '');
});

test('search combined with desa filter works', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $desa = \App\Models\desa::create(['desa_asal' => 'Search Desa']);
    $person = \App\Models\Person::create(['nama' => 'Searchable12345', 'jenis_kelamin' => 'L', 'desa_id' => $desa->id]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('desaId', (string) $desa->id)
        ->set('search', 'Searchable12345')
        ->assertSee('Searchable12345');
});

test('reset filter clears all filters', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($user);

    Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('search', 'test')
        ->set('desaId', '999')
        ->set('kelompokId', '999')
        ->set('jenisKelamin', 'L')
        ->call('resetFilter')
        ->assertSet('search', '')
        ->assertSet('desaId', '')
        ->assertSet('kelompokId', '')
        ->assertSet('jenisKelamin', '');
});

test('dependent dropdown kelompok filtered by desa', function () {
    $user = User::factory()->create(['role' => Role::SuperAdmin]);
    $batam = \App\Models\desa::create(['desa_asal' => 'Dep Batam']);
    $ringRoad = \App\Models\desa::create(['desa_asal' => 'Dep Ring Road']);
    \App\Models\kelompok::create(['kelompok_asal' => 'B1', 'desa_id' => $batam->id]);
    \App\Models\kelompok::create(['kelompok_asal' => 'B2', 'desa_id' => $batam->id]);
    \App\Models\kelompok::create(['kelompok_asal' => 'R1', 'desa_id' => $ringRoad->id]);
    $this->actingAs($user);

    $component = Livewire::test(\App\Livewire\MasterData\Person\IndexPerson::class)
        ->set('desaId', (string) $batam->id);

    $component->assertSee('B1');
    $component->assertSee('B2');
    $component->assertDontSee('R1');
});
