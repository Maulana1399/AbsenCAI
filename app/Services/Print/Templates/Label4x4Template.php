<?php

namespace App\Services\Print\Templates;

use App\Models\Participation;
use App\Services\QR\QRService;

class Label4x4Template
{
    public function __construct(
        private readonly QRService $qrService,
    ) {
    }

    public function render(Participation $participant): string
    {
        $source = $participant instanceof Participation ? $participant->person : $participant;

        $qrBase64 = base64_encode(
            $this->qrService->generatePng((string) $participant->attendance_code)
        );

        $participantNumber = htmlspecialchars((string) $participant->participant_number, ENT_QUOTES, 'UTF-8');
        $participantName = htmlspecialchars((string) ($source->nama ?? ''), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 4cm 4cm;
            margin: 0;
        }
        body {
            margin: 0;
            width: 4cm;
            height: 4cm;
            font-family: Arial, sans-serif;
        }
        .label {
            width: 4cm;
            height: 4cm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 2px;
            padding: 2mm;
            box-sizing: border-box;
        }
        .participant-number {
            font-size: 10pt;
            font-weight: bold;
        }
        .participant-name {
            font-size: 8pt;
            line-height: 1.1;
        }
        .qr {
            width: 1.8cm;
            height: 1.8cm;
        }

        .qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="participant-number">{$participantNumber}</div>
        <div class="qr">
            <img
                src="data:image/png;base64,{$qrBase64}"
                alt="QR Code"
                style="width:100%;height:100%;object-fit:contain;"
            >
        </div>

        <div class="participant-name">{$participantName}</div>
    </div>
</body>
</html>
HTML;
    }
}
