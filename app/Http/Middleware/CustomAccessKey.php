<?php

namespace App\Http\Middleware;

use Closure;
use Request;

class CustomAccessKey {

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if($key = $request->get('key'))
        {
            if($key === 'gg_56ed805063c59')
            {
                return $next($request);
            }
        }
        return response('', 403);
    }
}