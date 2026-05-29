@extends('layouts.app')

@section('content')

    {{-- Page header --}}
    <div style="margin-bottom: 6px;">
        <a href="{{ route('dashboard') }}" class="muted" style="font-size: 13px;">&larr; Dashboard</a>
    </div>
    <div class="actions" style="justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <h1 style="margin: 0;">Errori di sincronizzazione</h1>
    </div>

    {{-- Filtri --}}
    <form method="GET" action="{{ route('sync-errors.index') }}" style="margin-bottom: 18px;">
        <div class="actions" style="flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="field" style="margin: 0;">
                <label for="filter_site" style="font-weight: 700; margin-bottom: 4px; display: block;">Sito</label>
                <select id="filter_site" name="site_id" style="border: 1px solid var(--border); border-radius: 6px; padding: 9px 11px; font: inherit; background: #fff; min-width: 180px;">
                    <option value="">Tutti i siti</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                            {{ $site->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin: 0;">
                <label for="filter_code" style="font-weight: 700; margin-bottom: 4px; display: block;">Codice errore</label>
                <select id="filter_code" name="code" style="border: 1px solid var(--border); border-radius: 6px; padding: 9px 11px; font: inherit; background: #fff; min-width: 160px;">
                    <option value="">Tutti i codici</option>
                    @foreach(['TIMEOUT', 'HTTP_ERROR', 'INVALID_JSON', 'EXCEPTION'] as $code)
                        <option value="{{ $code }}" {{ request('code') === $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn primary" type="submit">Applica</button>
            <a href="{{ route('sync-errors.index') }}" class="btn">Reset</a>
        </div>
    </form>

    {{-- Tabella errori --}}
    @if($errors->count() > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Sito</th>
                        <th>Codice</th>
                        <th>HTTP Status</th>
                        <th>Messaggio</th>
                        <th>Failures</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($errors as $error)
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
                            <td style="white-space: nowrap;">
                                {{ $error->occurred_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>

                            {{-- Sito --}}
                            <td>
                                @if($error->site)
                                    <a href="{{ route('sites.show', $error->site) }}">{{ $error->site->name }}</a>
                                @else
                                    <span class="muted">Sito rimosso</span>
                                @endif
                            </td>

                            {{-- Codice --}}
                            <td>
                                <span style="display:inline-block; padding: 3px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; {{ $codeBadgeStyle }}">
                                    {{ $error->code ?? '-' }}
                                </span>
                            </td>

                            {{-- HTTP Status --}}
                            <td>{{ $error->http_status_code ?? '-' }}</td>

                            {{-- Messaggio --}}
                            <td>{{ $error->message }}</td>

                            {{-- Failures al momento --}}
                            <td>{{ $error->consecutive_failures }}</td>

                            {{-- Azioni --}}
                            <td>
                                @if($error->site)
                                    <a class="btn" href="{{ route('sites.show', $error->site) }}">Dettaglio sito</a>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="muted" style="font-size: 12px; margin-top: 8px; text-align: right;">
            Visualizzati {{ $errors->count() }} errori (max 100 per query).
        </div>
    @else
        <div class="panel" style="text-align: center; padding: 40px; color: var(--muted);">
            <strong style="display: block; font-size: 18px; margin-bottom: 8px; color: var(--ink);">Nessun errore di sincronizzazione.</strong>
            @if(request('site_id') || request('code'))
                Nessun risultato per i filtri selezionati. <a href="{{ route('sync-errors.index') }}">Rimuovi filtri</a>.
            @else
                Ottimo!
            @endif
        </div>
    @endif

@endsection
