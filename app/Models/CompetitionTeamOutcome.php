<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionTeamOutcome extends Model
{
    protected $fillable = [
        'competition_team_id',
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

    public function team()
    {
        return $this->belongsTo(CompetitionTeam::class, 'competition_team_id');
    }
}
