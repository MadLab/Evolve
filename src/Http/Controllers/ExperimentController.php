<?php

namespace MadLab\Evolve\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use MadLab\Evolve\Models\ConversionLog;
use MadLab\Evolve\Models\DailyStat;
use MadLab\Evolve\Models\Evolve;
use MadLab\Evolve\Models\Variant;

class ExperimentController extends Controller
{
    public function index(): Response
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        return Inertia::render('Evolve/Index', [
            'activeExperiments' => Evolve::where('is_active', true)->with('variantLogs')->get()->toArray(),
            'inactiveExperiments' => Evolve::where('is_active', false)->with('variantLogs')->get()->toArray(),
        ]);
    }

    public function show(Evolve $experiment): Response
    {
        abort_unless(Gate::allows('viewEvolveAdminPanel'), 403);

        $variantIds = $experiment->variantLogs()->pluck('id');

        $dailyStats = DailyStat::whereIn('variant_id', $variantIds)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date')
            ->get()
            ->groupBy('variant_id')
            ->map(fn ($stats) => $stats->map(fn ($stat) => [
                'date' => $stat->date->format('Y-m-d'),
                'views' => $stat->views,
                'bot_views' => $stat->bot_views,
                'conversions' => $stat->conversions ?? [],
            ])->values()->toArray())
            ->toArray();

        $conversionLogs = ConversionLog::whereIn('variant_id', $variantIds)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'conversion_name' => $log->conversion_name,
                'loggable_type' => $log->loggable_type,
                'loggable_id' => $log->loggable_id,
                'variant_id' => $log->variant_id,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();

        return Inertia::render('Evolve/Show', [
            'experiment' => $experiment->toArray(),
            'deletedVariants' => $experiment->variantLogs()->onlyTrashed()->get()->toArray(),
            'dailyStats' => $dailyStats,
            'conversionLogs' => $conversionLogs,
        ]);
    }

    public function update(Request $request, Evolve $experiment): RedirectResponse
    {
        return match ($request->action) {
            'enable' => $this->enableExperiment($experiment),
            'disable' => $this->disableExperiment($experiment),
            'deleteVariant' => $this->deleteVariant($request, $experiment),
            'restoreVariant' => $this->restoreVariant($request, $experiment),
            'delete' => $this->deleteExperiment($experiment),
            default => redirect()->route('evolve.experiments.index'),
        };
    }

    protected function enableExperiment(Evolve $experiment): RedirectResponse
    {
        $experiment->update([
            'is_active' => true,
            'started_at' => $experiment->started_at ?? now(),
            'stopped_at' => null,
        ]);

        return redirect()->route('evolve.experiments.index');
    }

    protected function disableExperiment(Evolve $experiment): RedirectResponse
    {
        $experiment->update([
            'is_active' => false,
            'stopped_at' => now(),
        ]);

        return redirect()->route('evolve.experiments.index');
    }

    protected function deleteVariant(Request $request, Evolve $experiment): RedirectResponse
    {
        Variant::destroy($request->variant_id);

        return redirect()->route('evolve.experiments.show', $experiment);
    }

    protected function restoreVariant(Request $request, Evolve $experiment): RedirectResponse
    {
        Variant::withTrashed()->find($request->variant_id)?->restore();

        return redirect()->route('evolve.experiments.show', $experiment);
    }

    protected function deleteExperiment(Evolve $experiment): RedirectResponse
    {
        if ($experiment->is_active) {
            return redirect()->route('evolve.experiments.show', $experiment)
                ->with('error', 'Cannot delete an active experiment. Pause it first.');
        }

        $experiment->delete();

        return redirect()->route('evolve.experiments.index');
    }
}
