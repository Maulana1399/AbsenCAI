<?php

namespace App\Exports;

use App\Models\desa;
use App\Models\kelompok;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PersonImportTemplateExport implements WithEvents, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PersonImportDataSheet,
            new PersonImportInstructionsSheet,
            new PersonImportMasterDataSheet,
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $spreadsheet = $event->getWriter()->getDelegate();
                $masterSheet = $spreadsheet->getSheetByName('Master Data');
                $templateSheet = $spreadsheet->getSheetByName('Template');

                if ($masterSheet === null || $templateSheet === null) {
                    return;
                }

                $masterSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

                $desas = desa::orderBy('desa_asal')->get();
                $currentRow = 2;
                $desaNames = [];
                $desaRanges = [];

                foreach ($desas as $d) {
                    $name = $d->desa_asal;
                    $desaNames[] = $name;

                    $currentRow++;
                    $kelompokStart = $currentRow;

                    $kelompoks = kelompok::where('desa_id', $d->id)
                        ->orderBy('kelompok_asal')
                        ->get();

                    foreach ($kelompoks as $k) {
                        $currentRow++;
                    }

                    $kelompokEnd = $currentRow - 1;
                    $rangeRef = "\$B\${$kelompokStart}:\$B\${$kelompokEnd}";

                    if ($kelompokStart <= $kelompokEnd) {
                        $desaRanges[$name] = $rangeRef;
                    }

                    $currentRow++;
                }

                $currentRow++;
                $currentRow++;
                $desaListStart = $currentRow;
                $desaListEnd = $desaListStart + count($desaNames) - 1;

                $spreadsheet->addNamedRange(
                    new NamedRange('DesaList', $masterSheet, "\$A\${$desaListStart}:\$A\${$desaListEnd}")
                );

                foreach ($desaRanges as $desaName => $range) {
                    $rangeName = str_replace([' ', '-', '.'], '_', $desaName);

                    $spreadsheet->addNamedRange(
                        new NamedRange($rangeName, $masterSheet, $range)
                    );
                }

                $desaValidation = new DataValidation;
                $desaValidation->setType(DataValidation::TYPE_LIST);
                $desaValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $desaValidation->setAllowBlank(false);
                $desaValidation->setShowInputMessage(true);
                $desaValidation->setShowErrorMessage(true);
                $desaValidation->setShowDropDown(true);
                $desaValidation->setFormula1('DesaList');
                $desaValidation->setPromptTitle('Pilih Desa');
                $desaValidation->setPrompt('Pilih desa dari daftar.');
                $desaValidation->setErrorTitle('Desa Tidak Valid');
                $desaValidation->setError('Pilih desa dari daftar yang tersedia.');
                $templateSheet->setDataValidation('D2:D1000', $desaValidation);

                $kelompokValidation = new DataValidation;
                $kelompokValidation->setType(DataValidation::TYPE_LIST);
                $kelompokValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $kelompokValidation->setAllowBlank(true);
                $kelompokValidation->setShowInputMessage(true);
                $kelompokValidation->setShowErrorMessage(true);
                $kelompokValidation->setShowDropDown(true);
                $kelompokValidation->setFormula1('=INDIRECT(SUBSTITUTE(D2," ","_"))');
                $kelompokValidation->setPromptTitle('Pilih Kelompok');
                $kelompokValidation->setPrompt('Pilih kelompok berdasarkan desa yang dipilih.');
                $kelompokValidation->setErrorTitle('Kelompok Tidak Valid');
                $kelompokValidation->setError('Pilih kelompok dari daftar yang tersedia.');
                $templateSheet->setDataValidation('E2:E1000', $kelompokValidation);

                $jkValidation = new DataValidation;
                $jkValidation->setType(DataValidation::TYPE_LIST);
                $jkValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $jkValidation->setAllowBlank(false);
                $jkValidation->setShowInputMessage(true);
                $jkValidation->setShowErrorMessage(true);
                $jkValidation->setShowDropDown(true);
                $jkValidation->setFormula1('"L,P"');
                $jkValidation->setPromptTitle('Jenis Kelamin');
                $jkValidation->setPrompt('Pilih L (Laki-Laki) atau P (Perempuan).');
                $templateSheet->setDataValidation('B2:B1000', $jkValidation);
            },
        ];
    }
}

class PersonImportDataSheet implements FromArray, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['Habibie Rahman', 'L', '2005-04-02', 'Timur Raya', 'Lemaru Sosial'],
            ['Irfan Maulana', 'L', '2006-11-15', 'Timur Raya', 'Lemaru Sosial'],
            ['Hana Safitri', 'P', '1990-01-13', 'Timur Raya', 'Lemaru Sosial'],
        ];
    }

    public function headings(): array
    {
        return [['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok']];
    }

    public function title(): string
    {
        return 'Template';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 18,
            'C' => 18,
            'D' => 20,
            'E' => 30,
        ];
    }

    public function styles($sheet)
    {
        return [
            'A1:E1' => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ],
        ];
    }
}

class PersonImportInstructionsSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return [
            ['PETUNJUK PENGISIAN TEMPLATE IMPORT PERSON'],
            [''],
            ['FORMAT FILE'],
            [''],
            ['Kolom yang tersedia:'],
            ['nama', 'Nama lengkap peserta'],
            ['jenis_kelamin', 'L atau P (Laki-Laki / Perempuan)'],
            ['tanggal_lahir', 'Format YYYY-MM-DD, contoh: 2000-01-15'],
            ['desa', 'Nama desa harus sesuai Master Data'],
            ['kelompok', 'Nama kelompok harus sesuai Master Data'],
            [''],
            ['JENIS KELAMIN'],
            ['L = Laki-Laki'],
            ['P = Perempuan'],
            [''],
            ['TANGGAL'],
            ['Format: YYYY-MM-DD'],
            ['Contoh: 2000-01-15'],
            [''],
            ['CATATAN'],
            ['Nama Desa harus sama dengan Master Data.'],
            ['Nama Kelompok harus sama dengan Master Data.'],
            ['Jangan mengubah nama kolom.'],
            ['Hapus baris contoh sebelum mengisi data.'],
            ['Pastikan tidak ada baris kosong di antara data.'],
        ];
    }

    public function headings(): array
    {
        return [['Keterangan', 'Penjelasan']];
    }

    public function title(): string
    {
        return 'Petunjuk';
    }

    public function styles($sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'A1' => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '059669']],
            ],
        ];
    }
}

class PersonImportMasterDataSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        $rows = [];
        $desas = desa::orderBy('desa_asal')->get();

        $headerWritten = false;

        foreach ($desas as $d) {
            $desaName = $d->desa_asal;

            if (! $headerWritten) {
                $rows[] = [$desaName, null];
                $headerWritten = true;
            } else {
                $rows[] = [$desaName, null];
            }

            $kelompoks = kelompok::where('desa_id', $d->id)
                ->orderBy('kelompok_asal')
                ->get();

            foreach ($kelompoks as $k) {
                $rows[] = [null, $k->kelompok_asal];
            }

            $rows[] = [null];
        }

        $rows[] = [null];
        $rows[] = ['DAFTAR DESA', null];

        foreach ($desas as $d) {
            $rows[] = [$d->desa_asal, null];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [['Desa / Kelompok', '']];
    }

    public function title(): string
    {
        return 'Master Data';
    }

    public function styles($sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1F2937']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ],
        ];
    }
}
