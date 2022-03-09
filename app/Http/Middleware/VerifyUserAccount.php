<?php

namespace App\Http\Middleware;

use Bouncer;
use Closure;
use Illuminate\Http\Request;

class VerifyUserAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = request()->user();
        if(Bouncer::is($user)->an('Admin', 'Account Owner') )
        {
            return $next($request);
        }

        return response('Access Denied', 401);
    }
}
