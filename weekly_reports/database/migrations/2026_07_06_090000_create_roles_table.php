<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // super_admin | admin | staff
            $table->string('label');            // Super Admin | Admin | Staff
            $table->boolean('is_protected')->default(false); // true only for super_admin — cannot be edited/deleted
            $table->timestamps();
        });

        // Seed the three fixed roles here so subsequent migrations (attaching
        // the existing admin/staff users to them) can rely on them existing,
        // regardless of whether seeders have run yet.
        $now = now();
        DB::table('roles')->insert([
            ['name' => 'super_admin', 'label' => 'Super Admin', 'is_protected' => true,  'created_at' => $now, 'updated_at' => $now],
            ['name' => 'admin',       'label' => 'Admin',       'is_protected' => false, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'staff',       'label' => 'Staff',       'is_protected' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
