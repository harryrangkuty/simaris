<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'app:install';
    protected $description = 'Generating key, Run migration, Seeding the database';

    public function handle()
    {
        // Generate key
        $this->call('key:generate');

        // Run migration
        $this->call('migrate');

        // Seed the database
        $this->call('db:seed');
    }
}
