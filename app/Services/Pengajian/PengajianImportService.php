<?php

namespace App\Services\Pengajian;

use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;

/**
 * Public API orchestrator for the Pengajian import.
 *
 * Keeps the exact legacy public contract (validate()/import() signatures and
 * result arrays) but delegates all work to the Import Framework
 * (ImportAdapter → PengajianImportDefinition → Pipeline → Committer). No
 * import logic is implemented here anymore.
 */
class PengajianImportService
{
    public function __construct(
        private readonly ImportAdapter $adapter,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function import(array $rows, int $eventId): array
    {
        $context = new ImportContext(
            type: 'pengajian',
            eventId: $eventId,
            source: 'service',
            mode: 'execute',
            options: ['rows' => $rows],
            definitionKey: 'pengajian',
        );

        $result = $this->adapter->commit('pengajian', $rows, $context);

        $commit = $result->commit;
        $metrics = $commit?->metrics ?? [];

        return [
            'created_persons' => $metrics['created_persons'] ?? 0,
            'matched_persons' => $metrics['matched_persons'] ?? 0,
            'created_participations' => $metrics['created_participations'] ?? 0,
            'skipped_duplicates' => $metrics['skipped_duplicates'] ?? 0,
            'failed_rows' => $metrics['failed_rows'] ?? 0,
            'errors' => array_map(
                fn ($row) => [
                    'row' => $row['row'] ?? 0,
                    'message' => $row['message'] ?? '',
                ],
                $commit?->failedRows ?? [],
            ),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{row: int, errors: array<int, string>}>
     */
    public function validate(array $rows): array
    {
        $context = new ImportContext(
            type: 'pengajian',
            source: 'service',
            mode: 'preview',
            options: ['rows' => $rows],
            definitionKey: 'pengajian',
        );

        $result = $this->adapter->preview('pengajian', $rows, $context);

        $grouped = [];

        foreach ($result->summary?->errors ?? [] as $error) {
            $row = $error instanceof ImportError ? $error->rowNumber : ($error['row'] ?? 0);
            $message = $error instanceof ImportError ? $error->message : ($error['message'] ?? '');

            $grouped[$row]['row'] = $row;
            $grouped[$row]['errors'][] = $message;
        }

        return array_values($grouped);
    }
}
