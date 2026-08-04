<?php

namespace App\Livewire\Database\Peserta;

use App\Services\Import\Adapters\Peserta\PesertaImportCommitter;
use App\Services\Import\Adapters\Peserta\PesertaImportDefinition;
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

class ImportPeserta extends Component
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
        $registry->register(new PesertaImportDefinition(
            new NullImportParser,
            new NullImportValidator,
            new NullImportNormalizer,
            new NullImportDuplicateDetector,
            new PesertaImportCommitter,
            new NullImportActivityLogger,
        ));

        $coordinator = new ImportCoordinator(
            $registry,
            new DefaultImportPipeline(new ArrayPipelineStageRunner),
        );

        $context = new ImportContext(
            type: 'peserta',
            userId: auth()->id(),
            fileName: $this->file->getClientOriginalName(),
            source: 'livewire',
            mode: 'execute',
            options: ['file' => $this->file],
            definitionKey: 'peserta',
        );

        $coordinator->execute('peserta', $context, $this->file);

        session()->flash('success', 'Import berhasil!');
        $this->reset('file');
        $this->dispatch('refreshPeserta');
    }

    public function render()
    {
        return view('livewire.database.peserta.import-peserta');
    }
}
