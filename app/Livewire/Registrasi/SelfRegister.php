<?php

namespace App\Livewire\Registrasi;

use App\Livewire\Traits\HasCascadingKelompok;
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
    use HasCascadingKelompok;

    public bool $processing = false;

    public string $nama = '';

    public ?string $tanggal_lahir = null;

    public string $jenis_kelamin = '';

    public string $jenis_peserta = peserta::JENIS_WAJIB;

    public string $desa_id = '';

    public string $kelompok_id = '';

    public ?int $regu_id = null;

    public string $regu_nama = '-';

    public string $warningMessage = '';

    public $daftarDesa = [];

    public $daftarKelompok = [];

    public function mount(): void
    {
        $this->daftarDesa = desa::orderBy('desa_asal')->get();
        $this->loadKelompokByDesa('daftarKelompok');
        $this->fillReguPlacement();
    }

    public function fillReguPlacement(): void
    {
        $eventId = app(ActiveEventContext::class)->id();
        $autoPlacement = PlacementService::autoPlacement($this->jenis_kelamin ?: null, $eventId);

        $this->regu_id = $autoPlacement['regu_id'];
        $this->regu_nama = $autoPlacement['regu_nama'];
    }

    public function updatedJenisKelamin(): void
    {
        $this->fillReguPlacement();
    }

    public function register(): void
    {
        $eventId = app(ActiveEventContext::class)->id();

        logger()->info('SelfRegister register: method entry', [
            'nama' => $this->nama,
            'tanggal_lahir' => $this->tanggal_lahir,
            'jenis_kelamin' => $this->jenis_kelamin,
            'jenis_peserta' => $this->jenis_peserta,
            'desa_id' => $this->desa_id,
            'kelompok_id' => $this->kelompok_id,
            'event_id' => $eventId,
        ]);

        Gate::authorize('manage-registration');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            logger()->info('SelfRegister register: before validate', [
                'nama' => $this->nama,
                'jenis_kelamin' => $this->jenis_kelamin,
                'jenis_peserta' => $this->jenis_peserta,
                'desa_id' => $this->desa_id,
                'kelompok_id' => $this->kelompok_id,
                'event_id' => $eventId,
            ]);

            $this->fillReguPlacement();

            if ($eventId !== null && $this->regu_id === null) {
                $this->warningMessage = 'Registrasi belum dapat dilakukan karena Event ini belum memiliki Regu. Silakan hubungi panitia.';
                logger()->warning('SelfRegister register: regu unavailable', [
                    'nama' => $this->nama,
                    'jenis_kelamin' => $this->jenis_kelamin,
                    'jenis_peserta' => $this->jenis_peserta,
                    'desa_id' => $this->desa_id,
                    'kelompok_id' => $this->kelompok_id,
                    'event_id' => $eventId,
                ]);

                return;
            }

            $validated = $this->validate([
                'nama' => ['required', 'string', 'max:255'],
                'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
                'jenis_kelamin' => ['required', Rule::in(['Laki - Laki', 'Perempuan'])],
                'jenis_peserta' => ['required', Rule::in(peserta::jenisPesertaOptions())],
                'desa_id' => ['required', Rule::exists('desas', 'id')],
                'kelompok_id' => ['required', Rule::exists('kelompoks', 'id')],
                'regu_id' => ['required', Rule::exists('regus', 'id')],
            ]);

            logger()->info('SelfRegister register: after validate', [
                'nama' => $validated['nama'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'jenis_peserta' => $validated['jenis_peserta'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'event_id' => $eventId,
            ]);

            $existingPerson = Person::where('nama', $validated['nama'])
                ->where('desa_id', $validated['desa_id'])
                ->where('kelompok_id', $validated['kelompok_id'])
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
            }

            logger()->info('SelfRegister register: before DB transaction', [
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'jenis_peserta' => $validated['jenis_peserta'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'event_id' => $eventId,
            ]);

            logger()->info('SelfRegister register: inside DB transaction', [
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'jenis_peserta' => $validated['jenis_peserta'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'event_id' => $eventId,
            ]);

            app(RegistrationService::class)->createParticipant([
                'nama' => $validated['nama'],
                'tanggal_lahir' => $validated['tanggal_lahir'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'jenis_peserta' => $validated['jenis_peserta'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'regu_id' => $this->regu_id,
                'status_registrasi' => peserta::STATUS_SELF_REGISTER,
            ]);

            logger()->info('SelfRegister register: after DB transaction', [
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'jenis_peserta' => $validated['jenis_peserta'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'event_id' => $eventId,
            ]);

            session()->flash('self_register', [
                'nama' => $this->nama,
                'desa' => desa::find($this->desa_id)?->desa_asal,
                'kelompok' => kelompok::find($this->kelompok_id)?->kelompok_asal,
            ]);

            logger()->info('SelfRegister register: before redirect', [
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'jenis_peserta' => $validated['jenis_peserta'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'event_id' => $eventId,
            ]);

            $this->redirect(route('register.success', absolute: false), navigate: true);
        } catch (ValidationException $throwable) {
            logger()->error('SelfRegister register: validation exception', [
                'message' => $throwable->getMessage(),
                'errors' => $throwable->errors(),
                'nama' => $this->nama,
                'jenis_kelamin' => $this->jenis_kelamin,
                'jenis_peserta' => $this->jenis_peserta,
                'desa_id' => $this->desa_id,
                'kelompok_id' => $this->kelompok_id,
                'event_id' => $eventId,
            ]);

            throw $throwable;
        } catch (\Throwable $throwable) {
            logger()->error('SelfRegister register: exception', [
                'message' => $throwable->getMessage(),
                'exception' => $throwable::class,
                'nama' => $this->nama,
                'jenis_kelamin' => $this->jenis_kelamin,
                'jenis_peserta' => $this->jenis_peserta,
                'desa_id' => $this->desa_id,
                'kelompok_id' => $this->kelompok_id,
                'event_id' => $eventId,
            ]);

            throw $throwable;
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.registrasi.self-register');
    }
}
