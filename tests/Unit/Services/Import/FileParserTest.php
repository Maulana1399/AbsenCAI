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
