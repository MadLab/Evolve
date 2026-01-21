<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cookie;
use MadLab\Evolve\Services\BotDetector;
use MathPHP\Probability\Distribution\Continuous\Beta;

class Evolve extends Model
{
    use SoftDeletes;

    protected $table = 'evolve_experiments';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    private static $cookieData;

    private static $resolvedExperiments = [];

    protected $conversionNames;

    protected $maxConversionRates;

    protected $confidenceLevels;

    public static function getValue($name, $variants)
    {
        // Check if this experiment has already been resolved in this request
        if (isset(self::$resolvedExperiments[$name])) {
            return self::$resolvedExperiments[$name];
        }

        $experiment = self::where('name', $name)->first();
        if (! $experiment) {
            $experiment = self::create([
                'name' => $name,
                'is_active' => true,
            ]);
        }

        // Track that this experiment is still being accessed (even if paused)
        $experiment->touchLastAccessed();

        if (! $experiment->is_active) {
            self::$resolvedExperiments[$name] = null; // Cache the null result

            return null;
        }

        $experiment->syncVariants($variants);

        // Call getUserVariant to resolve the variant and cache the value
        $value = $experiment->getUserVariant()->content;
        self::$resolvedExperiments[$name] = $value;

        return $value;
    }

    public function getUserVariant()
    {
        if (! $this->is_active) {
            return $this->defaultVariant();
        }

        if (is_null($this->getCookieData($this->id))) {
            $this->updateCookie([
                $this->id => $this->variantLogs->random()->hash,
            ]);
        }

        $currentHash = $this->getCookieData($this->id);

        if (! app()->environment('production')) {
            // Find the current key's index in the collection
            $keys = $this->variantLogs->pluck('hash');
            $currentIndex = $keys->search($this->getCookieData($this->id)); // Find the index of the current key

            // Determine the next index (loop back to the start if at the end)
            $nextIndex = ($currentIndex + 1) % $keys->count();

            // Get the next key and its associated value
            $currentHash = $keys->get($nextIndex);
            $this->updateCookie([
                $this->id => $currentHash,
            ]);
        }
        $currentValue = $this->variantLogs()->where('hash', $currentHash)->get('content')->first();
        $this->incrementView($currentHash, $currentValue);

        return $currentValue;
    }

    public function syncVariants(array $strings): void
    {
        $existingHashes = $this->variantLogs()->withTrashed()->pluck('hash');

        $newVariants = collect($strings)
            ->mapWithKeys(fn ($string) => [md5($string) => $string])
            ->reject(fn ($value, $hash) => $existingHashes->contains($hash));

        foreach ($newVariants as $hash => $value) {
            $this->variantLogs()->create([
                'hash' => $hash,
                'content' => $value,
            ]);
        }
    }

    public function incrementView(string $key, string $value): void
    {
        $this->increment('total_views');

        /** @var Variant $variant */
        $variant = $this->variantLogs()->firstOrCreate(
            ['hash' => $key],
            ['content' => $value]
        );

        $isBot = app(BotDetector::class)->isBot();

        $variant->incrementView($isBot);
        DailyStat::recordView($variant, $isBot);
    }

    public static function recordConversion(string $conversionName, ?Model $model = null): void
    {
        $cookieData = self::getCookieData();

        if (empty($cookieData)) {
            return;
        }

        foreach ($cookieData as $experimentId => $variantHash) {
            $experiment = self::find($experimentId);

            if ($experiment?->is_active) {
                $experiment->incrementConversion($variantHash, $conversionName, $model);
            }
        }
    }

    public function incrementConversion(string $variantHash, string $conversionName, ?Model $model = null): void
    {
        /** @var Variant|null $variant */
        $variant = $this->variantLogs()->where('hash', $variantHash)->first();

        if (! $variant?->view) {
            return;
        }

        $currentConversions = $variant->view->conversions ?? [];
        $currentConversions[$conversionName] = ($currentConversions[$conversionName] ?? 0) + 1;

        $variant->view->update(['conversions' => $currentConversions]);

        DailyStat::recordConversion($variant, $conversionName);

        if ($model) {
            ConversionLog::create([
                'variant_id' => $variant->id,
                'conversion_name' => $conversionName,
                'loggable_type' => $model->getMorphClass(),
                'loggable_id' => $model->getKey(),
            ]);
        }
    }

    public static function removeConversion(string $conversionName, ?Model $model = null): void
    {
        $cookieData = self::getCookieData();

        if (empty($cookieData)) {
            return;
        }

        foreach ($cookieData as $experimentId => $variantHash) {
            $experiment = self::find($experimentId);

            if ($experiment?->is_active) {
                $experiment->decrementConversion($variantHash, $conversionName, $model);
            }
        }
    }

    public function decrementConversion(string $variantHash, string $conversionName, ?Model $model = null): void
    {
        if (! $model) {
            return;
        }

        /** @var Variant|null $variant */
        $variant = $this->variantLogs()->where('hash', $variantHash)->first();

        if (! $variant?->view) {
            return;
        }

        $log = ConversionLog::where('variant_id', $variant->id)
            ->where('conversion_name', $conversionName)
            ->where('loggable_type', $model->getMorphClass())
            ->where('loggable_id', $model->getKey())
            ->first();

        if (! $log) {
            return;
        }

        $currentConversions = $variant->view->conversions ?? [];
        $currentCount = $currentConversions[$conversionName] ?? 0;

        if ($currentCount > 0) {
            $currentConversions[$conversionName] = $currentCount - 1;
            $variant->view->update(['conversions' => $currentConversions]);
            DailyStat::removeConversion($variant, $conversionName);
        }

        $log->delete();
    }

