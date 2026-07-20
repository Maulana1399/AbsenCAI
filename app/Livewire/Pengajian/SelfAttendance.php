<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\Person;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\IdentityCorrectionService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianIdentityService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class SelfAttendance extends Component
{
    public string $nonce;

    public ?string $eventName = null;
    public ?string $desaName = null;

    public int $step = 1;

    public string $query = '';
    public array $searchResults = [];
    public bool $searching = false;

    public ?int $selectedPersonId = null;
    public ?string $selectedPersonName = null;
    public bool $selectedPersonHasBirthDate = false;
    public ?string $selectedPersonBirthDateMasked = null;

    public string $birthDate = '';
    public bool $birthDateVerified = false;
    public bool $verificationSkipped = false;

    public string $correctionReason = '';
    public string $correctionName = '';
    public string $correctionBirthDate = '';
    public bool $correctionSubmitted = false;

    public ?string $errorMessage = null;

    public bool $processing = false;
    public bool $attendanceDone = false;
    public bool $attendanceAlreadyExists = false;

    public function mount(string $nonce): void
    {
        $this->nonce = $nonce;

        $resolved = app(DesaAccessService::class)->resolveNonce($nonce);

        if ($resolved === null) {
            $this->errorMessage = 'QR absen tidak valid atau sudah kedaluwarsa.';
            return;
        }

        $event = Event::find($resolved['event_id']);
        $desa = desa::find($resolved['desa_id']);

        if ($event === null || $desa === null) {
            $this->errorMessage = 'Data event atau desa tidak ditemukan.';
            return;
        }

        $this->eventName = $event->name;
        $this->desaName = $desa->desa_asal;
    }

    public function search(): void
    {
        $grant = $this->resolveValidGrant();

        if ($grant === null) {
            return;
        }

        $this->searching = true;
        $this->searchResults = [];
        $this->errorMessage = null;

        try {
            $results = app(PengajianIdentityService::class)
                ->searchPersons($grant, $this->query);
            $this->searchResults = $results;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Terjadi kesalahan saat mencari data.';
        } finally {
            $this->searching = false;
        }
    }

    public function selectPerson(int $personId): void
    {
        $this->errorMessage = null;
        $this->selectedPersonId = $personId;
        $this->birthDate = '';
        $this->birthDateVerified = false;
        $this->verificationSkipped = false;
        $this->correctionReason = '';
        $this->correctionSubmitted = false;

        $resolved = app(DesaAccessService::class)->resolveNonce($this->nonce);

        if ($resolved === null) {
            $this->errorMessage = 'Sesi QR sudah kedaluwarsa. Silakan scan QR ulang.';
            $this->selectedPersonId = null;
            return;
        }

        $person = app(PengajianIdentityService::class)
            ->findPersonInDesa($personId, $resolved['desa_id']);

        if ($person === null) {
            $this->errorMessage = 'Data peserta tidak valid.';
            $this->selectedPersonId = null;
            return;
        }

        $this->selectedPersonName = $person->nama;
        $this->selectedPersonHasBirthDate = $person->tanggal_lahir !== null;
        $this->selectedPersonBirthDateMasked = $person->tanggal_lahir?->format('d M');

        if ($this->selectedPersonHasBirthDate) {
            $this->step = 3;
        } else {
            $this->step = 4;
        }
    }

    public function verifyBirthDate(): void
    {
        if ($this->selectedPersonId === null) {
            return;
        }

        $this->errorMessage = null;

        $person = Person::find($this->selectedPersonId);

        if ($person === null) {
            $this->errorMessage = 'Data peserta tidak ditemukan.';
            return;
        }

        $verified = app(PengajianIdentityService::class)
            ->verifyBirthDate($person, $this->birthDate);

        if ($verified) {
            $this->birthDateVerified = true;
            $this->step = 4;
        } else {
            $this->errorMessage = 'Tanggal lahir tidak sesuai. Silakan coba lagi.';
        }
    }

    public function proceedWithoutBirthDate(): void
    {
        $this->verificationSkipped = true;
        $this->step = 4;
    }

    public function confirmAttendance(): void
    {
        if ($this->selectedPersonId === null) {
            $this->errorMessage = 'Sesi tidak valid. Silakan scan QR ulang.';
            return;
        }

        $grant = DesaAccessGrant::where('nonce', $this->nonce)->first();

        if ($grant === null) {
            $this->errorMessage = 'Sesi QR tidak valid. Silakan scan QR ulang.';
            return;
        }

        $person = app(PengajianIdentityService::class)
            ->findPersonInDesa($this->selectedPersonId, $grant->desa_id);

        if ($person === null) {
            $this->errorMessage = 'Data peserta tidak valid.';
            return;
        }

        $this->processing = true;
        $this->errorMessage = null;

        try {
            app(PengajianAttendanceService::class)->attendPersonPublicContext(
                $person,
                $grant,
            );

            $this->attendanceDone = true;
            $this->attendanceAlreadyExists = false;
            $this->step = 5;
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();

            if ($message === 'Peserta sudah tercatat hadir.') {
                $this->attendanceDone = true;
                $this->attendanceAlreadyExists = true;
                $this->step = 5;
            } else {
                $this->errorMessage = match ($message) {
                    'Person tidak memiliki desa assignment.' => 'Data peserta belum memiliki desa. Silakan hubungi operator.',
                    'Person tidak terdaftar di desa ini.' => 'Peserta tidak terdaftar di desa ini.',
                    'Sesi QR tidak valid atau sudah kedaluwarsa.' => 'Sesi QR sudah kedaluwarsa. Silakan scan QR ulang.',
                    default => 'Terjadi kesalahan. Silakan coba lagi.',
                };
            }
        } catch (\Illuminate\Database\QueryException $e) {
            $this->errorMessage = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            report($e);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
            report($e);
        } finally {
            $this->processing = false;
        }
    }

    public function submitCorrection(): void
    {
        if ($this->selectedPersonId === null) {
            $this->errorMessage = 'Sesi tidak valid.';
            return;
        }

        $grant = $this->resolveValidGrant();

        if ($grant === null) {
            return;
        }

        $person = app(PengajianIdentityService::class)
            ->findPersonInDesa($this->selectedPersonId, $grant->desa_id);

        if ($person === null) {
            $this->errorMessage = 'Data peserta tidak valid.';
            return;
        }

        $this->processing = true;
        $this->errorMessage = null;

        $proposed = ['reason' => $this->correctionReason ?: null];

        if (trim($this->correctionName) !== '') {
            $proposed['requested_name'] = trim($this->correctionName);
        }

        if (trim($this->correctionBirthDate) !== '') {
            $proposed['requested_birth_date'] = trim($this->correctionBirthDate);
        }

        try {
            app(IdentityCorrectionService::class)->submitFromPublicContext(
                $person,
                $grant,
                $proposed,
            );

            $this->correctionSubmitted = true;
        } catch (\Throwable $e) {
            $this->errorMessage = match ($e->getMessage()) {
                'Permintaan koreksi yang identik sudah menunggu review.' => 'Permintaan koreksi sudah dikirim sebelumnya.',
                'Tidak ada perubahan data yang perlu dikoreksi.' => 'Tidak ada perubahan data. Isi field yang ingin diperbaiki.',
                default => 'Terjadi kesalahan saat mengirim koreksi.',
            };
        } finally {
            $this->processing = false;
        }
    }

    public function resetSearch(): void
    {
        $this->step = 1;
        $this->query = '';
        $this->searchResults = [];
        $this->selectedPersonId = null;
        $this->selectedPersonName = null;
        $this->selectedPersonHasBirthDate = false;
        $this->selectedPersonBirthDateMasked = null;
        $this->birthDate = '';
        $this->birthDateVerified = false;
        $this->verificationSkipped = false;
        $this->correctionReason = '';
        $this->correctionName = '';
        $this->correctionBirthDate = '';
        $this->correctionSubmitted = false;
        $this->errorMessage = null;
        $this->attendanceDone = false;
        $this->attendanceAlreadyExists = false;
    }

    public function render()
    {
        return view('livewire.pengajian.self-attendance');
    }

    private function resolveValidGrant(): ?DesaAccessGrant
    {
        $nonce = trim($this->nonce);

        if (strlen($nonce) < 10) {
            $this->errorMessage = 'Sesi tidak valid. Silakan scan QR ulang.';
            return null;
        }

        $grant = DesaAccessGrant::where('nonce', $nonce)->first();

        if ($grant === null) {
            $this->errorMessage = 'Sesi tidak valid. Silakan scan QR ulang.';
            return null;
        }

        if (! $grant->isNonceValid()) {
            $this->errorMessage = 'Sesi QR sudah kedaluwarsa. Silakan scan QR ulang.';
            return null;
        }

        return $grant;
    }
}
