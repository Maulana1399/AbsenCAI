<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateDesaGrant extends Command
{
    protected $signature = 'pengajian:create-desa-grant
                            {--event= : ID or slug of the Event}
                            {--desa= : ID of the Desa}
                            {--valid-from= : Start of validity (Y-m-d H:i:s, defaults to now)}
                            {--valid-until= : End of validity (Y-m-d H:i:s, required if interactive)}';

    protected $description = 'Create a DesaAccessGrant and display the raw token once';

    public function handle(DesaAccessService $service): int
    {
        $event = $this->resolveEvent();
        if ($event === null) {
            return Command::FAILURE;
        }

        $desaRow = $this->resolveDesa();
        if ($desaRow === null) {
            return Command::FAILURE;
        }

        $validFrom = $this->resolveValidFrom();
        if ($validFrom === null) {
            return Command::FAILURE;
        }

        $validUntil = $this->resolveValidUntil($validFrom);
        if ($validUntil === null) {
            return Command::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>Event</>', $event->name);
        $this->components->twoColumnDetail('<fg=yellow>Desa</>', $desaRow->desa_asal);
        $this->components->twoColumnDetail('<fg=yellow>Valid From</>', $validFrom->format('Y-m-d H:i:s'));
        $this->components->twoColumnDetail('<fg=yellow>Valid Until</>', $validUntil->format('Y-m-d H:i:s'));

        if (! $this->confirm('Create this access grant?', true)) {
            $this->components->warn('Cancelled.');

            return Command::SUCCESS;
        }

        $result = $service->createGrant(
            $event,
            $desaRow,
            $validFrom,
            $validUntil,
        );

        $grant = $result['grant'];
        $rawToken = $result['raw_token'];

        $this->newLine();
        $this->components->success('DesaAccessGrant created successfully.');
        $this->line('');

        $this->components->twoColumnDetail('Grant ID', (string) $grant->id);

        $this->components->error('SAVE THIS TOKEN — IT WILL NEVER BE SHOWN AGAIN');
        $this->line('');
        $this->line('  <options=bold,reverse>'.$rawToken.'</>');
        $this->line('');
        $this->components->warn('This is the only time the raw token is displayed.');
        $this->components->warn('If lost, you must create a new grant.');
        $this->line('');
        $this->components->twoColumnDetail('Valid From', $grant->valid_from->format('Y-m-d H:i:s'));
        $this->components->twoColumnDetail('Valid Until', $grant->valid_until->format('Y-m-d H:i:s'));

        return Command::SUCCESS;
    }

    private function resolveEvent(): ?Event
    {
        $eventOption = $this->option('event');

        if ($eventOption !== null) {
            $event = is_numeric($eventOption)
                ? Event::find((int) $eventOption)
                : Event::where('slug', $eventOption)->first();

            if ($event === null) {
                $this->components->error('Event not found.');

                return null;
            }

            return $event;
        }

        $events = Event::orderBy('name')->get(['id', 'name', 'slug']);

        if ($events->isEmpty()) {
            $this->components->error('No events found. Create an event first.');

            return null;
        }

        if ($events->count() === 1) {
            $this->components->info('Using the only available event: '.$events->first()->name);

            return $events->first();
        }

        $choices = $events->map(fn (Event $e) => sprintf('[%d] %s (%s)', $e->id, $e->name, $e->slug))->values()->all();

        $selected = $this->choice('Select Event', $choices);

        preg_match('/\[(\d+)\]/', $selected, $matches);

        return Event::find((int) $matches[1]);
    }

    private function resolveDesa(): ?desa
    {
        $desaOption = $this->option('desa');

        if ($desaOption !== null) {
            $desaRow = desa::find((int) $desaOption);

            if ($desaRow === null) {
                $this->components->error('Desa not found.');

                return null;
            }

            return $desaRow;
        }

        $desas = desa::orderBy('desa_asal')->get(['id', 'desa_asal']);

        if ($desas->isEmpty()) {
            $this->components->error('No desas found. Import desa data first.');

            return null;
        }

        if ($desas->count() === 1) {
            $this->components->info('Using the only available desa: '.$desas->first()->desa_asal);

            return $desas->first();
        }

        $choices = $desas->map(fn (desa $d) => sprintf('[%d] %s', $d->id, $d->desa_asal))->values()->all();

        $selected = $this->choice('Select Desa', $choices);

        preg_match('/\[(\d+)\]/', $selected, $matches);

        return desa::find((int) $matches[1]);
    }

    private function resolveValidFrom(): ?Carbon
    {
        $value = $this->option('valid-from');

        if ($value !== null) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                $this->components->error('Invalid --valid-from format. Use Y-m-d H:i:s.');

                return null;
            }
        }

        return Carbon::now();
    }

    private function resolveValidUntil(Carbon $validFrom): ?Carbon
    {
        $value = $this->option('valid-until');

        if ($value !== null) {
            try {
                $parsed = Carbon::parse($value);

                if ($parsed->lessThanOrEqualTo($validFrom)) {
                    $this->components->error('valid-until must be after valid-from.');

                    return null;
                }

                return $parsed;
            } catch (\Throwable) {
                $this->components->error('Invalid --valid-until format. Use Y-m-d H:i:s.');

                return null;
            }
        }

        $default = (clone $validFrom)->addDay();
        $input = $this->ask('Valid until (Y-m-d H:i:s)', $default->format('Y-m-d H:i:s'));

        if ($input === null || trim($input) === '') {
            $this->components->error('valid-until is required.');

            return null;
        }

        try {
            $parsed = Carbon::parse(trim($input));

            if ($parsed->lessThanOrEqualTo($validFrom)) {
                $this->components->error('valid-until must be after valid-from.');

                return null;
            }

            return $parsed;
        } catch (\Throwable) {
            $this->components->error('Invalid date format. Use Y-m-d H:i:s.');

            return null;
        }
    }
}
