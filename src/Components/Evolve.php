<?php


namespace MadLab\Evolve\Components;

use Illuminate\View\Component;
use Illuminate\View\ComponentSlot;
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
            $variants = [];


            foreach($data['__laravel_slots'] as $key=>$val){
                if($key !== '__default' || $val->isNotEmpty()){
                    $variants[] = $val->toHtml();
                }
            }

            $experiment = EvolveExperiment::firstOrCreate([
                'name' => $this->test,
            ],[
                'type' => 'data',
                'variants' => $variants,
                'is_active' => true
            ]);
            /**
             * Create new test if not there (active by default).
             * Then add any new variants, and remove any ones that no longer exist
             * finally, pick a variant if test is active, else default
             *
             * Note: default should be in rotation for variant selection
             */

            return EvolveExperiment::getValue($this->test)?: $this->getDefaultVariant();
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

        return optional($slots->first(fn($slot) => optional($slot)->isNotEmpty()))->toHtml() ?? '';
    }
}