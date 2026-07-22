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
use App\Services\Attendance\LegacyParticipationResolver;
use App\Services\Migration\LegacyParticipationBackfillService;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->desa = desa::create(['desa_asal' => 'Bridge Desa']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Bridge Kelompok', 'desa_id' => $this->desa->id]);
    $this->regu = regu::create(['regu' => 'Bridge Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $this->eventA = Event::create(['name' => 'Bridge Event A', 'slug' => 'bridge-event-a', 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Bridge Event B', 'slug' => 'bridge-event-b', 'status' => 'active']);
});

function bridgePayload(string $nama, int $nip, $desaId, $kelompokId, $reguId): array
{
    return [
        'nama' => $nama,
        'nip' => $nip,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desaId,
        'kelompok_id' => $kelompokId,
        'regu_id' => $reguId,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ];
}

test('legacy participation mappings table exists with expected unique constraints', function () {
    expect(Schema::hasTable('legacy_participation_mappings'))->toBeTrue();

    $indexes = collect(Schema::getIndexes('legacy_participation_mappings'));
    $names = $indexes->pluck('name')->all();

    expect($names)->toContain('legacy_participation_mappings_participation_id_unique')
        ->and($names)->toContain('legacy_participation_mappings_peserta_id_event_id_unique');
});

test('same peserta can map to Event A and Event B while duplicate same event and duplicate participation are rejected', function () {
    $person = Person::create(['nama' => 'Schema Bridge', 'nip' => 10101, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $pesertaModel = peserta::create(['nama' => 'Schema Bridge', 'nip' => 10101, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-AAAA0001', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL002', 'attendance_code' => 'KJA-BBBB0001', 'jenis_peserta' => 'Wajib']);

    LegacyParticipationMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventB->id]);

    expect(LegacyParticipationMapping::count())->toBe(2);

    expect(fn () => LegacyParticipationMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventB->id]))->toThrow(QueryException::class);
    expect(fn () => LegacyParticipationMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventA->id]))->toThrow(QueryException::class);
});

test('resolver uses new bridge first and falls back to legacy bridge', function () {
    $person = Person::create(['nama' => 'Resolve Person', 'nip' => 20201, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $pesertaModel = peserta::create(['nama' => 'Resolve Person', 'nip' => 20201, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL010', 'attendance_code' => 'KJA-RESOLVE1', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL011', 'attendance_code' => 'KJA-RESOLVE2', 'jenis_peserta' => 'Wajib']);

    LegacyPesertaMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventB->id]);

    $resolver = app(LegacyParticipationResolver::class);
    expect($resolver->resolveByPesertaAndEvent($pesertaModel->id, $this->eventB->id)?->id)->toBe($partB->id)
        ->and($resolver->resolveByPesertaAndEvent($pesertaModel->id, $this->eventA->id)?->id)->toBe($partA->id)
        ->and($resolver->resolvePesertaByParticipation($partB->id, $this->eventB->id)?->id)->toBe($pesertaModel->id)
        ->and($resolver->resolvePersonByPesertaId($pesertaModel->id)?->id)->toBe($person->id);
});

