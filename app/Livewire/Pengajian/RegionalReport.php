<?php

namespace App\Livewire\Pengajian;

use App\Models\Event;
use App\Services\Pengajian\PengajianRegionalReportService;
use App\Support\ActiveEventContext;
use Livewire\Component;

class RegionalReport extends Component
{
    public array $summary = [];

    public array $desaBreakdown = [];

    public array $attendanceList = [];

    public ?int $selectedDesaId = null;

    public ?string $filterStatus = null;

    public ?string $filterMethod = null;

    public string $listSearch = '';

    public string $activeTab = 'summary';

    public function mount(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $event = app(ActiveEventContext::class)->requireCurrent();

        try {
            $service = app(PengajianRegionalReportService::class);

            $this->summary = $service->summary($event);
            $this->desaBreakdown = $service->desaBreakdown($event);
        } catch (\Throwable) {
            $this->summary = [];
            $this->desaBreakdown = [];
        }
    }

    public function selectDesa(int $desaId): void
    {
        $this->selectedDesaId = $desaId;
        $this->activeTab = 'list';
        $this->loadAttendanceList();
    }

    public function showAll(): void
    {
        $this->selectedDesaId = null;
        $this->activeTab = 'list';
        $this->loadAttendanceList();
    }

    public function loadAttendanceList(): void
    {
        $event = app(ActiveEventContext::class)->requireCurrent();

        try {
            $this->attendanceList = app(PengajianRegionalReportService::class)
                ->attendanceList(
                    $event,
                    desaId: $this->selectedDesaId,
                    search: $this->listSearch ?: null,
                    status: $this->filterStatus ?: null,
                    method: $this->filterMethod ?: null,
                );
        } catch (\Throwable) {
            $this->attendanceList = [];
        }
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'list' && empty($this->attendanceList)) {
            $this->loadAttendanceList();
        }
    }

    public function updatedListSearch(): void
    {
        $this->loadAttendanceList();
    }

    public function updatedFilterStatus(): void
    {
        $this->loadAttendanceList();
    }

    public function updatedFilterMethod(): void
    {
        $this->loadAttendanceList();
    }

    public function render()
    {
        if ($this->activeTab === 'list' && empty($this->attendanceList)) {
            $this->loadAttendanceList();
        }

        return view('livewire.pengajian.regional-report');
    }
}
