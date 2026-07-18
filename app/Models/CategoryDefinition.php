<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryDefinition extends Model
{
    protected $fillable = [
        'event_id',
        'name',
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

    public function activityCategories()
    {
        return $this->hasMany(ActivityCategory::class);
    }

    public function activityRegistrations()
    {
        return $this->hasMany(ActivityRegistration::class);
    }
}
