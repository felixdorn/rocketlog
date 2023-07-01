<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoRegisterMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (config('app.registration_enabled') || !$request->is('register')) {
            return $next($request);
        }

        return to_route('login');
    }
}
