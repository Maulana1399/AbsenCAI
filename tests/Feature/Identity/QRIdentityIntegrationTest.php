<?php

use App\Livewire\QRLabel\Index as QRLabelIndex;
use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Models\peserta;
use App\Services\QR\QRService;
use App\Support\ActiveEventContext;

use Livewire\Livewire;

it('QR label print route encodes attendance code and keeps participant number as label only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $event = Event::create([
        'name' => 'QR Print Event',
        'slug' => 'qr-print-event-'.str()->random(6),
        'status' => 'active',
    ]);
    $person = Person::create([
        'nama' => 'Peserta QR Print',
        'nip' => 4001,
        'jenis_kelamin' => 'L',
    ]);
    $participant = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-QRPRINT1',
        'jenis_peserta' => 'Wajib',
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

    $legacy = peserta::create([
        'nama' => 'Peserta QR Print',
        'nip' => 4001,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-QRPRINT1',
        'jenis_kelamin' => 'Laki - Laki',
    ]);
    LegacyPesertaMapping::create([
        'peserta_id' => $legacy->id,
        'person_id' => $person->id,
        'participation_id' => $participant->id,
        'event_id' => $event->id,
    ]);

    app(ActiveEventContext::class)->set($event);

    $response = $this->get('/qr-label/print/selected/'.$legacy->id);

    $response->assertOk();
    $response->assertSee('KL001', false);
    $response->assertSee('Peserta QR Print', false);

    $fake = app(QRService::class);
    expect($fake->calls)->toContain('KJA-QRPRINT1')
        ->and($fake->calls)->not->toContain('4001')
        ->and($fake->calls)->not->toContain('KL001');
});

it('QR label Livewire download uses attendance code payload', function () {
    $event = Event::create([
        'name' => 'QR Livewire Event',
        'slug' => 'qr-livewire-event-'.str()->random(6),
        'status' => 'active',
    ]);
    $person = Person::create([
        'nama' => 'Peserta QR Livewire',
        'nip' => 4002,
        'jenis_kelamin' => 'P',
    ]);
    $participant = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KP001',
        'attendance_code' => 'KJA-QRLIVE1',
        'jenis_peserta' => 'Wajib',
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

    app(ActiveEventContext::class)->set($event);

    $response = Livewire::test(QRLabelIndex::class)
        ->set('selectedLabelParticipantId', $participant->id)
        ->call('downloadPng');

    $response->assertOk();
    expect($fake->calls)->not->toBeEmpty();
    expect(collect($fake->calls)->every(fn ($call) => $call === 'KJA-QRLIVE1'))->toBeTrue();
});

it('QR label print preview uses attendance code payload and participant number label only', function () {
    $event = Event::create([
        'name' => 'QR Print Preview Event',
        'slug' => 'qr-print-preview-event-'.str()->random(6),
        'status' => 'active',
    ]);
    $person = Person::create([
        'nama' => 'Peserta QR Preview',
        'nip' => 4003,
        'jenis_kelamin' => 'L',
    ]);
    $participant = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL777',
        'attendance_code' => 'KJA-QRPREV1',
        'jenis_peserta' => 'Wajib',
    ]);

    $fake = new class extends QRService {
        public array $calls = [];

        public function generatePng(string $attendanceCode): string
        {
            $this->calls[] = $attendanceCode;

            return 'preview-png';
        }
    };

    app()->instance(QRService::class, $fake);

    $response = Livewire::test(QRLabelIndex::class)
        ->set('selectedLabelParticipantId', $participant->id)
        ->call('printSelectedLabel');

    $response->assertOk();
    expect($fake->calls)->not->toBeEmpty();
    expect(array_values(array_unique($fake->calls)))->toBe(['KJA-QRPREV1']);
    expect(collect($fake->calls)->contains('KL777'))->toBeFalse();
    expect(collect($fake->calls)->contains(4003))->toBeFalse();
});
