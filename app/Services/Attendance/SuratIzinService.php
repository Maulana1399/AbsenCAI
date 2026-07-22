<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Support\ActiveEventContext;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuratIzinService
{
    public function __construct(
        private readonly AttendanceExceptionService $exceptionService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function create(array $data, int $createdBy): SuratIzin
    {
        $eventId = app(ActiveEventContext::class)->id();
        $peserta = \App\Models\peserta::find($data['peserta_id']);
        $participationId = null;

        if ($peserta && $eventId) {
            $participation = app(ParticipationResolver::class)->resolveByPeserta($peserta, $eventId);
            if ($participation) {
                $participationId = $participation->id;
            }
        }

        $surat = SuratIzin::create([
            'peserta_id'        => $data['peserta_id'],
            'participation_id'  => $participationId,
            'event_id'          => $eventId,
            'alasan'            => $data['alasan'],
            'jenis_izin'        => $data['jenis_izin'] ?? 'pulang',
            'tanggal_mulai'     => $data['tanggal_mulai'],
            'tanggal_selesai'   => $data['tanggal_selesai'],
            'status'            => 'draft',
            'created_by'        => $createdBy,
        ]);

        $this->activityLogService->log(
            action: 'created',
            module: 'surat_izin',
            description: 'Surat izin dibuat untuk ' . ($surat->peserta->nama ?? 'Peserta #' . $surat->peserta_id),
            subject: $surat,
            properties: [
                'nomor_surat'     => $surat->nomor_surat,
                'peserta_id'      => $surat->peserta_id,
                'status'          => 'draft',
                'tanggal_mulai'   => $surat->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $surat->tanggal_selesai?->toDateString(),
                'jenis_izin'      => $surat->jenis_izin,
            ],
        );

        return $surat;
    }

    public function submit(SuratIzin $surat): SuratIzin
    {
        if (! $surat->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya surat berstatus draft yang dapat disubmit.',
            ]);
        }

        $surat->update(['status' => 'pending']);

        $fresh = $surat->fresh();

        $this->activityLogService->log(
            action: 'submitted',
            module: 'surat_izin',
            description: 'Surat izin diajukan untuk ' . ($fresh->peserta->nama ?? 'Peserta #' . $fresh->peserta_id),
            subject: $fresh,
            properties: [
                'nomor_surat'     => $fresh->nomor_surat,
                'peserta_id'      => $fresh->peserta_id,
                'status'          => 'pending',
                'tanggal_mulai'   => $fresh->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $fresh->tanggal_selesai?->toDateString(),
                'jenis_izin'      => $fresh->jenis_izin,
            ],
        );

        return $fresh;
    }

    public function approve(SuratIzin $surat, User $approver): array
    {
        if (! $surat->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya surat berstatus pending yang dapat disetujui.',
            ]);
        }

        $result = DB::transaction(function () use ($surat, $approver) {
            $mulai = $surat->tanggal_mulai->toDateString();
            $selesai = $surat->tanggal_selesai->toDateString();

            $eventId = app(ActiveEventContext::class)->id();

            $sesis = SesiAbsensi::when($eventId, fn ($q) => $q->where('event_id', $eventId))
                ->whereBetween('tanggal', [$mulai, $selesai])
                ->get();

            $created      = [];
            $skippedHadir = [];
            $skippedIzin  = [];

            foreach ($sesis as $sesi) {
                if ($surat->peserta && Absensi::where('nip', $surat->peserta->nip)
                    ->where('sesi_id', $sesi->id)
                    ->exists()
                ) {
                    $skippedHadir[] = $sesi;
                    continue;
                }

                if ($surat->peserta_id && IzinAbsensi::where('peserta_id', $surat->peserta_id)
                    ->where('sesi_id', $sesi->id)
                    ->exists()
                ) {
                    $skippedIzin[] = $sesi;
                    continue;
                }

                if ($surat->participation_id && \App\Models\EventAttendance::where('participation_id', $surat->participation_id)
                    ->where('sesi_absensi_id', $sesi->id)
                    ->exists()
                ) {
                    $skippedHadir[] = $sesi;
                    continue;
                }

                if ($surat->participation_id && $surat->event_id === $sesi->event_id) {
                    $canonical = \App\Models\EventAttendance::create([
                        'participation_id' => $surat->participation_id,
                        'sesi_absensi_id'  => $sesi->id,
                        'event_id'         => $sesi->event_id,
                        'status'           => \App\Models\EventAttendance::STATUS_IZIN,
                        'attended_at'      => now(),
                        'method'           => 'surat_izin',
                        'recorded_by'      => auth()->id(),
                    ]);
                    $created[] = $canonical;
                } elseif ($surat->peserta_id) {
                    try {
                        $izin = $this->exceptionService->recordIzin(
                            pesertaId:   $surat->peserta_id,
                            sesiId:      $sesi->id,
                            source:      'surat_izin',
                            suratIzinId: $surat->id,
                        );
                        $created[] = $izin;
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $skippedIzin[] = $sesi;
                    }
                }
            }

            $nomorSurat = $this->generateNomorSurat($surat->id);

            $surat->update([
                'status'      => 'approved',
                'nomor_surat' => $nomorSurat,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return [
                'surat'          => $surat->fresh(),
                'created'        => $created,
                'skipped_hadir'  => $skippedHadir,
                'skipped_izin'   => $skippedIzin,
                'sesi_found'     => $sesis->count(),
            ];
        });

        $fresh = $result['surat'];

        $this->activityLogService->log(
            action: 'approved',
            module: 'surat_izin',
            description: 'Surat izin disetujui untuk ' . ($fresh->peserta->nama ?? 'Peserta #' . $fresh->peserta_id),
            subject: $fresh,
            properties: [
                'nomor_surat'     => $fresh->nomor_surat,
                'peserta_id'      => $fresh->peserta_id,
                'status'          => 'approved',
                'tanggal_mulai'   => $fresh->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $fresh->tanggal_selesai?->toDateString(),
                'jenis_izin'      => $fresh->jenis_izin,
                'sesi_count'      => $result['sesi_found'],
                'izin_created'    => count($result['created']),
            ],
        );

        return $result;
    }

    public function reject(SuratIzin $surat): SuratIzin
    {
        if (! $surat->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya surat berstatus pending yang dapat ditolak.',
            ]);
        }

        $surat->update(['status' => 'rejected']);

        $fresh = $surat->fresh();

        $this->activityLogService->log(
            action: 'rejected',
            module: 'surat_izin',
            description: 'Surat izin ditolak untuk ' . ($fresh->peserta->nama ?? 'Peserta #' . $fresh->peserta_id),
            subject: $fresh,
            properties: [
                'nomor_surat'     => $fresh->nomor_surat,
                'peserta_id'      => $fresh->peserta_id,
                'status'          => 'rejected',
                'tanggal_mulai'   => $fresh->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $fresh->tanggal_selesai?->toDateString(),
                'jenis_izin'      => $fresh->jenis_izin,
            ],
        );

        return $fresh;
    }

    public function markReturned(SuratIzin $surat, string $tanggalKembali): SuratIzin
    {
        if (! $surat->isApproved()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya surat yang sudah disetujui yang dapat ditandai kembali.',
            ]);
        }

        if ($surat->returned_at !== null) {
            throw ValidationException::withMessages([
                'returned_at' => 'Peserta sudah ditandai kembali.',
            ]);
        }

        $tanggalKembaliCarbon = Carbon::parse($tanggalKembali);

        if ($tanggalKembaliCarbon->lt($surat->tanggal_mulai->startOfDay()) || $tanggalKembaliCarbon->gt($surat->tanggal_selesai->endOfDay())) {
            throw ValidationException::withMessages([
                'tanggal_kembali' => 'Tanggal kembali harus dalam rentang tanggal surat izin (' .
                    $surat->tanggal_mulai->format('d/m/Y') . ' — ' .
                    $surat->tanggal_selesai->format('d/m/Y') . ').',
            ]);
        }

        DB::transaction(function () use ($surat, $tanggalKembaliCarbon) {
            $surat->izinAbsensis()
                ->whereHas('sesi', fn ($q) => $q->where('tanggal', '>=', $tanggalKembaliCarbon->toDateString()))
                ->delete();

            $surat->update(['returned_at' => $tanggalKembaliCarbon]);
        });

        $fresh = $surat->fresh();

        $this->activityLogService->log(
            action: 'returned',
            module: 'surat_izin',
            description: 'Surat izin ditandai kembali untuk ' . ($fresh->peserta->nama ?? 'Peserta #' . $fresh->peserta_id),
            subject: $fresh,
            properties: [
                'nomor_surat'     => $fresh->nomor_surat,
                'peserta_id'      => $fresh->peserta_id,
                'status'          => 'approved',
                'tanggal_mulai'   => $fresh->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $fresh->tanggal_selesai?->toDateString(),
                'tanggal_kembali' => $tanggalKembali,
                'jenis_izin'      => $fresh->jenis_izin,
            ],
        );

        return $fresh;
    }

    public function syncNewSession(SesiAbsensi $sesi): void
    {
        $tanggal = Carbon::parse($sesi->tanggal)->toDateString();

        $surats = SuratIzin::with('peserta')
            ->where('status', 'approved')
            ->whereNull('returned_at')
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->get();

        foreach ($surats as $surat) {
            if ($surat->peserta && Absensi::where('nip', $surat->peserta->nip)
                ->where('sesi_id', $sesi->id)
                ->exists()
            ) {
                continue;
            }

            if ($surat->peserta_id && IzinAbsensi::where('peserta_id', $surat->peserta_id)
                ->where('sesi_id', $sesi->id)
                ->exists()
            ) {
                continue;
            }

            if ($surat->participation_id && \App\Models\EventAttendance::where('participation_id', $surat->participation_id)
                ->where('sesi_absensi_id', $sesi->id)
                ->exists()
            ) {
                continue;
            }

            if ($surat->participation_id && $surat->event_id === $sesi->event_id) {
                \App\Models\EventAttendance::create([
                    'participation_id' => $surat->participation_id,
                    'sesi_absensi_id'  => $sesi->id,
                    'event_id'         => $sesi->event_id,
                    'status'           => \App\Models\EventAttendance::STATUS_IZIN,
                    'attended_at'      => now(),
                    'method'           => 'surat_izin',
                ]);
            } elseif ($surat->peserta_id) {
                try {
                    $this->exceptionService->recordIzin(
                        pesertaId:  $surat->peserta_id,
                        sesiId:     $sesi->id,
                        source:     'surat_izin',
                        suratIzinId: $surat->id,
                    );
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Skip this surat jika peserta tidak valid untuk session ini
                }
            }
        }
    }

    private function generateNomorSurat(int $suratId): string
    {
        $tahun = now()->format('Y');

        return sprintf('SI-%s-%04d', $tahun, $suratId);
    }
}
