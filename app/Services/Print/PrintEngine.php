<?php

namespace App\Services\Print;

use App\Models\peserta;
use App\Services\Print\Templates\Label4x4Template;

class PrintEngine
{
    public function __construct(
        private readonly Label4x4Template $label4x4Template,
    ) {
    }

    public function label4x4(peserta $participant): string
    {
        return $this->label4x4Template->render($participant);
    }
}
