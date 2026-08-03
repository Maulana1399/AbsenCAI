<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\kelompok;
use App\Models\desa;
use App\Models\regu;
use App\Models\peserta;
use Livewire\Attributes\On;
use App\Models\Participation;
use App\Support\ActiveEventContext;

class Database extends Component
{
    public $daftarkelompok = [];
    public $kelompok_id;
    public $daftarDesa = [];
    public $desa_id;
    public $daftarRegu = [];
    public $regu_id;
    public $search = '';

    public function mount()
    {
        $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
        $this->daftarRegu = regu::all();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $pesertaQuery = Participation::with(['person.desa', 'regu', 'person.legacyPesertaMapping.peserta.kelompok', 'legacyParticipationMapping.peserta.kelompok'])
            ->when($event, fn ($query) => $query->where('event_id', $event->id), fn ($query) => $query->whereRaw('0 = 1'));

        if ($this->search !== '') {
            $search = trim($this->search);
            $pesertaQuery->where(function ($query) use ($search) {
                $query->whereHas('person', fn ($personQuery) => $personQuery->where('nama', 'like', '%'.$search.'%'))
                    ->orWhere('participant_number', 'like', '%'.$search.'%')
                    ->orWhere('attendance_code', 'like', '%'.$search.'%');
            });
        }

        $daftarPeserta = $pesertaQuery->orderByDesc('id')->get()->map(function (Participation $participation) {
            $legacyPeserta = $participation->legacyParticipationMapping?->peserta;

            $status = $participation->status_registrasi
                ?? $legacyPeserta?->status_registrasi
                ?? 'Belum Registrasi';

            return (object) [
                'id' => $participation->id,
                'nama' => $participation->person?->nama ?? $legacyPeserta?->nama,
                'participant_number' => $participation->participant_number,
                'jenis_kelamin' => $participation->person?->jenis_kelamin ?? $legacyPeserta?->jenis_kelamin,
                'jenis_peserta' => $participation->jenis_peserta,
                'status_registrasi_label' => $status,
                'desa' => $participation->person?->desa,
                'kelompok' => $participation->person?->kelompok ?? $legacyPeserta?->kelompok,
                'regu' => $participation->regu,
                'participation' => $participation,
            ];
        });

        return view('livewire.database.peserta.database', [
            'daftarPeserta' => $daftarPeserta,
            'daftarkelompok' => $this->daftarkelompok,
            'daftarDesa' => $this->daftarDesa,
            'daftarRegu' => $this->daftarRegu
        ]);
    }

    public function edit($id)
    {
        $this->dispatch('editPeserta', id: $id);
    }

    public function ganti($id)
    {
        $this->dispatch('gantiPeserta', id: $id);
    }

    public function delete($id)
    {
        $this->dispatch('HapusPeserta', id: $id);
    }

    #[On('refreshPeserta')]
    public function refreshPeserta()
    {
        $this->daftarkelompok = kelompok::with('desa')->get();
        $this->daftarDesa = desa::all();
        $this->daftarRegu = regu::all();
    }

    public function cari()
    {
    }
}
