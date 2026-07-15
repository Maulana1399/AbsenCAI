<?php

namespace App\Services\QR;

class QRService
{
    public function generateSvg(string $attendanceCode): string
    {
        $encoded = htmlspecialchars($attendanceCode, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300" role="img" aria-label="QR Code">'
            . '<rect width="300" height="300" fill="#ffffff"/>'
            . '<text x="150" y="145" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" fill="#000000">'
            . $encoded
            . '</text>'
            . '<text x="150" y="170" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#666666">SVG placeholder for future QR renderer</text>'
            . '</svg>';
    }

    public function generatePng(string $attendanceCode): string
    {
        return $this->generateSvg($attendanceCode);
    }
}
