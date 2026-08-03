<?php

namespace App\Services\Dashboard;

use App\Models\Event;
use App\Services\Pengajian\PengajianRegionalReportService;

class PengajianDashboardPresenter implements DashboardPresenterContract
{
    public function __construct(
        private PengajianRegionalReportService $reportService,
    ) {}

    public function present(Event $event): array
    {
        try {
            $summary      = $this->reportService->summary($event);
            $desaBreakdown = $this->reportService->desaBreakdown($event);
        } catch (\Throwable) {
            $summary      = [];
            $desaBreakdown = [];
        }

        return [
            'summary'       => $summary,
            'desaBreakdown' => $desaBreakdown,
        ];
    }

    public function view(): string
    {
        return 'livewire.event.dashboard.pengajian';
    }
}
