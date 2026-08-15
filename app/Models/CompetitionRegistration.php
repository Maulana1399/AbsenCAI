<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionRegistration extends Model
{
    protected $fillable = [
        'participation_id',
        'competition_category_id',
        'competition_class_id',
        'registration_type',
    ];

    protected function casts(): array
    {
        return [
            'registration_type' => 'string',
        ];
    }

    public function participation()
    {
        return $this->belongsTo(Participation::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    public function competitionClass()
    {
        return $this->belongsTo(CompetitionClass::class);
    }

    public function outcome()
    {
        return $this->hasOne(CompetitionOutcome::class);
    }

    public function heatResults()
    {
        return $this->hasMany(CompetitionHeatResult::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(CompetitionScheduleEntry::class);
    }

    public function teamMember()
    {
        return $this->hasOne(CompetitionTeamMember::class);
    }

    public function team()
    {
        return $this->hasOneThrough(
            CompetitionTeam::class,
            CompetitionTeamMember::class,
            'competition_registration_id',
            'id',
            'id',
            'competition_team_id'
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (self $registration) {
            $registration->scheduleEntries()->delete();

            $registration->outcome()?->delete();
        });
    }
}
