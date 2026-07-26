<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulesPath = app_path('Modules');
        if (File::isDirectory($modulesPath)) {
            $modules = File::directories($modulesPath);
            foreach ($modules as $module) {
                $routesPath = $module.'/Routes/api.php';
                if (File::exists($routesPath)) {
                    $moduleName = basename($module);

                    Route::prefix('api')
                        ->middleware('api')
                        ->group($routesPath);
                }
            }
        }
    }
}
