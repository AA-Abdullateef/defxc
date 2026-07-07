<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * The full permission catalog. Add new permissions here as features grow —
     * they'll show up automatically on the super_admin's Permissions screen.
     */
    protected array $permissions = [
        ['name' => 'view-all-reports',        'label' => 'View All Reports',        'group' => 'Reports'],
        ['name' => 'manage-reports',          'label' => 'Complete Reports',        'group' => 'Reports'],
        ['name' => 'manage-leave-requests',   'label' => 'Manage Leave Requests',   'group' => 'Leave'],
        ['name' => 'manage-job-postings',     'label' => 'Manage Job Postings',     'group' => 'Jobs'],
        ['name' => 'manage-job-applications', 'label' => 'Manage Job Applications', 'group' => 'Jobs'],
        ['name' => 'manage-users',            'label' => 'Manage Users & Roles',    'group' => 'Users'],
        ['name' => 'manage-permissions',      'label' => 'Manage Role Permissions', 'group' => 'Users'],
    ];

    /**
     * Permissions granted to the `admin` role by default. `staff` starts
     * with none, and `super_admin` bypasses permission checks entirely
     * (see User::hasPermission()) so it never needs explicit rows here.
     */
    protected array $defaultAdminPermissions = [
        'view-all-reports',
        'manage-reports',
        'manage-leave-requests',
        'manage-job-postings',
        'manage-job-applications',
        'manage-users',
    ];

    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $ids = Permission::whereIn('name', $this->defaultAdminPermissions)->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($ids);
        }
    }
}
