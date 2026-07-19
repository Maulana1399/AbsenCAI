<?php

use App\Console\Commands\BackfillLegacyPeserta;
use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Migration\LegacyPesertaBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function backfillTest_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'CAI Operational',
        'slug' => 'cai-operational',
        'status' => 'active',
    ], $overrides));
}

function backfillTest_makePeserta(array $overrides = []): peserta
{
    static $sequence = 920;

    $participantNumber = $overrides['participant_number'] ?? ('KL'.str_pad((string) $sequence++, 3, '0', STR_PAD_LEFT));

    return peserta::create(array_merge([
        'nama' => 'Test Peserta',
        'nip' => random_int(1001, 9999),
        'participant_number' => $participantNumber,
        'attendance_code' => 'KJA-' . strtoupper(Str::random(8)),
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Belum Registrasi',
    ], $overrides));
}

// ===========================================================================
// COMMAND CONTRACT
// ===========================================================================

test('command defaults to dry-run', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nip' => 1501]);

    $exitCode = Artisan::call('backfill:legacy-peserta');
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('MODE: DRY RUN');
    expect($output)->toMatch('/Total Legacy Peserta:\s+1/');
    expect($output)->toMatch('/Database Writes:\s+0/');
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

test('explicit --dry-run performs zero writes', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta();

    $exitCode = Artisan::call('backfill:legacy-peserta', ['--dry-run' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('MODE: DRY RUN');
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

test('--dry-run + --execute rejected', function () {
    $event = backfillTest_makeEvent();

    $exitCode = Artisan::call('backfill:legacy-peserta', [
        '--dry-run' => true,
        '--execute' => true,
    ]);

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('Cannot use --dry-run and --execute together');
});

test('missing legacy event aborts with zero writes', function () {
    $exitCode = Artisan::call('backfill:legacy-peserta');

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('not found');
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

test('inactive legacy event aborts with zero writes', function () {
    $event = backfillTest_makeEvent(['slug' => 'cai-operational', 'status' => 'archived']);
    backfillTest_makePeserta();

    $exitCode = Artisan::call('backfill:legacy-peserta');

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('not active');
    expect(Person::count())->toBe(0);
});

test('output reports Database Writes: 0 in dry-run', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta();
    backfillTest_makePeserta();

    Artisan::call('backfill:legacy-peserta');

    $output = Artisan::output();
    expect($output)->toMatch('/Total Legacy Peserta:\s+2/');
    expect($output)->toMatch('/Database Writes:\s+0/');
    expect($output)->toMatch('/Person To Create:\s+2/');
    expect($output)->toMatch('/Participation To Create:\s+2/');
    expect($output)->toMatch('/Mapping To Create:\s+2/');
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

// ===========================================================================
// DRY RUN BEHAVIOR
// ===========================================================================

test('dry run projects Person Participation Mapping without writes', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: true);

    expect($report->totalPeserta)->toBe(1);
    expect($report->count('CREATE_PERSON'))->toBe(1);
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

test('dry run report counters are consistent', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501]);
    backfillTest_makePeserta(['nama' => 'Siti', 'nip' => 2001]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: true);

    expect($report->totalPeserta)->toBe(2);
    expect($report->count('CREATE_PERSON'))->toBe(2);
    expect($report->count('MATCHED_BY_NIP'))->toBe(0);
    expect($report->count('CONFLICT'))->toBe(0);
    expect($report->count('REVIEW_REQUIRED'))->toBe(0);
    expect($report->count('ALREADY_MAPPED'))->toBe(0);
    expect($report->count('ERROR'))->toBe(0);
});

test('dry run does not modify database tables', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501]);
    backfillTest_makePeserta(['nama' => 'Siti', 'nip' => 2001]);

    $initialPersonCount = Person::count();
    $initialParticipationCount = Participation::count();
    $initialMappingCount = LegacyPesertaMapping::count();

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: true);

    expect(Person::count())->toBe($initialPersonCount);
    expect(Participation::count())->toBe($initialParticipationCount);
    expect(LegacyPesertaMapping::count())->toBe($initialMappingCount);
});

test('dry run projects Person data correctly', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: true);

    $item = $report->items[0];
    expect($item->projectedPersonData)->not->toBeNull();
    expect($item->projectedPersonData['nama'])->toBe('Budi Santoso');
    expect($item->projectedPersonData['jenis_kelamin'])->toBe('L');
    expect($item->projectedPersonData['nip'])->toBe(1501);
});

