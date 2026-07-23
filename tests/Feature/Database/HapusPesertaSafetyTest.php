<?php

use App\Livewire\Database\Peserta\HapusPeserta;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IzinAbsensi;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function hp_admin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function hp_event(string $suffix): Event
{
    return Event::create([
        'name' => 'HP Event ' . $suffix,
        'slug' => 'hp-' . $suffix . '-' . str()->random(6),
        'status' => 'active',
        'event_type' => 'cai',
    ]);
}

function hp_fixture_multi(): array
{
    $desa = \App\Models\desa::create(['desa_asal' => 'HP Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'HP Kelompok', 'desa_id' => $desa->id]);
    $regu = \App\Models\regu::create(['regu' => 'HP Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = hp_event('a');
    $eventB = hp_event('b');
    $person = Person::create(['nama' => 'HP Remove', 'nip' => 50001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $peserta = peserta::create(['nama' => 'HP Remove', 'nip' => 50001, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'regu_id' => $regu->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KA001', 'attendance_code' => 'KJA-HPA001', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $eventB->id, 'participant_number' => 'KB001', 'attendance_code' => 'KJA-HPB001', 'jenis_peserta' => 'Wajib']);
    $legacy = LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'legacy_nip' => 50001, 'legacy_participant_number' => 'KL500', 'legacy_attendance_code' => 'KJA-HPLEG', 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $eventB->id, 'migrated_at' => now()]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'eventB', 'person', 'peserta', 'partA', 'partB', 'legacy');
}

function hp_fixture_single(): array
{
    $desa = \App\Models\desa::create(['desa_asal' => 'HP Single Desa']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'HP Single Kelompok', 'desa_id' => $desa->id]);
    $regu = \App\Models\regu::create(['regu' => 'HP Single Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $eventA = hp_event('single');
    $person = Person::create(['nama' => 'HP Single', 'nip' => 50011, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $peserta = peserta::create(['nama' => 'HP Single', 'nip' => 50011, 'jenis_kelamin' => 'Laki - Laki', 'jenis_peserta' => 'Wajib', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'regu_id' => $regu->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $eventA->id, 'participant_number' => 'KA101', 'attendance_code' => 'KJA-HPSINGLE', 'jenis_peserta' => 'Wajib']);
    $legacy = LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'legacy_nip' => 50011, 'legacy_participant_number' => 'KL510', 'legacy_attendance_code' => 'KJA-HPSLEG', 'migrated_at' => now()]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $eventA->id, 'migrated_at' => now()]);

    return compact('desa', 'kelompok', 'regu', 'eventA', 'person', 'peserta', 'partA', 'legacy');
}

beforeEach(function () {
    $this->actingAs(hp_admin());
});

test('removing Event A with event blocker keeps Event B intact', function () {
    $fixture = hp_fixture_multi();
    $session = SesiAbsensi::create(['nama_sesi' => 'Test', 'tanggal' => '2026-08-20', 'event_id' => $fixture['eventA']->id, 'aktif' => true]);
    EventAttendance::create(['participation_id' => $fixture['partA']->id, 'sesi_absensi_id' => $session->id, 'event_id' => $fixture['eventA']->id, 'status' => EventAttendance::STATUS_HADIR, 'method' => 'hadir', 'attended_at' => now()]);
    app(ActiveEventContext::class)->set($fixture['eventA']);

    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $fixture['peserta']->id)
        ->assertSet('canDelete', false);

    expect(Participation::find($fixture['partA']->id))->not->toBeNull()
        ->and(Participation::find($fixture['partB']->id))->not->toBeNull()
        ->and(Person::find($fixture['person']->id))->not->toBeNull()
        ->and(peserta::find($fixture['peserta']->id))->not->toBeNull()
        ->and(LegacyPesertaMapping::find($fixture['legacy']->id))->not->toBeNull();
});

test('event A removal succeeds when event B survives', function () {
    $fixture = hp_fixture_multi();
    app(ActiveEventContext::class)->set($fixture['eventA']);

    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $fixture['partA']->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($fixture['partA']->id))->toBeNull()
        ->and(Participation::find($fixture['partB']->id))->not->toBeNull()
        ->and(LegacyParticipationMapping::where('event_id', $fixture['eventA']->id)->count())->toBe(0)
        ->and(LegacyParticipationMapping::where('event_id', $fixture['eventB']->id)->count())->toBe(1)
        ->and(LegacyPesertaMapping::where('peserta_id', $fixture['peserta']->id)->first())->not->toBeNull()
        ->and(Person::find($fixture['person']->id))->not->toBeNull()
        ->and(peserta::find($fixture['peserta']->id))->not->toBeNull();
});

test('single last participation can be removed', function () {
    $fixture = hp_fixture_single();
    app(ActiveEventContext::class)->set($fixture['eventA']);

    Livewire::test(HapusPeserta::class)
        ->dispatch('HapusPeserta', id: $fixture['partA']->id)
        ->assertSet('canDelete', true)
        ->call('destroy');

    expect(Participation::find($fixture['partA']->id))->toBeNull()
        ->and(Person::find($fixture['person']->id))->not->toBeNull()
        ->and(peserta::find($fixture['peserta']->id))->not->toBeNull()
        ->and(LegacyParticipationMapping::where('peserta_id', $fixture['peserta']->id)->count())->toBe(0)
        ->and(LegacyPesertaMapping::find($fixture['legacy']->id))->not->toBeNull();
});
