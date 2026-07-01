<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class peserta extends Model
{
    public const STATUS_BELUM_REGISTRASI = 'Belum Registrasi';
    public const STATUS_SELF_REGISTER = 'Self Register';
    public const STATUS_REGISTRASI_ULANG = 'Registrasi Ulang';

    protected $fillable = ['nama', 'nip', 'jenis_kelamin', 'kelompok_id', 'desa_id', 'regu_id', 'status_registrasi'];

    public static function statusRegistrasiOptions(): array
    {
        return [
            self::STATUS_BELUM_REGISTRASI,
            self::STATUS_SELF_REGISTER,
            self::STATUS_REGISTRASI_ULANG,
        ];
    }

    public function getStatusRegistrasiLabelAttribute(): string
    {
        return $this->status_registrasi ?: self::STATUS_BELUM_REGISTRASI;
    }
    
    public function kelompok() {
        return $this->belongsTo(kelompok::class);
    }

    public function desa() {
        return $this->belongsTo(desa::class);
    }

    public function regu() {
        return $this->belongsTo(regu::class);
    }
}

    
