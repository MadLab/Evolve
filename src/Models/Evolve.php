<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;
use MathPHP\Probability\Distribution\Continuous\Beta;

class Evolve extends Model
{
    use HasFactory;

    protected $table = 'evolve_experiments';
    protected $guarded = [];

    private static $cookieData;
    private static $resolvedExperiments = [];
    protected $conversionNames;
    protected $maxConversionRates;
    protected $confidenceLevels;



    public $variants;


    public static function getValue($name, $variants)
    {
        // Check if this experiment has already been resolved in this request
        if (isset(self::$resolvedExperiments[$name])) {
            return self::$resolvedExperiments[$name];
        }

        $experiment = self::where('name', $name)->first();
        if (!$experiment) {
            $experiment = self::create([
                'name' => $name,
                'is_active' => true,
            ]);
        }

        if (!$experiment->is_active) {
            self::$resolvedExperiments[$name] = null; // Cache the null result
            return null;
        }

        $experiment->syncVariants($variants);

        // Call getUserVariant to resolve the variant and cache the value
        $value = $experiment->getUserVariant();
        self::$resolvedExperiments[$name] = $value;

        return $value;
    }


    public function getUserVariant(){
        if(!$this->is_active){
            return $this->defaultVariant();
        }

        if(is_null($this->getCookieData($this->id))){
            $this->updateCookie([
                $this->id =>$this->variants->keys()->random()
            ]);
        }

        $currentHash = $this->getCookieData($this->id);


        if (! app()->environment('production')) {
            // Find the current key's index in the collection
            $keys = $this->variants->keys();
            $currentIndex = $keys->search($this->getCookieData($this->id)); // Find the index of the current key

            // Determine the next index (loop back to the start if at the end)
            $nextIndex = ($currentIndex + 1) % $keys->count();

            // Get the next key and its associated value
            $currentHash = $keys->get($nextIndex);
            $this->updateCookie([
                $this->id => $currentHash
            ]);
        }
        $currentValue = $this->variants->get($currentHash);
        $this->incrementView($currentHash, $currentValue);

        return $currentValue;
    }

    public function syncVariants(array $strings)
    {
        // Compute the hashes for the provided strings
        $this->variants = collect($strings)->mapWithKeys(function ($string) {
            return [md5($string) => $string];
        });
    }

    public function incrementView($key, $value)
    {
        $this->increment('total_views');
        $variant = $this->variantLogs()->where('hash', $key)->first();
        if (!$variant) {
            $variant = $this->variantLogs()->create([
                'hash' => $key,
                'content' => $value,
            ]);

        }
        $variant->incrementView();
    }

    public static function recordConversion(string $conversionName)
    {
        // Get all cookie data
        $cookieData = self::getCookieData();

        // Check if there's any cookie data
        if (!$cookieData || empty($cookieData)) {
            return false;
        }

        // Loop through the cookie data (each experiment)
        foreach ($cookieData as $experimentId => $variantHash) {
            // Find the active experiment by its ID
            $experiment = self::find($experimentId);


            // If the experiment exists and is active, increment its conversion
            if ($experiment && $experiment->is_active) {
                $experiment->incrementConversion($variantHash, $conversionName);
            }
        }
    }

    public function incrementConversion($variantHash, $conversionName)
    {
        // Find the variant by hash
        $variant = $this->variantLogs()->where('hash', $variantHash)->first();

        if ($variant) {
            // Get the current conversions data from the 'conversions' JSON field

            $currentConversions = $variant->view->conversions ?? [];

            if(is_null($currentConversions) || !is_array($currentConversions)){
                $currentConversions = [];
            }
            // Increment the specific conversion count or initialize it
            $currentConversions[$conversionName] = ($currentConversions[$conversionName] ?? 0) + 1;

            // Update the 'conversions' field in the database
            if($variant->view){
                $variant->view->update(['conversions' => $currentConversions]);
            }
        }
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

        if(!is_null($key)){
            return self::$cookieData[$key] ?? null;
        }
        return self::$cookieData;
    }

    public function updateCookie(array $newData)
    {
        self::$cookieData = array_replace(self::getCookieData(), $newData);

        Cookie::queue('evolve', json_encode(self::$cookieData));
    }

    public function conversionNames()
    {
        if(!isset($this->conversionNames)){
            $this->conversionNames = $this->variantLogs->flatMap(function ($variant) {
                return array_keys($variant->view->conversions ?? []);
            })->unique();
        }
        return $this->conversionNames;
    }

    public function maxRate($variantName){
        if(!isset($this->maxConversionRates)){
            foreach($this->conversionNames() as $conversionName) {
                $maxRate = 0;
                $maxRateVariant = null;
                foreach($this->variantLogs as $variant) {
                    $rate = $variant->view ? $variant->view->conversionRate($conversionName) : 0;
                    if ($rate > $maxRate) {
                        $maxRate = $rate;
                        $maxRateVariant = $variant->id;
                    }
                }
                $this->maxConversionRates[$conversionName] = $maxRateVariant;
            }
        }
        return $this->maxConversionRates[$variantName] ?? null;
    }



    public function calculateConfidenceLevels(int $iterations = 10000)
    {
        if(!isset($this->confidenceLevels)){
            $variantCount = count($this->variantLogs);
            $conversionNames = $this->conversionNames();

            foreach($conversionNames as $conversionName){
                // Initialize win counts for each variant.
                $wins = [];

                // Calculate Beta parameters for each variant.
                // We assume a uniform prior: Beta(1,1)
                // Thus, for each variant:
                //   α = conversions + 1
                //   β = (views - conversions) + 1
                $params = [];
                foreach ($this->variantLogs as $variant) {
                    $wins[$variant->id] = 0;

                    $conversions = $variant?->view?->conversions[$conversionName]??0;
                    $views = $variant->view->views;
                    $alpha = $conversions + 1;
                    $beta = ($views - $conversions) + 1;
                    $params[$variant->id] = ['alpha' => $alpha, 'beta' => $beta];
                }

                // Run the Monte Carlo simulation.
                for ($i = 0; $i < $iterations; $i++) {
                    $samples = [];
                    // Draw a sample conversion rate for each variant.
                    foreach ($params as $variant => $p) {
                        // Use MathPHP's Beta sampler.
                        $samples[$variant] = (new Beta($p['alpha'], $p['beta']))->rand();
                    }
                    // Find the variant with the highest sampled conversion rate.
                    $maxSample = max($samples);
                    $winningIndex = array_search($maxSample, $samples);
                    $wins[$winningIndex]++;
                }

                // Calculate win probabilities for each variant.
                $probabilities = [];
                foreach ($wins as $index => $winCount) {
                    $probabilities[$index] = $winCount / $iterations;
                }

                // Determine which variant is most likely to be best.
                $bestVariantIndex = array_keys($probabilities, max($probabilities))[0];

                $this->confidenceLevels[$conversionName] = $probabilities;
            }
        }
        return $this->confidenceLevels;

    }

    public function getConfidenceLevel($conversionName, $variantId){
        return $this->calculateConfidenceLevels()[$conversionName][$variantId];
    }
}