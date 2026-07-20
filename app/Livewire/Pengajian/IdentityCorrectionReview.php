<?php

namespace App\Livewire\Pengajian;

use App\Models\IdentityCorrectionRequest;
use App\Services\Pengajian\IdentityCorrectionService;
use Livewire\Component;

class IdentityCorrectionReview extends Component
{
    public string $rejectReason = '';

    public bool $processing = false;

    public ?int $confirmingRejectId = null;

    public function render()
    {
        $pending = app(IdentityCorrectionService::class)->listPending();

        return view('livewire.pengajian.identity-correction-review', [
            'pendingRequests' => $pending,
        ]);
    }

    public function approve(int $requestId): void
    {
        if ($this->processing) {
            return;
        }

        $this->processing = true;

        try {
            $request = IdentityCorrectionRequest::findOrFail($requestId);

            app(IdentityCorrectionService::class)->approve(
                $request,
                auth()->user(),
            );

            session()->flash('success', 'Koreksi berhasil disetujui.');
            $this->confirmingRejectId = null;
            $this->rejectReason = '';
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menyetujui koreksi.');
        } finally {
            $this->processing = false;
        }
    }

    public function confirmReject(int $requestId): void
    {
        $this->confirmingRejectId = $requestId;
        $this->rejectReason = '';
    }

    public function cancelReject(): void
    {
        $this->confirmingRejectId = null;
        $this->rejectReason = '';
    }

    public function reject(int $requestId): void
    {
        if ($this->processing) {
            return;
        }

        $this->processing = true;

        try {
            $request = IdentityCorrectionRequest::findOrFail($requestId);

            app(IdentityCorrectionService::class)->reject(
                $request,
                auth()->user(),
                $this->rejectReason ?: null,
            );

            session()->flash('success', 'Koreksi berhasil ditolak.');
            $this->confirmingRejectId = null;
            $this->rejectReason = '';
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menolak koreksi.');
        } finally {
            $this->processing = false;
        }
    }
}
