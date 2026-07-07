<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLeaveController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// Root redirect
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/', fn () => redirect()->route('dashboard'));

// ─────────────────────────────────────────────────────────────────────────────
// Auth routes (login, register, logout)
// ─────────────────────────────────────────────────────────────────────────────
require __DIR__ . '/auth.php';

// ─────────────────────────────────────────────────────────────────────────────
// Shared dashboard — every authenticated user (admin or staff) lands here.
// Role-specific actions are exposed via the sidebar and protected individually
// below, rather than via separate dashboards per role.
//
// EnsureUserHasRole guards the whole group: a user with no role at all
// (e.g. a job applicant sitting in the users table pre-acceptance) never
// gets in here, even if they're authenticated.
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', EnsureUserHasRole::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::post('/', [ReportController::class, 'store'])->name('store');
    });

    Route::prefix('leave-requests')->name('leave.')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::post('/', [LeaveRequestController::class, 'store'])->name('store');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Admin — must be authenticated AND have role=admin or super_admin.
// Non-admins hitting these routes get a 403, not a redirect.
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', EnsureUserHasRole::class, AdminMiddleware::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/',                    [AdminController::class, 'index'])->name('index');
            Route::get('/{report}',            [AdminController::class, 'show'])->name('show');
            Route::patch('/{report}/accept',   [AdminController::class, 'accept'])->name('accept');
        });

        // Gated by the actual manage-leave-requests permission (not just
        // "is admin"), since that's the point of building the permission system.
        Route::middleware('permission:manage-leave-requests')
            ->prefix('leave-requests')
            ->name('leave.')
            ->group(function () {
                Route::get('/',                  [AdminLeaveController::class, 'index'])->name('index');
                Route::patch('/{leaveRequest}/approve', [AdminLeaveController::class, 'approve'])->name('approve');
                Route::patch('/{leaveRequest}/reject',  [AdminLeaveController::class, 'reject'])->name('reject');
            });
    });
