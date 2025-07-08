<?php

namespace App\Livewire\Database\Regu;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\regu;

class EditRegu extends Component
{
    public $regu;
    public $regu_id;

    #[On("editRegu")]
    public function editRegu($id)
    {
        $data = regu::find($id);
        $this->regu_id = $data->id;
        $this->regu = $data->regu;
        Flux::modal("edit-regu")->show();
    }

    public function update()
    {
        $this->validate([
            'regu' => 'required'
        ]);
        regu::where('id', $this->regu_id)->update([
            'regu' => $this->regu
        ]);
        // Flux::modal("edit-regu")->hide();
        return redirect()->to('/regu');
    }

    public function render()
    {
        return view('livewire.database.regu.edit-regu');
    }
}
