<?php

namespace App\Livewire\Registrasi;

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
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class SelfRegister extends Component
{
    public bool $processing = false;

    public string $nama = '';

    public string $nip = '';

    public string $jenis_kelamin = '';

    public string $jenis_peserta = peserta::JENIS_WAJIB;

    public string $desa_id = '';

    public string $kelompok_id = '';

    public ?int $regu_id = null;

    public string $regu_nama = '-';

    public $daftarDesa = [];

    public $daftarKelompok = [];

    public function mount(): void
    {
        $this->daftarDesa = desa::orderBy('desa_asal')->get();
        $this->daftarKelompok = kelompok::with('desa')->orderBy('kelompok_asal')->get();
        $this->fillAutoPlacement();
    }

    public function fillAutoPlacement(): void
    {
        $eventId = app(ActiveEventContext::class)->id();
        $autoPlacement = PlacementService::autoPlacement($this->jenis_kelamin ?: null, $eventId);

        $this->nip = $autoPlacement['nip'];
        $this->regu_id = $autoPlacement['regu_id'];
        $this->regu_nama = $autoPlacement['regu_nama'];
    }

    public function updatedJenisKelamin(): void
    {
        $this->fillAutoPlacement();
    }

    public function register(): void
    {
        Gate::authorize('manage-registration');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->fillAutoPlacement();

            $validated = $this->validate([
                'nama'          => ['required', 'string', 'max:255'],
                'jenis_kelamin' => ['required', Rule::in(['Laki - Laki', 'Perempuan'])],
                'jenis_peserta' => ['required', Rule::in(peserta::jenisPesertaOptions())],
                'desa_id'       => ['required', Rule::exists('desas', 'id')],
                'kelompok_id'   => ['required', Rule::exists('kelompoks', 'id')],
                'regu_id'       => ['required', Rule::exists('regus', 'id')],
                'nip'           => ['required', 'integer'],
            ]);

            $existingPerson = Person::where('nama', $validated['nama'])
                ->where('desa_id', $validated['desa_id'])
                ->where('kelompok_id', $validated['kelompok_id'])
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
                $nip = $existingPerson->nip ?? (int) $validated['nip'];
            } else {
                // Case A: new Person — validate NIP uniqueness against both tables
                $nipValidator = validator(['nip' => $validated['nip']], [
                    'nip' => [
                        'required',
                        'integer',
                        Rule::unique('people', 'nip'),
                        Rule::unique('pesertas', 'nip'),
                    ],
                ]);

                if ($nipValidator->fails()) {
                    throw ValidationException::withMessages([
                        'nip' => $nipValidator->errors()->first('nip'),
                    ]);
                }

                $nip = (int) $validated['nip'];
            }

            app(RegistrationService::class)->createParticipant([
                'nama'             => $validated['nama'],
                'nip'              => $nip,
                'jenis_kelamin'    => $validated['jenis_kelamin'],
                'jenis_peserta'    => $validated['jenis_peserta'],
                'desa_id'          => $validated['desa_id'],
                'kelompok_id'      => $validated['kelompok_id'],
                'regu_id'          => $this->regu_id,
                'status_registrasi' => peserta::STATUS_SELF_REGISTER,
            ]);

            session()->flash('self_register', [
                'nama'     => $this->nama,
                'nip'      => $nip,
                'desa'     => desa::find($this->desa_id)?->desa_asal,
                'kelompok' => kelompok::find($this->kelompok_id)?->kelompok_asal,
            ]);

            $this->redirect(route('register.success', absolute: false), navigate: true);
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.registrasi.self-register');
    }
}
