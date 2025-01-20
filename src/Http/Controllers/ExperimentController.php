<?php

namespace MadLab\Evolve\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Gate;
use MadLab\Evolve\Models\Evolve;
use function PHPUnit\Framework\isNull;

class ExperimentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        $cookie = $request->cookie('evolve');
        if(isNull($cookie)){
            Cookie::queue('evolve', 'experiment', 10);
        }


        $copyExperiments = Evolve::where('type', 'data')->where('is_active', true)->get();

        return view('evolve::experiments.index', compact('copyExperiments'));
    }

    public function show(Evolve $experiment)
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        return view('evolve::experiments.show', compact('experiment'));
    }
}
