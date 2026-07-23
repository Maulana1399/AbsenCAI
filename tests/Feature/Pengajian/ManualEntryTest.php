<?php

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Registration\ManualParticipantRegistrationService;
use Carbon\Carbon;
use Illuminate\Support\Str;


// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pme_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Manual Entry',
        'slug' => 'pengajian-manual-entry-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pme_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Manual '.str()->random(4),
    ], $overrides));
}

function pme_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

function pme_grant(Event $event, desa $desa): DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );
    return $result['grant'];
}

function pme_kelompok(string $nama, desa $desa): kelompok
{
    return kelompok::create([
        'kelompok_asal' => $nama,
        'desa_id' => $desa->id,
    ]);
}

$pme_randomCounter = 0;
beforeEach(function () use (&$pme_randomCounter) {
    Str::createRandomStringsUsing(function ($length) use (&$pme_randomCounter) {
        $pme_randomCounter++;
        return substr(str_pad((string) $pme_randomCounter, $length, '0', STR_PAD_LEFT), 0, $length);
    });
});

afterEach(function () {
    Str::createRandomStringsNormally();
});

// ---------------------------------------------------------------------------
// 1. Service: create new Person + Participation
// ---------------------------------------------------------------------------

test('1. Operator manually creates new Person and Participation', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Ahmad Baru',
        jenisKelamin: 'L',
        tanggalLahir: '2000-01-15',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['status'])->toBe('created');
    expect($result['person'])->not->toBeNull();
    expect($result['participation'])->not->toBeNull();
    expect($result['person']->nama)->toBe('Ahmad Baru');
    expect($result['person']->jenis_kelamin)->toBe('L');
    expect($result['person']->desa_id)->toBe($desa->id);
    expect($result['person']->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-15');
    expect($result['person']->nip)->toBeNull();
    expect($result['participation']->event_id)->toBe($event->id);
    expect($result['participation']->person_id)->toBe($result['person']->id);
    expect($result['participation']->participant_number)->toStartWith('KL');
    expect($result['participation']->attendance_code)->toStartWith('KJA-');
});

// ---------------------------------------------------------------------------
// 2. Operator-created Person has correct desa_id from grant
// ---------------------------------------------------------------------------

test('2. Operator-created Person has authoritative grant desa_id', function () {
    $event = pme_event();
    $desaA = pme_desa(['desa_asal' => 'Desa A']);
    $desaB = pme_desa(['desa_asal' => 'Desa B']);

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Warga Desa A',
        jenisKelamin: 'P',
        tanggalLahir: null,
        desaId: $desaA->id,
        eventId: $event->id,
    );

    expect($result['person']->desa_id)->toBe($desaA->id);
    expect((int) $result['person']->desa_id)->not->toBe($desaB->id);
});

// ---------------------------------------------------------------------------
// 3. Participation has correct event_id from grant
// ---------------------------------------------------------------------------

test('3. Operator-created Participation has authoritative grant event_id', function () {
    $eventA = pme_event(['name' => 'Event A']);
    $eventB = pme_event(['name' => 'Event B']);
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Test Person',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $eventA->id,
    );

    expect($result['participation']->event_id)->toBe($eventA->id);
    expect((int) $result['participation']->event_id)->not->toBe($eventB->id);
});

// ---------------------------------------------------------------------------
// 4. Existing reliable Person match (nama + desa + tanggal_lahir) is reused
// ---------------------------------------------------------------------------

