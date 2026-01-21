<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStat extends Model
{
    protected $table = 'evolve_daily_stats';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'conversions' => 'json',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public static function recordView(Variant $variant, bool $isBot = false): void
    {
        $stat = static::getOrCreateTodaysStat($variant);
        $stat->increment($isBot ? 'bot_views' : 'views');
    }

    public static function recordConversion(Variant $variant, string $conversionName): void
    {
        $stat = static::getOrCreateTodaysStat($variant);

        $conversions = $stat->conversions ?? [];
        $conversions[$conversionName] = ($conversions[$conversionName] ?? 0) + 1;
        $stat->update(['conversions' => $conversions]);
    }

    public static function removeConversion(Variant $variant, string $conversionName): void
    {
        $stat = static::getOrCreateTodaysStat($variant);

        $conversions = $stat->conversions ?? [];
        $currentCount = $conversions[$conversionName] ?? 0;

        if ($currentCount > 0) {
            $conversions[$conversionName] = $currentCount - 1;
            $stat->update(['conversions' => $conversions]);
        }
    }

    protected static function getOrCreateTodaysStat(Variant $variant): self
    {
        return static::firstOrCreate(
            [
                'variant_id' => $variant->id,
                'date' => now()->toDateString(),
            ],
            [
                'views' => 0,
                'bot_views' => 0,
                'conversions' => [],
            ]
        );
    }
}
