<?php

use Illuminate\Support\Facades\Route;
use MadLab\Evolve\Http\Controllers\ExperimentController;


Route::get('/', [ExperimentController::class, 'index'])
    ->name('evolve.experiments.index');
Route::get('/{experiment}', [ExperimentController::class, 'show'])
    ->name('evolve.experiments.show');
Route::post('/{experiment}', [ExperimentController::class, 'update'])
    ->name('evolve.experiments.update');
