<?php

namespace App\Livewire\Database\Sesi;

use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class HapusSesi extends Component
{
    public $sesi_id;

    public $sesi_nama;

    #[On('HapusSesi')]
    public function hapusSesi($id)
    {
        $eventId = app(ActiveEventContext::class)->id();

        if ($eventId === null) {
            return;
        }

        $data = SesiAbsensi::where('event_id', $eventId)->find($id);

        if ($data === null) {
            return;
        }

        $this->sesi_id = $data->id;
        $this->sesi_nama = $data->nama_sesi;
        Flux::modal('hapus-sesi')->show();
    }

    public function delete()
    {
        Gate::authorize('manage-sessions');

        $eventId = app(ActiveEventContext::class)->id();

        if ($eventId === null) {
            return redirect()->to('/sesi-absensi');
        }

        SesiAbsensi::where('event_id', $eventId)->where('id', $this->sesi_id)->delete();
        session()->flash('success', 'Sesi absensi berhasil dihapus.');

        return redirect()->to('/sesi-absensi');
    }

    public function render()
    {
        return view('livewire.database.sesi.hapus-sesi');
    }
}
