<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Farm;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_cannot_see_another_farms_batches(): void
    {
        $permission = Permission::create([
            'key' => 'batch.view',
            'name' => 'Batch View',
            'module_key' => 'batch',
        ]);

        $role = Role::create([
            'farm_id' => null,
            'name' => 'Owner',
            'key' => 'owner',
            'is_system' => true,
        ]);
        $role->permissions()->attach($permission);

        $alice = User::create([
            'name' => 'Alice Farmer',
            'email' => 'alice@example.test',
            'email_verified_at' => now(),
            'password' => 'password123',
        ]);

        $bob = User::create([
            'name' => 'Bob Farmer',
            'email' => 'bob@example.test',
            'email_verified_at' => now(),
            'password' => 'password123',
        ]);

        $farmA = Farm::create([
            'owner_id' => $alice->id,
            'name' => 'Alice Farm',
            'slug' => 'alice-farm',
        ]);

        $farmB = Farm::create([
            'owner_id' => $bob->id,
            'name' => 'Bob Farm',
            'slug' => 'bob-farm',
        ]);

        foreach ([[$farmA, $alice], [$farmB, $bob]] as [$farm, $user]) {
            DB::table('farm_user')->insert([
                'farm_id' => $farm->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'status' => 'active',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Batch::create([
            'farm_id' => $farmA->id,
            'created_by' => $alice->id,
            'batch_number' => 'ALICE-001',
            'production_type' => 'broiler',
            'date_in' => now()->toDateString(),
            'initial_birds' => 100,
            'current_birds' => 100,
            'purchase_cost' => 0,
            'status' => 'active',
        ]);

        Batch::create([
            'farm_id' => $farmB->id,
            'created_by' => $bob->id,
            'batch_number' => 'BOB-SECRET-001',
            'production_type' => 'broiler',
            'date_in' => now()->toDateString(),
            'initial_birds' => 500,
            'current_birds' => 500,
            'purchase_cost' => 0,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($alice)
            ->withSession(['current_farm_id' => $farmA->id])
            ->get('/batches');

        $response->assertOk();
        $response->assertSee('ALICE-001');
        $response->assertDontSee('BOB-SECRET-001');
    }

    public function test_farmer_cannot_switch_to_a_farm_they_do_not_belong_to(): void
    {
        $alice = User::create([
            'name' => 'Alice Farmer',
            'email' => 'alice2@example.test',
            'email_verified_at' => now(),
            'password' => 'password123',
        ]);

        $bob = User::create([
            'name' => 'Bob Farmer',
            'email' => 'bob2@example.test',
            'email_verified_at' => now(),
            'password' => 'password123',
        ]);

        $farmB = Farm::create([
            'owner_id' => $bob->id,
            'name' => 'Bob Farm',
            'slug' => 'bob-farm-2',
        ]);

        $response = $this
            ->actingAs($alice)
            ->post('/farms/switch', ['farm_id' => $farmB->id]);

        $response->assertNotFound();
    }
}
