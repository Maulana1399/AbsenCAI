<?php

use App\Models\peserta;
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
