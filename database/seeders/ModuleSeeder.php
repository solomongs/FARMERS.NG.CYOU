<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('modules', []) as $module) {
            Module::updateOrCreate(
                ['key' => $module['key']],
                [
                    'name' => $module['name'],
                    'description' => $module['description'] ?? null,
                    'version' => '1.0.0',
                    'is_core' => (bool) ($module['core'] ?? false),
                    'enabled' => true,
                    'dependencies' => [],
                ]
            );
        }
    }
}
