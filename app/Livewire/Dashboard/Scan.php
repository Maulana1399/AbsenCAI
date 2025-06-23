<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use Carbon\Carbon;

class Scan extends Component
{
    public $nama;
    public $nip;
    public $jam_scan;

    public function scanPeserta($data)
    {
        [$nip, $nama] = explode('|', $data);
        $this->nip = $nip;
        $this->nama = $nama;
        $this->jam_scan = Carbon::now()->format('H:i:s');
    }

    public function render()
    {
        return view('livewire.dashboard.scan');
    }
}