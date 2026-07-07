<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    /**
     * Simple enum-style catalog of leave types. Kept as a plain string column
     * (not a lookup table) — add/rename entries here if the list changes.
     */
    public const TYPES = [
        'annual'      => 'Annual Leave',
        'sick'        => 'Sick Leave',
        'casual'      => 'Casual Leave',
        'maternity'   => 'Maternity Leave',
        'paternity'   => 'Paternity Leave',
        'bereavement' => 'Bereavement Leave',
        'unpaid'      => 'Unpaid Leave',
        'other'       => 'Other',
    ];

    protected $fillable = [
        'user_id',
        'leave_type',
        'leave_title',
        'start_date',
        'end_date',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function daysRequested(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function leaveTypeLabel(): string
    {
        return self::TYPES[$this->leave_type] ?? 'Leave';
    }

    /**
     * leave_title is optional — fall back to a generated one from the type
     * + date range so the UI always has something to show.
     */
    public function displayTitle(): string
    {
        return $this->leave_title
            ?: "{$this->leaveTypeLabel()} — {$this->start_date->format('d M')} to {$this->end_date->format('d M')}";
    }
}