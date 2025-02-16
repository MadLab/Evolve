<?php

namespace MadLab\Evolve\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MadLab\Evolve\Models\Evolve;
use MadLab\Evolve\Models\Variant;

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
        switch ($request->action) {
            case 'enable':
                $experiment->is_active = true;
                $experiment->save();
                break;
            case 'disable':
                $experiment->is_active = false;
                $experiment->save();
                break;
            case 'deleteVariant':
                Variant::destroy($request->variant_id);
                return redirect()->route('evolve.experiments.show', $experiment);
                break;
            case 'restoreVariant':
                $variant = Variant::withTrashed()->find($request->variant_id);
                if ($variant) {
                    $variant->restore();
                }
                return redirect()->route('evolve.experiments.show', $experiment);
                break;

        }

        return redirect()->route('evolve.experiments.index');
    }
}
