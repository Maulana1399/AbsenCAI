<?php

namespace App\Livewire\Audit;

use App\Models\ActivityLog;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterModule = '';
    public string $filterAction = '';
    public ?int $expandedLogId = null;

    public function render()
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if (trim($this->search) !== '') {
            $keyword = trim($this->search);
            $query->where('description', 'like', '%' . $keyword . '%');
        }

        if ($this->filterModule !== '') {
            $query->where('module', $this->filterModule);
        }

        if ($this->filterAction !== '') {
            $query->where('action', $this->filterAction);
        }

        return view('livewire.audit.activity-log-index', [
            'logs' => $query->paginate(20),
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterModule()
    {
        $this->resetPage();
    }

    public function updatingFilterAction()
    {
        $this->resetPage();
    }

    public function toggleDetail(int $logId): void
    {
        $this->expandedLogId = $this->expandedLogId === $logId ? null : $logId;
    }

    public function getModulesProperty()
    {
        return ActivityLog::distinct()->pluck('module')->sort()->values();
    }

    public function getActionsProperty()
    {
        return ActivityLog::distinct()->pluck('action')->sort()->values();
    }
}
