<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    use HasFactory;

    protected $table = 'evolve_views';
    protected $guarded = [];

    protected $casts = [
        'conversions' => 'json'
    ];

    public function conversionRate($conversion){
        return round(100 * (($this->conversions[$conversion]??0) / ($this->views??1)), 2);
    }
    public function conversionRange($conversion){

        if ($this->views === 0) {
            return [0, 0, 0]; // Handle cases with no views.
        }

        $z = 1.96; // Z-score for 95% confidence. Change for other confidence levels.
        $p = ($this->conversions[$conversion]??0) / ($this->views??1); // Conversion rate.
        $n = $this->views??1;

        $zSquared = $z ** 2;
        $center = $p + $zSquared / (2 * $n);
        $margin = $z * sqrt(($p * (1 - $p) / $n) + $zSquared / (4 * $n ** 2));
        $denominator = 1 + $zSquared / $n;

        $lowerBound = max(0, ($center - $margin) / $denominator);
        $upperBound = min(1, ($center + $margin) / $denominator);

        return vsprintf('%s%% - %s%%', [
            round($lowerBound * 100, 2),
            round($upperBound * 100, 2),
        ]);
    }

}
