<?php

use App\Livewire\Database\Peserta\Database;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function pd_event(string $suffix): Event
{
    return Event::create([
        'name' => 'PD Event '.$suffix,
        'slug' => 'pd-'.$suffix.'-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

test('daftar peserta shows only active event participations', function () {
    $eventA = pd_event('a');
    $eventB = pd_event('b');
    $personA = Person::create(['nama' => 'List Person A', 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'List Person B', 'jenis_kelamin' => 'P']);
    $legacy = peserta::create(['nama' => 'Legacy Only', 'nip' => 91003, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => null, 'kelompok_id' => null, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'PA001', 'attendance_code' => 'KJA-PD-A001', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'PB001', 'attendance_code' => 'KJA-PD-B001', 'jenis_peserta' => 'Wajib']);
    LegacyPesertaMapping::create(['peserta_id' => $legacy->id, 'person_id' => $personB->id, 'legacy_nip' => 91003, 'legacy_participant_number' => 'PL001', 'legacy_attendance_code' => 'KJA-PD-L001', 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $personB->id, 'participation_id' => $partB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(Database::class)
        ->assertSee('List Person A')
        ->assertDontSee('List Person B')
        ->assertDontSee('Legacy Only');
});

test('search and delete act on active event participation id', function () {
    $eventA = pd_event('search-a');
    $eventB = pd_event('search-b');
    $personA = Person::create(['nama' => 'Shared Name', 'jenis_kelamin' => 'L']);
    $personB = Person::create(['nama' => 'Shared Name', 'jenis_kelamin' => 'P']);
    $partA = Participation::create(['person_id' => $personA->id, 'event_id' => $eventA->id, 'participant_number' => 'PA002', 'attendance_code' => 'KJA-PD-A002', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $personB->id, 'event_id' => $eventB->id, 'participant_number' => 'PB002', 'attendance_code' => 'KJA-PD-B002', 'jenis_peserta' => 'Wajib']);

    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(Database::class)
        ->set('search', 'Shared')
        ->assertSee('Shared Name')
        ->call('edit', $partA->id)
        ->call('ganti', $partA->id)
        ->call('delete', $partA->id);

    expect(Participation::find($partA->id))->not->toBeNull()
        ->and(Participation::find($partB->id))->not->toBeNull();
});

test('global legacy peserta without active event participation does not appear', function () {
    $eventA = pd_event('isolated');
    $person = Person::create(['nama' => 'Isolated Person', 'jenis_kelamin' => 'L']);
    $legacy = peserta::create(['nama' => 'Isolated Legacy', 'nip' => 93001, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => null, 'kelompok_id' => null, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);

    app(ActiveEventContext::class)->set($eventA);

    Livewire::test(Database::class)
        ->assertDontSee('Isolated Legacy')
        ->assertDontSee('Isolated Person');
});
