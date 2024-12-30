<?php

namespace MadLab\Evolve\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class View extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value' => 'json'
    ];
}
