<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    public $timestamps = false;

    protected $fillable = ['staff_id', 'weekday', 'is_working', 'starts', 'ends', 'break_starts', 'break_ends'];

    protected function casts(): array
    {
        return ['is_working' => 'boolean'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
