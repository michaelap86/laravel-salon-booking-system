<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** All settings merged over the defaults in config/salon.php. */
    public static function all_(): array
    {
        return Cache::remember('settings', 300, function () {
            return array_merge(config('salon.defaults'), static::query()->pluck('value', 'key')->all());
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_()[$key] ?? $default;
    }

    public static function int(string $key): int
    {
        return (int) static::get($key, 0);
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('settings');
    }
}
