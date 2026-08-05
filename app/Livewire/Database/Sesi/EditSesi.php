<?php

namespace App\Livewire\Database\Sesi;

use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class EditSesi extends Component
{
    public $sesi;

    public $sesi_id;

    public $nama_sesi = '';

    public $tanggal = '';

    public $aktif = false;

    #[On('editSesi')]
    public function editSesi($id)
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
        $this->nama_sesi = $data->nama_sesi;
        $this->tanggal = $data->tanggal;
        $this->aktif = (bool) $data->aktif;
        Flux::modal('edit-sesi')->show();
    }

    public function update()
    {
        Gate::authorize('manage-sessions');

        $this->validate([
            'nama_sesi' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        $event = app(ActiveEventContext::class)->requireCurrent();

        if ($this->aktif) {
            SesiAbsensi::where('event_id', $event->id)->update(['aktif' => false]);
        }

        SesiAbsensi::where('event_id', $event->id)->where('id', $this->sesi_id)->update([
            'nama_sesi' => $this->nama_sesi,
            'tanggal' => $this->tanggal,
            'aktif' => $this->aktif ? 1 : 0,
        ]);

        return redirect()->to('/sesi-absensi');
    }

    public function render()
    {
        return view('livewire.database.sesi.edit-sesi');
    }
}
