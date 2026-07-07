<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
        });

        // Backfill: attach every existing user to a role based on the old
        // string `role` column (dropped in the next migration), so nobody
        // loses access when this ships.
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $staffRoleId = DB::table('roles')->where('name', 'staff')->value('id');
        $now = now();

        $rows = DB::table('users')->select('id', 'role')->get()->map(function ($user) use ($adminRoleId, $staffRoleId, $now) {
            return [
                'role_id'    => $user->role === 'admin' ? $adminRoleId : $staffRoleId,
                'user_id'    => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if (! empty($rows)) {
            DB::table('role_user')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
