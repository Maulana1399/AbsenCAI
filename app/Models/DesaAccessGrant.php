<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesaAccessGrant extends Model
{
    protected $fillable = [
        'event_id',
        'desa_id',
        'token_hash',
        'token_prefix',
        'valid_from',
        'valid_until',
        'revoked_at',
        'nonce',
        'nonce_expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'revoked_at' => 'datetime',
            'nonce_expires_at' => 'datetime',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null
            && now()->greaterThanOrEqualTo($this->valid_from)
            && now()->lessThanOrEqualTo($this->valid_until);
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->valid_until);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isNonceValid(): bool
    {
        return $this->isValid()
            && now()->lessThanOrEqualTo($this->nonce_expires_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForDesa($query, int $desaId)
    {
        return $query->where('desa_id', $desaId);
    }
}
