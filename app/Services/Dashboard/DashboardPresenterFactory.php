<?php

namespace App\Services\Dashboard;

use App\Models\Event;
use InvalidArgumentException;

class DashboardPresenterFactory
{
    public function __construct(
        private CaiDashboardPresenter         $cai,
        private CompetitionDashboardPresenter $competition,
        private PengajianDashboardPresenter   $pengajian,
    ) {}

    public function make(Event $event): DashboardPresenterContract
    {
        return match (true) {
            $event->isCompetition() => $this->competition,
            $event->isPengajian()   => $this->pengajian,
            default                 => $this->cai,
        };
    }
}
