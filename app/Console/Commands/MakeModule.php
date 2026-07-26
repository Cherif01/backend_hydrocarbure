<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeModule extends Command
{
    protected $signature = 'make:module {name}';
    protected $description = 'Create a new module with a standard folder structure';

    public function handle()
    {
        $name = $this->argument('name');
        $modulePath = base_path('app/Modules/'.$name);

        $folders = ['Controllers', 'Models', 'Requests', 'Services', 'Resources', 'Routes'];
        foreach ($folders as $folder) {
            File::makeDirectory("{$modulePath}/{$folder}", 0755, true, true);
        }

        // Create routes/api.php
        $routesFile = "{$modulePath}/Routes/api.php";
        if (!File::exists($routesFile)) {
            File::put($routesFile, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n// Define API routes for {$name} module here\n");
        }

        $this->info("Module {$name} created successfully with standard folders.");
    }
}