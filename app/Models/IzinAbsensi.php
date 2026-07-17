<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IzinAbsensi extends Model
{
    protected $table = 'izin_absensis';

    protected $fillable = [
        'peserta_id',
        'sesi_id',
        'status',
        'source',
        'surat_izin_id',
    ];

    public function peserta()
    {
        return $this->belongsTo(peserta::class);
    }

    public function sesi()
    {
        return $this->belongsTo(SesiAbsensi::class, 'sesi_id');
    }

    public function suratIzin()
    {
        return $this->belongsTo(SuratIzin::class, 'surat_izin_id');
    }
}
