<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionTeamMember extends Model
{
    protected $fillable = [
        'competition_team_id',
        'competition_registration_id',
        'is_substitute',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_substitute' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function team()
    {
        return $this->belongsTo(CompetitionTeam::class, 'competition_team_id');
    }

    public function competitionRegistration()
    {
        return $this->belongsTo(CompetitionRegistration::class);
    }
}
