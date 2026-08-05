<?php

use App\Enums\Role;
use App\Livewire\Pengajian\Admin\ImportMassal;
use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

class UploadEndpointFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function upload_endpoint_csv(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'uep').'.csv';
    file_put_contents($path, "nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nJono,L,2000-01-15,Desa Import,Kelompok A\n");

    return new UploadEndpointFile($path, 'import.csv', 'text/csv', null, true);
}

beforeEach(function () {
    Event::create([
        'name' => 'E',
        'slug' => 'e-'.str()->random(4),
        'event_type' => 'pengajian',
        'status' => 'active',
    ]);
    app(ActiveEventContext::class)->set(Event::first());
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
});

// ---------------------------------------------------------------------------
// Browser-flow trace: real upload endpoint (signed URL) must return 200 and
// populate $file so the "Preview & Validasi" button becomes enabled.
// ---------------------------------------------------------------------------

test('real upload endpoint accepts signed POST and stores temp file', function () {
    $signedUrl = URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5));

    $response = $this->post($signedUrl, ['files' => [upload_endpoint_csv()]]);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->json('paths'))->toBeArray()->not->toBeEmpty();

    $path = ltrim($response->json('paths')[0], '/');
    $stored = storage_path('app/private/livewire-tmp/'.$path);
    expect(file_exists($stored))->toBeTrue();
});

test('full upload flow sets $file so preview button is no longer disabled', function () {
    $comp = Livewire::test(ImportMassal::class);

    // Before upload: $file null -> button disabled.
    $before = $comp->html();
    expect(str_contains($before, 'wire:click="preview" disabled'))->toBeTrue();

    $signedUrl = URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5));
    $response = $this->post($signedUrl, ['files' => [upload_endpoint_csv()]]);
    $path = $response->json('paths')[0];

    $comp->call('_finishUpload', 'file', $path, false);

    // After upload: $file set -> button enabled (disabled attribute gone).
    $after = $comp->html();
    expect($comp->get('file'))->not->toBeNull()
        ->and(str_contains($after, 'wire:click="preview" disabled'))->toBeFalse();
});
