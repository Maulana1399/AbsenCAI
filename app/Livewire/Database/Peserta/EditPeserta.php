<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\Participation;
use App\Models\peserta;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Services\Person\PersonLegacySyncService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;

class EditPeserta extends Component
{

    public $peserta;
    public $nama;
    public $jenis_kelamin;
    public $jenis_peserta;
    public $desa_id;
    public $kelompok_id;
    public $regu_id;
    public $peserta_id;
    public $participation_id;
    public $person_id;
    public $daftarDesa = [];
    public $daftarKelompok = [];
    public $daftarRegu = [];


    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->daftarKelompok = kelompok::with('desa')->get();
        $this->daftarRegu = regu::all();
    }

    #[On("editPeserta")]
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

        $this->participation_id = $participation->id;
        $this->person_id = $participation->person_id;
        $this->peserta_id = $legacyPeserta?->id;
        $this->nama = $participation->person?->nama;
        $this->jenis_kelamin = $participation->person?->jenis_kelamin;
        $this->jenis_peserta = $participation->jenis_peserta;
        $this->desa_id = $participation->person?->desa_id;
        $this->kelompok_id = $participation->person?->kelompok_id;
        $this->regu_id = $participation->regu_id;
        Flux::modal("edit-peserta")->show();
    }
    public function update()
    {
        Gate::authorize('manage-participants');

        $this->validate([
            'nama' => 'required',
            'jenis_kelamin' => 'required',
            'jenis_peserta' => 'required|in:Wajib,Kiriman,Person',
            'desa_id' => 'required',
            'kelompok_id' => 'required',
            'regu_id' => 'required'
        ]);

        $event = app(ActiveEventContext::class)->current();
        if ($event === null || ! $this->participation_id) {
            return redirect()->to('/database');
        }

        $participation = Participation::with('person')->findOrFail($this->participation_id);
        if ((int) $participation->event_id !== (int) $event->id) {
            return redirect()->to('/database');
        }

        $participation->update([
            'jenis_peserta' => $this->jenis_peserta,
            'regu_id' => $this->regu_id,
        ]);

        $participation->person?->update([
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin === 'Perempuan' ? 'P' : 'L',
            'desa_id' => $this->desa_id,
            'kelompok_id' => $this->kelompok_id,
        ]);

        app(PersonLegacySyncService::class)->syncToPeserta($participation->person);

        $legacyPeserta = $participation->person?->legacyPesertaMapping?->peserta;
        $this->peserta_id = $legacyPeserta?->id;

        return redirect()->to('/database');
    }

    public function render()
    {
        return view('livewire.database.peserta.edit-peserta');
    }
}
