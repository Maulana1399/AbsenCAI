<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaiParticipantReplacement extends Model
{
    protected $fillable = [
        'event_id',
        'peserta_id',
        'old_person_id',
        'old_participation_id',
        'new_person_id',
        'new_participation_id',
        'legacy_nip',
        'participant_number',
        'attendance_code',
        'desa_id',
        'kelompok_id',
        'regu_id',
        'reason',
        'replaced_by',
        'replaced_at',
    ];

    protected function casts(): array
    {
        return [
            'replaced_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function peserta()
    {
        return $this->belongsTo(peserta::class);
    }

    public function oldPerson()
    {
        return $this->belongsTo(Person::class, 'old_person_id');
    }

    public function newPerson()
    {
        return $this->belongsTo(Person::class, 'new_person_id');
    }

    public function oldParticipation()
    {
        return $this->belongsTo(Participation::class, 'old_participation_id');
    }

    public function newParticipation()
    {
        return $this->belongsTo(Participation::class, 'new_participation_id');
    }

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function kelompok()
    {
        return $this->belongsTo(kelompok::class);
    }

    public function regu()
    {
        return $this->belongsTo(regu::class);
    }

    public function replacedBy()
    {
        return $this->belongsTo(User::class, 'replaced_by');
    }
}
