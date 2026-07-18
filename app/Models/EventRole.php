<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRole extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'scope',
        'description',
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

    public function committeeAssignments()
    {
        return $this->hasMany(EventCommitteeAssignment::class);
    }
}
