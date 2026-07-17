<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Participation extends Model
{
    protected $fillable = [
        'person_id',
        'event_id',
        'participant_number',
        'attendance_code',
        'jenis_peserta',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function legacyPesertaMapping()
    {
        return $this->hasOne(LegacyPesertaMapping::class, 'participation_id');
    }
}
