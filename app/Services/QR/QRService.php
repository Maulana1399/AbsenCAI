<?php

namespace App\Services\QR;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

class QRService
{
    public function generatePng(string $attendanceCode): string
    {
        $renderer = new GDLibRenderer(
            size: 300,
            margin: 2,
            imageFormat: 'png'
        );

        $writer = new Writer($renderer);

        return $writer->writeString($attendanceCode);
    }

    public function generateSvg(string $attendanceCode): string
    {
        throw new \RuntimeException('SVG renderer belum diimplementasikan.');
    }
}