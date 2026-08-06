<?php

use App\Exports\PersonImportTemplateExport;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Import\Adapters\Pengajian\PengajianImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(Tests\TestCase::class, RefreshDatabase::class);

function png_context(int $eventId, string $mode = 'execute'): ImportContext
{
    return new ImportContext(
        type: 'pengajian',
        eventId: $eventId,
        mode: $mode,
        options: ['rows' => []],
        definitionKey: 'pengajian',
    );
}

function png_rows(array $rows): array
{
    return array_merge(
        [['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok']],
        $rows,
    );
}

function png_csv(array $rows): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'png_csv').'.csv';
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return new UploadedFile($path, 'import.csv', 'text/csv', null, true);
}

function png_xlsx(array $data): UploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'png_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->definition = app(PengajianImportDefinition::class);
    $this->event = Event::create(['name' => 'Event Pengajian', 'slug' => 'event-pengajian-'.str()->random(5), 'event_type' => 'pengajian', 'status' => 'active']);
});

test('pengajian definition exposes metadata', function () {
    expect($this->definition->displayName())->toBe('Import Massal Peserta Pengajian')
        ->and($this->definition->description())->toContain('Pengajian')
        ->and($this->definition->icon())->not->toBe('')
        ->and($this->definition->key())->toBe('pengajian')
        ->and($this->definition->parameters())->toBe([])
        ->and($this->definition->columns())->toHaveKeys(['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'])
        ->and($this->definition->rules())->toHaveKeys(['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa']);
});

test('pengajian definition template wraps the golden template unchanged', function () {
    $template = $this->definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_person.xlsx')
        ->and($template->toExport())->toBeInstanceOf(PersonImportTemplateExport::class)
        ->and(count($template->toExport()->sheets()))->toBe(3);
});

test('pengajian parser keeps every csv row including blank lines', function () {
    $rows = $this->definition->parser()->parse(
        png_csv(png_rows([
            ['Jono', 'L', '2000-01-15', 'Desa A', 'Kelompok A'],
            [],
        ])),
        png_context($this->event->id),
    );

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->rowNumber)->toBe(2)
        ->and($rows[0]->raw['nama'])->toBe('Jono')
        ->and($rows[1]->rowNumber)->toBe(3);
});

test('pengajian parser skips excel rows with empty nama', function () {
    $rows = $this->definition->parser()->parse(
        png_xlsx([
            ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
            ['', 'L', '2000-01-15', 'Desa A', 'Kelompok A'],
            ['Jono', 'L', '2000-01-15', 'Desa A', 'Kelompok A'],
        ]),
        png_context($this->event->id),
    );

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->raw['nama'])->toBe('Jono');
});

test('pengajian parser throws for missing required column', function () {
    $file = png_csv([
        ['nama', 'jenis_kelamin'],
        ['Jono', 'L'],
    ]);

    $this->definition->parser()->parse($file, png_context($this->event->id));
})->throws(RuntimeException::class, 'Kolom wajib tidak ditemukan');

test('pengajian validator preserves exact messages and order', function () {
    desa::create(['desa_asal' => 'Desa A']);
    $desaId = desa::where('desa_asal', 'Desa A')->value('id');

    $rows = $this->definition->normalize($this->definition->parser()->parse(
        png_csv(png_rows([
            ['', '', '', ''],
        ])),
        png_context($this->event->id),
    ), png_context($this->event->id));

    $summary = $this->definition->validator()->validate($rows, png_context($this->event->id));

    $messages = collect($summary->errors)->pluck('message')->all();

    expect($messages)->toBe([
        'Nama wajib diisi.',
        'Jenis kelamin wajib diisi.',
        'Tanggal lahir wajib diisi.',
        'Desa wajib diisi.',
    ])->and($summary->errors[0]->rowNumber)->toBe(2);
});

test('pengajian validator rejects Laki - Laki gender (strict L/P)', function () {
    $rows = $this->definition->normalize($this->definition->parser()->parse(
        png_csv(png_rows([
            ['Jono', 'Laki - Laki', '2000-01-15', 'Desa A', ''],
        ])),
        png_context($this->event->id),
    ), png_context($this->event->id));

    $summary = $this->definition->validator()->validate($rows, png_context($this->event->id));

    expect($summary->errors[0]->message)->toBe('Jenis kelamin harus L atau P.');
});

test('pengajian committer produces exact golden metrics', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $desa->id]);

    Person::create(['nama' => 'Existing', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $desa->id]);

    $rows = $this->definition->normalize($this->definition->parser()->parse(
        png_csv(png_rows([
            ['Existing', 'L', '2000-01-15', 'Desa A', 'Kelompok A'], // matched
            ['New Person', 'L', '1990-05-20', 'Desa A', 'Kelompok A'], // new
            ['New Person', 'L', '1990-05-20', 'Desa A', 'Kelompok A'], // duplicate participation
            ['', 'L', '1990-05-20', 'Desa A', 'Kelompok A'], // fail: empty nama
        ])),
        png_context($this->event->id),
    ), png_context($this->event->id));

    $commit = $this->definition->commit($rows, png_context($this->event->id));

    expect($commit->metrics['matched_persons'])->toBe(2)
        ->and($commit->metrics['created_persons'])->toBe(1)
        ->and($commit->metrics['created_participations'])->toBe(2)
        ->and($commit->metrics['skipped_duplicates'])->toBe(1)
        ->and($commit->metrics['failed_rows'])->toBe(1)
        ->and($commit->failedRows)->toHaveCount(1)
        ->and($commit->failedRows[0]['message'])->toBe('Nama wajib diisi.');
});
