<?php

namespace App\Livewire\MasterData\Person;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Person;
use App\Livewire\Traits\HasCascadingKelompok;
use App\Services\Person\PersonLegacySyncService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

class EditPerson extends Component
{
    use HasCascadingKelompok;

    public ?int $person_id = null;
    public string $nama = '';
    public string $jenis_kelamin = '';
    public string $tanggal_lahir = '';
    public ?string $desa_id = null;
    public ?string $kelompok_id = null;

    public bool $hasLegacyMapping = false;

    #[On('editPerson')]
    public function editPerson(int $id): void
    {
        $person = Person::with('legacyPesertaMapping')->findOrFail($id);

        $this->person_id = $person->id;
        $this->nama = $person->nama;
        $this->jenis_kelamin = $person->jenis_kelamin ?? '';
        $this->tanggal_lahir = $person->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->desa_id = (string) $person->desa_id;
        $this->kelompok_id = (string) $person->kelompok_id;

        $this->hasLegacyMapping = app(PersonLegacySyncService::class)->hasMapping($person);

        Flux::modal('edit-person')->show();
    }

    public function update(): void
    {
        Gate::authorize('manage-master-data');

        $this->validate();

        $person = Person::findOrFail($this->person_id);

        $person->update([
            'nama' => trim($this->nama),
            'jenis_kelamin' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir ?: null,
            'desa_id' => $this->desa_id ?: null,
            'kelompok_id' => $this->kelompok_id ?: null,
        ]);

        app(PersonLegacySyncService::class)->syncToPeserta($person);

        $this->dispatch('refreshPerson');
        Flux::modal('edit-person')->close();
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

    public function render()
    {
        return view('livewire.master-data.person.edit-person', [
            'daftarDesa' => desa::orderBy('desa_asal')->get(),
            'daftarKelompok' => $this->resolveDesaId()
                ? kelompok::where('desa_id', $this->resolveDesaId())->orderBy('kelompok_asal')->get()
                : kelompok::orderBy('kelompok_asal')->get(),
        ]);
    }
}