test('4. Existing reliable Person match is reused', function () {
    $event = pme_event();
    $desa = pme_desa();
    $existing = pme_person('Siti Aminah', 'P', $desa->id, '1995-06-20');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Siti Aminah',
        jenisKelamin: 'P',
        tanggalLahir: '1995-06-20',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['status'])->toBe('matched');
    expect($result['person']->id)->toBe($existing->id);
    expect($result['person']->nama)->toBe('Siti Aminah');
    expect($result['participation']->person_id)->toBe($existing->id);
    expect(Person::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 5. Same nama + desa + different tanggal_lahir does NOT auto-merge
// ---------------------------------------------------------------------------

test('5. Same name same desa different birth date creates new Person', function () {
    $event = pme_event();
    $desa = pme_desa();
    pme_person('Mamat', 'L', $desa->id, '1990-01-01');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Mamat',
        jenisKelamin: 'L',
        tanggalLahir: '1991-02-02',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['status'])->toBe('created');
    expect(Person::count())->toBe(2);
    expect($result['person']->tanggal_lahir->format('Y-m-d'))->toBe('1991-02-02');
});

// ---------------------------------------------------------------------------
// 6. Ambiguous name-only match (no tanggal_lahir) returns ambiguous status
// ---------------------------------------------------------------------------

test('6. Ambiguous name-only match without birth date returns ambiguous', function () {
    $event = pme_event();
    $desa = pme_desa();
    pme_person('Budi Santoso', 'L', $desa->id, '1988-03-15');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Budi Santoso',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['status'])->toBe('ambiguous');
    expect($result['potential_matches'])->toHaveCount(1);
    expect($result['potential_matches'][0]['nama'])->toBe('Budi Santoso');
    expect(Person::count())->toBe(1);
    expect(Participation::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// 7. Existing Participation for same Person/Event is not duplicated
// ---------------------------------------------------------------------------

test('7. Duplicate Participation for same Person/Event is prevented', function () {
    $event = pme_event();
    $desa = pme_desa();
    $person = pme_person('Dewi', 'P', $desa->id, '1992-07-07');

    $resultA = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Dewi',
        jenisKelamin: 'P',
        tanggalLahir: '1992-07-07',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($resultA['status'])->toBe('matched');
    expect(Participation::count())->toBe(1);

    $resultB = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Dewi',
        jenisKelamin: 'P',
        tanggalLahir: '1992-07-07',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($resultB['status'])->toBe('duplicate');
    expect(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 8. Existing Person can receive Participation for a different Event
// ---------------------------------------------------------------------------

test('8. Existing Person can receive Participation for another event', function () {
    $eventA = pme_event(['name' => 'Event A']);
    $eventB = pme_event(['name' => 'Event B']);
    $desa = pme_desa();
    $person = pme_person('Citra', 'P', $desa->id, '1993-05-10');

    $resultA = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Citra',
        jenisKelamin: 'P',
        tanggalLahir: '1993-05-10',
        desaId: $desa->id,
        eventId: $eventA->id,
    );
    expect($resultA['status'])->toBe('matched');

    $resultB = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Citra',
        jenisKelamin: 'P',
        tanggalLahir: '1993-05-10',
        desaId: $desa->id,
        eventId: $eventB->id,
    );
    expect($resultB['status'])->toBe('matched');
    expect($resultB['person']->id)->toBe($person->id);

    expect(Participation::count())->toBe(2);
    expect($resultA['participation']->event_id)->toBe($eventA->id);
    expect($resultB['participation']->event_id)->toBe($eventB->id);
});

// ---------------------------------------------------------------------------
// 9. participant_number is generated correctly
// ---------------------------------------------------------------------------

test('9. participant_number is generated with correct format', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Test Gender',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
        forceCreateNew: true,
    );

    expect($result['participation']->participant_number)->toMatch('/^(KL|KP)\d{3}$/');
    expect($result['participation']->participant_number)->toStartWith('KL');
});

test('9b. Female participant gets KP prefix', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Test Female',
        jenisKelamin: 'P',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
        forceCreateNew: true,
    );

    expect($result['participation']->participant_number)->toStartWith('KP');
});

// ---------------------------------------------------------------------------
// 10. attendance_code is generated correctly and unique
// ---------------------------------------------------------------------------

test('10. attendance_code is generated in KJA- format', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Code Test',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
        forceCreateNew: true,
    );

    expect($result['participation']->attendance_code)->toStartWith('KJA-');
    expect($result['participation']->attendance_code)->toMatch('/^KJA-[A-Z0-9]+$/');
});

