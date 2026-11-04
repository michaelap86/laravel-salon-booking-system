<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Stylist = 'stylist';
    case Customer = 'customer';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function blurb(): string
    {
        return match ($this) {
            self::Admin => 'Full access, including settings, users and the activity log',
            self::Manager => 'Runs the day: bookings, clients, services, staff and reports',
            self::Stylist => 'Sees their own calendar, appointments and clients',
            self::Customer => 'Books online and manages their own appointments',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'violet',
            self::Manager => 'sky',
            self::Stylist => 'rose',
            self::Customer => 'slate',
        };
    }
}
