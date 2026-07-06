<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiAbsensi extends Model
{
    protected $fillable = ['nama_sesi', 'tanggal', 'aktif'];

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'sesi_id');
    }
}
