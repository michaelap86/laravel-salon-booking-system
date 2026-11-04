<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'service_category_id', 'name', 'slug', 'description', 'duration', 'buffer', 'price',
        'deposit_required', 'featured', 'active', 'sort',
    ];

    protected function casts(): array
    {
        return ['deposit_required' => 'boolean', 'featured' => 'boolean', 'active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'service_staff');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('active', true);
    }

    /** Deposit in cents for a given (possibly discounted) price. */
    public function depositFor(int $priceCents): int
    {
        if (! $this->deposit_required) {
            return 0;
        }

        return (int) round($priceCents * Setting::int('deposit_percent') / 100);
    }
}
