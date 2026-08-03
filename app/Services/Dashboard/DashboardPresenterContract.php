<?php

namespace App\Services\Dashboard;

use App\Models\Event;

interface DashboardPresenterContract
{
    public function present(Event $event): array;

    public function view(): string;
}
