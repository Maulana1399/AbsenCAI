<?php

use App\Models\LegacyPesertaMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Helper: create desa + kelompok records and return their IDs.
 */
function backfillTest_createData(): object
{
    DB::table('desas')->insert(['desa_asal' => 'Test Desa']);
    $desaId = DB::table('desas')->first()->id;

    DB::table('kelompoks')->insert(['kelompok_asal' => 'Kelompok A', 'desa_id' => $desaId]);
    DB::table('kelompoks')->insert(['kelompok_asal' => 'Kelompok B', 'desa_id' => $desaId]);
    DB::table('kelompoks')->insert(['kelompok_asal' => 'Kelompok C', 'desa_id' => $desaId]);

    $kelA = DB::table('kelompoks')->where('kelompok_asal', 'Kelompok A')->first()->id;
    $kelB = DB::table('kelompoks')->where('kelompok_asal', 'Kelompok B')->first()->id;
    $kelC = DB::table('kelompoks')->where('kelompok_asal', 'Kelompok C')->first()->id;

    return (object) compact('desaId', 'kelA', 'kelB', 'kelC');
}

// ===========================================================================
// BackfillPersonKelompok
// ===========================================================================

test('backfill sets kelompok_id on matched person', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User',
        'nip' => 1001,
        'kelompok_id' => $d->kelA,
        'desa_id' => $d->desaId,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);

    DB::table('people')->insert([
        'nama' => 'Test User',
        'nip' => 1001,
        'desa_id' => $d->desaId,
        'jenis_kelamin' => 'L',
        'kelompok_id' => null,
    ]);

    $personId = DB::table('people')->first()->id;

    $this->artisan('app:backfill-person-kelompok')
        ->expectsOutputToContain('Updated')
        ->assertExitCode(0);

    $personKel = DB::table('people')->where('id', $personId)->value('kelompok_id');
    expect((int) $personKel)->toBe($d->kelA);
});

test('backfill is idempotent — second run does nothing', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
        'kelompok_id' => null,
    ]);
    $personId = DB::table('people')->first()->id;

    $this->artisan('app:backfill-person-kelompok')->assertExitCode(0);
    expect((int) DB::table('people')->where('id', $personId)->value('kelompok_id'))->toBe($d->kelA);

    $this->artisan('app:backfill-person-kelompok')
        ->expectsOutputToContain('Nothing to update')
        ->assertExitCode(0);
});

test('backfill skips person with existing kelompok_id conflict', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
        'kelompok_id' => $d->kelB,
    ]);
    $personId = DB::table('people')->first()->id;

    $this->artisan('app:backfill-person-kelompok')
        ->expectsOutputToContain('CONFLICTS')
        ->expectsOutputToContain('Nothing to update')
        ->assertExitCode(0);

    expect((int) DB::table('people')->where('id', $personId)->value('kelompok_id'))->toBe($d->kelB);
});

test('backfill dry-run does not modify data', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
        'kelompok_id' => null,
    ]);
    $personId = DB::table('people')->first()->id;

    $this->artisan('app:backfill-person-kelompok --dry-run')
        ->expectsOutputToContain('DRY RUN')
        ->assertExitCode(0);

    expect(DB::table('people')->where('id', $personId)->value('kelompok_id'))->toBeNull();
});

test('backfill skips unmatched pesertas', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Orphan', 'nip' => 9999,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL099', 'attendance_code' => 'KJA-ORPHAN',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Belum Registrasi',
    ]);

    $this->artisan('app:backfill-person-kelompok')
        ->expectsOutputToContain('Unmatched pesertas')
        ->expectsOutputToContain('Nothing to update')
        ->assertExitCode(0);
});

// ===========================================================================
// RebuildLegacyMappings
// ===========================================================================

test('rebuild creates mappings for matched pairs', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
    ]);
    $pesertaId = DB::table('pesertas')->first()->id;
    $personId = DB::table('people')->first()->id;

    $this->artisan('app:rebuild-legacy-mappings')
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(DB::table('legacy_peserta_mappings')
        ->where('peserta_id', $pesertaId)
        ->where('person_id', $personId)
        ->exists()
    )->toBeTrue();
});

test('rebuild is idempotent', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
    ]);

    $this->artisan('app:rebuild-legacy-mappings')->assertExitCode(0);
    $this->artisan('app:rebuild-legacy-mappings')
        ->expectsOutputToContain('already mapped')
        ->assertExitCode(0);

    expect(DB::table('legacy_peserta_mappings')->count())->toBe(1);
});

test('rebuild dry-run does not create mappings', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test User', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
    ]);

    $this->artisan('app:rebuild-legacy-mappings --dry-run')
        ->expectsOutputToContain('DRY RUN')
        ->assertExitCode(0);

    expect(DB::table('legacy_peserta_mappings')->count())->toBe(0);
});

// ===========================================================================
// ResetEventData — legacy_peserta_mappings preserved
// ===========================================================================

test('reset preserves legacy_peserta_mappings', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test', 'nip' => 1001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL001', 'attendance_code' => 'KJA-TEST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test', 'nip' => 1001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
    ]);
    $pesertaId = DB::table('pesertas')->first()->id;
    $personId = DB::table('people')->first()->id;

    DB::table('legacy_peserta_mappings')->insert([
        'peserta_id' => $pesertaId,
        'person_id' => $personId,
        'participation_id' => null,
        'event_id' => null,
        'legacy_nip' => 1001,
        'legacy_participant_number' => 'KL001',
        'legacy_attendance_code' => 'KJA-TEST',
        'migrated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('app:reset-event-data --dry-run')
        ->assertExitCode(0);

    expect(DB::table('legacy_peserta_mappings')->count())->toBe(1);
});

test('reset deletes legacy_participation_mappings', function () {
    $d = backfillTest_createData();

    DB::table('pesertas')->insert([
        'nama' => 'Test', 'nip' => 9001,
        'kelompok_id' => $d->kelA, 'desa_id' => $d->desaId,
        'participant_number' => 'KL900', 'attendance_code' => 'KJA-TST',
        'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib',
        'status_registrasi' => 'Registrasi Ulang',
    ]);
    DB::table('people')->insert([
        'nama' => 'Test', 'nip' => 9001,
        'desa_id' => $d->desaId, 'jenis_kelamin' => 'L',
    ]);
    DB::table('events')->insert([
        'name' => 'Test Event', 'slug' => 'test-event',
        'status' => 'active', 'event_type' => 'cai',
    ]);
    DB::table('participations')->insert([
        'person_id' => DB::table('people')->first()->id,
        'event_id' => DB::table('events')->first()->id,
        'participant_number' => 'KL900',
        'attendance_code' => 'KJA-TST',
    ]);

    $pId = DB::table('pesertas')->first()->id;
    $peId = DB::table('people')->first()->id;
    $prId = DB::table('participations')->first()->id;
    $eId = DB::table('events')->first()->id;

    DB::table('legacy_participation_mappings')->insert([
        'peserta_id' => $pId,
        'person_id' => $peId,
        'participation_id' => $prId,
        'event_id' => $eId,
    ]);

    expect(DB::table('legacy_participation_mappings')->count())->toBe(1);

    $this->artisan('app:reset-event-data')
        ->assertExitCode(0);

    expect(DB::table('legacy_participation_mappings')->count())->toBe(0);
});