test('dry run creates zero records of any kind', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Siti Aisyah', 'nip' => 2001]);
    backfillTest_makePeserta(['nama' => 'Ahmad Junaedi', 'nip' => 1002]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: true);

    expect($report->totalPeserta)->toBe(2);
    expect(Person::count())->toBe(0);
    expect(Participation::count())->toBe(0);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

// ===========================================================================
// EXECUTE MODE
// ===========================================================================

test('execute creates Person', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->totalPeserta)->toBe(1);
    expect(Person::count())->toBe(1);

    $person = Person::first();
    expect($person->nama)->toBe('Budi Santoso');
    expect($person->nip)->toBe(1501);
});

test('execute creates Participation', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Participation::count())->toBe(1);
    $participation = Participation::first();
    expect($participation->participant_number)->toBe('KL001');
    expect($participation->attendance_code)->toBe('KJA-BUDI001');
    expect($participation->event_id)->toBe($event->id);
});

test('execute creates Mapping', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(LegacyPesertaMapping::count())->toBe(1);
    $mapping = LegacyPesertaMapping::first();
    expect($mapping->peserta_id)->toBe($peserta->id);
    expect($mapping->legacy_nip)->toBe(1501);
    expect($mapping->legacy_participant_number)->toBe('KL001');
    expect($mapping->legacy_attendance_code)->toBe('KJA-BUDI001');
    expect($mapping->backfill_batch_id)->toBe('test-batch');
    expect($mapping->migrated_at)->not->toBeNull();
});

// ===========================================================================
// FIELD MAPPING
// ===========================================================================

test('gender Laki - Laki normalized to L', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Person::first()->jenis_kelamin)->toBe('L');
});

test('gender Perempuan normalized to P', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Siti', 'nip' => 2001, 'jenis_kelamin' => 'Perempuan']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Person::first()->jenis_kelamin)->toBe('P');
});

test('Person fields mapped correctly', function () {
    $event = backfillTest_makeEvent();

    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Example']);
    backfillTest_makePeserta([
        'nama' => 'Ahmad Fauzi',
        'nip' => 1805,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $desa->id,
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    $person = Person::first();
    expect($person->nama)->toBe('Ahmad Fauzi');
    expect($person->jenis_kelamin)->toBe('L');
    expect($person->desa_id)->toBe($desa->id);
    expect($person->nip)->toBe(1805);
});

// ===========================================================================
// NIP MATCHING
// ===========================================================================

test('existing exact NIP matches Person', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Person::count())->toBe(1);
    expect($report->count('MATCHED_BY_NIP'))->toBe(1);
    expect($report->count('CREATE_PERSON'))->toBe(0);
});

test('same name without same NIP does NOT match', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1502, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Person::count())->toBe(2);
    expect($report->count('CREATE_PERSON'))->toBe(1);
    expect($report->count('MATCHED_BY_NIP'))->toBe(0);
});

test('two same names with different NIP create separate People', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki']);
    backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1502, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Person::count())->toBe(2);
    $people = Person::all();
    expect($people->pluck('nip')->sort()->values()->toArray())->toBe([1501, 1502]);
});

// ===========================================================================
// CONFLICT DETECTION
// ===========================================================================

test('NIP match with conflicting name requires review', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $peserta = backfillTest_makePeserta(['nama' => 'Ahmad Junaedi', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('REVIEW_REQUIRED'))->toBe(1);
    expect(Person::count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

test('NIP match with conflicting gender causes conflict', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'Perempuan']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CONFLICT'))->toBe(1);
    expect(Person::count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

test('NIP match with conflicting desa requires review', function () {
    $event = backfillTest_makeEvent();
    $desaA = \App\Models\desa::create(['desa_asal' => 'Desa A']);
    $desaB = \App\Models\desa::create(['desa_asal' => 'Desa B']);
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L', 'desa_id' => $desaA->id]);
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $desaB->id]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('REVIEW_REQUIRED'))->toBe(1);
    expect(Person::count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(0);
});

// ===========================================================================
// PARTICIPATION EXISTING / CONFLICT
// ===========================================================================

test('existing compatible Participation reused', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
        'jenis_peserta' => 'Wajib',
    ]);
    $peserta = backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'jenis_kelamin' => 'Laki - Laki',
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('MATCHED_BY_NIP'))->toBe(1);
    expect(Participation::count())->toBe(1);
});

