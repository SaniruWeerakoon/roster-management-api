<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShiftType extends Model
{
    public function rosters(): BelongsToMany
    {
        return $this->belongsToMany(Roster::class, 'roster_shift_types')
            ->withPivot(['position', 'required_per_day']);
    }
}
