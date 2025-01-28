<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

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
}
