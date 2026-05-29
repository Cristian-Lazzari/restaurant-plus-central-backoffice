<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BackofficeAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get('backoffice_authenticated')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