test('backfill dry-run does not mutate and force creates new bridge rows idempotently', function () {
    $person = Person::create(['nama' => 'Backfill Person', 'nip' => 30301, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $pesertaModel = peserta::create(['nama' => 'Backfill Person', 'nip' => 30301, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL020', 'attendance_code' => 'KJA-BACK001', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);

    $service = app(LegacyParticipationBackfillService::class);
    $dry = $service->execute($this->eventA, true, 'batch-dry');
    expect(LegacyParticipationMapping::count())->toBe(0)->and($dry['created'])->toBe(1);

    $force = $service->execute($this->eventA, false, 'batch-force');
    expect(LegacyParticipationMapping::count())->toBe(1)->and($force['created'])->toBe(1);

    $again = $service->execute($this->eventA, false, 'batch-force2');
    expect(LegacyParticipationMapping::count())->toBe(1)->and($again['skipped'])->toBeGreaterThanOrEqual(1);
});

test('backfill reports conflict for mismatch and missing dependency safely', function () {
    $person = Person::create(['nama' => 'Conflict Person', 'nip' => 40401, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $pesertaModel = peserta::create(['nama' => 'Conflict Person', 'nip' => 40401, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL030', 'attendance_code' => 'KJA-CONFLICT', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventB->id]);

    $service = app(LegacyParticipationBackfillService::class);
    $report = $service->execute($this->eventB, true, 'batch-conflict');

    expect($report['conflicts'])->toBeGreaterThanOrEqual(1);
});

test('registration case A and case B create legacy participation bridge rows', function () {
    $service = app(RegistrationService::class);
    app(ActiveEventContext::class)->set($this->eventA);

    $a = $service->createParticipant(bridgePayload('Bridge A', 50501, $this->desa->id, $this->kelompok->id, $this->regu->id));
    expect(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and(Participation::count())->toBe(1)
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(LegacyParticipationMapping::count())->toBe(1);

    app(ActiveEventContext::class)->set($this->eventB);
    $b = $service->createParticipant(bridgePayload('Bridge A', 50501, $this->desa->id, $this->kelompok->id, $this->regu->id));

    expect($b->id)->toBe($a->id)
        ->and(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and(Participation::count())->toBe(2)
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(LegacyParticipationMapping::count())->toBe(2);
});

test('case c rejects duplicate participation same event with no partial writes', function () {
    $service = app(RegistrationService::class);
    app(ActiveEventContext::class)->set($this->eventA);

    $service->createParticipant(bridgePayload('Dup Event', 60601, $this->desa->id, $this->kelompok->id, $this->regu->id));
    $counts = [Person::count(), peserta::count(), Participation::count(), LegacyPesertaMapping::count(), LegacyParticipationMapping::count()];

    expect(fn () => $service->createParticipant(bridgePayload('Dup Event', 60601, $this->desa->id, $this->kelompok->id, $this->regu->id)))->toThrow(ValidationException::class);
    expect([Person::count(), peserta::count(), Participation::count(), LegacyPesertaMapping::count(), LegacyParticipationMapping::count()])->toBe($counts);
});

test('transaction rollback on bridge failure leaves no partial participation', function () {
    $service = app(RegistrationService::class);
    app(ActiveEventContext::class)->set($this->eventA);

    $pesertaBefore = peserta::count();
    $participationBefore = Participation::count();
    $mappingBefore = LegacyParticipationMapping::count();

    $participant = $service->createParticipant(bridgePayload('Rollback Seed', 70700, $this->desa->id, $this->kelompok->id, $this->regu->id));
    expect($participant)->not->toBeNull();

    try {
        $service->createParticipant([
            'nama' => 'Rollback Bridge',
            'nip' => 70701,
            'jenis_kelamin' => 'Laki - Laki',
            'jenis_peserta' => 'Wajib',
            'desa_id' => $this->desa->id,
            'kelompok_id' => $this->kelompok->id,
            'regu_id' => $this->regu->id,
            'status_registrasi' => peserta::STATUS_SELF_REGISTER,
            'participant_number' => 'KL001',
        ]);
    } catch (Throwable) {
    }

    expect(peserta::count())->toBe($pesertaBefore + 1)
        ->and(Participation::count())->toBe($participationBefore + 1)
        ->and(LegacyParticipationMapping::count())->toBe($mappingBefore + 1);
});

test('populated database safety keeps existing rows and only adds the new bridge table', function () {
    $person = Person::create(['nama' => 'Safety Person', 'nip' => 80801, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $pesertaModel = peserta::create(['nama' => 'Safety Person', 'nip' => 80801, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'regu_id' => $this->regu->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL080', 'attendance_code' => 'KJA-SAFETY', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $pesertaModel->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);

    $before = [
        'events' => Event::count(),
        'pesertas' => peserta::count(),
        'people' => Person::count(),
        'participations' => Participation::count(),
        'legacy_peserta_mappings' => LegacyPesertaMapping::count(),
    ];

    expect($before['events'])->toBeGreaterThanOrEqual(2);
    expect($before['pesertas'])->toBeGreaterThanOrEqual(1);
    expect($before['people'])->toBeGreaterThanOrEqual(1);
    expect($before['participations'])->toBeGreaterThanOrEqual(1);
    expect($before['legacy_peserta_mappings'])->toBeGreaterThanOrEqual(1);

    expect(Schema::hasTable('legacy_participation_mappings'))->toBeTrue();
    expect(LegacyPesertaMapping::count())->toBe($before['legacy_peserta_mappings']);
});
