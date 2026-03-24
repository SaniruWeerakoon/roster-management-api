<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityBlock extends Model
{
    protected $fillable = [
        'person_id',
        'date_from',
        'date_to',
        'reason',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
