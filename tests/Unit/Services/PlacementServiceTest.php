<?php

use App\Models\peserta;
use App\Models\regu;
use App\Services\Placement\PlacementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('generate participant number uses gender prefix and padded sequence', function () {
    expect(PlacementService::generateParticipantNumber('Laki - Laki'))->toBe('KL001')
        ->and(PlacementService::generateParticipantNumber('Perempuan'))->toBe('KP001');
});

test('generate participant number continues the next sequence for the same gender prefix', function () {
    peserta::create([
        'nama' => 'Peserta Laki 1',
        'nip' => 1001,
        'participant_number' => 'KL001',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    peserta::create([
        'nama' => 'Peserta Perempuan 1',
        'nip' => 2001,
        'participant_number' => 'KP009',
        'jenis_kelamin' => 'Perempuan',
    ]);

    expect(PlacementService::generateParticipantNumber('Laki - Laki'))->toBe('KL002')
        ->and(PlacementService::generateParticipantNumber('Perempuan'))->toBe('KP010');
});

test('least filled regu picks the regu with the fewest participants for the selected gender', function () {
    $reguA = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $reguB = regu::create(['regu' => 'Regu B', 'jenis_kelamin' => 'Laki - Laki']);

    peserta::create([
        'nama' => 'Peserta 1',
        'nip' => 1001,
        'jenis_kelamin' => 'Laki - Laki',
        'regu_id' => $reguA->id,
    ]);

    peserta::create([
        'nama' => 'Peserta 2',
        'nip' => 1002,
        'jenis_kelamin' => 'Laki - Laki',
        'regu_id' => $reguA->id,
    ]);

    expect(PlacementService::leastFilledRegu('Laki - Laki')?->id)->toBe($reguB->id)
        ->and(PlacementService::leastFilledReguId('Laki - Laki'))->toBe($reguB->id)
        ->and(PlacementService::leastFilledReguName('Laki - Laki'))->toBe('Regu B');
});

test('auto placement uses legacy nip compatibility and least filled regu', function () {
    $reguA = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Perempuan']);
    $reguB = regu::create(['regu' => 'Regu B', 'jenis_kelamin' => 'Perempuan']);

    peserta::create([
        'nama' => 'Peserta 1',
        'nip' => 2001,
        'jenis_kelamin' => 'Perempuan',
        'regu_id' => $reguA->id,
    ]);

    expect(PlacementService::autoPlacement('Perempuan'))->toBe([
        'nip' => '2002',
        'regu_id' => $reguB->id,
        'regu_nama' => 'Regu B',
    ]);
});
