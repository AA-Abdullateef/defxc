<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates exactly one super_admin account (the protected, un-deletable
     * account) plus one regular admin account. Credentials come from .env
     * so nothing is hardcoded — change these before running in production.
     *
     * Run with:
     *   php artisan db:seed --class=AdminUserSeeder
     */
    public function run(): void
    {
        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();
        $adminRole      = Role::where('name', 'admin')->firstOrFail();

        $superAdmin = User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@zeltech.com')],
            [
                'name'     => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
            ]
        );
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        $this->command->info("Super Admin account ready -> {$superAdmin->email}");

        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@zeltech.com')],
            [
                'name'     => env('ADMIN_NAME', 'Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $this->command->info("Admin account ready -> {$admin->email}");
    }
}