    public function variantLogs(): HasMany
    {
        return $this->hasMany(Variant::class, 'experiment_id', 'id');
    }

    public static function getCookieData($key = null)
    {
        if (self::$cookieData === null) {
            self::$cookieData = json_decode(request()->cookie('evolve', '{}'), true);
        }

        if (! is_null($key)) {
            return self::$cookieData[$key] ?? null;
        }

        return self::$cookieData;
    }

    public function updateCookie(array $newData): void
    {
        self::$cookieData = array_replace(self::getCookieData(), $newData);

        Cookie::queue('evolve', json_encode(self::$cookieData));
    }

    public function conversionNames(): \Illuminate\Support\Collection
    {
        if (! isset($this->conversionNames)) {
            $this->conversionNames = $this->variantLogs
                ->flatMap(fn ($variant) => array_keys($variant->view->conversions ?? []))
                ->unique();
        }

        return $this->conversionNames;
    }

    public function maxRate(string $conversionName): ?int
    {
        if (! isset($this->maxConversionRates)) {
            foreach ($this->conversionNames() as $name) {
                $bestVariant = $this->variantLogs
                    ->filter(fn ($variant) => $variant->view)
                    ->sortByDesc(fn ($variant) => $variant->view->conversionRate($name))
                    ->first();

                $this->maxConversionRates[$name] = $bestVariant?->id;
            }
        }

        return $this->maxConversionRates[$conversionName] ?? null;
    }

    public function calculateConfidenceLevels(int $iterations = 1000): ?array
    {
        if (isset($this->confidenceLevels)) {
            return $this->confidenceLevels;
        }

        foreach ($this->conversionNames() as $conversionName) {
            $wins = [];
            $params = [];

            // Calculate Beta parameters for each variant using uniform prior: Beta(1,1)
            // α = conversions + 1, β = (views - conversions) + 1
            foreach ($this->variantLogs as $variant) {
                $wins[$variant->id] = 0;
                $conversions = $variant?->view?->conversions[$conversionName] ?? 0;
                $views = $variant->view->views ?? 0;
                $params[$variant->id] = [
                    'alpha' => $conversions + 1,
                    'beta' => ($views - $conversions) + 1,
                ];
            }

            // Run Monte Carlo simulation
            for ($i = 0; $i < $iterations; $i++) {
                $samples = [];
                foreach ($params as $variantId => $p) {
                    $samples[$variantId] = (new Beta($p['alpha'], $p['beta']))->rand();
                }
                $wins[array_search(max($samples), $samples)]++;
            }

            // Calculate win probabilities
            $this->confidenceLevels[$conversionName] = array_map(
                fn ($winCount) => $winCount / $iterations,
                $wins
            );
        }

        return $this->confidenceLevels;
    }

    public function getConfidenceLevel(string $conversionName, int $variantId): float
    {
        return $this->calculateConfidenceLevels()[$conversionName][$variantId];
    }

    public function dailyStats(): HasManyThrough
    {
        return $this->hasManyThrough(
            DailyStat::class,
            Variant::class,
            'experiment_id',
            'variant_id',
            'id',
            'id'
        );
    }

    public function toArray()
    {
        $array = parent::toArray();

        // Load variant_logs with their views if not already loaded
        if (! $this->relationLoaded('variantLogs')) {
            $this->load('variantLogs.view');
        }

        $array['variant_logs'] = $this->variantLogs->map(function ($variant) {
            $variantArray = $variant->toArray();
            $variantArray['view'] = $variant->view ? $variant->view->toArray() : null;

            // Add range calculations for each conversion
            if ($variant->view) {
                $variantArray['view']['range'] = [];
                foreach ($this->conversionNames() as $conversionName) {
                    $variantArray['view']['range'][$conversionName] = $variant->view->conversionRange($conversionName);
                }
            }

            return $variantArray;
        })->toArray();

        $array['conversion_names'] = $this->conversionNames()->values()->toArray();

        // Confidence levels are expensive to calculate (Monte Carlo simulation)
        // Only include if explicitly requested via includeConfidence()
        $array['confidence_levels'] = $this->shouldIncludeConfidence ? $this->calculateConfidenceLevels() : [];

        // Include deletion safety info
        $array['is_still_in_use'] = $this->isStillInUse();
        $array['can_be_deleted'] = $this->canBeDeleted();

        return $array;
    }

    protected bool $shouldIncludeConfidence = false;

    public function includeConfidence(): static
    {
        $this->shouldIncludeConfidence = true;

        return $this;
    }

    /**
     * Update last_accessed_at timestamp (throttled to once per minute to reduce DB writes)
     */
    public function touchLastAccessed(): void
    {
        // Only update if it's been more than a minute since last update
        if (! $this->last_accessed_at || $this->last_accessed_at->diffInMinutes(now()) >= 1) {
            $this->update(['last_accessed_at' => now()]);
        }
    }

    /**
     * Check if this experiment is still being accessed by site code
     */
    public function isStillInUse(int $withinHours = 24): bool
    {
        if (! $this->last_accessed_at) {
            return false;
        }

        return $this->last_accessed_at->diffInHours(now()) < $withinHours;
    }

    /**
     * Check if this experiment can be safely deleted
     */
    public function canBeDeleted(): bool
    {
        return ! $this->is_active && ! $this->isStillInUse();
    }
}
