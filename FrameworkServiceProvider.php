<?php

namespace BRM\Authentication;

use Illuminate\Support\ServiceProvider;

class FrameworkServiceProvider extends ServiceProvider
{

    public function boot()
    {
        if (!class_exists('\BRM\Tenants\FrameworkServiceProvider')) {
            $this->loadMigrationsFrom(__DIR__.'/app/Database/Migrations');
        }
        $this->loadRoutesFrom(__DIR__.'/app/routes.php');
    }
}
