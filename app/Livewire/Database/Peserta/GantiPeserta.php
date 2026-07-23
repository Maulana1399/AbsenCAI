<?php

namespace App\Livewire\Database\Peserta;

use App\Models\Participation;
use App\Models\peserta;
use App\Services\Cai\CaiParticipantReplacementService;
use Illuminate\Support\Facades\Gate;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;
use Throwable;

class GantiPeserta extends Component
{
    public ?int $peserta_id = null;

    public string $nama_lama = '';
    public ?string $nip = null;
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

        $participation = Participation::with(['person.desa', 'person.kelompok', 'regu', 'person.legacyPesertaMapping.peserta'])->findOrFail($id);

        $peserta = $participation->person?->legacyPesertaMapping?->peserta
            ?? peserta::with(['desa', 'kelompok', 'regu'])->find($id);

        if ($peserta === null) {
            $this->errorMessage = 'Data legacy peserta tidak ditemukan.';
            Flux::modal('ganti-peserta-error')->show();
            return;
        }

        try {
            app(CaiParticipantReplacementService::class)
                ->assertReplaceable($peserta);
        } catch (RuntimeException $e) {
            $this->errorMessage = $e->getMessage();

            Flux::modal('ganti-peserta-error')->show();

            return;
        }

        $this->peserta_id = $peserta->id;
        $this->nama_lama = $peserta->nama;
        $this->nip = (string) $peserta->nip;

        $this->desa = $peserta->desa?->desa_asal ?? '-';
        $this->kelompok = $peserta->kelompok?->kelompok_asal ?? '-';
        $this->regu = $participation->regu?->regu ?? $peserta->regu?->regu ?? '-';

        $this->jenis_kelamin = $peserta->jenis_kelamin ?? '';

        Flux::modal('ganti-peserta')->show();
    }

    public function replace(): void
    {
        Gate::authorize('manage-participants');

        $validated = $this->validate([
            'peserta_id' => ['required', 'integer'],
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
            $peserta = peserta::findOrFail($validated['peserta_id']);

            app(CaiParticipantReplacementService::class)->replace(
                $peserta,
                [
                    'nama' => $validated['nama'],
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'tanggal_lahir' => $validated['tanggal_lahir'] ?: null,
                ],
                $validated['reason'],
            );

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

        $this->peserta_id = null;
        $this->nama_lama = '';
        $this->nip = null;
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