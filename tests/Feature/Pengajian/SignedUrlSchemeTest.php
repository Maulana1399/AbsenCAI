<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('REPRO: signed upload URL scheme when origin request is HTTP (Cloudflare Tunnel)', function () {
    Event::create(['name' => 'E', 'slug' => 'e-'.str()->random(4), 'event_type' => 'pengajian', 'status' => 'active']);
    app(ActiveEventContext::class)->set(Event::first());
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));

    // Simulasi persis Cloudflare Tunnel -> nginx -> php-fpm:
    // origin menerima HTTP, membawa X-Forwarded-Proto: https,
    // namun Laravel TIDAK trust proxy -> header diabaikan.
    $request = Request::create(
        'http://kmm.kja-techno.my.id/livewire/update',
        'POST',
        server: [
            'HTTP_HOST' => 'kmm.kja-techno.my.id',
            'SERVER_PORT' => 80,
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ],
    );
    app()->instance('request', $request);
    app('url')->forceRootUrl(null);

    $url = URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5));

    fwrite(STDERR, "\n[REPRO] signed upload URL = ".$url."\n");
    fwrite(STDERR, '[REPRO] request isSecure   = '.(app('request')->isSecure() ? 'true' : 'false')."\n");

    expect(str_starts_with($url, 'https://'))->toBeTrue();
});
