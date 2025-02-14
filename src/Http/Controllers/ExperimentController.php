<?php

namespace MadLab\Evolve\Http\Controllers;

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

        $conversionNames = $experiment->variantLogs->flatMap(function ($variant) {
            return array_keys($variant->view->conversions ?? []);
        })->unique();

        $maxConversionRates = [];
        foreach($conversionNames as $conversionName) {
            $maxRate = 0;
            $maxRateVariant = null;
            foreach($experiment->variantLogs as $variant) {
                $rate = $variant->view ? $variant->view->conversionRate($conversionName) : 0;
                if ($rate > $maxRate) {
                    $maxRate = $rate;
                    $maxRateVariant = $variant->id;
                }
            }
            $maxConversionRates[$conversionName] = $maxRateVariant;
        }

        return view('evolve::experiments.show', compact('experiment', 'maxConversionRates'));
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
