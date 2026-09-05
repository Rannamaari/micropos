<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // English is the default for every new session; users opt in to Dhivehi with the DV switch.
        app()->setLocale($request->session()->get('locale', 'en'));

        return $next($request);
    }
}
