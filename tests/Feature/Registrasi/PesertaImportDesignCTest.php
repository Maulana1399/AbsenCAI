<?php

use App\Imports\PesertaImport;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function pid_event(string $suffix): Event
{
    return Event::create([
        'name' => 'PID Event ' . $suffix,
        'slug' => 'pid-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
    ]);
}

function pid_fixtures(): array
{
    $desaA = desa::create(['desa_asal' => 'PID Desa A']);
    $desaB = desa::create(['desa_asal' => 'PID Desa B']);
    $kelompokA = kelompok::create(['kelompok_asal' => 'PID Kelompok A', 'desa_id' => $desaA->id]);
    $kelompokB = kelompok::create(['kelompok_asal' => 'PID Kelompok B', 'desa_id' => $desaB->id]);
    $reguL = regu::create(['regu' => 'PID Regu L', 'jenis_kelamin' => 'Laki - Laki']);
    $reguP = regu::create(['regu' => 'PID Regu P', 'jenis_kelamin' => 'Perempuan']);
    $eventA = pid_event('a');
    $eventB = pid_event('b');

    return compact('desaA', 'desaB', 'kelompokA', 'kelompokB', 'reguL', 'reguP', 'eventA', 'eventB');
}

function pid_create_case_a(array $ctx): array
{
    app(ActiveEventContext::class)->set($ctx['eventA']);

    $result = app(RegistrationService::class)->createParticipant([
        'nama' => 'Import Alpha',
        'nip' => 11001,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $ctx['desaA']->id,
        'kelompok_id' => $ctx['kelompokA']->id,
        'regu_id' => $ctx['reguL']->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    return [
        'result' => $result,
        'person' => Person::where('nama', 'Import Alpha')->first(),
        'peserta' => peserta::where('nama', 'Import Alpha')->first(),
        'participation' => Participation::where('event_id', $ctx['eventA']->id)->first(),
    ];
}

beforeEach(function () {
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']));
});

test('import case A creates one full record set', function () {
    $ctx = pid_fixtures();
    $import = new PesertaImport();

    app(ActiveEventContext::class)->set($ctx['eventA']);
    $model = $import->model([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa' => 'PID Desa A',
        'kelompok' => 'PID Kelompok A',
    ]);

    expect($model)->toBeInstanceOf(peserta::class)
        ->and(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and(Participation::count())->toBe(1)
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(LegacyParticipationMapping::count())->toBe(1);
});

test('import case B reuses person peserta and creates only event membership bridge', function () {
    $ctx = pid_fixtures();
    pid_create_case_a($ctx);

    $before = [
        'people' => Person::count(),
        'peserta' => peserta::count(),
        'legacy_peserta' => LegacyPesertaMapping::count(),
        'participations' => Participation::count(),
        'legacy_participations' => LegacyParticipationMapping::count(),
    ];

    app(ActiveEventContext::class)->set($ctx['eventB']);
    $model = app(PesertaImport::class)->model([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa' => 'PID Desa A',
        'kelompok' => 'PID Kelompok A',
    ]);

    expect($model)->toBeInstanceOf(peserta::class)
        ->and(Person::count())->toBe($before['people'])
        ->and(peserta::count())->toBe($before['peserta'])
        ->and(LegacyPesertaMapping::count())->toBe($before['legacy_peserta'])
        ->and(Participation::count())->toBe($before['participations'] + 1)
        ->and(LegacyParticipationMapping::count())->toBe($before['legacy_participations'] + 1);
});

test('import case C same event does not duplicate membership', function () {
    $ctx = pid_fixtures();
    pid_create_case_a($ctx);
    app(ActiveEventContext::class)->set($ctx['eventA']);

    expect(fn () => app(PesertaImport::class)->model([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa' => 'PID Desa A',
        'kelompok' => 'PID Kelompok A',
    ]))->toThrow(ValidationException::class);
});

test('import case B reuses the same Person', function () {
    $ctx = pid_fixtures();
    $first = pid_create_case_a($ctx);
    app(ActiveEventContext::class)->set($ctx['eventB']);

    $result = app(RegistrationService::class)->createParticipant([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa_id' => $ctx['desaA']->id,
        'kelompok_id' => $ctx['kelompokA']->id,
        'regu_id' => $ctx['reguL']->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    expect($result)->toBeInstanceOf(peserta::class)
        ->and(Person::count())->toBe(1);
});

test('import preserves event-specific participant identifiers across events', function () {
    $ctx = pid_fixtures();
    $a = pid_create_case_a($ctx);
    $beforeA = Participation::where('event_id', $ctx['eventA']->id)->first()->fresh();
    $participantNumberA = $beforeA->participant_number;
    $attendanceCodeA = $beforeA->attendance_code;
    app(ActiveEventContext::class)->set($ctx['eventB']);

    app(RegistrationService::class)->createParticipant([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa_id' => $ctx['desaA']->id,
        'kelompok_id' => $ctx['kelompokA']->id,
        'regu_id' => $ctx['reguL']->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $pa = $beforeA->fresh();
    $pb = Participation::where('event_id', $ctx['eventB']->id)->first();

    expect($pa->id)->not->toBe($pb->id)
        ->and($pa->event_id)->toBe($ctx['eventA']->id)
        ->and($pb->event_id)->toBe($ctx['eventB']->id)
        ->and($pa->participant_number)->toBe('KL001')
        ->and($pa->attendance_code)->not->toBeNull()
        ->and($pb->participant_number)->not->toBeNull()
        ->and($pb->attendance_code)->not->toBeNull()
        ->and($pa->participant_number)->toBe($participantNumberA)
        ->and($pa->attendance_code)->toBe($attendanceCodeA)
        ->and($pa->attendance_code)->not->toBe($pb->attendance_code);
});

test('different Person without conflict passes registration', function () {
    $ctx = pid_fixtures();
    pid_create_case_a($ctx);
    app(ActiveEventContext::class)->set($ctx['eventB']);

    $result = app(RegistrationService::class)->createParticipant([
        'nama' => 'Different Person',
        'jenis_kelamin' => 'Perempuan',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $ctx['desaB']->id,
        'kelompok_id' => $ctx['kelompokB']->id,
        'regu_id' => $ctx['reguP']->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    expect($result)->toBeInstanceOf(peserta::class)
        ->and(Person::count())->toBe(2);
});

test('same name different desa creates separate Person', function () {
    $ctx = pid_fixtures();
    pid_create_case_a($ctx);
    app(ActiveEventContext::class)->set($ctx['eventB']);

    $result = app(RegistrationService::class)->createParticipant([
        'nama' => 'Import Alpha',
        'nip' => 11002,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa_id' => $ctx['desaB']->id,
        'kelompok_id' => $ctx['kelompokB']->id,
        'regu_id' => $ctx['reguL']->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    expect($result)->toBeInstanceOf(peserta::class)
        ->and(Person::where('nama', 'Import Alpha')->count())->toBe(2)
        ->and(Person::where('desa_id', $ctx['desaB']->id)->count())->toBe(1)
        ->and(peserta::count())->toBe(2)
        ->and(peserta::where('participant_number', $ctx['eventA']->id ? 'KL001' : 'KL001')->count())->toBe(1)
        ->and(Participation::where('event_id', $ctx['eventB']->id)->count())->toBe(1);
});

test('case B bridge failure rolls back attempted membership', function () {
    $ctx = pid_fixtures();
    $first = pid_create_case_a($ctx);
    app(ActiveEventContext::class)->set($ctx['eventB']);

    Participation::create([
        'person_id' => $first['person']->id,
        'event_id' => $ctx['eventB']->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-ROLLBACKB',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
    ]);

    $before = [
        'people' => Person::count(),
        'peserta' => peserta::count(),
        'legacy_peserta' => LegacyPesertaMapping::count(),
        'participations' => Participation::count(),
        'legacy_participations' => LegacyParticipationMapping::count(),
    ];

    expect(fn () => app(RegistrationService::class)->createParticipant([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_KIRIMAN,
        'desa_id' => $ctx['desaA']->id,
        'kelompok_id' => $ctx['kelompokA']->id,
        'regu_id' => $ctx['reguL']->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]))->toThrow(ValidationException::class);

    expect(Person::count())->toBe($before['people'])
        ->and(peserta::count())->toBe($before['peserta'])
        ->and(LegacyPesertaMapping::count())->toBe($before['legacy_peserta'])
        ->and(Participation::count())->toBe($before['participations'])
        ->and(LegacyParticipationMapping::count())->toBe($before['legacy_participations']);
});

test('missing event context fails safely', function () {
    app(ActiveEventContext::class)->clear();

    expect(fn () => app(PesertaImport::class)->model([
        'nama' => 'Import Alpha',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa' => 'PID Desa A',
        'kelompok' => 'PID Kelompok A',
    ]))->toThrow(RuntimeException::class);
});

test('existing legacy import behavior remains compatible', function () {
    $ctx = pid_fixtures();
    app(ActiveEventContext::class)->set($ctx['eventA']);

    $model = (new PesertaImport())->model([
        'nama' => 'Legacy Compatible',
        'jenis_kelamin' => 'Perempuan',
        'kelompok' => 'PID Kelompok A',
        'desa' => 'PID Desa A',
    ]);

    expect($model)->toBeInstanceOf(peserta::class)
        ->and($model->status_registrasi)->toBe(peserta::STATUS_BELUM_REGISTRASI);
});
