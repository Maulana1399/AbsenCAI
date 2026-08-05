<?php

namespace App\Livewire\Database\Sesi;

use App\Models\SesiAbsensi;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class TambahSesi extends Component
{
    public bool $processing = false;

    public $nama_sesi = '';

    public $tanggal = '';

    public $aktif = true;

    public function render()
    {
        return view('livewire.database.sesi.tambah-sesi');
    }

    public function simpan()
    {
        Gate::authorize('manage-sessions');

        if ($this->processing) {
            return;
        }

        $this->processing = true;

        try {
            $this->validate([
                'nama_sesi' => 'required|string',
                'tanggal' => 'required|date',
            ]);

            $event = app(ActiveEventContext::class)->requireCurrent();

            if ($this->aktif) {
                SesiAbsensi::where('event_id', $event->id)->update(['aktif' => false]);
            }

            $sesi = SesiAbsensi::create([
                'event_id' => $event->id,
                'nama_sesi' => $this->nama_sesi,
                'tanggal' => $this->tanggal,
                'aktif' => $this->aktif ? 1 : 0,
            ]);

            app(SuratIzinService::class)->syncNewSession($sesi);

            $this->dispatch('refreshSesi');
            $this->reset(['nama_sesi', 'tanggal', 'aktif']);
            $this->resetErrorBag();

            session()->flash('success', 'Sesi berhasil ditambahkan');
        } finally {
            $this->processing = false;
        }
    }
}
