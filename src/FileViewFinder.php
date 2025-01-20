<?php

namespace MadLab\Evolve;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\View;
use Illuminate\View\ViewFinderInterface;
use InvalidArgumentException;
use MadLab\Evolve\Models\Evolve;

class FileViewFinder extends \Illuminate\View\FileViewFinder
{
    public function find($name)
    {
        $experiments = app('experiments');
        foreach($experiments as $experiment) {
            if($experiment->type == 'view' && ($experiment->trigger == request()->getRequestUri() || $experiment->trigger == request()->route()->uri())){
                $value = $experiment->getUserVariant()->value;
                if ($value['from'] == $name) {
                    $name = $value['to'];
                }
            }
        }
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        if ($this->hasHintInformation($name = trim($name))) {
            return $this->views[$name] = $this->findNamespacedView($name);
        }

        return $this->views[$name] = $this->findInPaths($name, $this->paths);
    }

}
