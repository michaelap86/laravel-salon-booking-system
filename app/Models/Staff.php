<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = ['user_id', 'name', 'title', 'bio', 'specialties', 'color', 'years', 'instagram', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_staff');
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function timeOff(): HasMany
    {
        return $this->hasMany(TimeOff::class);
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
        return $q->where('active', true)->orderBy('sort');
    }

    public function getInitialsAttribute(): string
    {
        return initials($this->name);
    }

    public function getFirstNameAttribute(): string
    {
        return explode(' ', $this->name)[0];
    }

    public function isOffOn(\DateTimeInterface $date): bool
    {
        $day = $date->format('Y-m-d');

        return $this->timeOff->contains(fn ($t) => $t->starts_on->format('Y-m-d') <= $day && $t->ends_on->format('Y-m-d') >= $day);
    }
}
