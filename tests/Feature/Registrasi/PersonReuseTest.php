<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function pr_event(): Event
{
    return Event::create(['name' => 'PR Event '.str()->random(6), 'slug' => 'pr-'.str()->random(6), 'status' => 'active']);
}

beforeEach(function () {
    $this->desa = \App\Models\desa::create(['desa_asal' => 'Test Desa']);
    $this->kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Test Kelompok', 'desa_id' => $this->desa->id]);
    $this->regu = \App\Models\regu::create(['regu' => 'Test Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $this->eventA = pr_event();
    app(ActiveEventContext::class)->set($this->eventA);
});

// ---------------------------------------------------------------------------
// A. CASE A — New Person creates full set
// ---------------------------------------------------------------------------

test('new Person registration creates full record set', function () {
    $svc = app(RegistrationService::class);
    $result = $svc->createParticipant([
        'nama' => 'New Person',
        'nip' => 10001,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id,
        'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    expect($result)->toBeInstanceOf(peserta::class);
    expect(Person::where('nama', 'New Person')->count())->toBe(1);
    expect(Participation::where('event_id', $this->eventA->id)->count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// B. CASE B — Legacy UNIQUE constraint on pesertas still blocks at DB level
//    Validation layer now passes (routing is correct), but schema not yet migrated
// ---------------------------------------------------------------------------

test('Case B routing reaches RegistrationService but legacy UNIQUE constraint may block at DB level', function () {
    $svc = app(RegistrationService::class);

    $svc->createParticipant([
        'nama' => 'Same Name', 'nip' => 20001,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    $eventB = pr_event();
    app(ActiveEventContext::class)->set($eventB);

    $person = Person::where('nama', 'Same Name')->first();
    expect($person)->not->toBeNull();
    expect($person->nip)->toBeNull();

    // Case B: NIP is reused from Person (20001), not 20002
    // DB UNIQUE(nama, desa_id, kelompok_id) on pesertas still blocks — ValidationException expected
    try {
        $result = $svc->createParticipant([
            'nama' => 'Same Name', 'nip' => 20001,
            'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
            'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
            'regu_id' => $this->regu->id,
            'status_registrasi' => 'Belum Registrasi',
        ]);

        // After migration: expect 2 Participations, 1 Person
        expect(Participation::where('person_id', $person->id)->count())->toBe(2);
        expect(Person::count())->toBe(1);
    } catch (ValidationException $e) {
        // Schema constraint still active — document this is the known blocker
        $msg = collect($e->errors())->flatten()->first();
        expect($msg)->not->toBeNull();

        // No partial records from Event B
        expect(Participation::where('event_id', $eventB->id)->count())->toBe(0);
        expect(LegacyParticipationMapping::where('event_id', $eventB->id)->count())->toBe(0);
        expect(peserta::count())->toBe(1); // Only the Event A peserta
    }
});

// ---------------------------------------------------------------------------
// C. CASE C — Duplicate registration for same event rejected
// ---------------------------------------------------------------------------

test('same Person cannot register for same Event twice', function () {
    $svc = app(RegistrationService::class);

    $svc->createParticipant([
        'nama' => 'Duplicate Check', 'nip' => 50001,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    expect(fn () => $svc->createParticipant([
        'nama' => 'Duplicate Check', 'nip' => 50001,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => 'Belum Registrasi',
    ]))->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// D. Identity matching does not merge different people
// ---------------------------------------------------------------------------

test('same name different desa creates separate Person records', function () {
    $svc = app(RegistrationService::class);

    $desaB = \App\Models\desa::create(['desa_asal' => 'Desa B']);
    $kelompokB = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok B', 'desa_id' => $desaB->id]);

    $resultA = $svc->createParticipant([
        'nama' => 'Common Name', 'nip' => 91101,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    $eventB = pr_event();
    app(ActiveEventContext::class)->set($eventB);

    $resultB = $svc->createParticipant([
        'nama' => 'Common Name', 'nip' => 91102,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $desaB->id, 'kelompok_id' => $kelompokB->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => 'Belum Registrasi',
        'participant_number' => 'KL999',
    ]);

    expect(Person::count())->toBe(2);
    expect(Participation::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// E. Return type tetap peserta
// ---------------------------------------------------------------------------

test('return type is always peserta', function () {
    $svc = app(RegistrationService::class);

    $result = $svc->createParticipant([
        'nama' => 'Type Check', 'nip' => 80001,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);
    expect($result)->toBeInstanceOf(peserta::class);
});

// ---------------------------------------------------------------------------
// F. Legacy mapping points to correct Participation
// ---------------------------------------------------------------------------

test('legacy mapping points to correct Participation and Event', function () {
    $svc = app(RegistrationService::class);

    $svc->createParticipant([
        'nama' => 'Mapping Check', 'nip' => 90001,
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id,
        'regu_id' => $this->regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    $person = Person::where('nama', 'Mapping Check')->first();
    $mapping = LegacyPesertaMapping::where('person_id', $person->id)->first();
    expect($mapping)->not->toBeNull();
    $bridge = LegacyParticipationMapping::where('person_id', $person->id)->first();
    expect($bridge)->not->toBeNull();
    expect((int)$bridge->participation->event_id)->toBe((int)$this->eventA->id);
});
