<?php

use App\Livewire\QRLabel\Index as QRLabelIndex;
use App\Models\User;
use App\Models\peserta;
use App\Services\QR\QRService;
use Livewire\Livewire;

it('QR label print route encodes attendance code and keeps participant number as label only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $participant = peserta::create([
        'nama' => 'Peserta QR Print',
        'nip' => 4001,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-QRPRINT1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);

    $fake = new class extends QRService {
        public array $calls = [];

        public function generatePng(string $attendanceCode): string
        {
            $this->calls[] = $attendanceCode;

            return 'png-binary';
        }
    };

    app()->instance(QRService::class, $fake);

    $response = $this->get('/qr-label/print/selected/'.$participant->id);

    $response->assertOk();
    $response->assertSee('KL001', false);
    $response->assertSee('Peserta QR Print', false);

    $fake = app(QRService::class);
    expect($fake->calls)->toContain('KJA-QRPRINT1')
        ->and($fake->calls)->not->toContain('4001')
        ->and($fake->calls)->not->toContain('KL001');
});

it('QR label Livewire download uses attendance code payload', function () {
    $participant = peserta::create([
        'nama' => 'Peserta QR Livewire',
        'nip' => 4002,
        'participant_number' => 'KP001',
        'attendance_code' => 'KJA-QRLIVE1',
        'jenis_kelamin' => 'Perempuan',
    ]);

    $fake = new class extends QRService {
        public array $calls = [];

        public function generatePng(string $attendanceCode): string
        {
            $this->calls[] = $attendanceCode;

            return 'livewire-png';
        }
    };

    app()->instance(QRService::class, $fake);

    $response = Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $participant->id)
        ->set('selectedParticipant', $participant)
        ->call('downloadPng');

    $response->assertOk();
    expect($fake->calls)->not->toBeEmpty();
    expect(collect($fake->calls)->every(fn ($call) => $call === 'KJA-QRLIVE1'))->toBeTrue();
});
