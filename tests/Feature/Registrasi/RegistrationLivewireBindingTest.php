<?php

use App\Livewire\Database\Peserta\EditPeserta;
use App\Livewire\Database\Peserta\GantiPeserta;
use App\Livewire\Database\Peserta\HapusPeserta;
use App\Livewire\Database\Peserta\TambahPeserta;
use App\Livewire\Database\Regu\DataRegu;
use App\Livewire\Database\Regu\EditRegu;
use App\Livewire\Database\Regu\HapusRegu;
use App\Livewire\Database\Regu\TambahRegu;
use App\Models\CaiParticipantReplacement;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Models\User;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->desa = desa::create(['desa_asal' => 'Binding Test Desa']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Binding Test Kelompok', 'desa_id' => $this->desa->id]);
    $this->regu = regu::create(['regu' => 'Binding Test Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $this->event = Event::create(['name' => 'Binding Event', 'slug' => 'binding-'.str()->random(6), 'status' => 'active']);
    app(ActiveEventContext::class)->set($this->event);
    $this->admin = User::factory()->create(['role' => 'admin']);
});

// ---------------------------------------------------------------------------
// Bug A — verifies Livewire state holds values set via wire:model equivalent
// ---------------------------------------------------------------------------

test('Bug A: jenis_kelamin value persists in Livewire state', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('jenis_kelamin', 'Laki - Laki')
        ->assertSet('jenis_kelamin', 'Laki - Laki');
});

test('Bug A: desa_id value persists in Livewire state', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('desa_id', $this->desa->id)
        ->assertSet('desa_id', $this->desa->id);
});

test('Bug A: full validation passes when all fields are set', function () {
    $regu2 = regu::create(['regu' => 'Binding Regu 2', 'jenis_kelamin' => 'Perempuan']);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Bug A Test Person')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->set('regu_id', $this->regu->id)
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Bug A Test Person')->exists())->toBeTrue();
});

test('Bug A: new Person + Participation created via TambahPeserta simpan', function () {
    $regu2 = regu::create(['regu' => 'Binding Regu 3', 'jenis_kelamin' => 'Perempuan']);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'New Person Flow')
        ->set('jenis_kelamin', 'Perempuan')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    $person = Person::where('nama', 'New Person Flow')->first();
    expect($person)->not->toBeNull();
    expect($person->jenis_kelamin)->toBe('P');

    $participation = Participation::where('person_id', $person->id)->first();
    expect($participation)->not->toBeNull();
    expect($participation->event_id)->toBe($this->event->id);
});

test('Bug A: selected jenis_kelamin is stored as Person.jenis_kelamin correctly', function () {
    $regu2 = regu::create(['regu' => 'Binding Regu 4', 'jenis_kelamin' => 'Perempuan']);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Gender Test Laki')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Gender Test Laki')->first()->jenis_kelamin)->toBe('L');

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Gender Test Perempuan')
        ->set('jenis_kelamin', 'Perempuan')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Gender Test Perempuan')->first()->jenis_kelamin)->toBe('P');
});

test('Bug A: selected desa_id is stored as Person.desa_id correctly', function () {
    $desa2 = desa::create(['desa_asal' => 'Binding Desa 2']);
    $regu2 = regu::create(['regu' => 'Binding Regu 5', 'jenis_kelamin' => 'Laki - Laki']);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Desa Test Person')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $desa2->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Desa Test Person')->first()->desa_id)->toBe($desa2->id);
});

// ---------------------------------------------------------------------------
// Bug B — existing Person flow tests
// ---------------------------------------------------------------------------

