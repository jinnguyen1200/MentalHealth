<?php

namespace MentalHealthAI\Http\Middleware;

use Closure;
use MentalHealthAI\Role;

class CheckRoles
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$args)
    {
        $request->user()->authorizeRoles($args);
        return $next($request);
    }
}
