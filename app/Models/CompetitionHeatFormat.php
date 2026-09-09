<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionHeatFormat extends Model
{
    protected $fillable = [
        'competition_class_id',
        'round',
        'participants_per_heat',
        'min_participants_to_start',
        'qualifiers_per_heat',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'participants_per_heat' => 'integer',
            'min_participants_to_start' => 'integer',
            'qualifiers_per_heat' => 'integer',
        ];
    }

    public function competitionClass()
    {
        return $this->belongsTo(CompetitionClass::class);
    }
}
