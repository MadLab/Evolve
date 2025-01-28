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

    public static function recordConversion(string $experimentName)
    {
        $experiment = self::where('name', $experimentName)->first();
        if(!$experiment){
            return false;
        }

        $variant = $experiment->getCookieData($experiment->id)??false;


        if ($variant) {
            $experiment->incrementConversion($variant);
        }
    }

    public function incrementConversion($variant)
    {
        $variant = $this->variantLogs()->where('hash', $variant)->first();
        if ($variant) {
            $variant->view->increment('conversions');
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
