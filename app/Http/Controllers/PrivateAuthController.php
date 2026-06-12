<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivateAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('backoffice_authenticated')) {
            $user = Auth::user();

            if ($user instanceof User && $user->isRestaurant()) {
                return $user->site_id
                    ? redirect()->route('sites.show', $user->site_id)
                    : redirect()->route('login');
            }

            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Too many login attempts. Try again later.',
            ]);
        }

        if (! $this->attemptLogin($request, $credentials)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'username' => 'Invalid credentials.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->put('backoffice_authenticated', true);

        $user = Auth::user();

        if ($user instanceof User && $user->isRestaurant()) {
            if (! $user->site_id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'username' => 'Account non collegato a nessun ristorante. Contatta l\'amministratore.',
                ]);
            }

            return redirect()->route('sites.show', $user->site_id);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->forget('backoffice_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function attemptLogin(Request $request, array $credentials): bool
    {
        try {
            if (Schema::hasTable('users')) {
                return Auth::attempt([
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                ]);
            }
        } catch (\Throwable $e) {
            // DB non raggiungibile o tabella corrotta — prova il fallback legacy.
        }

        // Fallback legacy: migration non ancora eseguita su questo deploy.
        $expectedUsername = (string) config('backoffice.username');
        $expectedPassword = (string) config('backoffice.password');

        return $expectedPassword !== ''
            && hash_equals($expectedUsername, $credentials['username'])
            && hash_equals($expectedPassword, $credentials['password']);
    }
}
