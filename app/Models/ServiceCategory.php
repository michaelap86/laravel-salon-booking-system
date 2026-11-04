<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'icon', 'theme', 'blurb', 'ladies_pricing', 'sort'];

    protected function casts(): array
    {
        return ['ladies_pricing' => 'boolean'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort');
    }
}
