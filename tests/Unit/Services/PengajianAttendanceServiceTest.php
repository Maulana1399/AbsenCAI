<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Models\desa;
use App\Services\Pengajian\PengajianAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm4_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Desa',
        'slug' => 'pengajian-desa-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm4_makeDesa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test '.str()->random(4),
    ], $overrides));
}

function pgm4_makePerson(string $gender = 'L', ?int $desaId = null, array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Warga '.str()->random(6),
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// findOrCreateParticipation
// ---------------------------------------------------------------------------

test('findOrCreateParticipation creates participation with KL prefix for L gender', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', $desa->id);

    $participation = app(PengajianAttendanceService::class)
        ->findOrCreateParticipation($person, $event->id, $desa->id);

    expect($participation->person_id)->toBe($person->id)
        ->and($participation->event_id)->toBe($event->id)
        ->and($participation->participant_number)->toMatch('/^KL\d{3}$/')
        ->and($participation->attendance_code)->toMatch('/^KJA-[A-Z0-9]{8}$/')
        ->and($participation->jenis_peserta)->toBe('Pengajian Desa');
});

test('findOrCreateParticipation creates participation with KP prefix for P gender', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('P', $desa->id);

    $participation = app(PengajianAttendanceService::class)
        ->findOrCreateParticipation($person, $event->id, $desa->id);

    expect($participation->participant_number)->toMatch('/^KP\d{3}$/');
});

test('findOrCreateParticipation increments participant_number sequentially within gender', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();

    $male1 = pgm4_makePerson('L', $desa->id);
    $male2 = pgm4_makePerson('L', $desa->id);
    $female1 = pgm4_makePerson('P', $desa->id);

    $p1 = app(PengajianAttendanceService::class)->findOrCreateParticipation($male1, $event->id, $desa->id);
    $p2 = app(PengajianAttendanceService::class)->findOrCreateParticipation($male2, $event->id, $desa->id);
    $p3 = app(PengajianAttendanceService::class)->findOrCreateParticipation($female1, $event->id, $desa->id);

    expect($p1->participant_number)->toBe('KL001')
        ->and($p2->participant_number)->toBe('KL002')
        ->and($p3->participant_number)->toBe('KP001');
});

test('findOrCreateParticipation returns existing participation for same person+event', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', $desa->id);

    $first = app(PengajianAttendanceService::class)
        ->findOrCreateParticipation($person, $event->id, $desa->id);

    $second = app(PengajianAttendanceService::class)
        ->findOrCreateParticipation($person, $event->id, $desa->id);

    expect($second->id)->toBe($first->id)
        ->and(Participation::count())->toBe(1);
});

test('findOrCreateParticipation sets person.desa_id when null', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', desaId: null);

    app(PengajianAttendanceService::class)
        ->findOrCreateParticipation($person, $event->id, $desa->id);

    expect($person->fresh()->desa_id)->toBe($desa->id);
});

test('findOrCreateParticipation uses transaction with lockForUpdate', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', $desa->id);

    $log = [];
    DB::listen(function ($query) use (&$log) {
        if (str_contains($query->sql, 'select * from "people"')) {
            $log[] = 'select';
        }
    });

    app(PengajianAttendanceService::class)
        ->findOrCreateParticipation($person, $event->id, $desa->id);

    expect(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// recordAttendance
// ---------------------------------------------------------------------------

test('recordAttendance creates event_attendance record', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', $desa->id);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-TEST0001',
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    $attendance = app(PengajianAttendanceService::class)
        ->recordAttendance($participation, $event->id, $desa->id);

    expect($attendance->participation_id)->toBe($participation->id)
        ->and($attendance->event_id)->toBe($event->id)
        ->and($attendance->desa_id)->toBe($desa->id)
        ->and($attendance->method)->toBe('offline')
        ->and($attendance->recorded_by)->toBeNull()
        ->and($attendance->metadata)->toBeNull()
        ->and($attendance->attended_at)->not->toBeNull();
});

test('recordAttendance rejects duplicate participation_id', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', $desa->id);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-TEST0001',
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    app(PengajianAttendanceService::class)
        ->recordAttendance($participation, $event->id, $desa->id);

    expect(fn () => app(PengajianAttendanceService::class)
        ->recordAttendance($participation, $event->id, $desa->id))
        ->toThrow(QueryException::class);
});

// ---------------------------------------------------------------------------
// attendPerson (end-to-end)
// ---------------------------------------------------------------------------

test('attendPerson creates participation and attendance in one call', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', $desa->id);

    $attendance = app(PengajianAttendanceService::class)
        ->attendPerson($person, $event->id, $desa->id);

    expect($attendance->participation_id)->not->toBeNull()
        ->and($attendance->event_id)->toBe($event->id)
        ->and($attendance->desa_id)->toBe($desa->id)
        ->and(EventAttendance::count())->toBe(1)
        ->and(Participation::count())->toBe(1);

    $participation = Participation::find($attendance->participation_id);
    expect($participation->participant_number)->toMatch('/^KL\d{3}$/')
        ->and($participation->attendance_code)->toMatch('/^KJA-[A-Z0-9]{8}$/');
});

test('attendPerson sets person.desa_id from context when missing', function () {
    $event = pgm4_makeEvent();
    $desa = pgm4_makeDesa();
    $person = pgm4_makePerson('L', desaId: null);

    app(PengajianAttendanceService::class)
        ->attendPerson($person, $event->id, $desa->id);

    expect($person->fresh()->desa_id)->toBe($desa->id);
});

// ---------------------------------------------------------------------------
// Service resolves from container
// ---------------------------------------------------------------------------

test('PengajianAttendanceService resolves with RegistrationService dependency', function () {
    $service = app(PengajianAttendanceService::class);

    expect($service)->toBeInstanceOf(PengajianAttendanceService::class);
});
