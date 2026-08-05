<?php

namespace App\Livewire\Database\Peserta;

use App\Models\CaiParticipantReplacement;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Cai\CaiParticipantReplacementService;
use App\Services\Placement\PlacementService;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;
use Throwable;

class GantiPeserta extends Component
{
    public ?int $participation_id = null;

    public ?int $event_id = null;

    public string $nama_lama = '';

    public string $participant_number = '';

    public string $desa = '-';

    public string $kelompok = '-';

    public string $regu = '-';

    public string $nama = '';

    public string $jenis_kelamin = '';

    public ?string $tanggal_lahir = null;

    public string $reason = '';

    public string $errorMessage = '';

    #[On('gantiPeserta')]
    public function open(int $id): void
    {
        $this->resetForm();

        $participation = Participation::with(['event', 'person.desa', 'person.kelompok', 'regu', 'person.legacyPesertaMapping.peserta'])->findOrFail($id);

        $event = $participation->event;

        if (! $event || ! $event->isCai()) {
            $this->errorMessage = 'Penggantian peserta hanya dapat dilakukan pada event CAI.';
            Flux::modal('ganti-peserta-error')->show();

            return;
        }

        $this->participation_id = $participation->id;
        $this->event_id = $participation->event_id;

        $legacyPeserta = $participation->person?->legacyPesertaMapping?->peserta;

        if ($legacyPeserta) {
            try {
                app(CaiParticipantReplacementService::class)
                    ->assertReplaceable($legacyPeserta);
            } catch (RuntimeException $e) {
                $this->errorMessage = $e->getMessage();
                Flux::modal('ganti-peserta-error')->show();

                return;
            }

            $this->nama_lama = $legacyPeserta->nama;
            $this->participant_number = $legacyPeserta->participant_number ?? '';
            $this->desa = $legacyPeserta->desa?->desa_asal ?? '-';
            $this->kelompok = $legacyPeserta->kelompok?->kelompok_asal ?? '-';
            $this->jenis_kelamin = $legacyPeserta->jenis_kelamin ?? '';
        } else {
            $this->nama_lama = $participation->person?->nama ?? '-';
            $this->participant_number = $participation->participant_number ?? '';
            $this->desa = $participation->person?->desa?->desa_asal ?? '-';
            $this->kelompok = $participation->person?->kelompok?->kelompok_asal ?? '-';
            $this->jenis_kelamin = $participation->person?->jenis_kelamin_label ?? '';
        }

        $this->regu = $participation->regu?->regu ?? '-';

        Flux::modal('ganti-peserta')->show();
    }

    public function replace(): void
    {
        Gate::authorize('manage-participants');

        $validated = $this->validate([
            'participation_id' => ['required', 'integer'],
            'event_id' => ['required', 'integer'],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'in:Laki - Laki,Perempuan'],
            'tanggal_lahir' => ['nullable', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'nama.required' => 'Nama peserta pengganti wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'tanggal_lahir.date_format' => 'Format tanggal lahir tidak valid.',
            'reason.required' => 'Alasan penggantian wajib diisi.',
        ]);

        try {
            $oldParticipation = Participation::with('person.legacyPesertaMapping.peserta')->findOrFail($validated['participation_id']);

            $event = \App\Models\Event::findOrFail($validated['event_id']);

            if (! $event->isCai()) {
                throw new RuntimeException('Penggantian peserta hanya dapat dilakukan pada event CAI.');
            }

            $legacyPeserta = $oldParticipation->person?->legacyPesertaMapping?->peserta;

            DB::transaction(function () use ($validated, $oldParticipation, $legacyPeserta, $event) {
                $oldParticipation->refresh();
                $oldParticipation = Participation::query()->lockForUpdate()->findOrFail($oldParticipation->id);

                $participantNumber = $oldParticipation->participant_number;
                $attendanceCode = $oldParticipation->attendance_code;

                $genderLabel = $legacyPeserta
                    ? $legacyPeserta->jenis_kelamin ?? $validated['jenis_kelamin']
                    : $oldParticipation->person?->jenis_kelamin_label ?? $validated['jenis_kelamin'];

                if ($genderLabel !== $validated['jenis_kelamin']) {
                    $participantNumber = PlacementService::generateParticipantNumber(
                        $event->id,
                        $validated['jenis_kelamin'],
                    );
                }

                if ($legacyPeserta) {
                    app(CaiParticipantReplacementService::class)->replace(
                        $legacyPeserta,
                        [
                            'nama' => $validated['nama'],
                            'jenis_kelamin' => $validated['jenis_kelamin'],
                            'tanggal_lahir' => $validated['tanggal_lahir'] ?: null,
                        ],
                        $validated['reason'],
                    );
                } else {
                    $oldParticipation->update([
                        'participant_number' => null,
                        'attendance_code' => null,
                    ]);

                    $newPerson = Person::create([
                        'nama' => $validated['nama'],
                        'jenis_kelamin' => $validated['jenis_kelamin'] === 'Perempuan' ? 'P' : 'L',
                        'tanggal_lahir' => $validated['tanggal_lahir'] ?: null,
                        'desa_id' => $oldParticipation->person?->desa_id,
                        'kelompok_id' => $oldParticipation->person?->kelompok_id,
                    ]);

                    $newParticipation = Participation::create([
                        'person_id' => $newPerson->id,
                        'event_id' => $event->id,
                        'participant_number' => $participantNumber,
                        'attendance_code' => $attendanceCode,
                        'jenis_peserta' => $oldParticipation->jenis_peserta,
                        'regu_id' => $oldParticipation->regu_id,
                    ]);

                    CaiParticipantReplacement::create([
                        'event_id' => $event->id,
                        'old_person_id' => $oldParticipation->person_id,
                        'old_participation_id' => $oldParticipation->id,
                        'new_person_id' => $newPerson->id,
                        'new_participation_id' => $newParticipation->id,
                        'participant_number' => $participantNumber,
                        'attendance_code' => $attendanceCode,
                        'desa_id' => $oldParticipation->person?->desa_id,
                        'kelompok_id' => $oldParticipation->person?->kelompok_id,
                        'regu_id' => $oldParticipation->regu_id,
                        'reason' => $validated['reason'],
                        'replaced_by' => auth()->id(),
                        'replaced_at' => now(),
                    ]);
                }
            });

            Flux::modal('ganti-peserta')->close();

            $this->dispatch('refreshPeserta');

            $this->resetForm();
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();

            Flux::modal('ganti-peserta')->close();
            Flux::modal('ganti-peserta-error')->show();
        }
    }

    private function resetForm(): void
    {
        $this->resetValidation();

        $this->participation_id = null;
        $this->event_id = null;
        $this->nama_lama = '';
        $this->participant_number = '';
        $this->desa = '-';
        $this->kelompok = '-';
        $this->regu = '-';

        $this->nama = '';
        $this->jenis_kelamin = '';
        $this->tanggal_lahir = null;
        $this->reason = '';

        $this->errorMessage = '';
    }

    public function render()
    {
        return view('livewire.database.peserta.ganti-peserta');
    }
}
