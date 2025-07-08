<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;

class Dashboard extends Component
{
    public $totalPeserta;
    public $totalDesa;
    public $totalKelompok;
    public $totalRegu;

    public function mount()
    {
        $this->totalPeserta = Peserta::count();
        $this->totalDesa = Desa::count();
        $this->totalKelompok = Kelompok::count();
        $this->totalRegu = Regu::count();
    }

    public function render()
    {
        return view('livewire.dashboard.dashboard');
    }
}
