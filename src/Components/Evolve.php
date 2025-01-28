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
            ], [
                'is_active' => true
            ]);
            if($experiment->is_active){
                $experiment->syncVariants($variants);
                return $experiment->getUserVariant();
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

        return optional($slots->first(fn($slot) => optional($slot)->isNotEmpty()))->toHtml() ?? '';
    }
}