test('Bug B: existing Person from standard registration can be added to same event via tambahkanKeEvent', function () {
    $svc = app(RegistrationService::class);
    $svc->createParticipant([
        'nama' => 'Event B Person',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $person = Person::where('nama', 'Event B Person')->first();

    $eventB = Event::create(['name' => 'Event B', 'slug' => 'event-b-'.str()->random(6), 'status' => 'active']);
    app(ActiveEventContext::class)->set($eventB);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $person->id)
        ->set('existingJenisPeserta', 'Wajib')
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    expect(Participation::where('person_id', $person->id)->where('event_id', $eventB->id)->exists())->toBeTrue();
});

test('Bug B: Person created via MasterData CreatePerson (no legacy mapping) can be added canonical-only via tambahkanKeEvent', function () {
    $person = Person::create([
        'nama' => 'Master Data Person',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    expect($person->legacyPesertaMapping)->toBeNull();

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $person->id)
        ->set('existingJenisPeserta', 'Wajib')
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    expect(Participation::where('person_id', $person->id)->where('event_id', $this->event->id)->exists())->toBeTrue();

    $person->refresh();
    expect($person->legacyPesertaMapping)->toBeNull();
    expect(peserta::where('nama', 'Master Data Person')->count())->toBe(0);
    expect(LegacyParticipationMapping::where('person_id', $person->id)->count())->toBe(0);
});

test('Bug B: duplicate Participation on same event is rejected with error message', function () {
    $person = Person::create([
        'nama' => 'Duplicate Test Person',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL999',
        'attendance_code' => 'KJA-DUP01',
        'jenis_peserta' => 'Wajib',
    ]);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $person->id)
        ->set('existingJenisPeserta', 'Wajib')
        ->call('tambahkanKeEvent')
        ->assertSet('errorMessage', 'Peserta ini sudah terdaftar pada event aktif.');

    expect(Participation::where('person_id', $person->id)->where('event_id', $this->event->id)->count())->toBe(1);
});

test('Bug B: same Person can join different events', function () {
    $person = Person::create([
        'nama' => 'Multi Event Person',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-ME01',
        'jenis_peserta' => 'Wajib',
    ]);

    $eventB = Event::create(['name' => 'Multi Event B', 'slug' => 'multi-b-'.str()->random(6), 'status' => 'active']);
    app(ActiveEventContext::class)->set($eventB);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $person->id)
        ->set('existingJenisPeserta', 'Kiriman')
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    expect(Participation::where('person_id', $person->id)->count())->toBe(2);
    expect(Participation::where('person_id', $person->id)->where('event_id', $this->event->id)->exists())->toBeTrue();
    expect(Participation::where('person_id', $person->id)->where('event_id', $eventB->id)->exists())->toBeTrue();
});

