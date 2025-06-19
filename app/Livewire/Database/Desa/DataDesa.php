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

    public function edit($id)
    {
        $this->dispatch('editDesa', id: $id);
    }
}
