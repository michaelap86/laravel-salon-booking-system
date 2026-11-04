<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeOff extends Model
{
    protected $table = 'time_off';

    public $timestamps = false;

    protected $fillable = ['staff_id', 'starts_on', 'ends_on', 'reason'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }
}
