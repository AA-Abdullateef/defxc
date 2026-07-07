<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'job_title',
        'report',
        'challenges',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reports submitted within the ISO week (Mon-Sun) that contains $date.
     */
    public function scopeForWeekOf($query, Carbon $date)
    {
        return $query->whereBetween('created_at', [
            $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            $date->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
        ]);
    }

    /**
     * Check if the authenticated user already has a report for the current week.
     */
    public static function existsForUserThisWeek(int $userId): bool
    {
        return static::where('user_id', $userId)
            ->forWeekOf(Carbon::now())
            ->exists();
    }

    /**
     * Submissions are only open from Thursday through Saturday.
     */
    public static function isSubmissionOpen(?Carbon $date = null): bool
    {
        $now = $date ?: Carbon::now();

        return in_array($now->dayOfWeek, [
            Carbon::THURSDAY,
            Carbon::FRIDAY,
            Carbon::SATURDAY,
        ], true);
    }

    /**
     * Friday from 20:00 and Saturday submissions are late.
     */
    public static function resolveStatus(): string
    {
        $now = Carbon::now();

        $afterFriday8pm = $now->dayOfWeek === Carbon::FRIDAY && $now->hour >= 20;
        $isSaturday     = $now->dayOfWeek === Carbon::SATURDAY;

        return ($afterFriday8pm || $isSaturday) ? 'late' : 'submitted';
    }
}
