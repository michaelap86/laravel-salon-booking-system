<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Appointment extends Model
{
    protected $fillable = [
        'reference', 'client_id', 'staff_id', 'service_id', 'start_at', 'end_at', 'status', 'price', 'deposit',
        'deposit_paid', 'promo_code', 'source', 'notes', 'cancel_reason', 'reminded_at',
    ];

    /** An iCalendar file the client can add to their phone calendar. */
    public function toIcs(): string
    {
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');

        return implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Lumiere Studio//Booking//EN', 'CALSCALE:GREGORIAN', 'BEGIN:VEVENT',
            'UID:'.$this->reference.'@lumiere-studio.example', 'DTSTAMP:'.$fmt(now()), 'DTSTART:'.$fmt($this->start_at), 'DTEND:'.$fmt($this->end_at),
            'SUMMARY:'.$this->service->name.' at Lumière Studio', 'DESCRIPTION:With '.$this->staff->name.'. Booking ref '.$this->reference.'.',
            'LOCATION:'.str_replace(',', '\,', config('salon.address')), 'END:VEVENT', 'END:VCALENDAR',
        ]);
    }

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'deposit_paid' => 'boolean',
            'reminded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            $a->reference ??= static::newReference();
        });
    }

    public static function newReference(): string
    {
        do {
            $ref = strtoupper(Str::random(8));
        } while (static::query()->where('reference', $ref)->exists());

        return $ref;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function scopeBlocking(Builder $q): Builder
    {
        return $q->whereIn('status', AppointmentStatus::blocking());
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('start_at', '>=', now())->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Confirmed->value]);
    }

    public function scopeBetween(Builder $q, $from, $to): Builder
    {
        return $q->where('start_at', '>=', $from)->where('start_at', '<', $to);
    }

    /** Free cancellation if the visit is far enough away. */
    public function cancellableFree(): bool
    {
        return $this->start_at->gt(now()->addHours(Setting::int('free_cancel_hours')));
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [AppointmentStatus::Pending, AppointmentStatus::Confirmed], true) && $this->start_at->isFuture();
    }

    public function toCalendarEvent(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->client->name.' · '.$this->service->name,
            // Wall-clock times without an offset, so the calendar shows the salon's local time to every viewer
            'start' => $this->start_at->format('Y-m-d\TH:i:s'),
            'end' => $this->end_at->format('Y-m-d\TH:i:s'),
            'color' => $this->status === AppointmentStatus::Cancelled || $this->status === AppointmentStatus::NoShow ? '#94a3b8' : $this->staff->color,
            'extendedProps' => [
                'status' => $this->status->value,
                'statusLabel' => $this->status->label(),
                'staff' => $this->staff->name,
                'staffId' => $this->staff_id,
                'client' => $this->client->name,
                'service' => $this->service->name,
                'price' => money($this->price),
                'url' => route('admin.appointments.show', $this),
                'movable' => $this->isCancellable(),
            ],
        ];
    }
}
