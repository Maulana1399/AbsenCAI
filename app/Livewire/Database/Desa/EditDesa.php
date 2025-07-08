<?php

namespace App\Livewire\Database\Desa;

use Livewire\Component;
use App\Models\desa;
use Livewire\Attributes\On;
use Flux\Flux;

class EditDesa extends Component
{
    public $desa;
    public $desa_id;


    #[On("editDesa")]
    public function editDesa($id)
    {
        $data = desa::find($id);
        $this->desa_id = $data->id;
        $this->desa = $data->desa_asal;
        Flux::modal("edit-desa")->show();
    }
    public function update()
    {
        $this->validate([
            'desa' => 'required'
        ]);
        desa::where('id', $this->desa_id)->update([
            'desa_asal' => $this->desa
        ]);
        // \Flux\Flux::modal("edit-desa")->hide();
        return redirect()->to('/desa');
    }

    public function render()
    {
        return view('livewire.database.desa.edit-desa');
    }
}
