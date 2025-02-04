<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;

class Evolve extends Model
{
    use HasFactory;

    protected $table = 'evolve_experiments';
    protected $guarded = [];

    private static $cookieData;


    public $variants;


    public static function getValue($name, $variants){
        $experiment = self::where('name', $name)
            ->first();
        if(!$experiment){
            $experiment = self::create([
                'name' => $name,
                'is_active'=> true
            ]);
        }
        if(!$experiment->is_active){
            return null;
        }

        $experiment->syncVariants($variants);
        return $experiment->getUserVariant();
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
}
