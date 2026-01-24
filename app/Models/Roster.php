<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roster extends Model
{
    protected $fillable = ['month', 'name'];

    protected $casts = [
        'month' => 'date',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function constraints(): HasMany
    {
        return $this->hasMany(Constraint::class);
    }
}
