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

        $resolver = app(LegacyParticipationResolver::class);
        $participation = Participation::with('person')->find($id);

        if ($participation === null || (int) $participation->event_id !== (int) $event->id) {
            $participation = $resolver->resolveByPesertaAndEvent($id, $event->id);
        }

        if ($participation === null) {
            return;
        }

        $participation->update([
            'jenis_peserta' => $participation->jenis_peserta,
        ]);

        $legacyPeserta = $resolver->resolvePesertaByParticipation($participation->id, $event->id);
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

        $resolver = app(LegacyParticipationResolver::class);
        $participation = Participation::with('person')->find($id);

        if ($participation === null || (int) $participation->event_id !== (int) $event->id) {
            $participation = $resolver->resolveByPesertaAndEvent((int) $id, $event->id);
        }

        if ($participation === null) {
            return;
        }

        $legacyPeserta = $resolver->resolvePesertaByParticipation($participation->id, $event->id);

        $this->editId = $participation->id;
        $this->editNama = $participation->person?->nama;
        $this->editJenisKelamin = $participation->person?->jenis_kelamin;
        $this->editJenisPeserta = $participation->jenis_peserta;
        $this->editDesa = $participation->person?->desa_id;
        $this->editKelompok = $participation->person?->kelompok_id;
        $this->editRegu = $legacyPeserta?->regu_id;

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
        ]);

        $participation->person?->update([
            'nama' => $this->editNama,
            'jenis_kelamin' => $this->editJenisKelamin === 'Perempuan' ? 'P' : 'L',
            'desa_id' => $this->editDesa,
            'kelompok_id' => $this->editKelompok,
        ]);

        $legacyPeserta = app(LegacyParticipationResolver::class)->resolvePesertaByParticipation($participation->id, $event->id);
        if ($legacyPeserta) {
            $legacyPeserta->update([
                'regu_id' => $this->editRegu,
            ]);
        }

        $this->showEditModal = false;

        session()->flash('success','Data peserta berhasil diperbarui');

        $this->dispatch('$refresh');
    }


    public function render()
    {
        $search = trim($this->search);

        $peserta = collect();

        if ($search !== '') {
            $peserta = peserta::with(['desa', 'kelompok', 'regu'])
                ->where(function ($query) use ($search) {
                    $query->where('nama', 'like', '%' . $search . '%')
                        ->orWhere('nip', 'like', '%' . $search . '%');
                })
                ->orderBy('nama')
                ->limit(10)
                ->get();
        }

        return view('livewire.registrasi.ulang', [
            'daftarPeserta' => $peserta,
            'daftarDesa' => desa::all(),
            'daftarKelompok' => kelompok::all(),
            'daftarRegu' => regu::all(),
        ]);
    }
}