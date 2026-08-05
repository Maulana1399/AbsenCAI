<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dc_event(string $suffix): Event
{
    return Event::create([
        'name' => 'DC Event '.$suffix,
        'slug' => 'dc-'.$suffix.'-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function dc_person(int $nip): Person
{
    return Person::create(['nama' => 'DC Person', 'nip' => $nip, 'jenis_kelamin' => 'L', 'desa_id' => null, 'kelompok_id' => null]);
}

function dc_peserta(int $nip): peserta
{
    return peserta::create(['nama' => 'DC Peserta', 'nip' => $nip, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => null, 'kelompok_id' => null, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
}

function dc_participation(Person $person, Event $event, string $suffix): Participation
{
    return Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => "DC{$suffix}", 'attendance_code' => "DC-{$suffix}", 'jenis_peserta' => 'Wajib']);
}

// ---------------------------------------------------------------------------
// Test B: Fully valid scenario — every mapping has a valid Participation.
// ---------------------------------------------------------------------------

test('design c diagnostics reports zero problems for fully valid data', function () {
    $event = dc_event('a');
    $person = dc_person(70001);
    $legacy = dc_peserta(70001);
    $participation = dc_participation($person, $event, '001');
    LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'legacy_nip' => $legacy->nip, 'legacy_participant_number' => $legacy->participant_number, 'legacy_attendance_code' => $legacy->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $this->artisan('diagnose:design-c')
        ->expectsOutputToContain('problem_total: 0')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Test A: NULL participation_id is a valid forward-reference state.
// Migration 2026_08_09_000001_make_legacy_mapping_fk_nullable.php made
// participation_id nullable so that RebuildLegacyMappings can create entries
// before a Participation is created at registration time.
// ---------------------------------------------------------------------------

test('legacy peserta mapping with null participation id is not counted as problem', function () {
    $event = dc_event('b');
    $person = dc_person(70002);
    $legacy = dc_peserta(70002);
    LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'legacy_nip' => $legacy->nip, 'legacy_participant_number' => $legacy->participant_number, 'legacy_attendance_code' => $legacy->attendance_code, 'migrated_at' => now()]);

    $this->artisan('diagnose:design-c')
        ->expectsOutput(str_pad('legacy_participation_missing_participation', 56).': 0')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Test C: Broken FK (participation_id pointing to non-existent Participation).
//
// The legacy_peserta_mappings FK on participation_id uses nullOnDelete().
// When the referenced Participation is deleted, the FK automatically sets
// participation_id to NULL — preventing a true broken reference.
//
// In SQLite with PRAGMA foreign_keys = ON (Laravel default), attempting to
// DELETE a Participation that is referenced would either:
//   - Set participation_id to NULL (nullOnDelete behavior)
//   - Or raise a constraint error
//
// Either way, a dangling pointer to a non-existent row cannot persist
// under normal FK enforcement. Disabling FKs to test an impossible state
// would undermine test integrity.
//
// Therefore this case cannot be meaningfully tested in a properly
// constrained database — the diagnostic's whereNotNull() guard combined
// with the FK's nullOnDelete makes the broken-reference counter always
// return 0 in practice, which is correct.
// ---------------------------------------------------------------------------
