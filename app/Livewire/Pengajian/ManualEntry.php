<?php

namespace App\Livewire\Pengajian;

use App\Livewire\Traits\HandlesManualEntryResult;
use App\Models\DesaAccessGrant;
use App\Models\kelompok;
use App\Services\Registration\ManualParticipantRegistrationService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.pengajian')]
class ManualEntry extends Component
{
    use HandlesManualEntryResult;

    public bool $processing = false;

    public string $nama = '';

    public string $jenisKelamin = '';

    public string $tanggalLahir = '';

    public string $kelompokId = '';

    public ?string $eventName = null;

    public ?string $desaName = null;

    public int $step = 1;

    public string $errorMessage = '';

    public string $successMessage = '';

    public array $potentialMatches = [];

    public bool $creatingNew = false;

    public ?string $resultPersonName = null;

    public ?string $resultParticipantNumber = null;

    public ?int $resultPersonId = null;

    public ?int $resultParticipationId = null;

    public array $daftarKelompok = [];

    public function mount(): void
    {
        $session = session('pengajian_access');

        if ($session === null || ! isset($session['grant_id'], $session['event_id'], $session['desa_id'])) {
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        $grant = DesaAccessGrant::with('event', 'desa')->find($session['grant_id']);

        if ($grant === null || $grant->revoked_at !== null || now()->greaterThan($grant->valid_until) || now()->lessThan($grant->valid_from)) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        if ((int) $grant->event_id !== (int) $session['event_id'] || (int) $grant->desa_id !== (int) $session['desa_id']) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        $this->eventName = $grant->event->name;
        $this->desaName = $grant->desa->desa_asal;
        $this->daftarKelompok = kelompok::where('desa_id', $grant->desa_id)
            ->orderBy('kelompok_asal')
            ->get()
            ->toArray();
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jenisKelamin' => ['required', 'in:L,P'],
            'tanggalLahir' => ['required', 'date', 'before:today'],
            'kelompokId' => ['required', 'exists:kelompoks,id'],
        ];
    }

    protected $messages = [
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
            $grant = $this->revalidateGrant();
            $eventId = (int) $grant->event_id;
            $desaId = (int) $grant->desa_id;

            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: $desaId,
                eventId: $eventId,
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
            $grant = $this->revalidateGrant();
            $eventId = (int) $grant->event_id;
            $desaId = (int) $grant->desa_id;

            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: $desaId,
                eventId: $eventId,
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
            $grant = $this->revalidateGrant();
            $eventId = (int) $grant->event_id;
            $desaId = (int) $grant->desa_id;

            $validated = $this->validate();

            $result = app(ManualParticipantRegistrationService::class)->register(
                nama: $validated['nama'],
                jenisKelamin: $validated['jenisKelamin'],
                tanggalLahir: $validated['tanggalLahir'],
                desaId: $desaId,
                eventId: $eventId,
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
        $this->creatingNew = false;
        $this->resultPersonName = null;
        $this->resultParticipantNumber = null;
        $this->resultPersonId = null;
        $this->resultParticipationId = null;
    }

    public function goToDashboard(): void
    {
        $this->redirect($this->desaRoute(), navigate: true);
    }

    private function currentEventId(): ?int
    {
        return app(\App\Support\ActiveEventContext::class)->id()
            ?? session('pengajian_access.event_id');
    }

    private function enterTokenRoute(): string
    {
        return route('pengajian.enter-token', ['event' => $this->currentEventId()], absolute: false);
    }

    private function desaRoute(): string
    {
        return route('pengajian.desa', ['event' => $this->currentEventId()], absolute: false);
    }

    public function render()
    {
        return view('livewire.pengajian.manual-entry');
    }

    private function revalidateGrant(): DesaAccessGrant
    {
        $session = session('pengajian_access');

        if ($session === null || ! isset($session['grant_id'], $session['event_id'], $session['desa_id'])) {
            throw new \RuntimeException('Sesi tidak valid. Silakan masuk kembali.');
        }

        $grant = DesaAccessGrant::find($session['grant_id']);

        if ($grant === null) {
            session()->forget('pengajian_access');
            throw new \RuntimeException('Grant tidak ditemukan. Silakan masuk kembali.');
        }

        if ($grant->revoked_at !== null) {
            session()->forget('pengajian_access');
            throw new \RuntimeException('Akses telah dicabut. Silakan hubungi operator daerah.');
        }

        if (now()->greaterThan($grant->valid_until)) {
            session()->forget('pengajian_access');
            throw new \RuntimeException('Masa berlaku akses telah habis. Silakan minta token baru.');
        }

        if (now()->lessThan($grant->valid_from)) {
            session()->forget('pengajian_access');
            throw new \RuntimeException('Token belum dapat digunakan.');
        }

        if ((int) $grant->event_id !== (int) $session['event_id'] || (int) $grant->desa_id !== (int) $session['desa_id']) {
            session()->forget('pengajian_access');
            throw new \RuntimeException('Data grant tidak konsisten. Silakan masuk kembali.');
        }

        return $grant;
    }
}
