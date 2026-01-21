<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use SoftDeletes;

    protected $table = 'evolve_variants';

    protected $guarded = [];

    public function incrementView(bool $isBot = false): void
    {
        if ($this->view) {
            $this->view->increment($isBot ? 'bot_views' : 'views');
        } else {
            $this->view()->create([
                'views' => $isBot ? 0 : 1,
                'bot_views' => $isBot ? 1 : 0,
            ]);
        }
    }

    public function view(): HasOne
    {
        return $this->hasOne(View::class, 'variant_id', 'id');
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Evolve::class);
    }

    public function conversionLogs(): HasMany
    {
        return $this->hasMany(ConversionLog::class);
    }

    public function confidenceIsBest(string $conversionName): float
    {
        return round($this->experiment->getConfidenceLevel($conversionName, $this->id) * 100, 2);
    }

    public function __toString(): string
    {
        return $this->content ?? '';
    }
}
