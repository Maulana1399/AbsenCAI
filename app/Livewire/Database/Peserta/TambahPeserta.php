<?php

namespace App\Livewire\Database\Peserta;

use App\Models\desa;
use App\Models\kelompok;
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

    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->daftarKelompok = kelompok::all();
        $this->generateAutoFields();
    }

    public function generateAutoFields(): void
    {
        $autoPlacement = PlacementService::autoPlacement($this->jenis_kelamin ?: null);

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
                // CASE B or C — Person exists. Check if same-event (Case C).
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

                // Case B: existing Person, new event — NIP dari Person, bukan dari form
                $nip = $existingPerson->nip ?? (int) $this->nip;
            } else {
                // Case A: new Person — validate NIP uniqueness against both tables
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

    public function render()
    {
        return view('livewire.database.peserta.tambah-peserta');
    }
}
