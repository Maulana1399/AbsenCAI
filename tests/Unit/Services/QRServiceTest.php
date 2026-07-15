<?php

use App\Services\QR\QRService;

uses(Tests\TestCase::class);

test('generate png returns non-empty png binary content', function () {
    if (! extension_loaded('gd')) {
        $this->markTestSkipped('QR PNG generation requires the GD extension.');
    }

    $content = app(QRService::class)->generatePng('KJA-QR123456');

    expect($content)->not->toBe('')
        ->and(substr($content, 0, 8))->toBe("\x89PNG\r\n\x1A\n");
});