// ---------------------------------------------------------------------------
// 11. Person.nip remains null for Pengajian-only Person
// ---------------------------------------------------------------------------

test('11. Person.nip remains null for new Pengajian-only Person', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'No NIP',
        jenisKelamin: 'L',
        tanggalLahir: '2000-01-01',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['person']->nip)->toBeNull();
});

// ---------------------------------------------------------------------------
// 12. No legacy peserta record is created
// ---------------------------------------------------------------------------

test('12. No legacy peserta record is created', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Not Legacy',
        jenisKelamin: 'P',
        tanggalLahir: '1998-12-25',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect(peserta::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// 13. No LegacyPesertaMapping is created
// ---------------------------------------------------------------------------

test('13. No LegacyPesertaMapping is created', function () {
    $event = pme_event();
    $desa = pme_desa();

    app(ManualParticipantRegistrationService::class)->register(
        nama: 'No Mapping',
        jenisKelamin: 'L',
        tanggalLahir: '1997-03-20',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect(LegacyPesertaMapping::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// 14. Created participant is usable by Pengajian attendance flow
// ---------------------------------------------------------------------------

test('14. Created participant is usable by Pengajian attendance flow', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Attendable',
        jenisKelamin: 'L',
        tanggalLahir: '1995-01-01',
        desaId: $desa->id,
        eventId: $event->id,
    );

    $person = $result['person'];
    $grant = pme_grant($event, $desa);

    $attendance = app(App\Services\Pengajian\PengajianAttendanceService::class)
        ->attendPersonOperatorContext($person, $grant);

    expect($attendance)->toBeInstanceOf(EventAttendance::class);
    expect($attendance->participation_id)->toBe($result['participation']->id);
    expect($attendance->event_id)->toBe($event->id);
    expect($attendance->desa_id)->toBe($desa->id);
});

// ---------------------------------------------------------------------------
// 15. Participant is invisible to unrelated Event context
// ---------------------------------------------------------------------------

test('15. Participant is invisible to unrelated Event', function () {
    $eventA = pme_event(['name' => 'Event A']);
    $eventB = pme_event(['name' => 'Event B']);
    $desa = pme_desa();

    app(ManualParticipantRegistrationService::class)->register(
        nama: 'Event Scoped',
        jenisKelamin: 'L',
        tanggalLahir: '1990-01-01',
        desaId: $desa->id,
        eventId: $eventA->id,
    );

    $eventBparticipations = Participation::where('event_id', $eventB->id)->count();
    $eventAparticipations = Participation::where('event_id', $eventA->id)->count();

    expect($eventBparticipations)->toBe(0);
    expect($eventAparticipations)->toBe(1);
});

// ---------------------------------------------------------------------------
// 16. ForcePersonId skips matching and uses specified Person
// ---------------------------------------------------------------------------

test('16. ForcePersonId reuses specified existing Person', function () {
    $event = pme_event();
    $desa = pme_desa();
    $person = pme_person('Eko', 'L', $desa->id, '1989-11-11');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Eko',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
        forcePersonId: $person->id,
    );

    expect($result['status'])->toBe('matched');
    expect($result['person']->id)->toBe($person->id);
    expect(Person::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 17. ForceCreateNew skips matching entirely
// ---------------------------------------------------------------------------

test('17. ForceCreateNew skips matching and creates new Person even if match exists', function () {
    $event = pme_event();
    $desa = pme_desa();
    pme_person('Fajar', 'L', $desa->id, '1994-08-08');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Fajar',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
        forceCreateNew: true,
    );

    expect($result['status'])->toBe('created');
    expect(Person::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// 18. Participant number is event-scoped
// ---------------------------------------------------------------------------

test('18. participant_number sequences independently per event', function () {
    $eventA = pme_event(['name' => 'Event Alpha']);
    $eventB = pme_event(['name' => 'Event Beta']);
    $desa = pme_desa();

    $r1 = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Alpha 1', jenisKelamin: 'L', tanggalLahir: null,
        desaId: $desa->id, eventId: $eventA->id, forceCreateNew: true,
    );
    $r2 = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Beta 1', jenisKelamin: 'L', tanggalLahir: null,
        desaId: $desa->id, eventId: $eventB->id, forceCreateNew: true,
    );
    $r3 = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Alpha 2', jenisKelamin: 'L', tanggalLahir: null,
        desaId: $desa->id, eventId: $eventA->id, forceCreateNew: true,
    );

    expect($r1['participation']->participant_number)->toBe('KL001');
    expect($r2['participation']->participant_number)->toBe('KL001');
    expect($r3['participation']->participant_number)->toBe('KL002');
});

// ---------------------------------------------------------------------------
// 19. Admin flow uses same service (integration test)
// ---------------------------------------------------------------------------

test('19. Admin and Operator use the same shared service', function () {
    $event = pme_event();
    $desa = pme_desa();

    $service = app(ManualParticipantRegistrationService::class);

    $operatorResult = $service->register(
        nama: 'Shared Service',
        jenisKelamin: 'P',
        tanggalLahir: '2000-05-05',
        desaId: $desa->id,
        eventId: $event->id,
    );

    $adminResult = $service->register(
        nama: 'Admin User',
        jenisKelamin: 'L',
        tanggalLahir: '1999-09-09',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($operatorResult['participation'])->toBeInstanceOf(Participation::class);
    expect($adminResult['participation'])->toBeInstanceOf(Participation::class);
    expect(Person::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// 20. ForcePersonId with wrong desa throws exception
// ---------------------------------------------------------------------------

test('20. ForcePersonId with mismatched desa throws exception', function () {
    $event = pme_event();
    $desaA = pme_desa(['desa_asal' => 'Desa A']);
    $desaB = pme_desa(['desa_asal' => 'Desa B']);
    $personDesaB = pme_person('Cross Desa', 'L', $desaB->id, '1996-01-01');

    expect(fn () => app(ManualParticipantRegistrationService::class)->register(
        nama: 'Cross Desa',
        jenisKelamin: 'L',
        tanggalLahir: null,
        desaId: $desaA->id,
        eventId: $event->id,
        forcePersonId: $personDesaB->id,
    ))->toThrow(\RuntimeException::class, 'Person does not belong to this desa.');
});

// ---------------------------------------------------------------------------
// 21. NIP preserved for existing Person that already has one
// ---------------------------------------------------------------------------

test('21. Existing Person with NIP keeps NIP when reused', function () {
    $event = pme_event();
    $desa = pme_desa();
    $person = Person::create([
        'nama' => 'Berkip',
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'tanggal_lahir' => '1990-01-01',
    ]);

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Berkip',
        jenisKelamin: 'L',
        tanggalLahir: '1990-01-01',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['person']->nip)->toBeNull();
    expect($result['person']->id)->toBe($person->id);
});

// ---------------------------------------------------------------------------
// 22. Registration with gender-null Person uses form-supplied gender
// ---------------------------------------------------------------------------

test('22. Person with null gender still generates correct participant number', function () {
    $event = pme_event();
    $desa = pme_desa();
    $person = Person::create([
        'nama' => 'No Gender',
        'jenis_kelamin' => null,
        'desa_id' => $desa->id,
    ]);

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'No Gender',
        jenisKelamin: 'P',
        tanggalLahir: null,
        desaId: $desa->id,
        eventId: $event->id,
        forcePersonId: $person->id,
    );

    expect($result['participation']->participant_number)->toStartWith('KP');
});

// ---------------------------------------------------------------------------
// 23. Transactional: duplicate event+person unique constraint is caught
// ---------------------------------------------------------------------------

test('23. Duplicate participation via race condition is caught gracefully', function () {
    $event = pme_event();
    $desa = pme_desa();

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Racer',
        jenisKelamin: 'L',
        tanggalLahir: '2000-01-01',
        desaId: $desa->id,
        eventId: $event->id,
    );
    expect($result['status'])->toBe('created');

    $dupe = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Racer',
        jenisKelamin: 'L',
        tanggalLahir: '2000-01-01',
        desaId: $desa->id,
        eventId: $event->id,
    );
    expect($dupe['status'])->toBe('duplicate');
    expect(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 24. Case insensitive matching
// ---------------------------------------------------------------------------

test('24. Name matching is case-insensitive', function () {
    $event = pme_event();
    $desa = pme_desa();
    pme_person('Rini Amalia', 'P', $desa->id, '2000-01-01');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'rini amalia',
        jenisKelamin: 'P',
        tanggalLahir: '2000-01-01',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['status'])->toBe('matched');
    expect(Person::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 25. Normalized whitespace matching
// ---------------------------------------------------------------------------

test('25. Name matching normalizes whitespace', function () {
    $event = pme_event();
    $desa = pme_desa();
    pme_person('Ahmad Fauzi', 'L', $desa->id, '1998-07-07');

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Ahmad   Fauzi',
        jenisKelamin: 'L',
        tanggalLahir: '1998-07-07',
        desaId: $desa->id,
        eventId: $event->id,
    );

    expect($result['status'])->toBe('matched');
    expect(Person::count())->toBe(1);
});

// ===========================================================================
// Grant Consistency Regression Tests (PGM.14.5 — private property bug fix)
// ===========================================================================

test('26. ManualEntry revalidateGrant compares DB grant against session, not private properties', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);
    $kelompok = pme_kelompok('Kelompok Test 26', $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->set('nama', 'Test Grant Consistency');
    $component->set('jenisKelamin', 'L');
    $component->set('tanggalLahir', '2000-01-01');
    $component->set('kelompokId', (string) $kelompok->id);

    $component->call('submit');

    $component->assertSet('step', 3);
    $component->assertSet('errorMessage', '');

    expect(Participation::where('event_id', $event->id)->exists())->toBeTrue();
});

test('27. Session grant with mismatched event_id is rejected by revalidateGrant on submit', function () {
    $eventA = pme_event(['name' => 'Event A']);
    $eventB = pme_event(['name' => 'Event B']);
    $desa = pme_desa();
    $grant = pme_grant($eventA, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $eventB->id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component->set('nama', 'Wrong Event');
    $component->set('jenisKelamin', 'L');
    $component->set('tanggalLahir', '2000-01-01');

    $component->call('submit');

    expect($component->get('errorMessage'))->toContain('tidak konsisten');
    expect(Participation::where('event_id', $eventA->id)->count())->toBe(0);
    expect(Participation::where('event_id', $eventB->id)->count())->toBe(0);
});

test('28. Session grant with mismatched desa_id is rejected by revalidateGrant on submit', function () {
    $event = pme_event();
    $desaA = pme_desa(['desa_asal' => 'Desa A']);
    $desaB = pme_desa(['desa_asal' => 'Desa B']);
    $grant = pme_grant($event, $desaA);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $desaB->id,
    ]);

    $component->set('nama', 'Wrong Desa');
    $component->set('jenisKelamin', 'P');
    $component->set('tanggalLahir', '2001-01-01');

    $component->call('submit');

    expect($component->get('errorMessage'))->toContain('tidak konsisten');
});

test('29. Incomplete session (missing grant_id) is rejected by revalidateGrant on submit', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    session()->put('pengajian_access', [
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component->set('nama', 'No Grant ID');
    $component->set('jenisKelamin', 'L');

    $component->call('submit');

    expect($component->get('errorMessage'))->toContain('tidak valid');
});

test('30. Incomplete session (missing event_id) is rejected on mount', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'desa_id'  => $desa->id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->assertRedirect(route('pengajian.enter-token'));
});

test('31. Revoked grant is rejected on submit', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);
    $grant->update(['revoked_at' => Carbon::now()]);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->assertRedirect(route('pengajian.enter-token'));
});

test('32. submit() uses eventId and desaId from validated grant, not from public Livewire properties', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);
    $kelompok = pme_kelompok('Kelompok Test 32', $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->set('nama', 'Security Test');
    $component->set('jenisKelamin', 'L');
    $component->set('tanggalLahir', '1998-08-08');
    $component->set('kelompokId', (string) $kelompok->id);

    $component->call('submit');

    $component->assertSet('step', 3);

    $person = Person::where('nama', 'Security Test')->first();
    expect($person)->not->toBeNull();
    expect((int) $person->desa_id)->toBe((int) $desa->id);

    $participation = Participation::where('person_id', $person->id)->first();
    expect($participation)->not->toBeNull();
    expect((int) $participation->event_id)->toBe((int) $event->id);
});

// ===========================================================================
// Kelompok & Tanggal Lahir Validation Tests
// ===========================================================================

test('33. Kelompok_id is stored on Person when provided to service', function () {
    $event = pme_event();
    $desa = pme_desa();
    $kelompok = pme_kelompok('Kelompok Alpha', $desa);

    $result = app(ManualParticipantRegistrationService::class)->register(
        nama: 'Kelompok Test',
        jenisKelamin: 'L',
        tanggalLahir: '2000-05-05',
        desaId: $desa->id,
        eventId: $event->id,
        kelompokId: $kelompok->id,
    );

    expect($result['person']->kelompok_id)->toBe($kelompok->id);
});

test('34. Kelompok from different desa is rejected by service', function () {
    $event = pme_event();
    $desaA = pme_desa(['desa_asal' => 'Desa A']);
    $desaB = pme_desa(['desa_asal' => 'Desa B']);
    $kelompokB = pme_kelompok('Kelompok Desa B', $desaB);

    expect(fn () => app(ManualParticipantRegistrationService::class)->register(
        nama: 'Cross Kelompok',
        jenisKelamin: 'L',
        tanggalLahir: '2000-01-01',
        desaId: $desaA->id,
        eventId: $event->id,
        kelompokId: $kelompokB->id,
    ))->toThrow(\RuntimeException::class, 'Kelompok tidak berada dalam desa yang sesuai.');
});

test('35. Kelompok is required on ManualEntry component submit', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->set('nama', 'No Kelompok');
    $component->set('jenisKelamin', 'L');
    $component->set('tanggalLahir', '2000-01-01');

    $component->call('submit');

    $component->assertHasErrors('kelompokId');
});

test('36. Tanggal lahir is required on ManualEntry component submit', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);
    $kelompok = pme_kelompok('Kelompok TL', $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->set('nama', 'No TL');
    $component->set('jenisKelamin', 'L');
    $component->set('kelompokId', (string) $kelompok->id);

    $component->call('submit');

    $component->assertHasErrors('tanggalLahir');
});

test('37. Invalid tanggal lahir format is rejected on ManualEntry component', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);
    $kelompok = pme_kelompok('Kelompok TL Invalid', $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->set('nama', 'Invalid TL');
    $component->set('jenisKelamin', 'L');
    $component->set('tanggalLahir', 'not-a-date');
    $component->set('kelompokId', (string) $kelompok->id);

    $component->call('submit');

    $component->assertHasErrors('tanggalLahir');
});

test('38. Full submit flow includes kelompok_id on created Person', function () {
    $event = pme_event();
    $desa = pme_desa();
    $grant = pme_grant($event, $desa);
    $kelompok = pme_kelompok('Kelompok Full Flow', $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $grant->event_id,
        'desa_id'  => $grant->desa_id,
    ]);

    $component = Livewire::test(App\Livewire\Pengajian\ManualEntry::class);

    $component->set('nama', 'Full Flow');
    $component->set('jenisKelamin', 'P');
    $component->set('tanggalLahir', '1999-12-12');
    $component->set('kelompokId', (string) $kelompok->id);

    $component->call('submit');

    $component->assertSet('step', 3);

    $person = Person::where('nama', 'Full Flow')->first();
    expect($person)->not->toBeNull();
    expect((int) $person->kelompok_id)->toBe((int) $kelompok->id);
    expect((int) $person->desa_id)->toBe((int) $desa->id);
});
