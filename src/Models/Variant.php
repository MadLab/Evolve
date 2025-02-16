<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'evolve_variants';
    protected $guarded = [];

    public function incrementView(){
        if ($this->view) {
            $this->view->increment('views');
        } else {
            $this->view()->create(['views' => 1]);
        }
    }

    public function view(){
        return $this->hasOne(View::class, 'variant_id', 'id');
    }

    public function experiment()
    {
        return $this->belongsTo(Evolve::class);
    }

    public function confidenceIsBest($conversionName)
    {
        return round($this->experiment->getConfidenceLevel($conversionName, $this->id) * 100, 2);
    }

}
