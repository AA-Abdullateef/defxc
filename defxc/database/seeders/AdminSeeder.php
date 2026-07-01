<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@defxc.com')],
            [
                'id'                => (string) Str::uuid(),
                'username'          => env('ADMIN_USERNAME', 'admin'),
                'country_id'        => 'US',
                'password'          => bcrypt(env('ADMIN_PASSWORD', 'password')),
                'admin'             => true,
                'email_verified_at' => now(),
                'profile_completed' => true,
            ]
        );

        Profile::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'first_name' => 'Platform',
                'last_name'  => 'Admin',
            ]
        );

        // Create wallet if it doesn't exist
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'id'              => (string) Str::uuid(),
                'mnemonic'        => 'test test test test test test test test test test test test',
                'mnemonic_hash'   => Hash::make('test admin wallet phrase for development only'),
                'fingerprint'     => Str::random(32),
                'public_key'      => Str::random(64),
            ]
        );

        $this->command->info("Admin user ready: {$admin->email}");
    }
}