<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionOutcome extends Model
{
    protected $fillable = [
        'competition_registration_id',
        'position',
        'status',
        'score',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'score' => 'decimal:2',
        ];
    }

    public function competitionRegistration()
    {
        return $this->belongsTo(CompetitionRegistration::class);
    }
}
