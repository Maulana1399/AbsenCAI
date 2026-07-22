<?php

use App\Livewire\Database\Peserta\HapusPeserta;
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
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function per_event(string $suffix): Event
{
    return Event::create([
        'name' => 'PER Event ' . $suffix,
        'slug' => 'per-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function per_fixture(): array
{
    $desa = desa::create(['desa_asal' => 'PER Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'PER Kelompok', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'PER Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = per_event('a');
    $eventB = per_event('b');
    $person = Person::create(['nama' => 'Remove Person', 'nip' => 99001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $legacy = peserta::create(['nama' => 'Remove Person', 'nip' => 99001, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'regu_id' => $regu->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KA001', 'attendance_code' => 'KJA-PERA001', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KB001', 'attendance_code' => 'KJA-PERB001', 'jenis_peserta' => 'Wajib']);
    $mapping = LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'legacy_nip' => 99001, 'legacy_participant_number' => 'KL001', 'legacy_attendance_code' => 'KJA-PERLEG1', 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'eventB', 'person', 'legacy', 'partA', 'partB', 'mapping');
}

function per_single_fixture(): array
{
    $desa = desa::create(['desa_asal' => 'PER Single Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'PER Single Kelompok', 'desa_id' => $desa->id]);
    $regu = regu::create(['regu' => 'PER Single Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = per_event('single-a');
    $person = Person::create(['nama' => 'Remove Single', 'nip' => 99011, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $legacy = peserta::create(['nama' => 'Remove Single', 'nip' => 99011, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'regu_id' => $regu->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KA101', 'attendance_code' => 'KJA-SINGLEA01', 'jenis_peserta' => 'Wajib']);
    $mapping = LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'legacy_nip' => 99011, 'legacy_participant_number' => 'KL101', 'legacy_attendance_code' => 'KJA-SINGLELEG1', 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'person', 'legacy', 'partA', 'mapping');
}

beforeEach(function () {
    $this->actingAs(\App\Models\User::factory()->create(['role' => 'admin']));
});

// ... existing tests unchanged ...

test('last participation removal is blocked under current schema', function () {
    $fixture = per_single_fixture();
    app(ActiveEventContext::class)->set($fixture['eventA']);

    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $fixture['legacy']->id)
        ->assertSet('canDelete', false)
        ->assertSet('blockReason', fn ($value) => str_contains($value, 'legacy'));

    expect(Person::find($fixture['person']->id))->not->toBeNull()
        ->and(peserta::find($fixture['legacy']->id))->not->toBeNull()
        ->and(Participation::find($fixture['partA']->id))->not->toBeNull()
        ->and(LegacyParticipationMapping::find($fixture['mapping']->id))->not->toBeNull()
        ->and(LegacyPesertaMapping::find($fixture['mapping']->id))->not->toBeNull();
});

