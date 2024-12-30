<?php

namespace MadLab\Evolve\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ControllerVersion
{
    public function handle(Request $request, Closure $next)
    {
        $experiments = app('experiments');
        foreach($experiments as $experiment){
            if($experiment->type == 'controller' && ($experiment->trigger == $request->getRequestUri() || $experiment->trigger == $request->route()->uri())){
                $route = $request->route();

                $routeAction = array_merge($route->getAction(), [
                    'uses'       => $experiment->getUserVariant(),
                    'controller' => $experiment->getUserVariant(),
                ]);
                $route->setAction($routeAction);
                $route->controller = false;
            }
        }

        return $next($request);
    }
}

