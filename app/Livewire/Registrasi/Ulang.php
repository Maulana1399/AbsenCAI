<?php

namespace App\Livewire\Registrasi;

use App\Models\Participation;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Ulang extends Component
{
    public string $search = '';

    public $showEditModal = false;

    public $editId;
    public $editNama;
    public $editJenisKelamin;
    public $editJenisPeserta;
    public $editDesa;
    public $editKelompok;
    public $editRegu;

    public function registrasiUlang(int $id): void
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->current();
        if ($event === null) {
            return;
        }

        $participation = Participation::with('person')->find($id);
        if ($participation === null || (int) $participation->event_id !== (int) $event->id) {
            return;
        }

        $participation->update([
            'jenis_peserta' => $participation->jenis_peserta,
        ]);

        $legacyPeserta = $participation->person?->legacyPesertaMapping?->peserta;
        if ($legacyPeserta) {
            $legacyPeserta->update([
                'status_registrasi' => peserta::STATUS_REGISTRASI_ULANG,
            ]);
        }

        session()->flash('success', 'Registrasi ulang berhasil.');
    }


    public function editPeserta($id)
    {
        $event = app(ActiveEventContext::class)->current();
        if ($event === null) {
            return;
        }

        $participation = Participation::with('person')->find($id);
        if ($participation === null || (int) $participation->event_id !== (int) $event->id) {
            return;
        }

        $this->editId = $participation->id;
        $this->editNama = $participation->person?->nama;
        $this->editJenisKelamin = $participation->person?->jenis_kelamin;
        $this->editJenisPeserta = $participation->jenis_peserta;
        $this->editDesa = $participation->person?->desa_id;
        $this->editKelompok = $participation->person?->kelompok_id;
        $this->editRegu = $participation->regu_id;

        $this->showEditModal = true;
    }


    public function updatePeserta()
    {
        Gate::authorize('manage-registration');

        $event = app(ActiveEventContext::class)->current();
        if ($event === null || ! $this->editId) {
            return;
        }

        $participation = Participation::with('person')->findOrFail($this->editId);
        if ((int) $participation->event_id !== (int) $event->id) {
            return;
        }

        $participation->update([
            'jenis_peserta' => $this->editJenisPeserta,
            'regu_id' => $this->editRegu,
        ]);

        $participation->person?->update([
            'nama' => $this->editNama,
            'jenis_kelamin' => $this->editJenisKelamin === 'Perempuan' ? 'P' : 'L',
            'desa_id' => $this->editDesa,
            'kelompok_id' => $this->editKelompok,
        ]);

        $this->showEditModal = false;

        session()->flash('success','Data peserta berhasil diperbarui');

        $this->dispatch('$refresh');
    }


    public function render()
    {
        $search = trim($this->search);

        $daftarPeserta = collect();

        if ($search !== '') {
            $event = app(ActiveEventContext::class)->current();
            $daftarPeserta = Participation::with(['person.desa', 'person.kelompok', 'regu', 'person.legacyPesertaMapping.peserta'])
                ->when($event, fn ($q) => $q->where('event_id', $event->id), fn ($q) => $q->whereRaw('0 = 1'))
                ->where(function ($query) use ($search) {
                    $query->whereHas('person', fn ($q) => $q->where('nama', 'like', '%' . $search . '%'));
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(function (Participation $p) {
                    $lp = $p->person?->legacyPesertaMapping?->peserta;
                    return (object) [
                        'id' => $p->id,
                        'nama' => $p->person?->nama,
                        'desa' => $p->person?->desa,
                        'kelompok' => $p->person?->kelompok,
                        'regu' => $p->regu,
                        'status_registrasi_label' => $lp?->status_registrasi_label ?? '-',
                    ];
                });
        }

        return view('livewire.registrasi.ulang', [
            'daftarPeserta' => $daftarPeserta,
            'daftarDesa' => desa::all(),
            'daftarKelompok' => kelompok::all(),
            'daftarRegu' => regu::all(),
        ]);
    }
}