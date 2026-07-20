<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\User;
use App\Models\desa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DesaAccessService
{
    public function createGrant(
        Event $event,
        desa $desa,
        \DateTimeInterface $validFrom,
        \DateTimeInterface $validUntil,
        ?User $createdBy = null,
        ?int $nonceTtlMinutes = 1440,
    ): array {
        $rawToken = $this->generateRawToken();
        $tokenHash = Hash::make($rawToken);
        $tokenPrefix = substr($rawToken, 0, 16);
        $nonce = $this->generateNonce();
        $nonceExpiresAt = Carbon::now()->addMinutes($nonceTtlMinutes);

        $grant = DesaAccessGrant::create([
            'event_id' => $event->id,
            'desa_id' => $desa->id,
            'token_hash' => $tokenHash,
            'token_prefix' => $tokenPrefix,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'nonce' => $nonce,
            'nonce_expires_at' => $nonceExpiresAt,
            'created_by' => $createdBy?->id,
        ]);

        return [
            'grant' => $grant,
            'raw_token' => $rawToken,
        ];
    }

    public function validateToken(string $rawToken, int $eventId): ?DesaAccessGrant
    {
        $rawToken = trim($rawToken);

        if (strlen($rawToken) < 16) {
            return null;
        }

        $prefix = substr($rawToken, 0, 16);

        $candidates = DesaAccessGrant::where('token_prefix', $prefix)
            ->where('event_id', $eventId)
            ->get();

        foreach ($candidates as $grant) {
            if (! Hash::check($rawToken, $grant->token_hash)) {
                continue;
            }

            if ($grant->revoked_at !== null) {
                return null;
            }

            if (Carbon::now()->lessThan($grant->valid_from)) {
                return null;
            }

            if (Carbon::now()->greaterThan($grant->valid_until)) {
                return null;
            }

            return $grant;
        }

        return null;
    }

    public function findGrantByToken(string $rawToken): ?DesaAccessGrant
    {
        $rawToken = trim($rawToken);

        if (strlen($rawToken) < 16) {
            return null;
        }

        $prefix = substr($rawToken, 0, 16);

        $candidates = DesaAccessGrant::where('token_prefix', $prefix)->get();

        foreach ($candidates as $grant) {
            if (! Hash::check($rawToken, $grant->token_hash)) {
                continue;
            }

            if ($grant->revoked_at !== null) {
                return null;
            }

            if (Carbon::now()->lessThan($grant->valid_from)) {
                return null;
            }

            if (Carbon::now()->greaterThan($grant->valid_until)) {
                return null;
            }

            return $grant;
        }

        return null;
    }

    public function exchangeForNonce(DesaAccessGrant $grant): string
    {
        $nonce = $this->generateNonce();
        $nonceExpiresAt = Carbon::now()->addDay();

        $grant->update([
            'nonce' => $nonce,
            'nonce_expires_at' => $nonceExpiresAt,
        ]);

        return $nonce;
    }

    public function resolveNonce(string $nonce): ?array
    {
        $grant = DesaAccessGrant::where('nonce', $nonce)->first();

        if ($grant === null) {
            return null;
        }

        if ($grant->revoked_at !== null) {
            return null;
        }

        if (Carbon::now()->lessThan($grant->valid_from)) {
            return null;
        }

        if (Carbon::now()->greaterThan($grant->valid_until)) {
            return null;
        }

        if (Carbon::now()->greaterThan($grant->nonce_expires_at)) {
            return null;
        }

        return [
            'event_id' => $grant->event_id,
            'desa_id' => $grant->desa_id,
        ];
    }

    public function revokeGrant(DesaAccessGrant $grant): void
    {
        $grant->update(['revoked_at' => Carbon::now()]);
    }

    public function rotateNonce(DesaAccessGrant $grant): string
    {
        return $this->exchangeForNonce($grant);
    }

    private function generateRawToken(): string
    {
        return 'kja-dgt-'.Str::random(60);
    }

    private function generateNonce(): string
    {
        return Str::random(32);
    }
}
