<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    public $timestamps = false;

    protected $fillable = ['weekday', 'is_open', 'opens', 'closes'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean'];
    }

    public static function dayName(int $weekday): string
    {
        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$weekday];
    }
}
