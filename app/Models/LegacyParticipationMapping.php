<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyParticipationMapping extends Model
{
    protected $fillable = [
        'peserta_id',
        'person_id',
        'participation_id',
        'event_id',
        'backfill_batch_id',
        'migrated_at',
    ];

    protected function casts(): array
    {
        return [
            'migrated_at' => 'datetime',
        ];
    }

    public function peserta()
    {
        return $this->belongsTo(peserta::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function participation()
    {
        return $this->belongsTo(Participation::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
