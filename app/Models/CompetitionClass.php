<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionClass extends Model
{
    protected $fillable = [
        'event_id',
        'competition_category_id',
        'name',
        'gender',
        'code',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competitionCategory()
    {
        return $this->belongsTo(CompetitionCategory::class);
    }

    public function competitionSchedules()
    {
        return $this->hasMany(CompetitionSchedule::class);
    }

    public function competitionRegistrations()
    {
        return $this->hasMany(CompetitionRegistration::class);
    }
}
