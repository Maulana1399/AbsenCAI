<?php

namespace App\Livewire\Pengajian\Admin;

use App\Services\Import\Adapters\Pengajian\PengajianImportCommitter;
use App\Services\Import\Adapters\Pengajian\PengajianImportDefinition;
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
use App\Services\Pengajian\PengajianImportService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportMassal extends Component
{
    use WithFileUploads;

    public $file = null;

    public bool $processing = false;

    public int $step = 1;

    public array $previewRows = [];

    public array $validationErrors = [];

    public array $importResult = [];

    public bool $noActiveEvent = false;

    public ?string $eventName = null;

    protected function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ];
    }

    protected $messages = [
        'file.required' => 'Pilih file CSV atau Excel untuk diimport.',
        'file.file' => 'File harus berupa file yang valid.',
        'file.mimes' => 'File harus berformat CSV, TXT, XLSX, atau XLS.',
        'file.max' => 'Ukuran file maksimal 5MB.',
    ];

    public function mount(): void
    {
        $event = app(ActiveEventContext::class)->current();

        if ($event === null) {
            $this->noActiveEvent = true;

            return;
        }

        $this->eventName = $event->name;
    }

    public function preview(): void
    {
        Gate::authorize('manage-pengajian');

        $this->validate();

        $this->processing = true;
        $this->validationErrors = [];
        $this->previewRows = [];
        $this->importResult = [];

        try {
            $rows = $this->parseFile();
            $this->previewRows = $rows;

            $service = app(PengajianImportService::class);
            $errors = $service->validate($rows);

            if (! empty($errors)) {
                $this->validationErrors = $errors;
                $this->step = 2;
            } else {
                $this->step = 2;
            }
        } catch (\Throwable $e) {
            $this->validationErrors = [
                ['row' => 0, 'errors' => ['Gagal membaca file: '.$e->getMessage()]],
            ];
            $this->step = 2;
        } finally {
            $this->processing = false;
        }
    }

    public function executeImport(): void
    {
        Gate::authorize('manage-pengajian');

        if ($this->processing || empty($this->previewRows)) {
            return;
        }

        $this->processing = true;

        try {
            $event = app(ActiveEventContext::class)->requireCurrent();

            $registry = new ImportRegistry;
            $registry->register(new PengajianImportDefinition(
                new NullImportParser,
                new NullImportValidator,
                new NullImportNormalizer,
                new NullImportDuplicateDetector,
                app(PengajianImportCommitter::class),
                new NullImportActivityLogger,
            ));

            $coordinator = new ImportCoordinator(
                $registry,
                new DefaultImportPipeline(new ArrayPipelineStageRunner),
            );

            $context = new ImportContext(
                type: 'pengajian',
                eventId: $event->id,
                userId: auth()->id(),
                fileName: $this->file?->getClientOriginalName(),
                source: 'livewire',
                mode: 'execute',
                options: ['file' => $this->file],
                definitionKey: 'pengajian',
            );

            $result = $coordinator->execute('pengajian', $context, $this->previewRows);
            $this->importResult = [
                'created_participations' => $result->commit?->summary->createdRows ?? 0,
                'skipped_duplicates' => $result->commit?->summary->skippedRows ?? 0,
                'errors' => $result->commit?->summary->errors ?? [],
            ];
            $this->step = 3;
        } catch (\Throwable $e) {
            $this->importResult = [
                'error' => 'Gagal menjalankan import: '.$e->getMessage(),
            ];
            $this->step = 3;
        } finally {
            $this->processing = false;
        }
    }

    public function resetImport(): void
    {
        $this->step = 1;
        $this->file = null;
        $this->previewRows = [];
        $this->validationErrors = [];
        $this->importResult = [];
    }

    public function render()
    {
        return view('livewire.pengajian.admin.import-massal');
    }

    private function parseFile(): array
    {
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($path);
        }

        return $this->parseCsv($path);
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Tidak dapat membaca file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false || $headers === null) {
            fclose($handle);
            throw new \RuntimeException('File CSV tidak memiliki header.');
        }

        $headers = array_map(fn ($h) => trim(mb_strtolower(str_replace([' ', '-'], '_', $h))), $headers);

        $expected = ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'];
        $missing = array_diff($expected, $headers);

        if (! empty($missing)) {
            fclose($handle);
            throw new \RuntimeException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $missing).
                '. Kolom yang diharapkan: nama, jenis_kelamin, tanggal_lahir, desa, kelompok.'
            );
        }

        $rows = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $row = [];

            foreach ($headers as $i => $header) {
                $row[$header] = $data[$i] ?? '';
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function parseExcel(string $path): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('Library PhpSpreadsheet tidak tersedia untuk membaca Excel.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        if (empty($data)) {
            throw new \RuntimeException('File Excel kosong.');
        }

        $headers = array_map(fn ($h) => trim(mb_strtolower(str_replace([' ', '-'], '_', (string) $h))), $data[0]);

        $expected = ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'];
        $missing = array_diff($expected, $headers);

        if (! empty($missing)) {
            throw new \RuntimeException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $missing).
                '. Kolom yang diharapkan: nama, jenis_kelamin, tanggal_lahir, desa, kelompok.'
            );
        }

        $rows = [];

        for ($i = 1; $i < count($data); $i++) {
            $row = [];

            foreach ($headers as $j => $header) {
                $row[$header] = $data[$i][$j] ?? '';
            }

            if (! empty(trim($row['nama'] ?? ''))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }
}
