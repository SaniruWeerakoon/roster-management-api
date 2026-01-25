<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    protected $fillable = ['roster_id', 'date', 'person_id', 'shift_type_id'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function roster(): BelongsTo
    {
        return $this->belongsTo(Roster::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function shiftType(): BelongsTo
    {
        return $this->belongsTo(ShiftType::class);
    }
}
