<?php

use App\Services\Import\Exceptions\ImportParseException;
use App\Services\Import\Support\FileParser;
use Illuminate\Http\UploadedFile;

uses(Tests\TestCase::class);

function file_parser_xlsx(array $data): UploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'fp_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

/**
 * Build an xlsx whose worksheet XML declares a full-height dimension
 * (A1:E1048576) with trailing empty rows up to r="1048576" — the shape of the
 * production file that previously exhausted memory via Worksheet::toArray().
 */
function file_parser_huge_xlsx(array $data): UploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'fp_huge').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    $zip = new ZipArchive;
    $zip->open($path);
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

    $sheetXml = preg_replace('/<dimension[^\/]*\/>/', '<dimension ref="A1:E1048576"/>', $sheetXml, 1);

    $trailing = '';

    foreach ([1048572, 1048573, 1048574, 1048575, 1048576] as $row) {
        $cells = '';

        foreach (['A', 'B', 'C', 'D', 'E'] as $column) {
            $cells .= '<c r="'.$column.$row.'" t="inlineStr"><is><t></t></is></c>';
        }

        $trailing .= '<row r="'.$row.'" spans="1:5">'.$cells.'</row>';
    }

    $sheetXml = str_replace('</sheetData>', $trailing.'</sheetData>', $sheetXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    return new UploadedFile($path, 'import-huge.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('file parser reads a csv into rows keyed by normalized headers', function () {
    $file = UploadedFile::fake()->createWithContent('desa.csv', "desa\nDesa Satu\nDesa Dua\n");

    $rows = app(FileParser::class)->parse($file, ['desa'], 'Import Desa');

    expect($rows)->toBe([
        ['desa' => 'Desa Satu'],
        ['desa' => 'Desa Dua'],
    ]);
});

test('file parser normalizes header names (space/dash to underscore, lowercase)', function () {
    $file = UploadedFile::fake()->createWithContent('desa.csv', "Nama Desa\nDesa Satu\n");

    $rows = app(FileParser::class)->parse($file, ['nama_desa'], 'Import Desa');

    expect($rows)->toBe([
        ['nama_desa' => 'Desa Satu'],
    ]);
});

test('file parser reads an excel file', function () {
    $file = file_parser_xlsx([
        ['desa'],
        ['Desa Excel'],
        ['Desa Excel Dua'],
    ]);

    $rows = app(FileParser::class)->parse($file, ['desa'], 'Import Desa');

    expect($rows)->toBe([
        ['desa' => 'Desa Excel'],
        ['desa' => 'Desa Excel Dua'],
    ]);
});

test('file parser throws ImportParseException when a required column is missing', function () {
    $file = UploadedFile::fake()->createWithContent('desa.csv', "nama\nDesa Satu\n");

    app(FileParser::class)->parse($file, ['desa'], 'Import Desa');
})->throws(ImportParseException::class, 'desa');

test('file parser throws ImportParseException for a file without headers', function () {
    $file = UploadedFile::fake()->createWithContent('empty.csv', '');

    app(FileParser::class)->parse($file, ['desa'], 'Import Desa');
})->throws(ImportParseException::class);

test('file parser prunes fully empty rows', function () {
    $file = UploadedFile::fake()->createWithContent('desa.csv', "desa\nDesa Satu\n\n\n");

    $rows = app(FileParser::class)->parse($file, ['desa'], 'Import Desa');

    expect($rows)->toBe([
        ['desa' => 'Desa Satu'],
    ]);
});

test('file parser reads an xlsx with a 1,048,576-row dimension without exhausting memory', function () {
    $file = file_parser_huge_xlsx([
        ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
        ['Ahmad', 'L', '16/09/1999', 'Desa Import', 'Kelompok A'],
        ['Budi', 'P', '31-12-1985', 'Desa Import', 'Kelompok A'],
    ]);

    $rows = app(FileParser::class)->parse($file, ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'], 'Import Desa');

    expect($rows)->toBe([
        ['nama' => 'Ahmad', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '16/09/1999', 'desa' => 'Desa Import', 'kelompok' => 'Kelompok A'],
        ['nama' => 'Budi', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '31-12-1985', 'desa' => 'Desa Import', 'kelompok' => 'Kelompok A'],
    ]);
});