test('participant_number conflict detected', function () {
    $event = backfillTest_makeEvent();
    $personA = Person::create(['nama' => 'Person A', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    Participation::create([
        'person_id' => $personA->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-EXISTING',
        'jenis_peserta' => 'Wajib',
    ]);
    backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CONFLICT'))->toBe(1);
    expect(Participation::count())->toBe(1);
});

test('attendance_code conflict detected', function () {
    $event = backfillTest_makeEvent();
    $personA = Person::create(['nama' => 'Person A', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    Participation::create([
        'person_id' => $personA->id,
        'event_id' => $event->id,
        'participant_number' => 'KL002',
        'attendance_code' => 'KJA-GLOBAL',
        'jenis_peserta' => 'Wajib',
    ]);
    backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-GLOBAL',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CONFLICT'))->toBe(1);
    expect(Participation::count())->toBe(1);
});

test('participant_number reused in another Event is allowed', function () {
    $event = backfillTest_makeEvent();
    $otherEvent = Event::create(['name' => 'Other Event', 'slug' => 'other-event', 'status' => 'active']);
    $personA = Person::create(['nama' => 'Person A', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    Participation::create([
        'person_id' => $personA->id,
        'event_id' => $otherEvent->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-OTHER',
        'jenis_peserta' => 'Wajib',
    ]);
    backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CREATE_PERSON'))->toBe(1);
    expect(Participation::count())->toBe(2);
});

test('global attendance_code conflict across Events is rejected', function () {
    $event = backfillTest_makeEvent();
    $otherEvent = Event::create(['name' => 'Other Event', 'slug' => 'other-event', 'status' => 'active']);
    $personA = Person::create(['nama' => 'Person A', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    Participation::create([
        'person_id' => $personA->id,
        'event_id' => $otherEvent->id,
        'participant_number' => 'KL999',
        'attendance_code' => 'KJA-GLOBAL',
        'jenis_peserta' => 'Wajib',
    ]);
    backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-GLOBAL',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CONFLICT'))->toBe(1);
    expect(Participation::count())->toBe(1);
});

// ===========================================================================
// MAPPING INTEGRITY / IDEMPOTENCY
// ===========================================================================

test('existing valid mapping => ALREADY_MAPPED', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501]);
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => $peserta->participant_number,
        'attendance_code' => $peserta->attendance_code,
        'jenis_peserta' => 'Wajib',
    ]);
    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'legacy_nip' => $peserta->nip,
        'legacy_participant_number' => $peserta->participant_number,
        'legacy_attendance_code' => $peserta->attendance_code,
        'migrated_at' => now(),
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('ALREADY_MAPPED'))->toBe(1);
    expect($report->count('CREATE_PERSON'))->toBe(0);
    expect($report->count('CREATE_PERSON'))->toBe(0);
    expect(Person::count())->toBe(1);
    expect(Participation::count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(1);
});

test('rerun after execute creates no duplicates', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);

    $report1 = $service->execute($event, dryRun: false, batchId: 'batch-1');
    expect(LegacyPesertaMapping::count())->toBe(1);

    $report2 = $service->execute($event, dryRun: false, batchId: 'batch-2');
    expect($report2->count('ALREADY_MAPPED'))->toBe(1);
    expect($report2->count('CREATE_PERSON'))->toBe(0);
    expect(Person::count())->toBe(1);
    expect(Participation::count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(1);
});

test('identifier drift detected', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
    ]);
    $person = Person::create(['nama' => 'Budi Santoso', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI001',
        'jenis_peserta' => 'Wajib',
    ]);
    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'legacy_nip' => 1501,
        'legacy_participant_number' => 'KL-OLD',
        'legacy_attendance_code' => 'KJA-OLD',
        'migrated_at' => now(),
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false);

    expect($report->count('DRIFT_DETECTED'))->toBe(1);
});

test('broken mapping detected — participation belongs to different person', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501]);
    $personA = Person::create(['nama' => 'Person A', 'nip' => 1001, 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'Person B', 'nip' => 1002, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $personA->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-A',
        'jenis_peserta' => 'Wajib',
    ]);

    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $personB->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false);

    expect($report->count('BROKEN_MAPPING'))->toBe(1);
});

// ===========================================================================
// DOMAIN SAFETY
// ===========================================================================

