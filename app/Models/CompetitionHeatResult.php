<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionHeatResult extends Model
{
    protected $fillable = [
        'competition_schedule_id',
        'competition_registration_id',
        'competition_team_id',
        'score',
        'position',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function competitionSchedule()
    {
        return $this->belongsTo(CompetitionSchedule::class, 'competition_schedule_id');
    }

    public function competitionRegistration()
    {
        return $this->belongsTo(CompetitionRegistration::class);
    }

    public function competitionTeam()
    {
        return $this->belongsTo(CompetitionTeam::class, 'competition_team_id');
    }
}
