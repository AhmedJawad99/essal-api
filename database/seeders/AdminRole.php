<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminRole extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin',
            'password' => Hash::make('123456789'),
            'role' => 'admin',
            'phone' => '0000000000',

        ]);
        $admin->assignRole($role);
        $admin->adminProfile()->firstOrCreate([], [
            'user_id' => $admin->id,
            'job_title' => 'Admin',
            'is_super_admin' => true,
        ]);
    }
}
