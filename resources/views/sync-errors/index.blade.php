@extends('layouts.app')

@section('content')

    {{-- Section: Page header --}}
    <div class="page-header">
        <nav class="breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <span>{{ __('Log errori') }}</span>
        </nav>
        <h1 class="page-title">{{ __('Errori di sincronizzazione') }}</h1>
    </div>

    {{-- Section: Filtri --}}
    <div class="panel mb-4">
        <form method="GET" action="{{ route('sync-errors.index') }}">
            <div class="actions" style="flex-wrap: wrap; gap: 14px; align-items: flex-end;">
                <div class="field" style="margin: 0; min-width: 180px; flex: 1 1 180px;">
                    <label for="filter_site">{{ __('Sito') }}</label>
                    <select id="filter_site" name="site_id">
                        <option value="">{{ __('Tutti i siti') }}</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin: 0; min-width: 160px; flex: 1 1 160px;">
                    <label for="filter_code">{{ __('Codice errore') }}</label>
                    <select id="filter_code" name="code">
                        <option value="">{{ __('Tutti i codici') }}</option>
                        @foreach(['TIMEOUT', 'HTTP_ERROR', 'INVALID_JSON', 'EXCEPTION'] as $code)
                            <option value="{{ $code }}" {{ request('code') === $code ? 'selected' : '' }}>{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions" style="gap: 8px; flex-shrink: 0;">
                    <button class="btn btn-primary" type="submit">{{ __('Applica') }}</button>
                    <a href="{{ route('sync-errors.index') }}" class="btn">{{ __('Reset') }}</a>
                </div>
            </div>
        </form>
    </div>

    {{-- Section: Tabella errori --}}
    @if($syncErrors->count() > 0)
        <div class="card-table">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Data') }}</th>
                            <th>{{ __('Sito') }}</th>
                            <th>{{ __('Codice') }}</th>
                            <th>{{ __('HTTP Status') }}</th>
                            <th>{{ __('Messaggio') }}</th>
                            <th>{{ __('Failures') }}</th>
                            <th>{{ __('Azioni') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($syncErrors as $error)
                            @php
                                $codeBadgeStyle = match($error->code ?? '') {
                                    'TIMEOUT'      => 'color: #93370d; background: #fffaeb;',
                                    'HTTP_ERROR'   => 'color: #7a2e0e; background: #fff4ed;',
                                    'INVALID_JSON' => 'color: #b42318; background: #fef3f2;',
                                    'EXCEPTION'    => 'color: #6b2737; background: #fff1f3;',
                                    default        => 'color: #475467; background: #f2f4f7;',
                                };
                            @endphp
                            <tr>
                                {{-- Data --}}
                                <td data-label="{{ __('Data') }}" style="white-space: nowrap;">
                                    {{ $error->occurred_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>

                                {{-- Sito --}}
                                <td data-label="{{ __('Sito') }}">
                                    @if($error->site)
                                        <a href="{{ route('sites.show', $error->site) }}">{{ $error->site->name }}</a>
                                    @else
                                        <span class="text-muted">{{ __('Sito rimosso') }}</span>
                                    @endif
                                </td>

                                {{-- Codice --}}
                                <td data-label="{{ __('Codice') }}">
                                    <span style="display:inline-flex; align-items:center; padding: 3px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap; {{ $codeBadgeStyle }}">
                                        {{ $error->code ?? '-' }}
                                    </span>
                                </td>

                                {{-- HTTP Status --}}
                                <td data-label="{{ __('HTTP Status') }}">{{ $error->http_status_code ?? '-' }}</td>

                                {{-- Messaggio --}}
                                <td class="td-full" data-label="{{ __('Messaggio') }}" style="word-break: break-word; overflow-wrap: anywhere;">{{ $error->message }}</td>

                                {{-- Failures al momento --}}
                                <td data-label="{{ __('Failures') }}">{{ $error->consecutive_failures }}</td>

                                {{-- Azioni --}}
                                <td class="td-actions" data-label="{{ __('Azioni') }}">
                                    @if($error->site)
                                        <a class="btn" href="{{ route('sites.show', $error->site) }}">{{ __('Dettaglio sito') }}</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="text-muted text-sm" style="margin-top: 8px; text-align: right;">
            {{ __('Visualizzati') }} {{ $syncErrors->count() }} {{ __('errori (max 100 per query).') }}
        </div>
    @else
        <div class="empty-state">
            <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </svg>
            <strong style="font-size: 16px; color: var(--ink);">{{ __('Nessun errore di sincronizzazione.') }}</strong>
            @if(request('site_id') || request('code'))
                <span>{{ __('Nessun risultato per i filtri selezionati.') }} <a href="{{ route('sync-errors.index') }}">{{ __('Rimuovi filtri') }}</a>.</span>
            @else
                <span>{{ __('Ottimo!') }}</span>
            @endif
        </div>
    @endif

@endsection
