<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\PesertaImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportPeserta extends Component
{
    use WithFileUploads;

    public $file;

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(new PesertaImport, $this->file->getRealPath());
        session()->flash('success', 'Import berhasil!');
        $this->reset('file');
        $this->dispatch('refreshPeserta');
    }

    public function render()
    {
        return view('livewire.database.peserta.import-peserta');
    }
}
