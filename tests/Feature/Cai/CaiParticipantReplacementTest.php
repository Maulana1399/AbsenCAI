<?php

use App\Models\Absensi;
use App\Models\CaiParticipantReplacement;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Enums\Role;
use App\Models\User;
use App\Services\Cai\CaiParticipantReplacementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function caiUser(string $role): User
{
    return User::factory()->create(['role' => $role]);
}

function caiReplacementFixture(): array
{
    $event = Event::create([
        'name' => 'CAI Replacement Test',
        'slug' => 'cai-replacement-test',
        'status' => 'active',
        'event_type' => 'cai',
    ]);

    $desa = desa::create([
        'desa_asal' => 'Desa Replacement',
    ]);

    $kelompok = kelompok::create([
        'kelompok_asal' => 'Kelompok Replacement',
        'desa_id' => $desa->id,
    ]);

    $regu = regu::create([
        'regu' => 'Regu Replacement',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $peserta = peserta::create([
        'nama' => 'Peserta Lama',
        'nip' => 1001,
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
        'jenis_kelamin' => 'L',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'nip' => 1001,
    ]);

    $oldParticipation = Participation::create([
        'person_id' => $oldPerson->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-OLD0001',
        'jenis_peserta' => peserta::JENIS_WAJIB,
    ]);

    $mapping = LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $oldPerson->id,
        'participation_id' => $oldParticipation->id,
        'event_id' => $event->id,
        'legacy_nip' => 1001,
        'legacy_participant_number' => 'KL001',
        'legacy_attendance_code' => 'KJA-OLD0001',
        'migrated_at' => now(),
    ]);

    return compact(
        'event',
        'desa',
        'kelompok',
        'regu',
        'peserta',
        'oldPerson',
        'oldParticipation',
        'mapping',
    );
}

test('peserta without operational history is replaceable', function () {
    $fixture = caiReplacementFixture();

    $mapping = app(CaiParticipantReplacementService::class)
        ->assertReplaceable($fixture['peserta']);

    expect($mapping->id)->toBe($fixture['mapping']->id);
});

test('peserta with legacy attendance cannot be replaced', function () {
    $fixture = caiReplacementFixture();

    Absensi::create([
        'nip' => $fixture['peserta']->nip,
        'nama' => $fixture['peserta']->nama,
        'jam_scan' => now(),
        'sesi_id' => null,
    ]);

    expect(fn () => app(CaiParticipantReplacementService::class)
        ->assertReplaceable($fixture['peserta']))
        ->toThrow(
            RuntimeException::class,
            'Peserta sudah memiliki riwayat absensi dan tidak dapat diganti.'
        );
});

test('unauthorized role cannot execute replacement via livewire request', function () {
    $fixture = caiReplacementFixture();
    $this->actingAs(caiUser(Role::OperatorScan->value));

    Livewire\Livewire::test(\App\Livewire\Database\Peserta\GantiPeserta::class)
        ->set('peserta_id', $fixture['peserta']->id)
        ->set('nama', 'Hacked Replacement')
        ->set('jenis_kelamin', 'Laki - Laki')
        ->set('reason', 'Unauthorized attempt')
        ->call('replace')
        ->assertForbidden();
});

