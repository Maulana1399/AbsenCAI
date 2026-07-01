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
    public string $nama = '';

    public string $nip = '';

    public string $desa_id = '';

    public string $kelompok_id = '';

    public $daftarDesa = [];

    public $daftarKelompok = [];

    public function mount(): void
    {
        $this->daftarDesa = desa::orderBy('desa_asal')->get();
        $this->daftarKelompok = kelompok::with('desa')->orderBy('kelompok_asal')->get();
    }

    public function register(): void
    {
        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'integer'],
            'desa_id' => ['required', Rule::exists('desas', 'id')],
            'kelompok_id' => ['required', Rule::exists('kelompoks', 'id')],
        ]);

        peserta::create([
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'jenis_kelamin' => null,
            'desa_id' => $validated['desa_id'],
            'kelompok_id' => $validated['kelompok_id'],
            'regu_id' => null,
            'status_registrasi' => peserta::STATUS_SELF_REGISTER,
        ]);

        session()->flash('self_register', [
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'desa' => desa::find($validated['desa_id'])?->desa_asal,
            'kelompok' => kelompok::find($validated['kelompok_id'])?->kelompok_asal,
        ]);

        $this->redirect(route('register.success', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.registrasi.self-register');
    }
}
