<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = ['user_id', 'name', 'email', 'phone', 'birthday', 'notes', 'vip', 'marketing_opt_in', 'source'];

    protected function casts(): array
    {
        return ['birthday' => 'date', 'vip' => 'boolean', 'marketing_opt_in' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    /** Lifetime spend in cents (completed visits). */
    public function lifetimeSpend(): int
    {
        return (int) $this->appointments()->where('status', AppointmentStatus::Completed->value)->sum('price');
    }

    public function loyaltyPoints(): int
    {
        return intdiv($this->lifetimeSpend() / 100, max(1, Setting::int('loyalty_dollars_per_point')));
    }

    public function tier(): array
    {
        $points = $this->loyaltyPoints();
        $current = collect(config('salon.loyalty_tiers'))->last(fn ($t) => $points >= $t['min']);
        $next = collect(config('salon.loyalty_tiers'))->first(fn ($t) => $t['min'] > $points);

        return ['points' => $points, 'current' => $current, 'next' => $next];
    }
}