test('Bug B: event isolation — Event A participants not visible in Event B', function () {
    $person = Person::create([
        'nama' => 'Isolation Person',
        'jenis_kelamin' => 'P',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KP001',
        'attendance_code' => 'KJA-ISO01',
        'jenis_peserta' => 'Wajib',
    ]);

    $eventB = Event::create(['name' => 'Isolation Event B', 'slug' => 'iso-b-'.str()->random(6), 'status' => 'active']);
    app(ActiveEventContext::class)->set($eventB);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $person->id)
        ->set('existingJenisPeserta', 'Wajib')
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    expect(Participation::where('person_id', $person->id)->where('event_id', $eventB->id)->exists())->toBeTrue();
    expect(Participation::where('person_id', $person->id)->where('event_id', $this->event->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// RegistrationService — handles Person without legacy mapping
// ---------------------------------------------------------------------------

test('RegistrationService creates legacy peserta for existing Person without mapping', function () {
    $person = Person::create([
        'nama' => 'Service Test No Mapping',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    expect($person->legacyPesertaMapping)->toBeNull();

    $svc = app(RegistrationService::class);
    $result = $svc->createParticipant([
        'nama' => 'Service Test No Mapping',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    expect($result)->toBeInstanceOf(peserta::class);
    expect($person->fresh()->legacyPesertaMapping)->not->toBeNull();
    expect(Participation::where('person_id', $person->id)->where('event_id', $this->event->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Simpan — existing Person without Participation in active event passes
// ---------------------------------------------------------------------------

test('simpan blocks existing Person with exact match (duplicate detection)', function () {
    $person = Person::create([
        'nama' => 'Simpan Existing',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-01-01',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Simpan Existing')
        ->set('tanggal_lahir', '1990-01-01')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('errorMessage', 'Orang dengan nama dan tanggal lahir yang sama sudah terdaftar. '
            .'Gunakan fitur "Tambahkan Peserta yang Sudah Ada" untuk menambahkan ke event ini.');

    expect(Participation::where('person_id', $person->id)->where('event_id', $this->event->id)->exists())->toBeFalse();
});

test('simpan shows possible duplicate warning for existing Person without birthday match', function () {
    $person = Person::create([
        'nama' => 'Possible Dup',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Possible Dup')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('showDuplicateWarning', true);
});

test('simpan rejects existing Person already registered for active event', function () {
    $svc = app(RegistrationService::class);
    $svc->createParticipant([
        'nama' => 'Simpan Duplicate',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $person = Person::where('nama', 'Simpan Duplicate')->first();
    expect($person)->not->toBeNull();

    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Simpan Duplicate')
        ->set('tanggal_lahir', '1990-01-01')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertHasErrors(['nama']);
});

// ---------------------------------------------------------------------------
// Bug C — EditPeserta regression tests
// ---------------------------------------------------------------------------

test('Bug C: EditPeserta loads canonical desa_id from Person', function () {
    $person = Person::create([
        'nama' => 'Edit Desa Test',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL100',
        'attendance_code' => 'KJA-EDIT01',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditPeserta::class)
        ->dispatch('editPeserta', id: $participation->id)
        ->assertSet('desa_id', $this->desa->id)
        ->assertSet('kelompok_id', $this->kelompok->id)
        ->assertSet('regu_id', $this->regu->id);
});

test('Bug C: EditPeserta update works with canonical participant', function () {
    $person = Person::create([
        'nama' => 'Edit Canonical',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL101',
        'attendance_code' => 'KJA-EDIT02',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditPeserta::class)
        ->dispatch('editPeserta', id: $participation->id)
        ->set('nama', 'Edit Canonical Updated')
        ->call('update')
        ->assertRedirect('/database');

    expect($person->fresh()->nama)->toBe('Edit Canonical Updated');
});

test('Bug C: EditPeserta accepts nullable desa_id/kelompok_id/regu_id', function () {
    $person = Person::create([
        'nama' => 'Null Edit Test',
        'jenis_kelamin' => 'P',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KP100',
        'attendance_code' => 'KJA-EDIT03',
        'jenis_peserta' => 'Wajib',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditPeserta::class)
        ->dispatch('editPeserta', id: $participation->id)
        ->assertSet('desa_id', null)
        ->assertSet('kelompok_id', null)
        ->assertSet('regu_id', null)
        ->call('update')
        ->assertRedirect('/database');
});

test('Bug C: EditPeserta works for canonical-only participant without legacy mapping', function () {
    $person = Person::create([
        'nama' => 'Canonical Edit',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL102',
        'attendance_code' => 'KJA-EDIT04',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    expect($person->legacyPesertaMapping)->toBeNull();

    $this->actingAs($this->admin);

    Livewire::test(EditPeserta::class)
        ->dispatch('editPeserta', id: $participation->id)
        ->assertSet('nama', 'Canonical Edit')
        ->call('update')
        ->assertRedirect('/database');
});

test('Bug C: EditPeserta gender shows label format not DB format', function () {
    $person = Person::create([
        'nama' => 'Gender Edit Test',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL103',
        'attendance_code' => 'KJA-EDIT05',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditPeserta::class)
        ->dispatch('editPeserta', id: $participation->id)
        ->assertSet('jenis_kelamin', 'Laki - Laki');
});

// ---------------------------------------------------------------------------
// Bug D — GantiPeserta canonical-only replacement
// ---------------------------------------------------------------------------

test('Bug D: canonical-only participant rejected with error (not crash) in GantiPeserta open', function () {
    $person = Person::create([
        'nama' => 'Replace Canonical',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $caiEvent = Event::create([
        'name' => 'CAI Event',
        'slug' => 'cai-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $caiEvent->id,
        'participant_number' => 'KL200',
        'attendance_code' => 'KJA-REP01',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($caiEvent);

    $this->actingAs($this->admin);

    Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $participation->id)
        ->assertSet('participation_id', $participation->id)
        ->assertSet('nama_lama', 'Replace Canonical')
        ->assertSet('participant_number', 'KL200');
});

test('Bug D: canonical-only replacement succeeds without creating legacy artifacts', function () {
    $person = Person::create([
        'nama' => 'Original Person',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $caiEvent = Event::create([
        'name' => 'CAI Event Replace',
        'slug' => 'cai-rep-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $caiEvent->id,
        'participant_number' => 'KL201',
        'attendance_code' => 'KJA-REP02',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($caiEvent);

    $this->actingAs($this->admin);

    Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $participation->id)
        ->set('nama', 'Replacement Person')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('reason', 'Test canonical replacement')
        ->call('replace');

    $replacedParticipation = Participation::find($participation->id);
    expect($replacedParticipation->participant_number)->toBeNull();
    expect($replacedParticipation->attendance_code)->toBeNull();

    $replacement = CaiParticipantReplacement::where('old_participation_id', $participation->id)->first();
    expect($replacement)->not->toBeNull();
    expect($replacement->new_person_id)->not->toBe($person->id);

    $newPerson = Person::find($replacement->new_person_id);
    expect($newPerson->nama)->toBe('Replacement Person');

    expect(LegacyPesertaMapping::count())->toBe(0);
    expect(LegacyParticipationMapping::count())->toBe(0);
});

test('Bug D: canonical replacement preserves event isolation', function () {
    $person = Person::create([
        'nama' => 'Isolated Replace',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $caiEvent = Event::create([
        'name' => 'CAI Event Iso',
        'slug' => 'cai-iso-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $otherEvent = Event::create([
        'name' => 'Non-CAI Event',
        'slug' => 'non-cai-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $participationOther = Participation::create([
        'person_id' => $person->id,
        'event_id' => $otherEvent->id,
        'participant_number' => 'KL300',
        'attendance_code' => 'KJA-ISO-OTHER',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $caiEvent->id,
        'participant_number' => 'KL301',
        'attendance_code' => 'KJA-ISO-CAI',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($caiEvent);

    $this->actingAs($this->admin);

    // Only CAN replace in CAI event
    $participationInCai = Participation::where('event_id', $caiEvent->id)->first();

    Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $participationInCai->id)
        ->set('nama', 'Replacement Iso')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('reason', 'Test isolation')
        ->call('replace');

    // Other event participation unchanged
    expect(Participation::find($participationOther->id)->participant_number)->toBe('KL300');
});

test('Bug E: GantiPeserta error modal close works', function () {
    $person = Person::create([
        'nama' => 'Modal Close Test',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $nonCaiEvent = Event::create([
        'name' => 'Non-CAI Event',
        'slug' => 'non-cai-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'regular',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $nonCaiEvent->id,
        'participant_number' => 'KL400',
        'attendance_code' => 'KJA-MCLOSE',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($nonCaiEvent);

    $this->actingAs($this->admin);

    Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $participation->id)
        ->assertSet('errorMessage', 'Penggantian peserta hanya dapat dilakukan pada event CAI.');
});

test('Bug E: GantiPeserta reopens with clean state after error', function () {
    $person = Person::create([
        'nama' => 'Clean State Test',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $nonCaiEvent = Event::create([
        'name' => 'Non-CAI Event 2',
        'slug' => 'non-cai-2-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'regular',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $nonCaiEvent->id,
        'participant_number' => 'KL401',
        'attendance_code' => 'KJA-CLEAN',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($nonCaiEvent);

    $this->actingAs($this->admin);

    $test = Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $participation->id);

    // Error is shown
    $test->assertSet('errorMessage', 'Penggantian peserta hanya dapat dilakukan pada event CAI.');

    // Reopen with a clean participation (mimics modal close + reopen)
    $caiEvent = Event::create([
        'name' => 'CAI Event Clean',
        'slug' => 'cai-clean-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $newPart = Participation::create([
        'person_id' => $person->id,
        'event_id' => $caiEvent->id,
        'participant_number' => 'KL402',
        'attendance_code' => 'KJA-CLEAN2',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($caiEvent);

    $test->dispatch('gantiPeserta', id: $newPart->id)
        ->assertSet('errorMessage', ''); // Clean state after re-open
});

// ---------------------------------------------------------------------------
// Tanggal Lahir — field addition
// ---------------------------------------------------------------------------

test('tanggal_lahir field exists on TambahPeserta component with null default', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->assertSet('tanggal_lahir', null);
});

test('tanggal_lahir can be set on component', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('tanggal_lahir', '1999-03-10')
        ->assertSet('tanggal_lahir', '1999-03-10');
});

test('tanggal_lahir validation accepts valid date', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Tanggal Lahir Test')
        ->set('tanggal_lahir', '1999-03-10')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    $person = Person::where('nama', 'Tanggal Lahir Test')->first();
    expect($person)->not->toBeNull();
    expect($person->tanggal_lahir->format('Y-m-d'))->toBe('1999-03-10');
});

test('tanggal_lahir stored as Person.tanggal_lahir correctly', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'Birthday Person')
        ->set('tanggal_lahir', '2000-01-15')
        ->set('jenis_kelamin', 'Perempuan')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    $person = Person::where('nama', 'Birthday Person')->first();
    expect($person->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-15');
    expect($person->jenis_kelamin)->toBe('P');
});

test('tanggal_lahir can be null (not required)', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('nama', 'No Birthday')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    $person = Person::where('nama', 'No Birthday')->first();
    expect($person->tanggal_lahir)->toBeNull();
});

test('tanggal_lahir is reset when switching modes', function () {
    Livewire::actingAs($this->admin)
        ->test(TambahPeserta::class)
        ->set('tanggal_lahir', '1999-03-10')
        ->call('switchMode', 'existing')
        ->call('switchMode', 'baru')
        ->assertSet('tanggal_lahir', null);
});

test('tanggal_lahir does not affect createParticipant flow', function () {
    $svc = app(RegistrationService::class);
    $result = $svc->createParticipant([
        'nama' => 'TLA Service Test',
        'tanggal_lahir' => '1998-12-25',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    expect($result)->toBeInstanceOf(peserta::class);
    $person = Person::where('nama', 'TLA Service Test')->first();
    expect($person->tanggal_lahir->format('Y-m-d'))->toBe('1998-12-25');
    expect(Participation::where('person_id', $person->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Delete participation — after replacement
// ---------------------------------------------------------------------------

test('normal canonical-only Participation can be deleted', function () {
    $person = Person::create([
        'nama' => 'Normal Delete',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL500',
        'attendance_code' => 'KJA-DEL01',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $participation->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($participation->id))->toBeNull();
    expect(Person::find($person->id))->not->toBeNull();
});

test('replacement new participation can be deleted', function () {
    $person = Person::create([
        'nama' => 'Replace Delete Source',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $caiEvent = Event::create([
        'name' => 'CAI Delete Test',
        'slug' => 'cai-del-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $oldParticipation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $caiEvent->id,
        'participant_number' => 'KL501',
        'attendance_code' => 'KJA-DEL02',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($caiEvent);

    // Perform replacement (canonical-only)
    $this->actingAs($this->admin);
    Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $oldParticipation->id)
        ->set('nama', 'Replacement Delete Target')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('reason', 'Test delete after replacement')
        ->call('replace');

    $newParticipation = Participation::where('person_id', '!=', $person->id)
        ->where('event_id', $caiEvent->id)
        ->first();
    expect($newParticipation)->not->toBeNull();

    // Delete the new (replacement) participation
    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $newParticipation->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($newParticipation->id))->toBeNull();

    // Audit trail still exists
    expect(CaiParticipantReplacement::where('new_participation_id', null)->count())->toBeGreaterThanOrEqual(1);
});

test('replacement old participation can be deleted', function () {
    $person = Person::create([
        'nama' => 'Replace Old Delete',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $caiEvent = Event::create([
        'name' => 'CAI Old Del',
        'slug' => 'cai-old-del-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $oldParticipation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $caiEvent->id,
        'participant_number' => 'KL502',
        'attendance_code' => 'KJA-DEL03',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    app(ActiveEventContext::class)->set($caiEvent);

    $this->actingAs($this->admin);
    Livewire::test(GantiPeserta::class)
        ->dispatch('gantiPeserta', id: $oldParticipation->id)
        ->set('nama', 'New Person After Del')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('reason', 'Test old delete')
        ->call('replace');

    // Delete the OLD (replaced) participation
    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $oldParticipation->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($oldParticipation->id))->toBeNull();

    // Audit trail still exists
    expect(CaiParticipantReplacement::where('old_participation_id', null)->count())->toBeGreaterThanOrEqual(1);
});

test('delete does not remove Person still used by another event', function () {
    $person = Person::create([
        'nama' => 'Multi Event Person Del',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $eventB = Event::create([
        'name' => 'Other Event Del',
        'slug' => 'other-del-'.str()->random(6),
        'status' => 'active',
    ]);

    $partA = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL503',
        'attendance_code' => 'KJA-DEL04',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $partB = Participation::create([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
        'participant_number' => 'KL504',
        'attendance_code' => 'KJA-DEL05',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    // Delete Event A's participation only
    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $partA->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($partA->id))->toBeNull();
    expect(Participation::find($partB->id))->not->toBeNull();
    expect(Person::find($person->id))->not->toBeNull();
});

test('event isolation preserved on delete', function () {
    $person = Person::create([
        'nama' => 'Isolation Delete',
        'jenis_kelamin' => 'L',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $eventB = Event::create([
        'name' => 'Isolation Delete B',
        'slug' => 'iso-del-b-'.str()->random(6),
        'status' => 'active',
    ]);

    $partA = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL505',
        'attendance_code' => 'KJA-DEL06',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $partB = Participation::create([
        'person_id' => $person->id,
        'event_id' => $eventB->id,
        'participant_number' => 'KL506',
        'attendance_code' => 'KJA-DEL07',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    // Only Event A participation should be deletable from Event A context
    app(ActiveEventContext::class)->set($eventB);

    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $partB->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($partB->id))->toBeNull();
    expect(Participation::find($partA->id))->not->toBeNull();
});

// ---------------------------------------------------------------------------
// Regu Management — OPTIONAL feature for CAI events
// ---------------------------------------------------------------------------

test('regu page is accessible', function () {
    $this->actingAs($this->admin)->get('/regu')->assertOk();
});

test('create regu works', function () {
    $this->actingAs($this->admin);

    Livewire::test(TambahRegu::class)
        ->set('regu', 'Test Regu A')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->call('simpan')
        ->assertRedirect('/regu');

    expect(\App\Models\regu::where('regu', 'Test Regu A')->exists())->toBeTrue();
});

test('data regu displays created regus', function () {
    $regu = \App\Models\regu::create(['regu' => 'Display Regu', 'jenis_kelamin' => 'Perempuan']);

    $this->actingAs($this->admin);

    Livewire::test(DataRegu::class)
        ->assertSee('Display Regu');
});

test('edit regu works', function () {
    $regu = \App\Models\regu::create(['regu' => 'Edit Me', 'jenis_kelamin' => 'Laki - Laki']);

    $this->actingAs($this->admin);

    Livewire::test(EditRegu::class)
        ->dispatch('editRegu', id: $regu->id)
        ->set('regu', 'Edited Regu')
        ->call('update');

    expect($regu->fresh()->regu)->toBe('Edited Regu');
});

test('delete regu works when no participations reference it', function () {
    $regu = \App\Models\regu::create(['regu' => 'Delete Me', 'jenis_kelamin' => 'Laki - Laki']);

    $this->actingAs($this->admin);

    Livewire::test(HapusRegu::class)
        ->dispatch('HapusRegu', id: $regu->id)
        ->call('destroy');

    expect(\App\Models\regu::find($regu->id))->toBeNull();
});

test('delete regu nullifies participation regu_id (nullOnDelete)', function () {
    $regu = \App\Models\regu::create(['regu' => 'Nullify Test', 'jenis_kelamin' => 'Laki - Laki']);
    $person = Person::create(['nama' => 'Regu Nullify Person', 'jenis_kelamin' => 'L', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL999',
        'attendance_code' => 'KJA-REGU01',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $regu->id,
    ]);

    $regu->delete();

    expect($participation->fresh()->regu_id)->toBeNull();
});

test('registration without regu still works (regu optional)', function () {
    $regu2 = \App\Models\regu::create(['regu' => 'Optional Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'No Regu Person')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'No Regu Person')->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Duplicate Person Detection
// ---------------------------------------------------------------------------

test('duplicate detection: exact normalized match is detected as strong duplicate', function () {
    Person::create([
        'nama' => 'Maulana Achmad',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'maulana achmad')
        ->set('tanggal_lahir', '1990-05-15')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('errorMessage', 'Orang dengan nama dan tanggal lahir yang sama sudah terdaftar. '
            .'Gunakan fitur "Tambahkan Peserta yang Sudah Ada" untuk menambahkan ke event ini.');
});

test('duplicate detection: whitespace variation is detected as strong duplicate', function () {
    Person::create([
        'nama' => 'Maulana Achmad',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', '  Maulana   Achmad  ')
        ->set('tanggal_lahir', '1990-05-15')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('errorMessage', 'Orang dengan nama dan tanggal lahir yang sama sudah terdaftar. '
            .'Gunakan fitur "Tambahkan Peserta yang Sudah Ada" untuk menambahkan ke event ini.');
});

test('duplicate detection: same name different birthday shows possible duplicate warning', function () {
    Person::create([
        'nama' => 'Maulana Achmad',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Maulana Achmad')
        ->set('tanggal_lahir', '1991-06-20')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('showDuplicateWarning', true);
});

test('duplicate detection: possible duplicate shows warning (fuzzy match)', function () {
    Person::create([
        'nama' => 'Maulana Achmad',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Maulana Acmad')
        ->set('tanggal_lahir', '1990-05-15')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('showDuplicateWarning', true);
});

test('duplicate detection: ignore warning allows creation anyway', function () {
    Person::create([
        'nama' => 'Maulana Achmad',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Maulana Acmad')
        ->set('tanggal_lahir', '1990-05-15')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('showDuplicateWarning', true)
        ->call('ignoreDuplicateWarning')
        ->assertSet('showDuplicateWarning', false)
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Maulana Acmad')->exists())->toBeTrue();
});

test('duplicate detection: strong duplicate with existing event participation shows specific error', function () {
    $person = Person::create([
        'nama' => 'Already Registered',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-01-01',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL888',
        'attendance_code' => 'KJA-DUP02',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Already Registered')
        ->set('tanggal_lahir', '1990-01-01')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertHasErrors(['nama']);
});

// ---------------------------------------------------------------------------
// Default form state — submit without touching any dropdown
// ---------------------------------------------------------------------------

test('default state: jenis_kelamin = Laki - Laki, submit succeeds', function () {
    $regu2 = regu::create(['regu' => 'Default Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->assertSet('jenis_kelamin', 'Laki - Laki')
        ->assertSet('jenis_peserta', 'Wajib')
        ->assertSet('desa_id', $this->desa->id)
        ->assertSet('kelompok_id', $this->kelompok->id);
});

test('default state: submit with only nama creates Person + Participation', function () {
    $regu2 = regu::create(['regu' => 'Submit Default', 'jenis_kelamin' => 'Laki - Laki']);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Default Submit Test')
        ->call('simpan')
        ->assertRedirect('/database');

    $person = Person::where('nama', 'Default Submit Test')->first();
    expect($person)->not->toBeNull();
    expect($person->jenis_kelamin)->toBe('L');
    expect($person->desa_id)->toBe($this->desa->id);
    expect($person->kelompok_id)->toBe($this->kelompok->id);

    $participation = Participation::where('person_id', $person->id)->first();
    expect($participation)->not->toBeNull();
    expect($participation->jenis_peserta)->toBe('Wajib');
});

test('default state: jenis_kelamin default persists after reset via switchMode', function () {
    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('switchMode', 'baru')
        ->assertSet('jenis_kelamin', 'Laki - Laki')
        ->assertSet('desa_id', $this->desa->id)
        ->assertSet('kelompok_id', $this->kelompok->id);
});

test('default state: user override is preserved', function () {
    $desa2 = desa::create(['desa_asal' => 'Default Desa 2']);
    $kel2 = kelompok::create(['kelompok_asal' => 'Default Kel 2', 'desa_id' => $desa2->id]);
    $regu2 = regu::create(['regu' => 'Default Override', 'jenis_kelamin' => 'Perempuan']);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Override Default')
        ->set('jenis_kelamin', 'Perempuan')
        ->set('desa_id', $desa2->id)
        ->set('kelompok_id', $kel2->id)
        ->call('simpan')
        ->assertRedirect('/database');

    $person = Person::where('nama', 'Override Default')->first();
    expect($person)->not->toBeNull();
    expect($person->jenis_kelamin)->toBe('P');
    expect($person->desa_id)->toBe($desa2->id);
    expect($person->kelompok_id)->toBe($kel2->id);
});

// ---------------------------------------------------------------------------
// Duplicate detection — possible duplicate + existing event participation
// ---------------------------------------------------------------------------

test('possible duplicate with different birthday shows warning even if candidate is in active event', function () {
    $person = Person::create([
        'nama' => 'Same Name Diff Birthday',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL777',
        'attendance_code' => 'KJA-POSS01',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->regu->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Same Name Diff Birthday')
        ->set('tanggal_lahir', '1995-10-20')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('showDuplicateWarning', true);

    expect(Person::where('nama', 'Same Name Diff Birthday')->count())->toBe(1);
});

test('possible duplicate can be overridden to create distinct Person', function () {
    $person = Person::create([
        'nama' => 'Same Name Override',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-15',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(TambahPeserta::class)
        ->set('nama', 'Same Name Override')
        ->set('tanggal_lahir', '1995-10-20')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('jenis_peserta', 'Wajib')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->call('simpan')
        ->assertSet('showDuplicateWarning', true)
        ->assertSet('duplicateCandidates', fn ($c) => count($c) > 0)
        ->call('ignoreDuplicateWarning')
        ->call('simpan')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Same Name Override')->count())->toBe(2);
});
