<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending   = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Pending   => 'badge-pending',
            self::Completed => 'badge-completed',
            self::Cancelled => 'badge-cancelled',
        };
    }

    /**
     * Statuses that count as "active" — block a second pending transaction of the same type.
     */
    public static function activeStatuses(): array
    {
        return [self::Pending->value];
    }

    /**
     * Statuses that lock funds out on the debit side for withdrawals.
     * Pending and completed withdrawals both reduce available balance.
     */
    public static function withdrawalDebitStatuses(): array
    {
        return [self::Pending->value, self::Completed->value];
    }

    /**
     * Statuses that lock funds out for active trades.
     */
    public static function tradeDebitStatuses(): array
    {
        return [self::Pending->value];
    }
}