<?php

namespace App\Livewire\Database\Kelompok;

use App\Services\Import\Adapters\Kelompok\KelompokImportActivityLogger;
use App\Services\Import\Adapters\Kelompok\KelompokImportCommitter;
use App\Services\Import\Adapters\Kelompok\KelompokImportDefinition;
use App\Services\Import\Adapters\Kelompok\KelompokImportDuplicateDetector;
use App\Services\Import\Adapters\Kelompok\KelompokImportNormalizer;
use App\Services\Import\Adapters\Kelompok\KelompokImportParser;
use App\Services\Import\Adapters\Kelompok\KelompokImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Support\ArrayPipelineStageRunner;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportKelompok extends Component
{
    use WithFileUploads;

    public $file;

    public function import()
    {
        Gate::authorize('manage-master-data');

        $this->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        $registry = new ImportRegistry;
        $registry->register(new KelompokImportDefinition(
            new KelompokImportParser,
            new KelompokImportValidator,
            new KelompokImportNormalizer,
            new KelompokImportDuplicateDetector,
            new KelompokImportCommitter,
            new KelompokImportActivityLogger,
        ));

        $coordinator = new ImportCoordinator(
            $registry,
            new DefaultImportPipeline(new ArrayPipelineStageRunner),
        );

        $context = new ImportContext(
            type: 'kelompok',
            userId: auth()->id(),
            fileName: $this->file->getClientOriginalName(),
            source: 'livewire',
            mode: 'execute',
            options: ['file' => $this->file],
            definitionKey: 'kelompok',
        );

        $coordinator->execute('kelompok', $context, $this->file);

        session()->flash('success', 'Data kelompok berhasil diimpor.');
        $this->reset('file');
        $this->dispatch('refreshKelompokList');
    }

    public function render()
    {
        return view('livewire.database.kelompok.import-kelompok');
    }
}
