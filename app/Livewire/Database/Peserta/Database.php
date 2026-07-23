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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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
        $pesertaQuery = Participation::with(['person.desa', 'person.legacyPesertaMapping.peserta.regu', 'person.legacyPesertaMapping.peserta.kelompok', 'legacyParticipationMapping.peserta.regu', 'legacyParticipationMapping.peserta.kelompok'])
            ->when($event, fn ($query) => $query->where('event_id', $event->id), fn ($query) => $query->whereRaw('0 = 1'));

        if ($this->search !== '') {
            $search = trim($this->search);
            $pesertaQuery->where(function ($query) use ($search) {
                $query->whereHas('person', fn ($personQuery) => $personQuery->where('nama', 'like', '%'.$search.'%'))
                    ->orWhereHas('person', fn ($personQuery) => $personQuery->where('nip', 'like', '%'.$search.'%'))
                    ->orWhere('participant_number', 'like', '%'.$search.'%')
                    ->orWhere('attendance_code', 'like', '%'.$search.'%');
            });
        }

        $daftarPeserta = $pesertaQuery->orderByDesc('id')->get()->map(function (Participation $participation) {
            $legacyPeserta = $participation->legacyParticipationMapping?->peserta;

            return (object) [
                'id' => $participation->id,
                'nama' => $participation->person?->nama ?? $legacyPeserta?->nama,
                'nip' => $participation->person?->nip ?? $legacyPeserta?->nip,
                'jenis_kelamin' => $participation->person?->jenis_kelamin ?? $legacyPeserta?->jenis_kelamin,
                'jenis_peserta' => $participation->jenis_peserta,
                'status_registrasi_label' => $legacyPeserta?->status_registrasi_label ?? '-',
                'desa' => $participation->person?->desa,
                'kelompok' => $participation->person?->kelompok ?? $legacyPeserta?->kelompok,
                'regu' => $legacyPeserta?->regu,
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
