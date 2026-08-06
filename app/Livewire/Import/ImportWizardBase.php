<?php

namespace App\Livewire\Import;

use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportPipelineResult;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Reusable 5-step import wizard (Upload → Preview → Validation → Import → Result).
 *
 * Everything is driven by the module definition metadata — parameters, columns,
 * display name, template route — so the blade never hardcodes module details.
 */
abstract class ImportWizardBase extends Component
{
    use WithFileUploads;

    public $file = null;

    public bool $processing = false;

    public int $step = 1;

    public array $parameters = [];

    public array $previewRows = [];

    public array $validationErrors = [];

    public array $summary = [];

    public array $importResult = [];

    public ?string $uploadError = null;

    abstract protected function definitionKey(): string;

    abstract protected function gateAbility(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function steps(): array;

    abstract protected function refreshEvent(): ?string;

    protected function rules(): array
    {
        $rules = [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ];

        foreach ($this->metadata()['parameters'] as $key => $spec) {
            $rules['parameters.'.$key] = ($spec['required'] ?? false) ? 'required' : 'nullable';
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = [
            'file.required' => 'Pilih file CSV atau Excel untuk diimport.',
            'file.file' => 'File harus berupa file yang valid.',
            'file.mimes' => 'File harus berformat CSV, TXT, XLSX, atau XLS.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ];

        foreach ($this->metadata()['parameters'] as $key => $spec) {
            $messages['parameters.'.$key.'.required'] = $spec['label'].' wajib dipilih.';
        }

        return $messages;
    }

    public function updated($name): void
    {
        if (str_starts_with($name, 'parameters.')) {
            $this->previewRows = [];
            $this->validationErrors = [];
            $this->summary = [];
            $this->importResult = [];
            $this->step = 1;
        }
    }

    public function updatedFile(): void
    {
        $this->resetErrorBag('file');
        $this->uploadError = null;
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->summary = [];
        $this->importResult = [];
        $this->step = 1;
    }

    public function uploadError(): void
    {
        $this->uploadError = 'Upload file gagal. Periksa ukuran file (maks 5MB) dan format yang didukung (CSV, XLSX, XLS, TXT).';
    }

    public function preview(): void
    {
        Gate::authorize($this->gateAbility());

        $this->validate();
        $this->processing = true;
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->summary = [];
        $this->importResult = [];

        try {
            $result = app(ImportAdapter::class)->preview($this->definitionKey(), $this->file, parameters: $this->parameters);

            $this->previewRows = $this->extractPreviewRows($result);
            $this->validationErrors = $this->extractValidationErrors($result);

            if (empty($this->previewRows) && empty($this->validationErrors)) {
                $this->validationErrors = [
                    ['row' => 0, 'errors' => ['File tidak berisi data yang bisa diimport.']],
                ];
            }

            $this->summary = $this->summarize($result);

            $this->step = empty($this->validationErrors) ? 2 : 3;
        } catch (\Throwable $e) {
            $this->validationErrors = [
                ['row' => 0, 'errors' => ['Gagal membaca file: '.$e->getMessage()]],
            ];
            $this->step = 3;
        } finally {
            $this->processing = false;
        }
    }

    public function goToImport(): void
    {
        if (empty($this->previewRows) || ! empty($this->validationErrors)) {
            return;
        }

        $this->step = 4;
    }

    public function executeImport(): void
    {
        Gate::authorize($this->gateAbility());

        if ($this->processing || empty($this->previewRows) || ! empty($this->validationErrors)) {
            return;
        }

        $this->processing = true;

        try {
            $result = app(ImportAdapter::class)->commit($this->definitionKey(), $this->file, parameters: $this->parameters);

            $this->importResult = $this->extractResult($result);
            $this->step = 5;

            if ($this->refreshEvent() !== null) {
                $this->dispatch($this->refreshEvent());
            }
        } catch (\Throwable $e) {
            $this->importResult = ['error' => 'Gagal menjalankan import: '.$e->getMessage()];
            $this->step = 5;
        } finally {
            $this->processing = false;
        }
    }

    public function resetImport(): void
    {
        $this->step = 1;
        $this->file = null;
        $this->parameters = [];
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->summary = [];
        $this->importResult = [];
        $this->uploadError = null;
        $this->resetErrorBag('file');
    }

    public function render()
    {
        return view('livewire.import.import-wizard', [
            'meta' => $this->metadata(),
            'steps' => $this->steps(),
            'templateUrl' => $this->templateUrl(),
            'definitionKey' => $this->definitionKey(),
        ]);
    }

    protected function templateUrl(): string
    {
        return route('import.'.$this->definitionKey().'.template');
    }

    protected function metadata(): array
    {
        $adapter = app(ImportAdapter::class);
        $definition = $adapter->metadata($this->definitionKey());
        $context = new ImportContext(type: $this->definitionKey());

        $parameterOptions = [];

        foreach ($definition->parameters() as $key => $spec) {
            $parameterOptions[$key] = $definition->parameterOptions($key, $context);
        }

        return [
            'displayName' => $definition->displayName(),
            'description' => $definition->description(),
            'icon' => $definition->icon(),
            'parameters' => $definition->parameters(),
            'parameterOptions' => $parameterOptions,
            'columns' => $definition->columns(),
        ];
    }

    private function extractPreviewRows(ImportPipelineResult $result): array
    {
        return array_map(
            fn (NormalizedImportRow $row) => $row->data,
            $result->rows,
        );
    }

    private function extractValidationErrors(ImportPipelineResult $result): array
    {
        $errors = [];

        foreach ($result->summary?->errors ?? [] as $error) {
            $errors[] = [
                'row' => $error instanceof ImportError ? $error->rowNumber : ($error['row'] ?? 0),
                'errors' => [$error instanceof ImportError ? $error->message : ($error['message'] ?? 'Baris tidak valid.')],
            ];
        }

        return $errors;
    }

    private function summarize(ImportPipelineResult $result): array
    {
        $summary = $result->summary;

        $total = $summary?->totalRows ?? 0;
        $valid = $summary?->validRows ?? 0;
        $invalid = $summary?->invalidRows ?? 0;
        $duplicate = $summary?->duplicateRows ?? 0;

        return [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'duplicate' => $duplicate,
            'will_create' => max(0, $valid - $duplicate),
        ];
    }

    private function extractResult(ImportPipelineResult $result): array
    {
        $summary = $result->summary;

        return [
            'total' => $summary?->totalRows ?? 0,
            'created' => $summary?->createdRows ?? 0,
            'duplicate' => $summary?->skippedRows ?? 0,
            'failed' => count($result->commit?->failedRows ?? []),
            'warning' => count($summary?->warnings ?? []),
            'errors' => array_map(
                fn ($row) => [
                    'row' => $row instanceof ImportError ? $row->rowNumber : ($row['row'] ?? 0),
                    'message' => $row instanceof ImportError ? $row->message : ($row['message'] ?? ''),
                ],
                $result->commit?->failedRows ?? [],
            ),
        ];
    }
}
