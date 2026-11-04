<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'is_demo', 'active', 'notifications_seen_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'notifications_seen_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_demo' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->role === (is_string($role) ? Role::tryFrom($role) : $role)) {
                return true;
            }
        }

        return false;
    }

    /** Stylists only ever see their own appointments; everyone else sees all (null). */
    public function scopedStaffId(): ?int
    {
        return $this->role === Role::Stylist ? $this->staff?->id : null;
    }

    public function homeRoute(): string
    {
        return $this->isStaff() ? route('admin.dashboard') : route('account.dashboard');
    }
}
