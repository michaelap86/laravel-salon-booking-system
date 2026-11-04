<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = ['code', 'percent', 'description', 'active', 'uses'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public static function lookup(?string $code): ?self
    {
        if (! $code) {
            return null;
        }

        return static::query()->where('active', true)->whereRaw('upper(code) = ?', [strtoupper(trim($code))])->first();
    }
}
