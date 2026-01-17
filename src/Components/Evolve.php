<?php

namespace MadLab\Evolve\Components;

use Illuminate\View\Component;
use MadLab\Evolve\Models\Evolve as EvolveExperiment;

class Evolve extends Component
{
    public string $test;

    public array $data;

    public function __construct(string $test)
    {
        $this->test = $test;
    }

    public function render()
    {
        return function (array $data) {
            $this->data = $data;
            $slots = collect($data['__laravel_slots']);

            // Get slot names (excluding __default) as variant names
            $variantNames = $slots->keys()->filter(fn ($key) => $key !== '__default')->values()->all();

            $experiment = EvolveExperiment::firstOrCreate([
                'name' => $this->test,
            ], [
                'is_active' => true,
            ]);

            if ($experiment->is_active && count($variantNames) > 0) {
                $experiment->syncVariants($variantNames);
                $selectedVariant = (string) $experiment->getUserVariant();

                // Return the HTML for the selected slot
                if ($slots->has($selectedVariant)) {
                    return $slots->get($selectedVariant)->toHtml();
                }
            }

            return $this->getDefaultVariant();
        };
    }

    private function getDefaultVariant()
    {
        $slots = collect($this->data['__laravel_slots']);

        if (optional($slots->get('__default'))->isNotEmpty()) {
            return $slots->get('__default')->toHtml();
        }

        if (optional($slots->get('default'))->isNotEmpty()) {
            return $slots->get('default')->toHtml();
        }

        return optional($slots->first(fn ($slot) => optional($slot)->isNotEmpty()))->toHtml() ?? '';
    }
}
