<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\IdentityCorrectionRequest;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function ppd_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Test Event '.str()->random(5),
        'slug' => 'event-'.str()->random(8),
        'status' => 'active',
    ], $overrides));
}

function ppd_makeDesa(): desa
{
    return desa::create(['desa_asal' => 'Desa '.str()->random(5)]);
}

function ppd_makeKelompok(desa $desa): kelompok
{
    return kelompok::create(['kelompok_asal' => 'Kel '.str()->random(5), 'desa_id' => $desa->id]);
}

function ppd_makePerson(string $nama, array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => $nama,
        'jenis_kelamin' => 'L',
    ], $overrides));
}

function ppd_makeParticipation(Person $person, Event $event): Participation
{
    return Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'PN-'.str()->random(8),
        'attendance_code' => 'AC-'.str()->random(8),
        'jenis_peserta' => 'Wajib',
    ]);
}

function ppd_makeAttendance(Participation $participation, Event $event): EventAttendance
{
    return EventAttendance::create([
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'attended_at' => now(),
        'method' => 'scan',
        'status' => 'hadir',
    ]);
}

function ppd_makeLegacyChain(Person $person, Participation $participation, Event $event): peserta
{
    $desa = ppd_makeDesa();
    $kel = ppd_makeKelompok($desa);
    $regu = regu::create(['regu' => 'Regu '.str()->random(5)]);

    $legacy = peserta::create([
        'nama' => $person->nama,
        'participant_number' => 'LP-'.str()->random(8),
        'attendance_code' => 'LAC-'.str()->random(8),
        'jenis_kelamin' => 'Laki - laki',
        'jenis_peserta' => 'Wajib',
        'kelompok_id' => $kel->id,
        'desa_id' => $desa->id,
        'status_registrasi' => 'Belum Registrasi',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $legacy->id,
        'person_id' => $person->id,
        'legacy_nip' => 1234,
        'legacy_participant_number' => $legacy->participant_number,
        'legacy_attendance_code' => $legacy->attendance_code,
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $legacy->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    return $legacy;
}

function ppd_makeUserLinked(Person $person): User
{
    return User::factory()->create(['person_id' => $person->id]);
}

function ppd_makeIdentityCorrection(Person $person): IdentityCorrectionRequest
{
    return IdentityCorrectionRequest::create([
        'person_id' => $person->id,
        'requested_name' => 'New Name',
        'status' => 'pending',
    ]);
}

function ppd_makeCommitteeAssignment(Person $person, Event $event): EventCommitteeAssignment
{
    $role = EventRole::create([
        'event_id' => $event->id,
        'name' => 'Ketua Event',
        'code' => 'ketua_event',
    ]);

    return EventCommitteeAssignment::create([
        'event_id' => $event->id,
        'person_id' => $person->id,
        'event_role_id' => $role->id,
    ]);
}

// ---------------------------------------------------------------------------
// Dry run
// ---------------------------------------------------------------------------

test('dry run reports identification without any writes', function () {
    $event = ppd_makeEvent();
    $person = ppd_makePerson('Udin');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);
    ppd_makeLegacyChain($person, $participation, $event);
    $user = ppd_makeUserLinked($person);

    $this->artisan('person:purge-dummy', ['names' => ['Udin']])
        ->expectsOutputToContain("Person #{$person->id}: 'Udin'")
        ->expectsOutputToContain('event_attendances: 1 row(s)')
        ->expectsOutputToContain('legacy_peserta_mappings: 1 row(s)')
        ->expectsOutputToContain('users: 1 row(s)')
        ->expectsOutputToContain('DRY RUN')
        ->expectsOutputToContain('Database writes performed: 0')
        ->assertExitCode(0);

    $this->assertDatabaseHas('people', ['id' => $person->id, 'nama' => 'Udin']);
    $this->assertDatabaseHas('participations', ['id' => $participation->id]);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'person_id' => $person->id]);
});

// ---------------------------------------------------------------------------
// Ambiguity safety
// ---------------------------------------------------------------------------

test('multiple persons with the same name abort and list candidates', function () {
    $first = ppd_makePerson('Udin');
    $second = ppd_makePerson('Udin');

    $this->artisan('person:purge-dummy', ['names' => ['Udin']])
        ->expectsOutputToContain("Multiple Persons named 'Udin'")
        ->expectsOutputToContain("#{$first->id}: 'Udin'")
        ->expectsOutputToContain("#{$second->id}: 'Udin'")
        ->assertExitCode(1);

    $this->assertDatabaseHas('people', ['id' => $first->id]);
    $this->assertDatabaseHas('people', ['id' => $second->id]);
});

test('--id targets the correct record among same-name candidates', function () {
    ppd_makePerson('Udin');
    $target = ppd_makePerson('Udin');

    $this->artisan('person:purge-dummy', ['--id' => [$target->id], '--apply' => true])
        ->expectsOutputToContain("Person #{$target->id}: 'Udin'")
        ->assertExitCode(0);

    $this->assertDatabaseMissing('people', ['id' => $target->id]);
    expect(Person::count())->toBe(1);
});

