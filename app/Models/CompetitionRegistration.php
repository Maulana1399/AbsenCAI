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

    public function scheduleEntries()
    {
        return $this->hasMany(CompetitionScheduleEntry::class);
    }
}
