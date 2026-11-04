<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SentEmail extends Model
{
    public $timestamps = false;

    protected $fillable = ['to', 'subject', 'html', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
