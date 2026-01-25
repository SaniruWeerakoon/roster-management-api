<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function shiftTypes(): BelongsToMany
    {
        return $this->belongsToMany(ShiftType::class, 'roster_shift_types')
            ->withPivot(['position', 'required_per_day'])
            ->orderBy('roster_shift_types.position');
    }
}
