@extends('layouts.app')

@section('content')

    {{-- Section: Page header --}}
    <div class="page-header">
        <h1 class="page-title">{{ __('Utenti') }}</h1>
        <div class="page-subtitle">{{ __('Gestisci gli account di accesso al backoffice: CEO e ristoranti.') }}</div>
    </div>

    {{-- Section: Nuovo utente --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Nuovo utente') }}</h2>
    </div>

    <div class="panel mb-5">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="grid-auto" style="align-items: end;">
                <div class="field" style="margin: 0;">
                    <label for="name">{{ __('Nome') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field" style="margin: 0;">
                    <label for="username">{{ __('Username') }}</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autocomplete="off">
                </div>
                <div class="field" style="margin: 0;">
                    <label for="password">{{ __('Password') }}</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                </div>
                <div class="field" style="margin: 0;">
                    <label for="role">{{ __('Ruolo') }}</label>
                    <select id="role" name="role" required>
                        <option value="restaurant" {{ old('role', 'restaurant') === 'restaurant' ? 'selected' : '' }}>{{ __('Ristorante') }}</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>{{ __('CEO (accesso completo)') }}</option>
                    </select>
                </div>
                <div class="field" style="margin: 0;" id="siteField">
                    <label for="site_id">{{ __('Ristorante collegato') }}</label>
                    <select id="site_id" name="site_id">
                        <option value="">{{ __('— Seleziona —') }}</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ (int) old('site_id') === $site->id ? 'selected' : '' }}>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin: 0;">
                    <button class="btn btn-primary" type="submit" style="width: 100%;">{{ __('Crea utente') }}</button>
                </div>
            </div>
            <div class="text-muted text-sm mt-2">
                {{ __('Gli account "Ristorante" vedono solo la pagina dettaglio del proprio ristorante. Password: minimo 8 caratteri.') }}
            </div>
        </form>
    </div>

    {{-- Section: Elenco utenti --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Account esistenti') }} ({{ $users->count() }})</h2>
    </div>

    @if($users->isEmpty())
        <div class="empty-state">
            <span>{{ __('Nessun utente. Crea il primo account con il form qui sopra.') }}</span>
        </div>
    @else
        <div class="card-table">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Nome') }}</th>
                            <th>{{ __('Username') }}</th>
                            <th>{{ __('Ruolo') }}</th>
                            <th>{{ __('Ristorante') }}</th>
                            <th>{{ __('Creato il') }}</th>
                            <th>{{ __('Azioni') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="td-primary" data-label="{{ __('Nome') }}">
                                    <strong>{{ $user->name }}</strong>
                                    @if($user->id === auth()->id())
                                        <span class="badge" style="margin-left: 6px;">{{ __('Tu') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Username') }}">{{ $user->username }}</td>
                                <td data-label="{{ __('Ruolo') }}">
                                    @if($user->isAdmin())
                                        <span class="badge badge-green">{{ __('CEO') }}</span>
                                    @else
                                        <span class="badge badge-muted">{{ __('Ristorante') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Ristorante') }}">
                                    @if($user->site)
                                        <a href="{{ route('sites.show', $user->site) }}">{{ $user->site->name }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Creato il') }}">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
                                <td class="td-actions" data-label="{{ __('Azioni') }}">
                                    <div class="actions">
                                        <form method="POST" action="{{ route('users.update', $user) }}" style="display: inline-flex; gap: 6px;">
                                            @csrf
                                            @method('PUT')
                                            <input type="password" name="password" placeholder="{{ __('Nuova password') }}" required minlength="8" autocomplete="new-password" style="width: 150px; min-height: 36px; padding: 6px 9px;">
                                            <button class="btn" type="submit">{{ __('Reimposta') }}</button>
                                        </form>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('{{ __('Eliminare questo utente? Non potrà più accedere.') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger" type="submit">{{ __('Elimina') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
<script>
(function() {
    var role = document.getElementById('role');
    var siteField = document.getElementById('siteField');
    var siteSelect = document.getElementById('site_id');
    if (!role || !siteField || !siteSelect) return;
    function sync() {
        var isRestaurant = role.value === 'restaurant';
        siteField.style.opacity = isRestaurant ? '1' : '0.45';
        siteSelect.disabled = !isRestaurant;
        if (!isRestaurant) siteSelect.value = '';
    }
    role.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
