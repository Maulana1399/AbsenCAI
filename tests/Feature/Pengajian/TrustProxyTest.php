<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use App\Support\ActiveEventContext;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('trusted proxy X-Forwarded-Proto https makes request secure', function () {
    Event::create(['name' => 'E', 'slug' => 'e-'.str()->random(4), 'event_type' => 'pengajian', 'status' => 'active']);
    app(ActiveEventContext::class)->set(Event::first());
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));

    // Request melalui middleware TrustProxies dengan X-Forwarded-Proto: https
    $this->withServerVariables([
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ])->get(route('pengajian.import-massal', ['event' => Event::first()]));

    fwrite(STDERR, "\n[TRACE] request isSecure (X-Forwarded-Proto honored) = ".(app('request')->isSecure() ? 'true' : 'false')."\n");
    fwrite(STDERR, '[TRACE] request root = '.app('request')->root()."\n");

    expect(app('request')->isSecure())->toBeTrue();
});
