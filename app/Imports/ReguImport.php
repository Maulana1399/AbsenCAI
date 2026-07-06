<?php
namespace App\Imports;

use App\Models\regu;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ReguImport implements ToModel, WithHeadingRow, WithValidation
{
    public function prepareForValidation(array $row): array
    {
        if (isset($row['jenis_kelamin'])) {
            $row['jenis_kelamin'] = $this->normalizeJenisKelamin($row['jenis_kelamin']);
        }

        return $row;
    }

    public function rules(): array
    {
        return [
            'regu' => 'required|string',
            'jenis_kelamin' => 'required|in:Laki - Laki,Perempuan',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki - laki atau Perempuan',
            'jenis_kelamin.required' => 'Jenis kelamin harus diisi',
        ];
    }

    public function model(array $row)
    {
        return new Regu([
            'regu' => trim($row['regu'] ?? ''),
            'jenis_kelamin' => $row['jenis_kelamin'] ?? null,
        ]);
    }

    protected function normalizeJenisKelamin(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        $value = preg_replace('/[–—‑]+/u', '-', $value);
        $value = preg_replace('/\s*-\s*/u', ' - ', $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        if (preg_match('/^laki\s*-\s*laki$/iu', $value) || preg_match('/^laki\s+laki$/iu', $value)) {
            return 'Laki - Laki';
        }

        if (preg_match('/^perempuan$/iu', $value)) {
            return 'Perempuan';
        }

        return $value;
    }
}
