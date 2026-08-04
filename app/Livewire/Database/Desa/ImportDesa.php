<?php

namespace App\Livewire\Database\Desa;

use App\Services\Import\Adapters\Desa\DesaImportActivityLogger;
use App\Services\Import\Adapters\Desa\DesaImportCommitter;
use App\Services\Import\Adapters\Desa\DesaImportDefinition;
use App\Services\Import\Adapters\Desa\DesaImportDuplicateDetector;
use App\Services\Import\Adapters\Desa\DesaImportNormalizer;
use App\Services\Import\Adapters\Desa\DesaImportParser;
use App\Services\Import\Adapters\Desa\DesaImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Support\ArrayPipelineStageRunner;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportDesa extends Component
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
        $registry->register(new DesaImportDefinition(
            new DesaImportParser,
            new DesaImportValidator,
            new DesaImportNormalizer,
            new DesaImportDuplicateDetector,
            new DesaImportCommitter,
            new DesaImportActivityLogger,
        ));

        $coordinator = new ImportCoordinator(
            $registry,
            new DefaultImportPipeline(new ArrayPipelineStageRunner),
        );

        $context = new ImportContext(
            type: 'desa',
            userId: auth()->id(),
            fileName: $this->file->getClientOriginalName(),
            source: 'livewire',
            mode: 'execute',
            options: ['file' => $this->file],
            definitionKey: 'desa',
        );

        $coordinator->execute('desa', $context, $this->file);

        session()->flash('success', 'Data desa berhasil diimpor.');
        $this->reset('file');
        $this->dispatch('refreshDesaList');
    }

    public function render()
    {
        return view('livewire.database.desa.import-desa');
    }
}
