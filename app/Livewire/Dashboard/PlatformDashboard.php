<?php

namespace App\Livewire\Dashboard;

use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\User;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.platform')]
class PlatformDashboard extends Component
{
    public function getEventsProperty()
    {
        $user = auth()->user();

        if (! $user->isPlatformUser()) {
            $ids = app(EventAccessService::class)->getAssignedEventIds($user);
            return Event::active()->whereIn('id', $ids)
                ->withCount('participations')
                ->with(['sesiAbsensis' => fn ($q) => $q->where('aktif', true)])
                ->orderBy('name')
                ->get();
        }

        return Event::active()
            ->withCount('participations')
            ->with(['sesiAbsensis' => fn ($q) => $q->where('aktif', true)])
            ->orderBy('name')
            ->get();
    }

    /**
     * Daftar nama EventRole milik user pada satu event.
     *
     * Sumber: User → Person → EventCommitteeAssignment → EventRole->name.
     * Role platform (Super Admin / Admin) tetap memakai users.role.
     *
     * @return array<int, string>
     */
    public function roleNamesForEvent(Event $event, User $user): array
    {
        if ($user->isPlatformUser()) {
            return $user->role ? [$user->role->label()] : [];
        }

        if ($user->person_id === null) {
            return [];
        }

        return EventCommitteeAssignment::query()
            ->where('event_id', $event->id)
            ->where('person_id', $user->person_id)
            ->whereHas('eventRole')
            ->with('eventRole:id,name,code')
            ->get()
            ->map(fn ($assignment) => $assignment->eventRole->name)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Label peran untuk satu event, menangani multi-assignment.
     * Contoh: "Ketua Fosda", "Ketua Fosda (+1)", atau "Tidak ada peran".
     */
    public function roleLabelForEvent(Event $event, User $user): string
    {
        $roles = $this->roleNamesForEvent($event, $user);

        if ($roles === []) {
            return 'Tidak ada peran';
        }

        if (count($roles) === 1) {
            return $roles[0];
        }

        return $roles[0].' (+'.(count($roles) - 1).')';
    }

    public function openEvent(int $eventId)
    {
        $event = Event::active()->findOrFail($eventId);
        app(ActiveEventContext::class)->set($event);

        $this->redirect($event->dashboardRoute(), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $events = $this->events;

        // Header: role platform ditampilkan untuk Super Admin / Admin.
        // Untuk user event (non-platform), tampilkan ringkasan role assignment.
        $platformRoleLabel = $user->role && $user->isPlatformUser()
            ? $user->role->label()
            : null;

        $roleNamesByEvent = $events
            ->keyBy('id')
            ->map(fn (Event $event) => $this->roleNamesForEvent($event, $user));

        $roleLabelForEvent = fn (Event $event) => $this->roleLabelForEvent($event, $user);

        $activeRoleLabel = $platformRoleLabel
            ?? $this->summarizeActiveRoles($roleNamesByEvent->values()->all());

        $version = 'v1.0';

        return view('livewire.dashboard.platform-dashboard', [
            'events' => $events,
            'activeCount' => $events->count(),
            'userRole' => $activeRoleLabel,
            'roleNamesByEvent' => $roleNamesByEvent,
            'roleLabelForEvent' => $roleLabelForEvent,
            'version' => $version,
        ]);
    }

    /**
     * Ringkasan peran aktif lintas event untuk header.
     */
    private function summarizeActiveRoles(array $roleGroups): string
    {
        $roles = collect($roleGroups)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        if ($roles === []) {
            return 'Tidak ada peran';
        }

        if (count($roles) === 1) {
            return $roles[0];
        }

        return $roles[0].' (+'.(count($roles) - 1).')';
    }
}
