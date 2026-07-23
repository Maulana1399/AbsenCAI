<?php

use App\Livewire\Database\Peserta\Database;
use App\Livewire\Database\Peserta\TambahPeserta;
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
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function epj_event(string $suffix): Event
{
    return Event::create([
        'name' => 'EPJ Event ' . $suffix,
        'slug' => 'epj-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function epj_fixtures(): array
{
    $desa = desa::create(['desa_asal' => 'EPJ Desa']);
    $kelompok = kelompok::create(['kelompok_asal' => 'EPJ Kelompok', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'EPJ Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = epj_event('a');
    $eventB = epj_event('b');

    $legacyPeserta = peserta::create([
        'nama' => 'EPJ Existing Person',
        'nip' => 95001,
        'participant_number' => 'KL950',
        'attendance_code' => 'KJA-EPJ001',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $person = Person::create([
        'nama' => 'EPJ Existing Person',
        'nip' => 95001,
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
    ]);

    $participationA = Participation::create([
        'person_id' => $person->id,
        'event_id' => $eventA->id,
        'participant_number' => 'KL951',
        'attendance_code' => 'KJA-EPJ-A01',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $legacyPeserta->id,
        'person_id' => $person->id,
        'legacy_nip' => 95001,
        'legacy_participant_number' => 'KL950',
        'legacy_attendance_code' => 'KJA-EPJ001',
        'migrated_at' => now(),
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $legacyPeserta->id,
        'person_id' => $person->id,
        'participation_id' => $participationA->id,
        'event_id' => $eventA->id,
        'migrated_at' => now(),
    ]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'eventB', 'legacyPeserta', 'person', 'participationA');
}

// ---------------------------------------------------------------------------
// 1. Happy path — existing Person joins Event B
// ---------------------------------------------------------------------------

test('existing Person in Event A can be added to Event B', function () {
    $f = epj_fixtures();
    $user = User::factory()->create(['role' => 'admin']);

    app(ActiveEventContext::class)->set($f['eventB']);

    $countPersonBefore = Person::count();
    $countPesertaBefore = peserta::count();
    $countParticipationBBefore = Participation::where('event_id', $f['eventB']->id)->count();
    $countParticipationABefore = Participation::where('event_id', $f['eventA']->id)->count();

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $f['person']->id)
        ->set('existingJenisPeserta', 'Wajib')
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    expect(Person::count())->toBe($countPersonBefore);
    expect(peserta::count())->toBe($countPesertaBefore);
    expect(Participation::where('event_id', $f['eventB']->id)->count())->toBe($countParticipationBBefore + 1);
    expect(Participation::where('event_id', $f['eventA']->id)->count())->toBe($countParticipationABefore);

    $newParticipation = Participation::where('person_id', $f['person']->id)
        ->where('event_id', $f['eventB']->id)
        ->first();
    expect($newParticipation)->not->toBeNull();
    expect($newParticipation->jenis_peserta)->toBe('Wajib');

    $mapping = LegacyParticipationMapping::where('person_id', $f['person']->id)
        ->where('event_id', $f['eventB']->id)
        ->first();
    expect($mapping)->not->toBeNull();
    expect($mapping->peserta_id)->toBe($f['legacyPeserta']->id);
    expect($mapping->participation_id)->toBe($newParticipation->id);
});

// ---------------------------------------------------------------------------
// 2. Duplicate prevention
// ---------------------------------------------------------------------------

test('same Person cannot be added twice to Event B', function () {
    $f = epj_fixtures();
    $user = User::factory()->create(['role' => 'admin']);

    app(ActiveEventContext::class)->set($f['eventB']);

    $participationB = Participation::create([
        'person_id' => $f['person']->id,
        'event_id' => $f['eventB']->id,
        'participant_number' => 'KL952',
        'attendance_code' => 'KJA-EPJ-B01',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $f['legacyPeserta']->id,
        'person_id' => $f['person']->id,
        'participation_id' => $participationB->id,
        'event_id' => $f['eventB']->id,
        'migrated_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $f['person']->id)
        ->call('tambahkanKeEvent')
        ->assertSet('errorMessage', 'Peserta ini sudah terdaftar pada event aktif.');

    expect(Participation::where('event_id', $f['eventB']->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 3. Event isolation
// ---------------------------------------------------------------------------

test('Event A participant list is unchanged after Person joins Event B', function () {
    $f = epj_fixtures();
    $user = User::factory()->create(['role' => 'admin']);

    app(ActiveEventContext::class)->set($f['eventB']);

    Livewire::actingAs($user)
        ->test(Database::class)
        ->assertDontSee('EPJ Existing Person');

    app(ActiveEventContext::class)->set($f['eventA']);

    Livewire::actingAs($user)
        ->test(Database::class)
        ->assertSee('EPJ Existing Person');

    app(ActiveEventContext::class)->set($f['eventB']);

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $f['person']->id)
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    app(ActiveEventContext::class)->set($f['eventB']);

    Livewire::actingAs($user)
        ->test(Database::class)
        ->assertSee('EPJ Existing Person');

    app(ActiveEventContext::class)->set($f['eventA']);

    Livewire::actingAs($user)
        ->test(Database::class)
        ->assertSee('EPJ Existing Person');
});

// ---------------------------------------------------------------------------
// 4. Missing active event fails safely
// ---------------------------------------------------------------------------

test('missing active event fails safely when adding existing Person', function () {
    $f = epj_fixtures();
    $user = User::factory()->create(['role' => 'admin']);

    app(ActiveEventContext::class)->clear();

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $f['person']->id)
        ->call('tambahkanKeEvent')
        ->assertSet('errorMessage', 'Tidak ada event aktif.');
});

// ---------------------------------------------------------------------------
// 5. Unauthorized role is rejected
// ---------------------------------------------------------------------------

test('unauthorized role is rejected when adding existing Person to Event B', function () {
    $f = epj_fixtures();
    $user = User::factory()->create(['role' => 'operator_registrasi']);

    app(ActiveEventContext::class)->set($f['eventB']);

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->call('selectPerson', $f['person']->id)
        ->call('tambahkanKeEvent')
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// 6. Search by supported identity fields
// ---------------------------------------------------------------------------

test('existing Person search works by nama and NIP', function () {
    $f = epj_fixtures();
    $user = User::factory()->create(['role' => 'admin']);

    $personB = Person::create([
        'nama' => 'EPJ Other Person',
        'nip' => 95002,
        'jenis_kelamin' => 'P',
        'desa_id' => $f['desa']->id,
        'kelompok_id' => $f['kelompok']->id,
    ]);

    app(ActiveEventContext::class)->set($f['eventB']);

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->set('searchPerson', 'EPJ Existing')
        ->assertSet('searchResults', function ($results) {
            expect(count($results))->toBeGreaterThanOrEqual(1);
            $names = array_column($results, 'nama');
            expect(in_array('EPJ Existing Person', $names))->toBeTrue();
            return true;
        });

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->set('searchPerson', '95001')
        ->assertSet('searchResults', function ($results) {
            expect(count($results))->toBeGreaterThanOrEqual(1);
            $nips = array_column($results, 'nip');
            expect(in_array(95001, $nips))->toBeTrue();
            return true;
        });
});

// ---------------------------------------------------------------------------
// 7. Duplicate name disambiguation
// ---------------------------------------------------------------------------

test('duplicate names can be disambiguated without automatic merge', function () {
    $desaA = desa::create(['desa_asal' => 'Disambiguate Desa A']);
    $desaB = desa::create(['desa_asal' => 'Disambiguate Desa B']);
    $kelompokA = kelompok::create(['kelompok_asal' => 'Disambiguate Kelompok A', 'desa_id' => $desaA->id]);
    $kelompokB = kelompok::create(['kelompok_asal' => 'Disambiguate Kelompok B', 'desa_id' => $desaB->id]);
    $regu = regu::create(['regu' => 'Disambiguate Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $sourceEventA = epj_event('disam-src-a');
    $sourceEventB = epj_event('disam-src-b');
    $targetEvent = epj_event('disam-tgt');

    $legacyPesertaA = peserta::create([
        'nama' => 'Common Name',
        'nip' => 96001,
        'participant_number' => 'KL960',
        'attendance_code' => 'KJA-DIS-A01',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $desaA->id,
        'kelompok_id' => $kelompokA->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $legacyPesertaB = peserta::create([
        'nama' => 'Common Name',
        'nip' => 96002,
        'participant_number' => 'KL961',
        'attendance_code' => 'KJA-DIS-B01',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $desaB->id,
        'kelompok_id' => $kelompokB->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $personA = Person::create([
        'nama' => 'Common Name',
        'nip' => 96001,
        'jenis_kelamin' => 'L',
        'desa_id' => $desaA->id,
        'kelompok_id' => $kelompokA->id,
    ]);

    $personB = Person::create([
        'nama' => 'Common Name',
        'nip' => 96002,
        'jenis_kelamin' => 'L',
        'desa_id' => $desaB->id,
        'kelompok_id' => $kelompokB->id,
    ]);

    $participationA = Participation::create([
        'person_id' => $personA->id,
        'event_id' => $sourceEventA->id,
        'participant_number' => 'KL962',
        'attendance_code' => 'KJA-DIS-A02',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);

    $participationB = Participation::create([
        'person_id' => $personB->id,
        'event_id' => $sourceEventB->id,
        'participant_number' => 'KL963',
        'attendance_code' => 'KJA-DIS-B02',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $legacyPesertaA->id,
        'person_id' => $personA->id,
        'legacy_nip' => 96001,
        'legacy_participant_number' => 'KL960',
        'legacy_attendance_code' => 'KJA-DIS-A01',
        'migrated_at' => now(),
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $legacyPesertaB->id,
        'person_id' => $personB->id,
        'legacy_nip' => 96002,
        'legacy_participant_number' => 'KL961',
        'legacy_attendance_code' => 'KJA-DIS-B01',
        'migrated_at' => now(),
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $legacyPesertaA->id,
        'person_id' => $personA->id,
        'participation_id' => $participationA->id,
        'event_id' => $sourceEventA->id,
        'migrated_at' => now(),
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $legacyPesertaB->id,
        'person_id' => $personB->id,
        'participation_id' => $participationB->id,
        'event_id' => $sourceEventB->id,
        'migrated_at' => now(),
    ]);

    $user = User::factory()->create(['role' => 'admin']);
    app(ActiveEventContext::class)->set($targetEvent);

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->set('searchPerson', 'Common Name')
        ->assertSet('searchResults', function ($results) use ($personA, $personB) {
            expect(count($results))->toBe(2);
            $ids = array_column($results, 'id');
            expect(in_array($personA->id, $ids))->toBeTrue();
            expect(in_array($personB->id, $ids))->toBeTrue();
            return true;
        });

    Livewire::actingAs($user)
        ->test(TambahPeserta::class)
        ->call('switchMode', 'existing')
        ->set('searchPerson', 'Common Name')
        ->call('selectPerson', $personA->id)
        ->call('tambahkanKeEvent')
        ->assertRedirect('/database');

    expect(Person::where('nama', 'Common Name')->count())->toBe(2);
    expect(Participation::where('person_id', $personA->id)->count())->toBe(2);
    expect(Participation::where('person_id', $personB->id)->count())->toBe(1);
    expect(Participation::where('event_id', $targetEvent->id)->count())->toBe(1);
});
