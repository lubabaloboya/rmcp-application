<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DocumentTypeSeeder::class,
            SampleDataSeeder::class,
        ]);

        $superAdminRole = Role::where('role_name', 'Super Admin')->first();

        User::firstOrCreate(['email' => 'admin@rmcp.local'], [
            'name' => 'RMCP Super Admin',
            'password' => Hash::make('Admin@12345'),
            'role_id' => $superAdminRole?->id,
            'status' => 'active',
        ]);
    }
}
