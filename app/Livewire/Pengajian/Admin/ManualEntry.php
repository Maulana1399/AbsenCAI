<?php

namespace App\Livewire\Pengajian\Admin;

use App\Livewire\Traits\HandlesManualEntryResult;
use App\Models\desa;
use App\Models\kelompok;
use App\Services\Registration\ManualParticipantRegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ManualEntry extends Component
{
    use HandlesManualEntryResult;

    public bool $processing = false;

    public string $desaId = '';

    public string $nama = '';

    public string $jenisKelamin = '';

    public string $tanggalLahir = '';

    public string $kelompokId = '';

    public int $step = 1;

    public string $errorMessage = '';

    public string $successMessage = '';

    public array $potentialMatches = [];

    public ?string $resultPersonName = null;

    public ?string $resultParticipantNumber = null;

    public ?int $resultPersonId = null;

    public ?int $resultParticipationId = null;

    public function rules(): array
    {
        return [
            'desaId' => ['required', 'exists:desas,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenisKelamin' => ['required', 'in:L,P'],
            'tanggalLahir' => ['required', 'date', 'before:today'],
            'kelompokId' => ['required', 'exists:kelompoks,id'],
        ];
    }

    protected $messages = [
        'desaId.required' => 'Desa harus dipilih.',
        'desaId.exists' => 'Desa tidak valid.',
        'nama.required' => 'Nama harus diisi.',
        'jenisKelamin.required' => 'Jenis kelamin harus dipilih.',
        'jenisKelamin.in' => 'Jenis kelamin tidak valid.',
        'tanggalLahir.required' => 'Tanggal lahir harus diisi.',
        'tanggalLahir.date' => 'Format tanggal lahir tidak valid.',
        'tanggalLahir.before' => 'Tanggal lahir harus sebelum hari ini.',
        'kelompokId.required' => 'Kelompok harus dipilih.',
        'kelompokId.exists' => 'Kelompok tidak valid.',
    ];

    public function submit(): void
    {
        Gate::authorize('manage-pengajian');

        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->potentialMatches = [];

        try {
            $event = app(ActiveEventContext::class)->current();

            if ($event === null) {
                $this->errorMessage = 'Tidak ada event aktif. Silakan pilih event terlebih dahulu.';
                $this->processing = false;

                return;
            }

            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: (int) $validated['desaId'],
                eventId: $event->id,
                kelompokId: (int) $validated['kelompokId'],
            );

            $this->handleResult($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->processing = false;
            throw $e;
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
            $this->processing = false;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
            $this->processing = false;
        }
    }

    public function confirmMatch(int $personId): void
    {
        Gate::authorize('manage-pengajian');

        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $event = app(ActiveEventContext::class)->current();

            if ($event === null) {
                $this->errorMessage = 'Tidak ada event aktif.';
                $this->processing = false;

                return;
            }

            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: (int) $validated['desaId'],
                eventId: $event->id,
                kelompokId: (int) $validated['kelompokId'],
                forcePersonId: $personId,
            );

            $this->handleResult($result);
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
            $this->processing = false;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
            $this->processing = false;
        }
    }

    public function createNewPerson(): void
    {
        Gate::authorize('manage-pengajian');

        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $event = app(ActiveEventContext::class)->current();

            if ($event === null) {
                $this->errorMessage = 'Tidak ada event aktif.';
                $this->processing = false;

                return;
            }

            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: (int) $validated['desaId'],
                eventId: $event->id,
                kelompokId: (int) $validated['kelompokId'],
                forceCreateNew: true,
            );

            $this->handleResult($result);
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
            $this->processing = false;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
            $this->processing = false;
        }
    }

    public function resetForm(): void
    {
        $this->step = 1;
        $this->nama = '';
        $this->jenisKelamin = '';
        $this->tanggalLahir = '';
        $this->kelompokId = '';
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->potentialMatches = [];
        $this->resultPersonName = null;
        $this->resultParticipantNumber = null;
        $this->resultPersonId = null;
        $this->resultParticipationId = null;
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $kelompoks = $this->desaId
            ? kelompok::where('desa_id', $this->desaId)->orderBy('kelompok_asal')->get()
            : collect();

        return view('livewire.pengajian.admin.manual-entry', [
            'activeEvent' => $event,
            'desas' => desa::orderBy('desa_asal')->get(['id', 'desa_asal']),
            'kelompoks' => $kelompoks,
        ]);
    }
}
