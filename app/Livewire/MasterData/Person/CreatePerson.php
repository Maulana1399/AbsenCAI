<?php

namespace App\Livewire\MasterData\Person;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Person;
use App\Livewire\Traits\HasCascadingKelompok;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Flux\Flux;

class CreatePerson extends Component
{
    use HasCascadingKelompok;

    public bool $processing = false;

    public string $nama = '';
    public string $jenis_kelamin = '';
    public string $tanggal_lahir = '';
    public ?string $desa_id = null;
    public ?string $kelompok_id = null;

    public function render()
    {
        return view('livewire.master-data.person.create-person', [
            'daftarDesa' => desa::orderBy('desa_asal')->get(),
            'daftarKelompok' => $this->resolveDesaId()
                ? kelompok::where('desa_id', $this->resolveDesaId())->orderBy('kelompok_asal')->get()
                : kelompok::orderBy('kelompok_asal')->get(),
        ]);
    }

    public function simpan(): void
    {
        Gate::authorize('manage-master-data');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate();

            Person::create([
                'nama' => trim($this->nama),
                'jenis_kelamin' => $this->jenis_kelamin,
                'tanggal_lahir' => $this->tanggal_lahir ?: null,
                'desa_id' => $this->desa_id ?: null,
                'kelompok_id' => $this->kelompok_id ?: null,
            ]);

            $this->dispatch('refreshPerson');

            $this->reset([
                'nama', 'jenis_kelamin', 'tanggal_lahir',
                'desa_id', 'kelompok_id',
            ]);

            Flux::modal('tambah-person')->close();
        } finally {
            $this->processing = false;
        }
    }

    protected function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'nullable|date',
            'desa_id' => 'nullable|exists:desas,id',
            'kelompok_id' => 'nullable|exists:kelompoks,id',
        ];
    }

    protected $messages = [
        'nama.required' => 'Nama wajib diisi.',
        'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
        'jenis_kelamin.in' => 'Jenis kelamin harus Laki-laki atau Perempuan.',
        'desa_id.exists' => 'Desa tidak ditemukan.',
        'kelompok_id.exists' => 'Kelompok tidak ditemukan.',
    ];
}
