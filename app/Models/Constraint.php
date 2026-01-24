<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Constraint extends Model
{
    protected $fillable = ['roster_id', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];

    public function roster(): BelongsTo
    {
        return $this->belongsTo(Roster::class);
    }
}
