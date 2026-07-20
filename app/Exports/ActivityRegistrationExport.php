<?php

namespace App\Exports;

use App\Models\ActivityRegistration;
use App\Support\ActiveEventContext;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivityRegistrationExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        public $event_id = null,
        public $activity_group_id = null,
        public $activity_id = null,
        public $category_definition_id = null,
    ) {
    }

    public function collection()
    {
        $eventId = $this->event_id ?? app(ActiveEventContext::class)->current()?->id;

        $query = ActivityRegistration::with(['participation.person.desa', 'activity.activityGroup', 'categoryDefinition']);

        if ($eventId !== null) {
            $query->where('event_id', $eventId);
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

        return $query->orderBy('id')->get()->map(function (ActivityRegistration $registration, $index) {
            $person = $registration->participation?->person;

            return [
                'No' => $index + 1,
                'Nama' => $person?->nama,
                'Participant Number' => $registration->participation?->participant_number,
                'Attendance Code' => $registration->participation?->attendance_code,
                'Activity Group' => $registration->activity?->activityGroup?->name,
                'Activity' => $registration->activity?->name,
                'Category' => $registration->categoryDefinition?->name,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Participant Number',
            'Attendance Code',
            'Activity Group',
            'Activity',
            'Category',
        ];
    }
}
