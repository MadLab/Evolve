<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConversionLog extends Model
{
    protected $table = 'evolve_conversion_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}