<?php

namespace App\Livewire\Database\Desa;

use App\Models\desa;
use Livewire\Component;

class DataDesa extends Component
{
    public $daftardesa;

    public function mount()
    {
        $this->daftardesa = desa::all();
    }

    public function render()
    {
        return view('livewire.database.desa.data-desa', [
            'daftardesa' => $this->daftardesa
        ]);
    }
}
