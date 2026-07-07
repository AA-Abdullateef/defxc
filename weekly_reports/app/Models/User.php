<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // -------------------------------------------------------------------------
    // Role & Permission System
    // -------------------------------------------------------------------------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Central role-check method. Everything role-related in the app goes
     * through here.
     *
     * @param  string|array  $role
     */
    public function hasRole(string|array $role): bool
    {
        $roles = (array) $role;

        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    /**
     * Whether the user has been assigned any role at all. Users created as
     * job applicants (see JobApplicationController) have no role until an
     * admin accepts their application — this is what keeps them from being
     * able to access the staff portal even if they authenticate.
     */
    public function hasAnyRole(): bool
    {
        return $this->roles->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'super_admin']);
    }

    public function isStaff(): bool
    {
        return $this->hasRole('staff');
    }

    /**
     * Central permission-check method. Super admins bypass this entirely —
     * they are never assigned permission rows, they simply pass every check.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles->flatMap(fn (Role $role) => $role->permissions)
            ->pluck('name')
            ->contains($permission);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function weeklyReports(): HasMany
    {
        return $this->hasMany(WeeklyReport::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    // -------------------------------------------------------------------------
    // Query helpers
    // -------------------------------------------------------------------------

    /**
     * Every user who should be notified for a given permission — i.e. anyone
     * whose role explicitly has it, plus every super_admin (who bypass
     * permission rows entirely but should still get notified).
     */
    public static function withPermission(string $permission)
    {
        return static::whereHas('roles', function ($query) use ($permission) {
            $query->where('name', 'super_admin')
                ->orWhereHas('permissions', fn ($q) => $q->where('name', $permission));
        })->get();
    }
}
