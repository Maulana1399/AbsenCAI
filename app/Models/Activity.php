<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'event_id',
        'activity_group_id',
        'name',
        'slug',
        'description',
        'status',
        'requires_category',
    ];

    protected function casts(): array
    {
        return [
            'requires_category' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activityGroup()
    {
        return $this->belongsTo(ActivityGroup::class);
    }

    public function activityRegistrations()
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function committeeAssignments()
    {
        return $this->hasMany(EventCommitteeAssignment::class);
    }
}
