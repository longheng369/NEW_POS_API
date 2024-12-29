<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        DB::table('warehouses')->insert([
            'name' => 'Default Warehouse',
            'location' => null,
            'manager_id' => null, // Assuming no manager is assigned yet
            'contact_number' => null,
            'status' => true,
            'notes' => 'This is the default warehouse created during seeding.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make("admin123"),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'name' => 'walk-in'
        ]);

        DB::table("suppliers")->insert([
            'name' => 'Default Supplier',
            'phone_number' => '015 413 559'
        ]);

        $units = [
            ['name' => 'pcs', 'code' => 'pcs'],
            ['name' => 'box', 'code' => 'box', 'base_unit_id' => 1, 'conversion_factor' => 10]
        ];
        
        foreach ($units as $unit) {
            DB::table('units')->insert([
                'name' => $unit['name'],
                'code' => $unit['code'],
                'base_unit_id' => $unit['base_unit_id'] ?? null,
                'conversion_factor' => $unit['conversion_factor'] ?? null
            ]);
        }

        DB::table('categories')->insert([
            'name' => 'Fruits',
        ]);

        DB::table('brands')->insert([
            'name' => 'chanel'
        ]);
    }
}
