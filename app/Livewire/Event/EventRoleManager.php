<?php

namespace App\Livewire\Event;

use App\Models\Event;
use App\Models\EventRole;
use App\Services\Activity\EventCommitteeService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\On;

class EventRoleManager extends Component
{
    public bool $processing = false;

    public ?int $eventId = null;
    public ?string $eventName = null;

    public string $newName = '';
    public string $newTemplate = '';
    public ?string $newCode = null;
    public ?string $newDescription = null;
    public bool $newIsActive = true;
    public ?int $newSortOrder = null;

    public ?int $editRoleId = null;
    public string $editName = '';
    public ?string $editCode = null;
    public ?string $editDescription = null;
    public ?array $editPermissions = null;

    /**
     * Pilihan template permission. Kunci = code (source of truth),
     * nilai = label tampilan yang mudah dipahami admin.
     */
    public function getTemplateOptionsProperty(): array
    {
        return [
            'ketua_event' => 'Ketua Event',
            'sekretariat' => 'Sekretariat',
            'operator_registrasi' => 'Operator Registrasi',
            'operator_scan' => 'Operator Scan',
            'operator_lapangan' => 'Operator Lapangan',
            'pj_divisi' => 'PJ Divisi',
            'juri' => 'Juri',
            'viewer' => 'Viewer',
            'ketua_fosda' => 'Ketua Fosda',
            'admin_event' => 'Admin Event',
        ];
    }

    #[On('manageEventRoles')]
    public function load(int $id): void
    {
        $event = Event::findOrFail($id);
        $this->eventId = $event->id;
        $this->eventName = $event->name;
        $this->resetForm();
        Flux::modal('manage-event-roles')->show();
    }

    public function create(): void
    {
        Gate::authorize('manage-events');

        if ($this->processing) return;
        $this->processing = true;

        try {
            // newCode diisi otomatis dari newTemplate via updatedNewTemplate().
            $this->validate([
                'newName' => 'required|string|max:255',
                'newTemplate' => ['required', 'string', Rule::in(array_keys($this->templateOptions))],
                'newDescription' => 'nullable|string',
                'newIsActive' => 'boolean',
                'newSortOrder' => 'nullable|integer|min:0',
            ]);

            if (blank($this->newCode)) {
                $this->newCode = $this->newTemplate;
            }

            $event = Event::findOrFail($this->eventId);

            app(EventCommitteeService::class)->createRole([
                'event_id' => $event->id,
                'name' => $this->newName,
                'code' => $this->newCode,
                'description' => $this->newDescription,
                'is_active' => $this->newIsActive,
                'sort_order' => $this->newSortOrder,
            ]);

            $this->resetForm();
            session()->flash('success', 'Role berhasil dibuat.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->addError('newName', 'Nama role sudah digunakan di event ini.');
            } else {
                throw $e;
            }
        } finally {
            $this->processing = false;
        }
    }

    /**
     * Template Permission dipilih → code otomatis mengikuti template.
     * Admin tidak pernah mengisi code secara manual.
     */
    public function updatedNewTemplate(): void
    {
        $this->newCode = $this->newTemplate !== '' ? $this->newTemplate : null;
    }

    public function edit(int $roleId): void
    {
        Gate::authorize('manage-events');

        $role = EventRole::where('id', $roleId)
            ->where('event_id', $this->eventId)
            ->firstOrFail();

        $this->editRoleId = $role->id;
        $this->editName = $role->name;
        $this->editCode = $role->code;
        $this->editDescription = $role->description;
        $this->editPermissions = $role->permissions;
        $this->resetErrorBag();

        Flux::modal('edit-event-role')->show();
    }

    public function update(): void
    {
        Gate::authorize('manage-events');

        if ($this->editRoleId === null) return;

        $this->validate([
            'editName' => 'required|string|max:255|unique:event_roles,name,' . $this->editRoleId . ',id,event_id,' . $this->eventId,
            'editDescription' => 'nullable|string',
        ]);

        // Hanya name & description yang boleh diubah. Code & permissions
        // (template) adalah identitas Permission Engine — tidak disentuh.
        $role = EventRole::where('id', $this->editRoleId)
            ->where('event_id', $this->eventId)
            ->firstOrFail();

        $role->update([
            'name' => trim($this->editName),
            'description' => $this->editDescription,
        ]);

        $this->resetEditForm();
        Flux::modal('edit-event-role')->close();
        session()->flash('success', 'Role berhasil diperbarui.');
    }

    public function delete(int $roleId): void
    {
        Gate::authorize('manage-events');

        $role = EventRole::where('id', $roleId)
            ->where('event_id', $this->eventId)
            ->first();

        if ($role === null) {
            session()->flash('error', 'Role tidak ditemukan.');
            return;
        }

        $assignmentCount = $role->committeeAssignments()->count();

        if ($assignmentCount > 0) {
            session()->flash('error', "Role masih digunakan oleh {$assignmentCount} panitia.");
            return;
        }

        $role->delete();
        session()->flash('success', 'Role berhasil dihapus.');
    }

    #[On('refreshEventRoles')]
    public function refresh(): void
    {
    }

    private function resetForm(): void
    {
        $this->reset(['newName', 'newTemplate', 'newCode', 'newDescription', 'newSortOrder']);
        $this->newIsActive = true;
        $this->resetErrorBag();
    }

    private function resetEditForm(): void
    {
        $this->reset(['editRoleId', 'editName', 'editCode', 'editDescription', 'editPermissions']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $roles = $this->eventId
            ? EventRole::where('event_id', $this->eventId)
                ->withCount('committeeAssignments')
                ->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('livewire.event.event-role-manager', [
            'roles' => $roles,
            'templateOptions' => $this->templateOptions,
        ]);
    }
}
