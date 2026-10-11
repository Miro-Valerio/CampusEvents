<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    protected $fillable = ['name', 'description', 'logo_url'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
