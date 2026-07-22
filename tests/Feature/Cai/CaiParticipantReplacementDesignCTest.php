<?php

use App\Models\Absensi;
use App\Models\desa;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Models\SuratIzin;
use App\Services\Cai\CaiParticipantReplacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

uses(RefreshDatabase::class);

function cdr_event(string $suffix): Event
{
    return Event::create([
        'name' => 'CDR Event ' . $suffix,
        'slug' => 'cdr-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function cdr_fixture(): array
{
    $desa = desa::create(['desa_asal' => 'CDR Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'CDR Kelompok', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'CDR Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = cdr_event('a');
    $eventB = cdr_event('b');

    $peserta = peserta::create([
        'nama' => 'Peserta Lama',
        'nip' => 91001,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-OLD0001',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => peserta::JENIS_WAJIB,
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $regu->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $oldPerson = Person::create([
        'nama' => 'Peserta Lama',
        'nip' => 91001,
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
    ]);

    $personB = Person::create([
        'nama' => 'Peserta Lama',
        'nip' => 91002,
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
    ]);

    $participationA = Participation::create([
        'person_id' => $oldPerson->id,
        'event_id' => $eventA->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-OLD0001',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);

    $participationB = Participation::create([
        'person_id' => $personB->id,
        'event_id' => $eventB->id,
        'participant_number' => 'KL002',
        'attendance_code' => 'KJA-OLD0002',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);

    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $oldPerson->id,
        'participation_id' => $participationA->id,
        'event_id' => $eventA->id,
        'legacy_nip' => 91001,
        'legacy_participant_number' => 'KL001',
        'legacy_attendance_code' => 'KJA-OLD0001',
        'migrated_at' => now(),
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $oldPerson->id,
        'participation_id' => $participationA->id,
        'event_id' => $eventA->id,
        'migrated_at' => now(),
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $personB->id,
        'participation_id' => $participationB->id,
        'event_id' => $eventB->id,
        'migrated_at' => now(),
    ]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'eventB', 'peserta', 'oldPerson', 'personB', 'participationA', 'participationB', 'mapping');
}

test('replacement changes only Event A membership and leaves Event B unchanged', function () {
    $fixture = cdr_fixture();

    $result = app(CaiParticipantReplacementService::class)->replace(
        $fixture['peserta'],
        [
            'nama' => 'Peserta Pengganti',
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        'replace event a only',
    );

    expect($fixture['participationB']->fresh()->person_id)->toBe($fixture['personB']->id)
        ->and($fixture['participationB']->fresh()->participant_number)->toBe('KL002')
        ->and($fixture['participationB']->fresh()->attendance_code)->toBe('KJA-OLD0002')
        ->and($fixture['mapping']->fresh()->participation_id)->toBe($result['participation']->id)
        ->and($fixture['peserta']->fresh()->nama)->toBe('Peserta Pengganti')
        ->and($fixture['oldPerson']->fresh()->nip)->toBeNull();
});

test('replacement keeps old global identity alive when Event B still uses it', function () {
    $fixture = cdr_fixture();

    app(CaiParticipantReplacementService::class)->replace(
        $fixture['peserta'],
        [
            'nama' => 'Peserta Pengganti',
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        'replace event a only',
    );

    expect(Person::find($fixture['oldPerson']->id))->not->toBeNull()
        ->and($fixture['eventB']->fresh()->id)->toBe($fixture['eventB']->id)
        ->and($fixture['participationB']->fresh()->person_id)->toBe($fixture['personB']->id)
        ->and(LegacyParticipationMapping::where('event_id', $fixture['eventB']->id)->count())->toBe(1)
        ->and(LegacyParticipationMapping::where('event_id', $fixture['eventA']->id)->count())->toBe(1);
});

test('replacement rejects cross-event target mismatch', function () {
    $fixture = cdr_fixture();

    $service = app(CaiParticipantReplacementService::class);
    $result = $service->replace(
        $fixture['peserta'],
        [
            'nama' => 'Peserta Pengganti',
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        'replace event a only',
    );

    expect($result['participation']->event_id)->toBe($fixture['eventA']->id)
        ->and($fixture['participationB']->fresh()->participant_number)->toBe('KL002')
        ->and($fixture['participationB']->fresh()->person_id)->toBe($fixture['personB']->id)
        ->and(LegacyParticipationMapping::where('event_id', $fixture['eventB']->id)->count())->toBe(1);
});

test('replacement rollback restores old identity on failure', function () {
    $fixture = cdr_fixture();

    expect(fn () => app(CaiParticipantReplacementService::class)->replace(
        $fixture['peserta'],
        [
            'nama' => '',
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        'invalid',
    ))->toThrow(RuntimeException::class);

    expect($fixture['peserta']->fresh()->nama)->toBe('Peserta Lama')
        ->and($fixture['oldPerson']->fresh()->nip)->toBe(91001)
        ->and($fixture['participationA']->fresh()->participant_number)->toBe('KL001')
        ->and($fixture['participationB']->fresh()->participant_number)->toBe('KL002');
});
