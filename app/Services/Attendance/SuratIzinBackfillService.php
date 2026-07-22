<?php

namespace App\Services\Attendance;

use App\Models\LegacyPesertaMapping;
use App\Models\SuratIzin;
use App\Models\peserta;

class SuratIzinBackfillService
{
    public function backfill(?int $eventId = null): array
    {
        $stats = [
            'total' => 0,
            'mapped' => 0,
            'updated' => 0,
            'skip_existing' => 0,
            'no_peserta' => 0,
            'no_mapping' => 0,
            'no_participation' => 0,
            'ambiguous' => 0,
            'errors' => 0,
        ];

        $query = SuratIzin::whereNull('participation_id');

        if ($eventId !== null) {
            $query->where('event_id', $eventId);
        }

        $query->chunk(200, function ($surats) use (&$stats) {
            foreach ($surats as $surat) {
                $stats['total']++;

                if ($surat->peserta_id === null) {
                    $stats['no_peserta']++;
                    continue;
                }

                $peserta = peserta::find($surat->peserta_id);

                if ($peserta === null) {
                    $stats['no_peserta']++;
                    continue;
                }

                $eventId = $surat->event_id;

                if ($eventId === null) {
                    $mapping = LegacyPesertaMapping::where('peserta_id', $peserta->id)->first();
                    $eventId = $mapping?->event_id;
                }

                if ($eventId === null) {
                    $stats['no_mapping']++;
                    continue;
                }

                $participation = app(ParticipationResolver::class)->resolveByPeserta($peserta, $eventId);

                if ($participation === null) {
                    $stats['no_participation']++;
                    continue;
                }

                if ($participation === false) {
                    $stats['ambiguous']++;
                    continue;
                }

                $surat->update(['participation_id' => $participation->id]);
                $stats['updated']++;
                $stats['mapped']++;
            }
        });

        return $stats;
    }

    public function dryRun(?int $eventId = null): array
    {
        $stats = [
            'total' => 0,
            'mapped' => 0,
            'skip_existing' => 0,
            'no_peserta' => 0,
            'no_mapping' => 0,
            'no_participation' => 0,
            'ambiguous' => 0,
            'errors' => 0,
        ];

        $query = SuratIzin::whereNull('participation_id');

        if ($eventId !== null) {
            $query->where('event_id', $eventId);
        }

        $query->chunk(200, function ($surats) use (&$stats) {
            foreach ($surats as $surat) {
                $stats['total']++;

                if ($surat->peserta_id === null) {
                    $stats['no_peserta']++;
                    continue;
                }

                $peserta = peserta::find($surat->peserta_id);

                if ($peserta === null) {
                    $stats['no_peserta']++;
                    continue;
                }

                $eventId = $surat->event_id;

                if ($eventId === null) {
                    $mapping = LegacyPesertaMapping::where('peserta_id', $peserta->id)->first();
                    $eventId = $mapping?->event_id;
                }

                if ($eventId === null) {
                    $stats['no_mapping']++;
                    continue;
                }

                $participation = app(ParticipationResolver::class)->resolveByPeserta($peserta, $eventId);

                if ($participation === null) {
                    $stats['no_participation']++;
                    continue;
                }

                if ($participation === false) {
                    $stats['ambiguous']++;
                    continue;
                }

                $stats['mapped']++;
            }
        });

        return $stats;
    }
}
