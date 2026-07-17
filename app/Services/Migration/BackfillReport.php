<?php

namespace App\Services\Migration;

use App\Models\Event;

class BackfillReport
{
    public Event $event;
    public bool $dryRun;
    public ?string $batchId;
    public int $totalPeserta = 0;
    public array $items = [];

    public function __construct(Event $event, bool $dryRun, ?string $batchId)
    {
        $this->event = $event;
        $this->dryRun = $dryRun;
        $this->batchId = $batchId;
    }

    public function addItem(BackfillReportItem $item): void
    {
        $this->items[] = $item;
    }

    public function count(string $outcome): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            if ($item->outcome === $outcome) {
                $count++;
            }
        }
        return $count;
    }

    public function conflicts(): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->outcome === 'CONFLICT'));
    }

    public function reviews(): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->outcome === 'REVIEW_REQUIRED'));
    }

    public function errors(): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->outcome === 'ERROR'));
    }

    public function brokenMappings(): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->outcome === 'BROKEN_MAPPING'));
    }

    public function drifts(): array
    {
        return array_values(array_filter($this->items, fn ($i) => $i->outcome === 'DRIFT_DETECTED'));
    }

    public function totalPeopleCreated(): int
    {
        return array_sum(array_map(fn ($i) => $i->peopleCreated, $this->items));
    }

    public function totalParticipationsCreated(): int
    {
        return array_sum(array_map(fn ($i) => $i->participationsCreated, $this->items));
    }

    public function totalMappingsCreated(): int
    {
        return array_sum(array_map(fn ($i) => $i->mappingsCreated, $this->items));
    }

    public function totalDatabaseWrites(): int
    {
        return $this->totalPeopleCreated() + $this->totalParticipationsCreated() + $this->totalMappingsCreated();
    }
}
