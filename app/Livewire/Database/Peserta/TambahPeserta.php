<?php

namespace App\Livewire\Database\Peserta;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\LegacyParticipationMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TambahPeserta extends Component
{
    public bool $processing = false;

    public string $mode = 'baru';

    public $nama = '';
    public $nip = '';
    public $daftarDesa = [];
    public $desa_id;
    public $daftarKelompok = [];
    public $kelompok_id;
    public $regu_id;
    public string $regu_nama = '-';
    public $jenis_kelamin;
    public $daftarJenisKelamin = ['Laki - Laki', 'Perempuan'];
    public $jenis_peserta = 'Wajib';

    public $searchPerson = '';
    public $searchResults = [];
    public $selectedPersonId = null;
    public $selectedPerson = null;
    public $existingJenisPeserta = 'Wajib';
    public $errorMessage = '';

    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->daftarKelompok = kelompok::all();
        $this->generateAutoFields();
    }

    public function generateAutoFields(): void
    {
        $eventId = app(ActiveEventContext::class)->id();
        $autoPlacement = PlacementService::autoPlacement($this->jenis_kelamin ?: null, $eventId);

        $this->nip = $autoPlacement['nip'];
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

        try {
            $this->generateAutoFields();

            $this->validate([
                'nama'         => ['required', 'string', 'max:255'],
                'jenis_kelamin' => 'required|in:Laki - Laki,Perempuan',
                'jenis_peserta' => 'required|in:Wajib,Kiriman,Person',
                'desa_id'      => 'required|exists:desas,id',
                'kelompok_id'  => 'required|exists:kelompoks,id',
                'regu_id'      => 'required|exists:regus,id',
                'nip'          => ['required', 'integer'],
            ]);

            $existingPerson = Person::where('nama', $this->nama)
                ->where('desa_id', $this->desa_id)
                ->where('kelompok_id', $this->kelompok_id)
                ->first();

            if ($existingPerson) {
                $activeEvent = app(ActiveEventContext::class)->current();

                if ($activeEvent) {
                    $alreadyRegistered = Participation::where('person_id', $existingPerson->id)
                        ->where('event_id', $activeEvent->id)
                        ->exists();

                    if ($alreadyRegistered) {
                        throw ValidationException::withMessages([
                            'nama' => 'Peserta ini sudah terdaftar pada event aktif.',
                        ]);
                    }
                }

                $nip = $existingPerson->nip ?? (int) $this->nip;
            } else {
                $nipValidator = validator(['nip' => $this->nip], [
                    'nip' => [
                        'required',
                        'integer',
                        Rule::unique('people', 'nip'),
                        Rule::unique('pesertas', 'nip'),
                    ],
                ]);

                if ($nipValidator->fails()) {
                    $this->addError('nip', $nipValidator->errors()->first('nip'));
                    return;
                }

                $nip = (int) $this->nip;
            }

            app(RegistrationService::class)->createParticipant([
                'nama'             => $this->nama,
                'nip'              => $nip,
                'jenis_kelamin'    => $this->jenis_kelamin,
                'jenis_peserta'    => $this->jenis_peserta,
                'desa_id'          => $this->desa_id,
                'kelompok_id'      => $this->kelompok_id,
                'regu_id'          => $this->regu_id,
                'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
            ]);

            return redirect()->to('/database');
        } finally {
            $this->processing = false;
        }
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->searchPerson = '';
        $this->searchResults = [];
        $this->selectedPersonId = null;
        $this->selectedPerson = null;
        $this->errorMessage = '';
        $this->existingJenisPeserta = 'Wajib';

        if ($mode === 'baru') {
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
            $q->where('nama', 'like', "%{$query}%")
              ->orWhere('nip', 'like', "%{$query}%");
        })
        ->with(['desa', 'kelompok', 'participations' => fn ($q) => $q->with('regu')->latest()->limit(1)])
        ->limit(10)
        ->get();

        $this->searchResults = $results->map(function ($p) {
            $latestRegu = $p->participations->first()?->regu?->regu;
            return [
                'id' => $p->id,
                'nama' => $p->nama,
                'nip' => $p->nip,
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
            'nip' => $person->nip,
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
            return;
        }
        $this->errorMessage = '';
        $this->processing = true;

        try {
            $activeEvent = app(ActiveEventContext::class)->current();

            if (! $activeEvent) {
                $this->errorMessage = 'Tidak ada event aktif.';
                return;
            }

            if (! $this->selectedPersonId) {
                $this->errorMessage = 'Pilih peserta terlebih dahulu.';
                return;
            }

            $person = Person::with('legacyPesertaMapping')->find($this->selectedPersonId);

            if (! $person) {
                $this->errorMessage = 'Peserta tidak ditemukan.';
                return;
            }

            $alreadyRegistered = Participation::where('person_id', $person->id)
                ->where('event_id', $activeEvent->id)
                ->exists();

            if ($alreadyRegistered) {
                $this->errorMessage = 'Peserta ini sudah terdaftar pada event aktif.';
                return;
            }

            $legacyPeserta = $person->legacyPesertaMapping?->peserta;

            if (! $legacyPeserta) {
                $this->errorMessage = 'Data legacy peserta tidak ditemukan.';
                return;
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

            LegacyParticipationMapping::create([
                'peserta_id' => $legacyPeserta->id,
                'person_id' => $person->id,
                'participation_id' => $participation->id,
                'event_id' => $activeEvent->id,
                'migrated_at' => now(),
            ]);

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