test('replacement preserves cai slot and moves mapping to new identity', function () {
    $fixture = caiReplacementFixture();

    $result = app(CaiParticipantReplacementService::class)->replace(
        $fixture['peserta'],
        [
            'nama' => 'Peserta Pengganti',
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        reason: 'Peserta lama berhalangan hadir',
    );

    $peserta = $fixture['peserta']->fresh();
    $mapping = $fixture['mapping']->fresh();
    $oldParticipation = $fixture['oldParticipation']->fresh();
    $oldPerson = $fixture['oldPerson']->fresh();

    expect($peserta->id)->toBe($fixture['peserta']->id)

        // Identitas orang berubah.
        ->and($peserta->nama)->toBe('Peserta Pengganti')

        // Slot CAI tetap.
        ->and($peserta->nip)->toBe(1001)
        ->and($peserta->participant_number)->toBe('KL001')
        ->and($peserta->attendance_code)->toBe('KJA-OLD0001')
        ->and($peserta->desa_id)->toBe($fixture['desa']->id)
        ->and($peserta->kelompok_id)->toBe($fixture['kelompok']->id)
        ->and($peserta->regu_id)->toBe($fixture['regu']->id)

        // Person lama tidak dihapus.
        ->and(Person::find($fixture['oldPerson']->id))->not->toBeNull()
        ->and($oldPerson->nip)->toBeNull()
        ->and($result['person']->nip)->toBe(1001)   

        // Participation lama tetap ada tetapi identifier operasional dilepas.
        ->and($oldParticipation)->not->toBeNull()
        ->and($oldParticipation->participant_number)->toBeNull()
        ->and($oldParticipation->attendance_code)->toBeNull()

        // Mapping sekarang menunjuk identitas baru.
        ->and($mapping->person_id)->toBe($result['person']->id)
        ->and($mapping->participation_id)->toBe($result['participation']->id)

        // Participation baru mengambil identifier slot.
        ->and($result['participation']->participant_number)->toBe('KL001')
        ->and($result['participation']->attendance_code)->toBe('KJA-OLD0001')

        // Audit replacement tercatat.
        ->and(CaiParticipantReplacement::count())->toBe(1);

    $replacement = CaiParticipantReplacement::first();

    expect($replacement->event_id)->toBe($fixture['event']->id)
        ->and($replacement->peserta_id)->toBe($fixture['peserta']->id)
        ->and($replacement->old_person_id)->toBe($fixture['oldPerson']->id)
        ->and($replacement->old_participation_id)->toBe($fixture['oldParticipation']->id)
        ->and($replacement->new_person_id)->toBe($result['person']->id)
        ->and($replacement->new_participation_id)->toBe($result['participation']->id)
        ->and($replacement->legacy_nip)->toBe(1001)
        ->and($replacement->participant_number)->toBe('KL001')
        ->and($replacement->attendance_code)->toBe('KJA-OLD0001')
        ->and($replacement->reason)->toBe('Peserta lama berhalangan hadir');
});

test('replacement cannot be performed on non cai event', function () {
    $fixture = caiReplacementFixture();

    $fixture['event']->update([
        'event_type' => 'pengajian',
    ]);

    expect(fn () => app(CaiParticipantReplacementService::class)->replace(
        $fixture['peserta'],
        [
            'nama' => 'Peserta Pengganti',
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        reason: 'Tidak boleh karena bukan event CAI',
    ))->toThrow(
        RuntimeException::class,
        'Penggantian peserta hanya dapat dilakukan pada event CAI.'
    );

    expect($fixture['peserta']->fresh()->nama)->toBe('Peserta Lama')
        ->and($fixture['oldPerson']->fresh()->nip)->toBe(1001)
        ->and($fixture['oldParticipation']->fresh()->participant_number)->toBe('KL001')
        ->and($fixture['oldParticipation']->fresh()->attendance_code)->toBe('KJA-OLD0001')
        ->and(CaiParticipantReplacement::count())->toBe(0);
});

test('replacement transaction rolls back when new person creation fails', function () {
    $fixture = caiReplacementFixture();

    expect(fn () => app(CaiParticipantReplacementService::class)->replace(
        $fixture['peserta'],
        [
            'jenis_kelamin' => 'Laki - Laki',
            'tanggal_lahir' => '2000-01-15',
        ],
        reason: 'Simulasi kegagalan transaction',
    ))->toThrow(
        RuntimeException::class,
        'Nama peserta pengganti wajib diisi.'
    );

    $peserta = $fixture['peserta']->fresh();
    $oldPerson = $fixture['oldPerson']->fresh();
    $oldParticipation = $fixture['oldParticipation']->fresh();
    $mapping = $fixture['mapping']->fresh();

    expect($peserta->nama)->toBe('Peserta Lama')
        ->and($peserta->nip)->toBe(1001)

        // NIP Person lama harus kembali karena transaction rollback.
        ->and($oldPerson->nip)->toBe(1001)

        // Identifier Participation lama juga harus kembali.
        ->and($oldParticipation->participant_number)->toBe('KL001')
        ->and($oldParticipation->attendance_code)->toBe('KJA-OLD0001')

        // Mapping tidak boleh berubah.
        ->and($mapping->person_id)->toBe($fixture['oldPerson']->id)
        ->and($mapping->participation_id)->toBe($fixture['oldParticipation']->id)

        // Tidak boleh ada audit replacement.
        ->and(CaiParticipantReplacement::count())->toBe(0);
});