<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'merchant']);
        Role::create(['name' => 'driver']);
        Role::create(['name' => 'customer']);
        Role::create(['name' => 'manager']);
        Role::create(['name' => 'admin']);
    }
}
