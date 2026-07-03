<?php

namespace App\Livewire\Database\Regu;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\regu;
use Illuminate\Support\Facades\Schema;

class EditRegu extends Component
{
    public $regu;
    public $regu_id;
    public $jenis_kelamin = '';

    #[On("editRegu")]
    public function editRegu($id)
    {
        $data = regu::find($id);
        $this->regu_id = $data->id;
        $this->regu = $data->regu;
        $this->jenis_kelamin = $data->jenis_kelamin;
        Flux::modal("edit-regu")->show();
    }

    public function update()
    {
        $rules = [
            'regu' => 'required',
        ];

        $data = [
            'regu' => $this->regu,
        ];

        if (Schema::hasColumn('regus', 'jenis_kelamin')) {
            $rules['jenis_kelamin'] = 'required|in:Laki - Laki,Perempuan';
            $data['jenis_kelamin'] = $this->jenis_kelamin;
        }

        $this->validate($rules);

        regu::where('id', $this->regu_id)->update($data);
        // Flux::modal("edit-regu")->hide();
        return redirect()->to('/regu');
    }

    public function render()
    {
        return view('livewire.database.regu.edit-regu');
    }
}
