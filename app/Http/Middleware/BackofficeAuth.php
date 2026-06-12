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
        // Pagine con parametro {site}: dettaglio e piano marketing del proprio ristorante.
        if ($request->routeIs('sites.show', 'marketing.show', 'marketing.meta')) {
            $site = $request->route('site');
            $siteId = $site instanceof Site ? $site->id : (int) $site;

            return $siteId === (int) $user->site_id;
        }

        // Azioni AJAX sui contenuti del proprio piano marketing.
        if ($request->routeIs('marketing.items.*')) {
            $item = $request->route('item');

            if (! $item instanceof \App\Models\MarketingItem) {
                $item = \App\Models\MarketingItem::find((int) $item);
            }

            return $item !== null
                && (int) $item->plan?->site_id === (int) $user->site_id;
        }

        return false;
    }
}
