<?php

namespace App\Livewire\Rekap\Activity;

use App\Exports\ActivityRegistrationExport;
use App\Models\ActivityGroup;
use App\Models\ActivityRegistration;
use App\Models\CategoryDefinition;
use App\Models\EventRole;
use App\Services\Audit\ActivityLogService;
use App\Support\ActiveEventContext;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ActivityRegistrationReport extends Component
{
    public $activity_group_id = '';
    public $activity_id = '';
    public $category_definition_id = '';

    public $daftarActivityGroups = [];
    public $daftarCategories = [];

    public function mount()
    {
        $event = app(ActiveEventContext::class)->current();
        $this->daftarActivityGroups = $event?->activityGroups()->orderBy('name')->get() ?? collect();
        $this->daftarCategories = $event?->activityRegistrations()->with('categoryDefinition')->get()->pluck('categoryDefinition')->filter()->unique('id')->values();
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $query = ActivityRegistration::with(['participation.person', 'activity.activityGroup', 'categoryDefinition']);

        if ($event !== null) {
            $query->where('event_id', $event->id);
        } else {
            $query->whereRaw('0 = 1');
        }

        if ($this->activity_group_id) {
            $query->whereHas('activity', fn ($builder) => $builder->where('activity_group_id', $this->activity_group_id));
        }

        if ($this->activity_id) {
            $query->where('activity_id', $this->activity_id);
        }

        if ($this->category_definition_id) {
            $query->where('category_definition_id', $this->category_definition_id);
        }

        $rows = $query->orderBy('id')->get();

        return view('livewire.rekap.activity.activity-registration-report', [
            'rows' => $rows,
            'totalRegistrations' => $rows->count(),
        ]);
    }

    public function exportExcel()
    {
        $fileName = 'activity-registration-'.now()->format('YmdHis').'.xlsx';
        $filters = [];
        $this->activity_group_id && $filters['activity_group_id'] = $this->activity_group_id;
        $this->activity_id && $filters['activity_id'] = $this->activity_id;
        $this->category_definition_id && $filters['category_definition_id'] = $this->category_definition_id;

        app(ActivityLogService::class)->log(
            action: 'exported',
            module: 'export',
            description: 'Mengekspor data activity registration',
            properties: [
                'export_type' => 'activity_registration',
                'format' => 'xlsx',
                'filename' => $fileName,
                'filters' => $filters,
            ],
        );

        return Excel::download(new ActivityRegistrationExport(
            app(ActiveEventContext::class)->current()?->id,
            $this->activity_group_id,
            $this->activity_id,
            $this->category_definition_id,
        ), $fileName);
    }
}
