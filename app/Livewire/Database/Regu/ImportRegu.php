<?php

namespace App\Livewire\Database\Regu;

use App\Services\Import\Adapters\Regu\ReguImportCommitter;
use App\Services\Import\Adapters\Regu\ReguImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\NullObjects\NullImportActivityLogger;
use App\Services\Import\NullObjects\NullImportDuplicateDetector;
use App\Services\Import\NullObjects\NullImportNormalizer;
use App\Services\Import\NullObjects\NullImportParser;
use App\Services\Import\NullObjects\NullImportValidator;
use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Support\ArrayPipelineStageRunner;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

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

        $registry = new ImportRegistry;
        $registry->register(new ReguImportDefinition(
            new NullImportParser,
            new NullImportValidator,
            new NullImportNormalizer,
            new NullImportDuplicateDetector,
            new ReguImportCommitter,
            new NullImportActivityLogger,
        ));

        $coordinator = new ImportCoordinator(
            $registry,
            new DefaultImportPipeline(new ArrayPipelineStageRunner),
        );

        $context = new ImportContext(
            type: 'regu',
            userId: auth()->id(),
            fileName: $this->file->getClientOriginalName(),
            source: 'livewire',
            mode: 'execute',
            options: ['file' => $this->file],
            definitionKey: 'regu',
        );

        $coordinator->execute('regu', $context, $this->file);
        session()->flash('success', 'Import berhasil!');
        $this->reset('file');
        $this->dispatch('refreshRegu');
    }

    public function render()
    {
        return view('livewire.database.regu.import-regu');
    }
}
