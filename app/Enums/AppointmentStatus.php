<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::NoShow => 'No-show',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Confirmed => 'sky',
            self::Completed => 'emerald',
            self::Cancelled => 'slate',
            self::NoShow => 'rose',
        };
    }

    /** Statuses that block the stylist's time. */
    public static function blocking(): array
    {
        return [self::Pending->value, self::Confirmed->value, self::Completed->value];
    }

    /** Statuses that count as revenue. */
    public static function earning(): array
    {
        return [self::Confirmed->value, self::Completed->value];
    }
}
