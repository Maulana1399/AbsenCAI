<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dc_event(string $suffix): Event
{
    return Event::create([
        'name' => 'DC Event ' . $suffix,
        'slug' => 'dc-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

test('design c diagnostics reports bridge and participation issues', function () {
    $event = dc_event('a');
    $person = Person::create(['nama' => 'DC Person', 'nip' => 70001, 'jenis_kelamin' => 'L', 'desa_id' => null, 'kelompok_id' => null]);
    $legacy = \App\Models\peserta::create(['nama' => 'DC Person', 'nip' => 70001, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => null, 'kelompok_id' => null, 'regu_id' => null, 'status_registrasi' => \App\Models\peserta::STATUS_BELUM_REGISTRASI]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => 'DC001', 'attendance_code' => 'DC-001', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'legacy_nip' => $legacy->nip, 'legacy_participant_number' => $legacy->participant_number, 'legacy_attendance_code' => $legacy->attendance_code, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $this->artisan('diagnose:design-c')
        ->expectsOutputToContain('Design C Integrity Diagnostic (READ-ONLY)')
        ->expectsOutputToContain('participation_without_person')
        ->expectsOutputToContain('problem_total: 0')
        ->assertExitCode(0);
});
