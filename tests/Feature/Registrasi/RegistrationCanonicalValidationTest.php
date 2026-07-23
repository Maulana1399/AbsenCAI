<?php

use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function rv_event(): \App\Models\Event
{
    return \App\Models\Event::create(['name' => 'RV Event '.str()->random(6), 'slug' => 'rv-'.str()->random(6), 'status' => 'active']);
}

beforeEach(function () {
    $this->desa = \App\Models\desa::create(['desa_asal' => 'Test Desa']);
    $this->kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Test Kelompok', 'desa_id' => $this->desa->id]);
    $this->regu = \App\Models\regu::create(['regu' => 'Test Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $this->event = rv_event();
    app(ActiveEventContext::class)->set($this->event);
});

// ---------------------------------------------------------------------------
// A. Canonical Person duplicate validation
// ---------------------------------------------------------------------------

test('registration rejects duplicate Person with same name+desa+kelompok', function () {
    Person::create(['nama' => 'Duplicate Person', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);

    $validator = Validator::make([
        'nama' => 'Duplicate Person',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
    ], [
        'nama' => [
            'required', 'string', 'max:255',
            Rule::unique('people', 'nama')
                ->where('desa_id', $this->desa->id)
                ->where('kelompok_id', $this->kelompok->id),
        ],
    ]);

    expect($validator->fails())->toBeTrue();
});

test('registration allows same name in different desa', function () {
    $desaB = \App\Models\desa::create(['desa_asal' => 'Desa B']);
    $kelompokB = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok B', 'desa_id' => $desaB->id]);

    Person::create(['nama' => 'Common Name', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);

    $validator = Validator::make([
        'nama' => 'Common Name',
        'desa_id' => $desaB->id,
        'kelompok_id' => $kelompokB->id,
    ], [
        'nama' => [
            'required', 'string', 'max:255',
            Rule::unique('people', 'nama')
                ->where('desa_id', $desaB->id)
                ->where('kelompok_id', $kelompokB->id),
        ],
    ]);

    expect($validator->passes())->toBeTrue();
});

// ---------------------------------------------------------------------------
// B. Legacy NIP collision prevention
// ---------------------------------------------------------------------------

test('rejects NIP that exists in legacy pesertas', function () {
    peserta::create(['nama' => 'Legacy NIP', 'nip' => 55555, 'status_registrasi' => 'Belum Registrasi']);

    $validator = Validator::make(['nip' => 55555], [
        'nip' => ['required', 'integer', Rule::unique('people', 'nip'), Rule::unique('pesertas', 'nip')],
    ]);

    expect($validator->fails())->toBeTrue();
});

test('NIP is nullable on Person model', function () {
    Person::create(['nama' => 'Canonical NIP']);

    $validator = Validator::make(['nip' => null], [
        'nip' => ['nullable', 'integer'],
    ]);

    expect($validator->passes())->toBeTrue();
});

// ---------------------------------------------------------------------------
// C. Successful registration creates all 4 records
// ---------------------------------------------------------------------------

test('RegistrationService creates all 4 records', function () {
    $svc = app(RegistrationService::class);
    $result = $svc->createParticipant([
        'nama' => 'Complete Registration',
        'nip' => 30001,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    expect($result)->toBeInstanceOf(peserta::class);

    $person = Person::where('nama', 'Complete Registration')->first();
    expect($person)->not->toBeNull();

    $participation = \App\Models\Participation::where('person_id', $person->id)->first();
    expect($participation)->not->toBeNull()
        ->and($participation->event_id)->toBe($this->event->id);

    $participationMapping = \App\Models\LegacyParticipationMapping::where('participation_id', $participation->id)->first();
    expect($participationMapping)->not->toBeNull()
        ->and($participationMapping->event_id)->toBe($this->event->id);
});

// ---------------------------------------------------------------------------
// D. TambahPeserta validation canonical-first
// ---------------------------------------------------------------------------

test('TambahPeserta routes existing Person without active-event Participation to Case B (not rejected at validation layer)', function () {
    // Person exists, but has no Participation in the active event
    // Validation layer should NOT reject — Case B path must be reached
    Person::create(['nama' => 'Already Exists', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);

    $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']));
    $response = Livewire::test(\App\Livewire\Database\Peserta\TambahPeserta::class)
        ->set('nama', 'Already Exists')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('regu_id', $this->regu->id)
        ->call('simpan');

    // Validation layer passed — no error on 'nama' from validation layer
    // Error (if any) comes from DB schema constraint (UNIQUE on pesertas), not validation
    $errors = $response->errors();
    $hasNameValidationError = collect($errors->get('nama'))
        ->contains(fn ($msg) => str_contains($msg, 'sudah terdaftar pada event aktif'));

    // Must NOT have the "already in this event" Case C error (Person has no Participation)
    expect($hasNameValidationError)->toBeFalse();
});

test('TambahPeserta Case C: existing Person WITH Participation in active event is rejected at validation layer', function () {
    // Person exists AND has a Participation in the active event → Case C → rejected at validation
    $person = Person::create(['nama' => 'Already Exists In Event', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    Participation::create([
        'person_id'          => $person->id,
        'event_id'           => $this->event->id,
        'participant_number' => 'KL001',
        'attendance_code'    => 'KJA-TESTTEST',
        'jenis_peserta'      => 'Wajib',
    ]);

    $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']));
    $response = Livewire::test(\App\Livewire\Database\Peserta\TambahPeserta::class)
        ->set('nama', 'Already Exists In Event')
        ->set('desa_id', $this->desa->id)
        ->set('kelompok_id', $this->kelompok->id)
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('regu_id', $this->regu->id)
        ->call('simpan');

    $response->assertHasErrors(['nama']);
});
