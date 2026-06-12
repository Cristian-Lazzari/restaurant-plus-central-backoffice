<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('users')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Esegui le migration per abilitare la gestione utenti.');
        }

        $users = User::with('site')
            ->orderByRaw("role = 'admin' desc")
            ->orderBy('name')
            ->get();

        $sites = Site::orderBy('name')->get(['id', 'name']);

        return view('users.index', compact('users', 'sites'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_RESTAURANT])],
            'site_id' => ['required_if:role,' . User::ROLE_RESTAURANT, 'nullable', 'integer', 'exists:sites,id'],
        ], [
            'site_id.required_if' => 'Seleziona il ristorante a cui collegare l\'account.',
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'site_id' => $data['role'] === User::ROLE_RESTAURANT ? $data['site_id'] : null,
        ]);

        return redirect()->route('users.index')->with('success', 'Utente creato.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('users.index')->with('success', 'Password aggiornata.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'Non puoi eliminare il tuo account.');
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'Non puoi eliminare l\'unico account amministratore.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utente eliminato.');
    }
}
