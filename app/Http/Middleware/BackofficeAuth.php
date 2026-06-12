<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BackofficeAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get('backoffice_authenticated')) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Account ristorante: può vedere solo la pagina dettaglio del proprio sito.
        if ($user instanceof User && $user->isRestaurant()) {
            if (! $user->site_id) {
                Auth::logout();
                $request->session()->forget('backoffice_authenticated');
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login');
            }

            if (! $this->isAllowedForRestaurant($request, $user)) {
                return redirect()->route('sites.show', $user->site_id);
            }
        }

        return $next($request);
    }

    private function isAllowedForRestaurant(Request $request, User $user): bool
    {
        if (! $request->routeIs('sites.show')) {
            return false;
        }

        $site = $request->route('site');
        $siteId = $site instanceof Site ? $site->id : (int) $site;

        return $siteId === (int) $user->site_id;
    }
}
