<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratIzin extends Model
{
    protected $fillable = [
        'peserta_id',
        'participation_id',
        'event_id',
        'nomor_surat',
        'alasan',
        'jenis_izin',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'returned_at',
    ];

    protected $casts = [
        'tanggal_mulai'  => 'date',
        'tanggal_selesai' => 'date',
        'approved_at'    => 'datetime',
        'returned_at'    => 'datetime',
    ];

    public function peserta()
    {
        return $this->belongsTo(peserta::class);
    }

    public function participation()
    {
        return $this->belongsTo(Participation::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function izinAbsensis()
    {
        return $this->hasMany(IzinAbsensi::class, 'surat_izin_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isReturned(): bool
    {
        return $this->isApproved() && $this->returned_at !== null;
    }
}