test('missing name is reported and nothing is deleted', function () {
    ppd_makePerson('Udin');

    $this->artisan('person:purge-dummy', ['names' => ['Bira'], '--apply' => true])
        ->assertExitCode(1);

    expect(Person::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Apply
// ---------------------------------------------------------------------------

test('apply hard-deletes person, participation, attendance and legacy mappings', function () {
    $event = ppd_makeEvent();
    $person = ppd_makePerson('Udin');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);
    ppd_makeLegacyChain($person, $participation, $event);

    $this->artisan('person:purge-dummy', ['names' => ['Udin'], '--apply' => true])
        ->expectsOutputToContain('VERIFICATION OK')
        ->assertExitCode(0);

    $this->assertDatabaseMissing('people', ['id' => $person->id]);
    $this->assertDatabaseMissing('participations', ['id' => $participation->id]);
    $this->assertDatabaseMissing('event_attendances', ['participation_id' => $participation->id]);
    $this->assertDatabaseMissing('legacy_peserta_mappings', ['person_id' => $person->id]);
    $this->assertDatabaseMissing('legacy_participation_mappings', ['person_id' => $person->id]);
});

test('apply only affects the targeted person, never other people', function () {
    $event = ppd_makeEvent();
    $udin = ppd_makePerson('Udin');
    $udinPart = ppd_makeParticipation($udin, $event);
    ppd_makeAttendance($udinPart, $event);

    $keep = ppd_makePerson('Budi Santoso');
    $keepPart = ppd_makeParticipation($keep, $event);
    ppd_makeAttendance($keepPart, $event);
    $keepLegacy = ppd_makeLegacyChain($keep, $keepPart, $event);

    $this->artisan('person:purge-dummy', ['names' => ['Udin'], '--apply' => true])->assertExitCode(0);

    $this->assertDatabaseMissing('people', ['id' => $udin->id]);
    $this->assertDatabaseHas('people', ['id' => $keep->id, 'nama' => 'Budi Santoso']);
    $this->assertDatabaseHas('participations', ['id' => $keepPart->id]);
    $this->assertDatabaseHas('event_attendances', ['participation_id' => $keepPart->id]);
    $this->assertDatabaseHas('pesertas', ['id' => $keepLegacy->id]);
    $this->assertDatabaseHas('legacy_participation_mappings', ['person_id' => $keep->id]);
    $this->assertDatabaseHas('legacy_peserta_mappings', ['person_id' => $keep->id]);
});

test('apply nulls users.person_id instead of deleting the user', function () {
    $event = ppd_makeEvent();
    $person = ppd_makePerson('Bira');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);
    $user = ppd_makeUserLinked($person);

    $this->artisan('person:purge-dummy', ['names' => ['Bira'], '--apply' => true])->assertExitCode(0);

    $this->assertDatabaseMissing('people', ['id' => $person->id]);
    $this->assertDatabaseHas('users', ['id' => $user->id, 'person_id' => null]);
});

test('apply deletes identity corrections and committee assignments', function () {
    $event = ppd_makeEvent();
    $person = ppd_makePerson('Udin');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);
    ppd_makeIdentityCorrection($person);
    ppd_makeCommitteeAssignment($person, $event);

    $this->artisan('person:purge-dummy', ['names' => ['Udin'], '--apply' => true])->assertExitCode(0);

    $this->assertDatabaseMissing('identity_correction_requests', ['person_id' => $person->id]);
    $this->assertDatabaseMissing('event_committee_assignments', ['person_id' => $person->id]);
});

test('apply keeps master data intact (events, desa, kelompok, regu, legacy peserta)', function () {
    $event = ppd_makeEvent();
    $person = ppd_makePerson('Udin');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);
    $legacy = ppd_makeLegacyChain($person, $participation, $event);
    $regu = regu::create(['regu' => 'Regu Intact '.str()->random(5)]);

    $this->artisan('person:purge-dummy', ['names' => ['Udin'], '--apply' => true])->assertExitCode(0);

    $this->assertDatabaseHas('events', ['id' => $event->id]);
    $this->assertDatabaseHas('pesertas', ['id' => $legacy->id]);
    $this->assertDatabaseHas('desas', ['id' => $legacy->desa_id]);
    $this->assertDatabaseHas('kelompoks', ['id' => $legacy->kelompok_id]);
    $this->assertDatabaseHas('regus', ['id' => $regu->id]);
});

test('apply writes a backup file before deleting', function () {
    $backupPath = sys_get_temp_dir().'/ppd-backup-'.str()->random(8).'.json';

    $event = ppd_makeEvent();
    $person = ppd_makePerson('Udin');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);

    $this->artisan('person:purge-dummy', [
        'names' => ['Udin'],
        '--apply' => true,
        '--backup-path' => $backupPath,
    ])->assertExitCode(0);

    $this->assertFileExists($backupPath);
    $payload = json_decode((string) file_get_contents($backupPath), true);
    expect($payload['person_ids'])->toContain($person->id)
        ->and($payload['participation_ids'])->toContain($participation->id)
        ->and($payload['rows']['event_attendances'])->toHaveCount(1);

    @unlink($backupPath);
});

test('apply is idempotent - second run has nothing to purge', function () {
    $event = ppd_makeEvent();
    $person = ppd_makePerson('Udin');
    $participation = ppd_makeParticipation($person, $event);
    ppd_makeAttendance($participation, $event);

    $this->artisan('person:purge-dummy', ['names' => ['Udin'], '--apply' => true])->assertExitCode(0);

    $this->artisan('person:purge-dummy', ['names' => ['Udin'], '--apply' => true])
        ->expectsOutputToContain('No Person matched')
        ->assertExitCode(1);

    $this->assertDatabaseMissing('people', ['id' => $person->id]);
});