test('legacy peserta remains unchanged after backfill', function () {
    $event = backfillTest_makeEvent();
    $peserta = backfillTest_makePeserta([
        'nama' => 'Budi Santoso',
        'nip' => 1501,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    $peserta->refresh();
    expect($peserta->nama)->toBe('Budi Santoso');
    expect($peserta->nip)->toBe(1501);
    expect($peserta->status_registrasi)->toBe('Belum Registrasi');
});

test('no records created in unrelated Event', function () {
    $event = backfillTest_makeEvent();
    $otherEvent = Event::create(['name' => 'Other Event', 'slug' => 'other-event', 'status' => 'active']);
    backfillTest_makePeserta(['nama' => 'Budi Santoso', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($otherEvent, dryRun: false, batchId: 'test-batch');

    expect(Participation::where('event_id', $event->id)->count())->toBe(0);
    expect(Participation::where('event_id', $otherEvent->id)->count())->toBe(1);
});

// ===========================================================================
// TRANSACTION SAFETY
// ===========================================================================

test('failure for one peserta does not rollback successful different peserta', function () {
    $event = backfillTest_makeEvent();
    $peserta1 = backfillTest_makePeserta(['nama' => 'Sukses', 'nip' => 1001]);
    $peserta2 = backfillTest_makePeserta(['nama' => 'Gagal', 'nip' => 1002]);
    $person = Person::create(['nama' => 'Gagal', 'nip' => 1002, 'jenis_kelamin' => 'L']);
    $existingParticipation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL999',
        'attendance_code' => 'KJA-GAGAL',
        'jenis_peserta' => 'Wajib',
    ]);

    $peserta2->update([
        'participant_number' => 'KL888',
        'attendance_code' => 'KJA-GAGAL',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CREATE_PERSON'))->toBe(1);
    expect($report->count('CONFLICT'))->toBe(1);
    expect(Person::count())->toBe(2);
});

// ===========================================================================
// BULK DETERMINISM
// ===========================================================================

test('bulk peserta processes deterministically', function () {
    $event = backfillTest_makeEvent();
    for ($i = 0; $i < 5; $i++) {
        backfillTest_makePeserta([
            'nama' => "Peserta {$i}",
            'nip' => 1100 + $i,
        ]);
    }

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->totalPeserta)->toBe(5);
    expect($report->count('CREATE_PERSON'))->toBe(5);
    expect(Person::count())->toBe(5);
    expect(Participation::count())->toBe(5);
    expect(LegacyPesertaMapping::count())->toBe(5);

    $people = Person::orderBy('id')->get();
    expect($people->pluck('nip')->toArray())->toBe([1100, 1101, 1102, 1103, 1104]);
});

// ===========================================================================
// SERVICE DIRECT
// ===========================================================================

test('target event resolved by slug not ID', function () {
    $event = backfillTest_makeEvent();
    $otherEvent = Event::create(['name' => 'Wrong Event', 'slug' => 'wrong-event', 'status' => 'active']);
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect(Participation::where('event_id', $otherEvent->id)->count())->toBe(0);
    expect(Participation::where('event_id', $event->id)->count())->toBe(1);
});

test('--execute enables writes', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501]);

    Artisan::call('backfill:legacy-peserta', ['--execute' => true]);

    expect(Person::count())->toBe(1);
    expect(Participation::count())->toBe(1);
    expect(LegacyPesertaMapping::count())->toBe(1);
});

// ===========================================================================
// DATABASE WRITES COUNTER
// ===========================================================================

test('new Person + new Participation + mapping = 3 writes', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->totalPeopleCreated())->toBe(1);
    expect($report->totalParticipationsCreated())->toBe(1);
    expect($report->totalMappingsCreated())->toBe(1);
    expect($report->totalDatabaseWrites())->toBe(3);
});

test('matched Person + new Participation + mapping = 2 writes', function () {
    $event = backfillTest_makeEvent();
    Person::create(['nama' => 'Budi', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501, 'jenis_kelamin' => 'Laki - Laki']);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->totalPeopleCreated())->toBe(0);
    expect($report->totalParticipationsCreated())->toBe(1);
    expect($report->totalMappingsCreated())->toBe(1);
    expect($report->totalDatabaseWrites())->toBe(2);
});

test('existing compatible Participation + mapping = 1 write', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI',
        'jenis_peserta' => 'Wajib',
    ]);
    backfillTest_makePeserta([
        'nama' => 'Budi',
        'nip' => 1501,
        'jenis_kelamin' => 'Laki - Laki',
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->totalPeopleCreated())->toBe(0);
    expect($report->totalParticipationsCreated())->toBe(0);
    expect($report->totalMappingsCreated())->toBe(1);
    expect($report->totalDatabaseWrites())->toBe(1);
});

test('conflict results in 0 writes for that peserta', function () {
    $event = backfillTest_makeEvent();
    $person = Person::create(['nama' => 'Budi', 'nip' => 1501, 'jenis_kelamin' => 'L']);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-BUDI',
        'jenis_peserta' => 'Wajib',
    ]);
    backfillTest_makePeserta([
        'nama' => 'Budi',
        'nip' => 1501,
        'jenis_kelamin' => 'Laki - Laki',
        'participant_number' => 'KL999',
        'attendance_code' => 'KJA-BUDI',
    ]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: false, batchId: 'test-batch');

    expect($report->count('CONFLICT'))->toBe(1);
    expect($report->totalDatabaseWrites())->toBe(0);
});

test('dry-run reports 0 actual writes', function () {
    $event = backfillTest_makeEvent();
    backfillTest_makePeserta(['nama' => 'Budi', 'nip' => 1501]);

    $service = app(LegacyPesertaBackfillService::class);
    $report = $service->execute($event, dryRun: true);

    expect($report->totalDatabaseWrites())->toBe(0);
});
