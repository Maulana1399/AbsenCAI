<?php

namespace App\Models;

use App\Services\Placement\PlacementService;
use Illuminate\Database\Eloquent\Model;

class peserta extends Model
{
    public const STATUS_BELUM_REGISTRASI = 'Belum Registrasi';
    public const STATUS_SELF_REGISTER = 'Self Register';
    public const STATUS_REGISTRASI_ULANG = 'Registrasi Ulang';

    public const JENIS_WAJIB = 'Wajib';
    public const JENIS_KIRIMAN = 'Kiriman';
    public const JENIS_PERSON = 'Person';

    protected $fillable = [
        'nama',
        'nip',
        'participant_number',
        'attendance_code',
        'jenis_kelamin',
        'jenis_peserta',
        'kelompok_id',
        'desa_id',
        'regu_id',
        'status_registrasi',
    ];

    public static function statusRegistrasiOptions(): array
    {
        return [
            self::STATUS_BELUM_REGISTRASI,
            self::STATUS_SELF_REGISTER,
            self::STATUS_REGISTRASI_ULANG,
        ];
    }

    public static function jenisPesertaOptions(): array
    {
        return [
            self::JENIS_WAJIB,
            self::JENIS_KIRIMAN,
            self::JENIS_PERSON,
        ];
    }

    public static function nextAutoNip(?string $jenisKelamin = null): int
    {
        return PlacementService::legacyNextNip($jenisKelamin);
    }

    public static function nextAutoParticipantNumber(?string $jenisKelamin = null): string
    {
        return PlacementService::generateParticipantNumber($jenisKelamin);
    }

    public function getStatusRegistrasiLabelAttribute(): string
    {
        return $this->status_registrasi
            ?: self::STATUS_BELUM_REGISTRASI;
    }

    public function kelompok()
    {
        return $this->belongsTo(kelompok::class);
    }

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function regu()
    {
        return $this->belongsTo(regu::class);
    }

    public function suratIzins()
    {
        return $this->hasMany(SuratIzin::class, 'peserta_id');
    }
}
