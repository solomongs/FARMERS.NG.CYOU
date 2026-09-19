<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'dashboard' => ['view'],
            'farm' => ['view', 'edit'],
            'batch' => ['view', 'create', 'edit', 'delete'],
            'daily' => ['view', 'create', 'edit', 'delete'],
            'feed' => ['view', 'create', 'edit', 'delete'],
            'medication' => ['view', 'manage'],
            'mortality' => ['view', 'manage'],
            'egg-production' => ['view', 'manage'],
            'sales' => ['view', 'manage'],
            'expenses' => ['view', 'manage'],
            'inventory' => ['view', 'manage'],
            'reports' => ['view', 'export'],
            'team' => ['view', 'manage'],
            'settings' => ['view', 'manage'],
            'feature-requests' => ['view', 'create'],
        ];

        $permissions = collect();

        foreach ($groups as $module => $actions) {
            foreach ($actions as $action) {
                $key = $module.'.'.$action;
                $permissions->push(Permission::updateOrCreate(
                    ['key' => $key],
                    ['name' => ucwords(str_replace(['-', '.'], ' ', $key)), 'module_key' => $module]
                ));
            }
        }

        $roles = [
            'owner' => 'Owner',
            'manager' => 'Farm Manager',
            'supervisor' => 'Supervisor',
            'accountant' => 'Accountant',
            'worker' => 'Farm Worker',
            'health-officer' => 'Veterinary / Health Officer',
            'inventory-officer' => 'Inventory Officer',
            'sales-officer' => 'Sales Officer',
            'viewer' => 'Viewer',
        ];

        foreach ($roles as $key => $name) {
            $role = Role::updateOrCreate(
                ['farm_id' => null, 'key' => $key],
                ['name' => $name, 'is_system' => true]
            );

            if ($key === 'owner') {
                $role->permissions()->sync($permissions->pluck('id')->all());
            }
        }
    }
}
