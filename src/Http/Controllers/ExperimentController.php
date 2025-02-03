<?php

namespace MadLab\Evolve\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Gate;
use MadLab\Evolve\Models\Evolve;

class ExperimentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        $copyExperiments = Evolve::where('is_active', true)->get();

        return view('evolve::experiments.index', compact('copyExperiments'));
    }

    public function show(Evolve $experiment)
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        return view('evolve::experiments.show', compact('experiment'));
    }

    public function update(Request $request, Evolve $experiment)
    {
        if($request->action == 'enable') {
            $experiment->is_active = true;
            $experiment->save();
        }
        else{
            $experiment->is_active = false;
            $experiment->save();
        }
        return redirect()->route('evolve.experiments.index');
    }
}
