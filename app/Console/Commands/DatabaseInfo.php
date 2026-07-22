<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseInfo extends Command
{
    protected $signature = 'db:info';

    protected $description = 'Show active database location and backup instructions';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $this->info("Active connection: {$connection}");

        if ($connection === 'sqlite') {
            $path = $config['database'];
            $this->line("Database path: {$path}");
            $this->line("");
            $this->warn("Backup command:");
            $this->line("  cp {$path} {$path}.backup.$(date +%Y%m%d_%H%M%S)");
            $this->line("");
            $this->line("Restore command:");
            $this->line("  cp {$path}.backup.<timestamp> {$path}");
        } else {
            $this->line("Host: {$config['host']}");
            $this->line("Database: {$config['database']}");
            $this->warn("Use your standard {$connection} backup procedure.");
        }

        return 0;
    }
}
