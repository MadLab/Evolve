<?php

use Illuminate\Support\Facades\Route;
use MadLab\Evolve\Http\Controllers\ExperimentController;


Route::get('/experiments', [ExperimentController::class, 'index'])
    ->name('evolve.experiments.index');
Route::get('/experiments/{experiment}', [ExperimentController::class, 'show'])
    ->name('evolve.experiments.show');
