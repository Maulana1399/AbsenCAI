<?php

namespace App\Livewire\Database\Peserta;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Livewire\Traits\HasCascadingKelompok;
use App\Services\Person\PersonDuplicateDetectionService;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TambahPeserta extends Component
{
    use HasCascadingKelompok;
    public bool $processing = false;

    public string $mode = 'baru';

    public $nama = '';
    public $tanggal_lahir = null;
    public $daftarDesa = [];
    public $desa_id;
    public $daftarKelompok = [];
    public $kelompok_id;
    public $regu_id;
    public string $regu_nama = '-';
    public $jenis_kelamin = 'Laki - Laki';
    public $daftarJenisKelamin = ['Laki - Laki', 'Perempuan'];
    public $jenis_peserta = 'Wajib';

    public $searchPerson = '';
    public $searchResults = [];
    public $selectedPersonId = null;
    public $selectedPerson = null;
    public $existingJenisPeserta = 'Wajib';
    public $errorMessage = '';

    public bool $showDuplicateWarning = false;
    public array $duplicateCandidates = [];
    public ?int $duplicateTargetId = null;
    public bool $bypassDuplicateCheck = false;

    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->loadKelompokByDesa('daftarKelompok');
        $this->setDefaultDesaKelompok();
        $this->generateAutoFields();
    }

    private function setDefaultDesaKelompok(): void
    {
        $firstDesa = $this->daftarDesa->first();
        $this->desa_id = $firstDesa?->id;
        $this->loadKelompokByDesa('daftarKelompok');

        if ($firstDesa) {
            $firstKelompok = $this->daftarKelompok->firstWhere('desa_id', $firstDesa->id)
                ?? $this->daftarKelompok->first();
            $this->kelompok_id = $firstKelompok?->id;
        } else {
            $this->kelompok_id = $this->daftarKelompok->first()?->id;
        }
    }

    public function generateAutoFields(): void
    {
        $eventId = app(ActiveEventContext::class)->id();
        $autoPlacement = PlacementService::autoPlacement($this->jenis_kelamin ?: null, $eventId);

        $this->regu_id = $autoPlacement['regu_id'];
        $this->regu_nama = $autoPlacement['regu_nama'];
    }

    public function updatedJenisKelamin(): void
    {
        $this->generateAutoFields();
    }

    public function simpan()
    {
        Gate::authorize('manage-participants');

        if ($this->processing) {
            return;
        }
        $this->processing = true;
        $this->errorMessage = '';

        try {
            $this->generateAutoFields();

            $this->validate([
                'nama'          => ['required', 'string', 'max:255'],
                'tanggal_lahir' => ['nullable', 'date'],
                'jenis_kelamin' => 'required|in:Laki - Laki,Perempuan',
                'jenis_peserta' => 'required|in:Wajib,Kiriman,Person',
                'desa_id'       => 'required|exists:desas,id',
                'kelompok_id'   => 'required|exists:kelompoks,id',
                'regu_id'       => 'nullable|exists:regus,id',
            ]);

            if ($this->showDuplicateWarning && $this->duplicateTargetId) {
                return $this->createFromDuplicateCandidate();
            }

            if ($this->bypassDuplicateCheck) {
                $this->bypassDuplicateCheck = false;
                app(RegistrationService::class)->createParticipant([
                    'nama'              => $this->nama,
                    'tanggal_lahir'     => $this->tanggal_lahir ?: null,
                    'jenis_kelamin'     => $this->jenis_kelamin,
                    'jenis_peserta'     => $this->jenis_peserta,
                    'desa_id'           => $this->desa_id,
                    'kelompok_id'       => $this->kelompok_id,
                    'regu_id'           => $this->regu_id,
                    'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
                    '_force_new_person'  => true,
                ]);
                return redirect()->to('/database');
            }

            if ($this->shouldBlockDueToDuplicate()) {
                return null;
            }

            app(RegistrationService::class)->createParticipant([
                'nama'             => $this->nama,
                'tanggal_lahir'    => $this->tanggal_lahir ?: null,
                'jenis_kelamin'    => $this->jenis_kelamin,
                'jenis_peserta'    => $this->jenis_peserta,
                'desa_id'          => $this->desa_id,
                'kelompok_id'      => $this->kelompok_id,
                'regu_id'          => $this->regu_id,
                'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
            ]);

            return redirect()->to('/database');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->errorMessage = 'Terjadi kesalahan: ' . $e->getMessage();
        } finally {
            $this->processing = false;
        }
    }

    private function shouldBlockDueToDuplicate(): bool
    {
        if ($this->bypassDuplicateCheck) {
            $this->bypassDuplicateCheck = false;
            return false;
        }

        $service = app(PersonDuplicateDetectionService::class);
        $result = $service->detect([
            'nama' => $this->nama,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'desa_id' => $this->desa_id,
        ]);

        if (! empty($result['strong'])) {
            $candidate = $result['strong'][0];
            $activeEvent = app(ActiveEventContext::class)->current();

            if ($activeEvent) {
                $alreadyRegistered = Participation::where('person_id', $candidate->id)
                    ->where('event_id', $activeEvent->id)
                    ->exists();

                if ($alreadyRegistered) {
                    throw ValidationException::withMessages([
                        'nama' => 'Peserta ini sudah terdaftar pada event aktif.',
                    ]);
                }
            }

            $this->errorMessage = 'Orang dengan nama dan tanggal lahir yang sama sudah terdaftar. '
                . 'Gunakan fitur "Tambahkan Peserta yang Sudah Ada" untuk menambahkan ke event ini.';
            return true;
        }

        if (! empty($result['possible'])) {
            $this->duplicateCandidates = array_map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'jenis_kelamin' => $p->jenis_kelamin_label,
                'tanggal_lahir' => $p->tanggal_lahir?->format('Y-m-d'),
                'desa' => $p->desa?->desa_asal,
                'kelompok' => $p->kelompok?->kelompok_asal,
            ], $result['possible']);

            $this->showDuplicateWarning = true;
            return true;
        }

        return false;
    }

    public function useExistingPerson(int $personId): void
    {
        $this->selectedPersonId = $personId;
        $this->showDuplicateWarning = false;
        $this->duplicateCandidates = [];
        $this->errorMessage = '';

        $this->dispatch('switchMode', 'existing');
        $this->mode = 'existing';

        $person = Person::with(['desa', 'kelompok'])->find($personId);
        if ($person) {
            $this->selectedPerson = [
                'nama' => $person->nama,
                'desa' => $person->desa?->desa_asal,
                'kelompok' => $person->kelompok?->kelompok_asal,
                'jenis_kelamin' => $person->jenis_kelamin_label,
                'regu' => $person->participations->first()?->regu?->regu,
            ];
        }
    }

    public function ignoreDuplicateWarning(): void
    {
        $this->showDuplicateWarning = false;
        $this->duplicateCandidates = [];
        $this->bypassDuplicateCheck = true;
    }

    private function createFromDuplicateCandidate()
    {
        $personId = $this->duplicateTargetId;
        if (! $personId) {
            return redirect()->to('/database');
        }

        $activeEvent = app(ActiveEventContext::class)->current();
        if (! $activeEvent) {
            $this->errorMessage = 'Tidak ada event aktif.';
            return redirect()->to('/database');
        }

        $alreadyRegistered = Participation::where('person_id', $personId)
            ->where('event_id', $activeEvent->id)
            ->exists();

        if ($alreadyRegistered) {
            $this->errorMessage = 'Peserta ini sudah terdaftar pada event aktif.';
            return redirect()->to('/database');
        }

        $this->selectedPersonId = $personId;

        return $this->tambahkanKeEvent();
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->nama = '';
        $this->tanggal_lahir = null;
        $this->jenis_kelamin = 'Laki - Laki';
        $this->searchPerson = '';
        $this->searchResults = [];
        $this->selectedPersonId = null;
        $this->selectedPerson = null;
        $this->errorMessage = '';
        $this->existingJenisPeserta = 'Wajib';
        $this->showDuplicateWarning = false;
        $this->duplicateCandidates = [];
        $this->duplicateTargetId = null;

        if ($mode === 'baru') {
            $this->daftarDesa = desa::all();
            $this->loadKelompokByDesa('daftarKelompok');
            $this->setDefaultDesaKelompok();
            $this->generateAutoFields();
        }
    }

    public function updatedSearchPerson(): void
    {
        $query = trim($this->searchPerson);

        if (strlen($query) < 2) {
            $this->searchResults = [];
            return;
        }

        $results = Person::where(function ($q) use ($query) {
            $q->where('nama', 'like', "%{$query}%");
        })
        ->with(['desa', 'kelompok', 'participations' => fn ($q) => $q->with('regu')->latest()->limit(1)])
        ->limit(10)
        ->get();

        $this->searchResults = $results->map(function ($p) {
            $latestRegu = $p->participations->first()?->regu?->regu;
            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'desa' => $p->desa?->desa_asal,
                'kelompok' => $p->kelompok?->kelompok_asal,
                'jenis_kelamin' => $p->jenis_kelamin_label,
                'regu' => $latestRegu,
            ];
        })->toArray();
    }

    public function selectPerson(int $id): void
    {
        $person = Person::with(['desa', 'kelompok', 'participations' => fn ($q) => $q->with('regu')->latest()->limit(1)])->findOrFail($id);

        $this->selectedPersonId = $person->id;
        $this->selectedPerson = [
            'nama' => $person->nama,
            'desa' => $person->desa?->desa_asal,
            'kelompok' => $person->kelompok?->kelompok_asal,
            'jenis_kelamin' => $person->jenis_kelamin_label,
            'regu' => $person->participations->first()?->regu?->regu,
        ];
        $this->searchPerson = '';
        $this->searchResults = [];
    }

    public function tambahkanKeEvent()
    {
        Gate::authorize('manage-participants');

        if ($this->processing) {
            return null;
        }
        $this->errorMessage = '';
        $this->processing = true;

        try {
            $activeEvent = app(ActiveEventContext::class)->current();

            if (! $activeEvent) {
                $this->errorMessage = 'Tidak ada event aktif.';
                return null;
            }

            if (! $this->selectedPersonId) {
                $this->errorMessage = 'Pilih peserta terlebih dahulu.';
                return null;
            }

            $person = Person::with('legacyPesertaMapping')->find($this->selectedPersonId);

            if (! $person) {
                $this->errorMessage = 'Peserta tidak ditemukan.';
                return null;
            }

            $alreadyRegistered = Participation::where('person_id', $person->id)
                ->where('event_id', $activeEvent->id)
                ->exists();

            if ($alreadyRegistered) {
                $this->errorMessage = 'Peserta ini sudah terdaftar pada event aktif.';
                return null;
            }

            $placement = PlacementService::autoPlacement(
                $person->jenis_kelamin_label,
                $activeEvent->id,
            );

            $participantNumber = PlacementService::generateParticipantNumber(
                $activeEvent->id,
                $person->jenis_kelamin_label,
            );

            $attendanceCode = app(RegistrationService::class)->generateAttendanceCode();

            $participation = Participation::create([
                'person_id' => $person->id,
                'event_id' => $activeEvent->id,
                'participant_number' => $participantNumber,
                'attendance_code' => $attendanceCode,
                'jenis_peserta' => $this->existingJenisPeserta,
                'regu_id' => $placement['regu_id'],
            ]);

            $legacyPeserta = $person->legacyPesertaMapping?->peserta;

            if ($legacyPeserta) {
                LegacyParticipationMapping::create([
                    'peserta_id' => $legacyPeserta->id,
                    'person_id' => $person->id,
                    'participation_id' => $participation->id,
                    'event_id' => $activeEvent->id,
                    'migrated_at' => now(),
                ]);
            }

            return redirect()->to('/database');
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.database.peserta.tambah-peserta');
    }
}
