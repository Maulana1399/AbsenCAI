<?php

namespace App\Services\Attendance;

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuratIzinService
{
    public function __construct(
        private readonly AttendanceExceptionService $exceptionService,
    ) {}

    public function create(array $data, int $createdBy): SuratIzin
    {
        return SuratIzin::create([
            'peserta_id'      => $data['peserta_id'],
            'alasan'          => $data['alasan'],
            'jenis_izin'      => $data['jenis_izin'] ?? 'pulang',
            'tanggal_mulai'   => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'status'          => 'draft',
            'created_by'      => $createdBy,
        ]);
    }

    public function submit(SuratIzin $surat): SuratIzin
    {
        if (! $surat->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya surat berstatus draft yang dapat disubmit.',
            ]);
        }

        $surat->update(['status' => 'pending']);

        return $surat->fresh();
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

            $sesis = SesiAbsensi::whereBetween('tanggal', [$mulai, $selesai])->get();

            $created      = [];
            $skippedHadir = [];
            $skippedIzin  = [];

            foreach ($sesis as $sesi) {
                if (Absensi::where('nip', $surat->peserta->nip)
                    ->where('sesi_id', $sesi->id)
                    ->exists()
                ) {
                    $skippedHadir[] = $sesi;
                    continue;
                }

                if (IzinAbsensi::where('peserta_id', $surat->peserta_id)
                    ->where('sesi_id', $sesi->id)
                    ->exists()
                ) {
                    $skippedIzin[] = $sesi;
                    continue;
                }

                $izin = $this->exceptionService->recordIzin(
                    pesertaId:   $surat->peserta_id,
                    sesiId:      $sesi->id,
                    source:      'surat_izin',
                    suratIzinId: $surat->id,
                );
                $created[] = $izin;
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

        return $surat->fresh();
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

        return $surat->fresh();
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
            if (Absensi::where('nip', $surat->peserta->nip)
                ->where('sesi_id', $sesi->id)
                ->exists()
            ) {
                continue;
            }

            if (IzinAbsensi::where('peserta_id', $surat->peserta_id)
                ->where('sesi_id', $sesi->id)
                ->exists()
            ) {
                continue;
            }

            $this->exceptionService->recordIzin(
                pesertaId:  $surat->peserta_id,
                sesiId:     $sesi->id,
                source:     'surat_izin',
                suratIzinId: $surat->id,
            );
        }
    }

    private function generateNomorSurat(int $suratId): string
    {
        $tahun = now()->format('Y');

        return sprintf('SI-%s-%04d', $tahun, $suratId);
    }
}
