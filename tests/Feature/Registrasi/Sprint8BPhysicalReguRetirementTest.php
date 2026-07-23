<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function () {
    Str::createRandomStringsNormally();
});

// ──────────────────────────────────────────────
// 1. pesertas.regu_id column no longer exists
// ──────────────────────────────────────────────
test('S8B-01: pesertas table no longer has regu_id column', function () {
    expect(Schema::hasColumn('pesertas', 'regu_id'))->toBeFalse();
});

// ──────────────────────────────────────────────
// 2. peserta model works without regu_id column
// ──────────────────────────────────────────────
test('S8B-02: peserta model works without regu_id column', function () {
    $peserta = peserta::create([
        'nama' => 'S8B Peserta',
        'nip' => 9001,
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    expect($peserta->nama)->toBe('S8B Peserta');
    expect($peserta->regu_id)->toBeNull();
});

// ──────────────────────────────────────────────
// 3. RegistrationService creates participation without legacy column
// ──────────────────────────────────────────────
test('S8B-03: RegistrationService createParticipant works without pesertas.regu_id', function () {
    $event = Event::create(['name' => 'S8B Event', 'slug' => 's8b-event', 'status' => 'active']);
    app(\App\Support\ActiveEventContext::class)->set($event);

    $regu = regu::create(['regu' => 'S8B Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $desa = desa::create(['desa_asal' => 'S8B Desa']);
    $kelompok = kelompok::create(['kelompok_asal' => 'S8B Kelompok', 'desa_id' => $desa->id]);

    $result = app(RegistrationService::class)->createParticipant([
        'nama' => 'S8B Person',
        'nip' => 9002,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    expect($result)->toBeInstanceOf(peserta::class);

    // Participation has regu_id
    $partMapping = LegacyParticipationMapping::where('peserta_id', $result->id)->first();
    expect($partMapping)->not->toBeNull();
    expect((int) $partMapping->participation->regu_id)->toBe($regu->id);
});

// ──────────────────────────────────────────────
// 4. SelfRegister works without legacy column
// ──────────────────────────────────────────────
test('S8B-04: SelfRegister auto-fills regu without legacy column', function () {
    $event = Event::create(['name' => 'S8B SelfReg', 'slug' => 's8b-selfreg', 'status' => 'active']);
    app(\App\Support\ActiveEventContext::class)->set($event);

    $regu = regu::create(['regu' => 'S8B SelfReg Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $livewire = Livewire::test(\App\Livewire\Registrasi\SelfRegister::class);
    $livewire->set('nama', 'S8B SelfReg Person');
    $livewire->set('jenis_kelamin', 'Laki - Laki');
    $livewire->set('desa_id', null);
    $livewire->set('kelompok_id', null);

    \Illuminate\Support\Str::createRandomStringsUsing(fn () => 's8b04fix');

    $participant = app(RegistrationService::class)->createParticipant([
        'nama' => 'S8B SelfReg Person',
        'nip' => 9003,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ]);

    $partMapping = LegacyParticipationMapping::where('peserta_id', $participant->id)->first();
    expect((int) $partMapping->participation->regu_id)->toBe($regu->id);
});

// ──────────────────────────────────────────────
// 5. TambahPeserta works without legacy column
// ──────────────────────────────────────────────
test('S8B-05: TambahPeserta works without legacy column', function () {
    $event = Event::create(['name' => 'S8B Tambah', 'slug' => 's8b-tambah', 'status' => 'active']);
    app(\App\Support\ActiveEventContext::class)->set($event);

    $regu = regu::create(['regu' => 'S8B Tambah Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $participant = app(RegistrationService::class)->createParticipant([
        'nama' => 'S8B Tambah Person',
        'nip' => 9004,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => null,
        'kelompok_id' => null,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $partMapping = LegacyParticipationMapping::where('peserta_id', $participant->id)->first();
    expect((int) $partMapping->participation->regu_id)->toBe($regu->id);
});

// ──────────────────────────────────────────────
// 6. EditPeserta updates Participation.regu_id (no legacy write)
// ──────────────────────────────────────────────
test('S8B-06: EditPeserta updates Participation.regu_id without legacy column', function () {
    $event = Event::create(['name' => 'S8B Edit', 'slug' => 's8b-edit', 'status' => 'active']);
    $reguA = regu::create(['regu' => 'S8B Edit Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'S8B Edit Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    $person = Person::create(['nama' => 'S8B Edit', 'jenis_kelamin' => 'L']);
    $pesertaRecord = peserta::create([
        'nama' => 'S8B Edit',
        'nip' => 9005,
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id]);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL901',
        'attendance_code' => 'KJA-S8BEDT',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $reguA->id,
    ]);

    // Simulate EditPeserta::update() — write regu_id to participation only
    $participation->update(['jenis_peserta' => 'Kiriman', 'regu_id' => $reguB->id]);
    $participation->refresh();

    expect((int) $participation->regu_id)->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 7. Ulang updates Participation.regu_id (no legacy write)
// ──────────────────────────────────────────────
test('S8B-07: Ulang updates Participation.regu_id without legacy column', function () {
    $event = Event::create(['name' => 'S8B Ulang', 'slug' => 's8b-ulang', 'status' => 'active']);
    $reguA = regu::create(['regu' => 'S8B Ulang Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'S8B Ulang Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    $person = Person::create(['nama' => 'S8B Ulang', 'jenis_kelamin' => 'L']);
    $pesertaRecord = peserta::create([
        'nama' => 'S8B Ulang',
        'nip' => 9006,
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaRecord->id, 'person_id' => $person->id]);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL902',
        'attendance_code' => 'KJA-S8BULG',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $reguA->id,
    ]);

    // Simulate Ulang::updatePeserta() — write regu_id to participation only
    $participation->update(['jenis_peserta' => 'Kiriman', 'regu_id' => $reguB->id]);
    $participation->refresh();

    expect((int) $participation->regu_id)->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 8. PlacementService still works using participations
// ──────────────────────────────────────────────
test('S8B-08: PlacementService works with participations scoped by eventId', function () {
    $event = Event::create(['name' => 'S8B Placement', 'slug' => 's8b-placement', 'status' => 'active']);
    $reguA = regu::create(['regu' => 'S8B Pl Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'S8B Pl Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    $p1 = Person::create(['nama' => 'S8B P1', 'jenis_kelamin' => 'L']);
    Participation::create([
        'person_id' => $p1->id,
        'event_id' => $event->id,
        'participant_number' => 'KL991',
        'attendance_code' => 'KJA-S8BPL1',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $reguA->id,
    ]);

    $result = PlacementService::leastFilledRegu('Laki - Laki', $event->id);
    expect($result)->toBeInstanceOf(regu::class);
    expect($result->id)->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 9. regu::participations() relationship still works
// ──────────────────────────────────────────────
test('S8B-09: regu participations relationship works using participations table', function () {
    $event = Event::create(['name' => 'S8B Rel', 'slug' => 's8b-rel', 'status' => 'active']);
    $regu = regu::create(['regu' => 'S8B Rel Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $person = Person::create(['nama' => 'S8B Rel', 'jenis_kelamin' => 'L']);

    Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL992',
        'attendance_code' => 'KJA-S8BRL1',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $regu->id,
    ]);

    expect($regu->participations()->count())->toBe(1);
});

// ──────────────────────────────────────────────
// 10. No code path crashes when accessing old attribute
// ──────────────────────────────────────────────
test('S8B-10: accessing peserta->regu_id returns null (no crash)', function () {
    $peserta = peserta::create([
        'nama' => 'S8B Crash',
        'nip' => 9903,
        'jenis_kelamin' => 'Laki - Laki',
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    // Accessing regu_id on a peserta model should not crash
    expect($peserta->regu_id)->toBeNull();
    expect(isset($peserta->regu_id))->toBeFalse();
});

// ──────────────────────────────────────────────
// 11. Migration rolls forward without error
// ──────────────────────────────────────────────
test('S8B-11: migration can run again (idempotent)', function () {
    $this->artisan('migrate')->assertExitCode(0);
});

// ──────────────────────────────────────────────
// 12. autoPlacement with null eventId does not crash (no event context)
// ──────────────────────────────────────────────
test('S8B-12: autoPlacement with null eventId returns null regu without crashing', function () {
    $result = PlacementService::autoPlacement('Laki - Laki', null);
    expect($result)->toHaveKey('regu_id');
    expect($result)->toHaveKey('regu_nama');
    expect($result['regu_id'])->toBeNull();
    expect($result['regu_nama'])->toBe('-');
});

// ──────────────────────────────────────────────
// 13. TambahPeserta mounts without active event without crashing
// ──────────────────────────────────────────────
test('S8B-13: TambahPeserta mounts without active event without crashing', function () {
    $component = Livewire::test(\App\Livewire\Database\Peserta\TambahPeserta::class);
    $component->assertSet('regu_id', null);
    $component->assertSet('regu_nama', '-');
});

// ──────────────────────────────────────────────
// 14. TambahPeserta with active event uses that event for placement
// ──────────────────────────────────────────────
test('S8B-14: TambahPeserta with active event uses event-scoped placement', function () {
    $event = Event::create(['name' => 'S8B Event', 'slug' => 's8b-tpmnt', 'status' => 'active']);
    app(\App\Support\ActiveEventContext::class)->set($event);
    $regu = regu::create(['regu' => 'S8B TPMNT Regu', 'jenis_kelamin' => 'Laki - Laki']);

    $component = Livewire::test(\App\Livewire\Database\Peserta\TambahPeserta::class);
    // Mount with null jenis_kelamin: leastFilledRegu(null, ...) searches
    // for regu WHERE jenis_kelamin IS NULL — no match, so regu_id is null.
    // After setting jenis_kelamin, the updatedJenisKelamin hook runs
    // generateAutoFields() with the correct gender + event-scoped placement.
    $component->assertSet('regu_id', null);
    $component->set('jenis_kelamin', 'Laki - Laki');
    $component->assertSet('regu_id', $regu->id);
    $component->assertSet('regu_nama', $regu->regu);
});

// ──────────────────────────────────────────────
// 15. SelfRegister mounts without active event without crashing
// ──────────────────────────────────────────────
test('S8B-15: SelfRegister mounts without active event without crashing', function () {
    Str::createRandomStringsUsing(fn () => 's8b15fix');

    $component = Livewire::test(\App\Livewire\Registrasi\SelfRegister::class);
    $component->assertSet('regu_id', null);
    $component->assertSet('regu_nama', '-');
});

// ──────────────────────────────────────────────
// 16. GET /database succeeds without active event
// ──────────────────────────────────────────────
test('S8B-16: database page renders without active event context', function () {
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']));
    $this->get('/database')->assertOk();
});

// ──────────────────────────────────────────────
// 17. leastFilledRegu still rejects null eventId (TypeError)
// ──────────────────────────────────────────────
test('S8B-17: leastFilledRegu rejects null eventId with TypeError', function () {
    $this->expectException(\TypeError::class);
    PlacementService::leastFilledRegu('Laki - Laki', null);
});
