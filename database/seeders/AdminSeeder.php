<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        if (! $adminRole) {
            $this->command->error('❌ Admin role not found. Run RoleSeeder first!');
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'devnetmaxims@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
            ]
        );

        // Assign role
        $admin->assignRole($adminRole);

        $this->command->info('✅ Admin user created with admin role.');
    }
}
