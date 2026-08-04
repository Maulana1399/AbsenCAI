<?php

namespace App\Livewire\Database\Regu;

use App\Imports\ReguImport;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ImportRegu extends Component
{
    use WithFileUploads;

    public $file;

    public function import()
    {
        Gate::authorize('manage-participants');

        $this->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new ReguImport, $this->file);
        session()->flash('success', 'Import berhasil!');
        $this->reset('file');
        $this->dispatch('refreshRegu');
    }

    public function render()
    {
        return view('livewire.database.regu.import-regu');
    }
}
