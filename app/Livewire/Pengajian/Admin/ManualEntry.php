<?php

namespace App\Livewire\Pengajian\Admin;

use App\Models\Event;
use App\Models\desa;
use App\Models\kelompok;
use App\Services\Registration\ManualParticipantRegistrationService;
use Livewire\Component;

class ManualEntry extends Component
{
    public bool $processing = false;

    public string $eventId = '';
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
            'eventId' => ['required', 'exists:events,id'],
            'desaId' => ['required', 'exists:desas,id'],
            'nama' => ['required', 'string', 'max:255'],
            'jenisKelamin' => ['required', 'in:L,P'],
            'tanggalLahir' => ['required', 'date', 'before:today'],
            'kelompokId' => ['required', 'exists:kelompoks,id'],
        ];
    }

    protected $messages = [
        'eventId.required' => 'Event harus dipilih.',
        'eventId.exists' => 'Event tidak valid.',
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
        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->potentialMatches = [];

        try {
            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: (int) $validated['desaId'],
                eventId: (int) $validated['eventId'],
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
        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: (int) $validated['desaId'],
                eventId: (int) $validated['eventId'],
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
        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: (int) $validated['desaId'],
                eventId: (int) $validated['eventId'],
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
        $kelompoks = $this->desaId
            ? kelompok::where('desa_id', $this->desaId)->orderBy('kelompok_asal')->get()
            : collect();

        return view('livewire.pengajian.admin.manual-entry', [
            'events' => Event::orderBy('name')->get(['id', 'name']),
            'desas' => desa::orderBy('desa_asal')->get(['id', 'desa_asal']),
            'kelompoks' => $kelompoks,
        ]);
    }

    private function handleResult(array $result): void
    {
        $this->processing = false;

        switch ($result['status']) {
            case 'created':
            case 'matched':
                $this->step = 3;
                $this->successMessage = $result['message'];
                $this->resultPersonName = $result['person']->nama;
                $this->resultParticipantNumber = $result['participation']->participant_number;
                $this->resultPersonId = $result['person']->id;
                $this->resultParticipationId = $result['participation']->id;
                break;

            case 'duplicate':
                $this->errorMessage = $result['message'];
                break;

            case 'ambiguous':
                $this->step = 2;
                $this->potentialMatches = $result['potential_matches'];
                $this->errorMessage = $result['message'];
                break;

            default:
                $this->errorMessage = 'Hasil tidak dikenali.';
        }
    }
}
