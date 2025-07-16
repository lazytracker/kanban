<?php
// app/Console/Commands/RefreshPermissions.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class RefreshPermissions extends Command
{
    protected $signature = 'permissions:refresh';
    protected $description = 'Refresh all cached permissions';

    public function handle()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        $this->info('Permissions cache cleared successfully!');
        
        return 0;
    }
}