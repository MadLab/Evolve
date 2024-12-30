<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;

class Experiment extends Model
{
    use HasFactory;

    protected $table = 'evolve_experiments';
    protected $guarded = [];
    protected static $userVariant;

    protected function casts(): array
    {
        return [
            'variants' => AsCollection::class,
        ];
    }

    public function getUserVariant(){
        if(!isset($this->userVariant)){
            $cookie = request()->cookie('evolve');
            $cookieData = json_decode($cookie, true);

            if(!array_key_exists($this->id, $cookieData?:[])){
                $variant = $this->variants->random();
                $cookieData[$this->id] = $variant;
                Cookie::queue('evolve', json_encode($cookieData));
                $this->incrementView($variant);
            }
            $this->userVariant = $cookieData[$this->id];

            if (! app()->environment('production')) {
                $key = $this->variants->search($this->userVariant);
                $variant = $this->variants->get($key + 1) ?? $this->variants->first();

                $cookieData[$this->id] = $variant;
                Cookie::queue('evolve', json_encode($cookieData));

                $this->userVariant = $variant;
            }

        }

        if ($this->is_active) {
            return $this->userVariant;
        }

        return $this->variants->first();
    }

    public function incrementView($variant)
    {
        $view = $this->views()->where('variant', $variant)->first();
        if ($view) {
            $view->increment('views');
        } else {
            $this->views()->create([
                'variant' => $variant,
                'views' => 1,
            ]);
        }
    }

    public static function recordConversion(string $experimentName)
    {
        $experiment = Experiment::where('name', $experimentName)->first();

        $cookie = request()->cookie('evolve');
        $cookieData = json_decode($cookie, true);

        $variant = $cookieData[$experiment->id];
        if ($experiment->variants->contains($variant)) {
            $experiment->incrementConversion($variant);
        }
    }

    public function incrementConversion($variant)
    {
        $view = $this->views()->where('variant', $variant)->first();
        if ($view) {
            $view->increment('conversions');
        } else {
            $this->views()->create([
                'variant' => $variant,
                'conversions' => 1,
            ]);
        }
    }


    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }
}
