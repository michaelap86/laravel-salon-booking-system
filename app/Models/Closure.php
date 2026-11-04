<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Closure extends Model
{
    public $timestamps = false;

    protected $fillable = ['date', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
