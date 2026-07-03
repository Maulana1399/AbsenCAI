<?php

namespace App\Livewire\Registrasi;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class SelfRegister extends Component
{
    public bool $processing = false;

    public string $nama = '';

    public string $nip = '';

    public string $jenis_kelamin = '';

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
        $autoPlacement = peserta::autoPlacement($this->jenis_kelamin ?: null);

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
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->fillAutoPlacement();

            $validated = $this->validate([
                'nama' => ['required', 'string', 'max:255'],
                'nip' => ['required', 'integer', Rule::unique('pesertas', 'nip')],
                'jenis_kelamin' => ['required', Rule::in(['Laki - Laki', 'Perempuan'])],
                'desa_id' => ['required', Rule::exists('desas', 'id')],
                'kelompok_id' => ['required', Rule::exists('kelompoks', 'id')],
                'regu_id' => ['required', Rule::exists('regus', 'id')],
            ]);

            peserta::create([
                'nama' => $validated['nama'],
                'nip' => $validated['nip'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'desa_id' => $validated['desa_id'],
                'kelompok_id' => $validated['kelompok_id'],
                'regu_id' => $this->regu_id,
                'status_registrasi' => peserta::STATUS_SELF_REGISTER,
            ]);

            session()->flash('self_register', [
                'nama' => $validated['nama'],
                'nip' => $validated['nip'],
                'desa' => desa::find($validated['desa_id'])?->desa_asal,
                'kelompok' => kelompok::find($validated['kelompok_id'])?->kelompok_asal,
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
