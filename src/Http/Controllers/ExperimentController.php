<?php

namespace MadLab\Evolve\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MadLab\Evolve\Models\Evolve;

class ExperimentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        $activeExperiments = Evolve::where('is_active', true)->get();
        $inactiveExperiments = Evolve::where('is_active', false)->get();

        return view('evolve::experiments.index', compact('activeExperiments', 'inactiveExperiments'));
    }

    public function show(Evolve $experiment)
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        return view('evolve::experiments.show', compact( 'experiment'));
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
