@extends('layouts.app')

@section('content')
@php
    $orderCommissionPercent = old('order_commission_percent', $benchmark['order_commission_percent'] ?? 20);
    $reservationCoverFee    = old('reservation_cover_fee',    $benchmark['reservation_cover_fee']    ?? 4);
    $activeTab = request('tab', 'impostazioni');
@endphp

<style>
.settings-tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--border-soft); }
.settings-tab {
    padding: 10px 18px; border: none; background: transparent;
    color: var(--muted); font-size: 13px; font-weight: 600;
    border-bottom: 2px solid transparent; white-space: nowrap;
    margin-bottom: -1px; text-decoration: none; display: inline-block;
    transition: color .15s;
}
.settings-tab:hover { color: var(--ink); }
.settings-tab.active { color: var(--brand); border-bottom-color: var(--brand); }
</style>

{{-- Header --}}
<div class="page-header">
    <nav class="breadcrumb" aria-label="{{ __('Breadcrumb') }}">
        <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
        <span class="breadcrumb-sep" aria-hidden="true">›</span>
        <span>{{ __('Impostazioni') }}</span>
    </nav>
    <h1 class="page-title">{{ __('Impostazioni') }}</h1>
</div>

{{-- Tab nav --}}
<div class="settings-tabs">
    <a href="{{ route('backoffice-settings.edit') }}"
       class="settings-tab {{ $activeTab === 'impostazioni' ? 'active' : '' }}">
        ⚙ {{ __('Benchmark') }}
    </a>
    <a href="{{ route('backoffice-settings.edit', ['tab' => 'errori']) }}"
       class="settings-tab {{ $activeTab === 'errori' ? 'active' : '' }}">
        ⚠ {{ __('Log errori') }}
        @if($syncErrors->count() > 0)
            <span style="margin-left:5px;background:var(--red);color:#fff;font-size:10px;padding:1px 6px;border-radius:999px">{{ $syncErrors->count() }}</span>
        @endif
    </a>
</div>

{{-- ── TAB: BENCHMARK ── --}}
@if($activeTab === 'impostazioni')

    @if(! $settingsTableExists)
        <div class="panel mb-4" style="border-color: var(--amber-border); background: var(--amber-soft); color: #93370d;">
            {{ __('Esegui le migration per salvare le impostazioni benchmark. Nel frattempo vengono usati i valori default.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice-settings.update') }}">
        @csrf
        <div class="panel">
            <h2 class="section-title" style="margin-bottom: 16px;">{{ __('Risparmio stimato') }}</h2>

            <div class="grid-auto" style="margin-bottom: 16px;">
                <div class="field" style="margin: 0;">
                    <label for="order_commission_percent">{{ __('Commissione ordini marketplace (%)') }}</label>
                    <input id="order_commission_percent" name="order_commission_percent"
                        type="number" min="0" max="100" step="0.01"
                        value="{{ $orderCommissionPercent }}"
                        @disabled(! $settingsTableExists)>
                    <div class="text-muted text-sm" style="margin-top: 6px;">{{ __('Just Eat / Deliveroo / Glovo.') }}</div>
                </div>
                <div class="field" style="margin: 0;">
                    <label for="reservation_cover_fee">{{ __('Costo prenotazione per coperto (€)') }}</label>
                    <input id="reservation_cover_fee" name="reservation_cover_fee"
                        type="number" min="0" max="1000" step="0.01"
                        value="{{ $reservationCoverFee }}"
                        @disabled(! $settingsTableExists)>
                    <div class="text-muted text-sm" style="margin-top: 6px;">{{ __('Benchmark TheFork.') }}</div>
                </div>
            </div>

            <div class="panel text-muted mb-4" style="font-size: 13px; background: var(--surface-2);">
                <strong>{{ __('Formula:') }}</strong>
                {{ __('ricavi ordini') }} &times; {{ number_format((float) $orderCommissionPercent, 2, ',', '.') }}%
                + {{ __('coperti prenotati') }} &times; € {{ number_format((float) $reservationCoverFee, 2, ',', '.') }}.
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" @disabled(! $settingsTableExists)>{{ __('Salva benchmark') }}</button>
                <a class="btn" href="{{ route('dashboard') }}">{{ __('Annulla') }}</a>
            </div>
        </div>
    </form>

@endif

{{-- ── TAB: LOG ERRORI ── --}}
@if($activeTab === 'errori')

    <div class="panel mb-4">
        <form method="GET" action="{{ route('backoffice-settings.edit') }}">
            <input type="hidden" name="tab" value="errori">
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
                    <a href="{{ route('backoffice-settings.edit', ['tab' => 'errori']) }}" class="btn">{{ __('Reset') }}</a>
                </div>
            </div>
        </form>
    </div>

    @if($syncErrors->count() > 0)
        <div class="card-table">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Data') }}</th>
                            <th>{{ __('Sito') }}</th>
                            <th>{{ __('Codice') }}</th>
                            <th>{{ __('HTTP') }}</th>
                            <th>{{ __('Messaggio') }}</th>
                            <th>{{ __('Failures') }}</th>
                            <th>{{ __('Azioni') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($syncErrors as $error)
                            @php
                                $codeBadgeStyle = match($error->code ?? '') {
                                    'TIMEOUT'      => 'color:#93370d;background:#fffaeb;',
                                    'HTTP_ERROR'   => 'color:#7a2e0e;background:#fff4ed;',
                                    'INVALID_JSON' => 'color:#b42318;background:#fef3f2;',
                                    'EXCEPTION'    => 'color:#6b2737;background:#fff1f3;',
                                    default        => 'color:#475467;background:#f2f4f7;',
                                };
                            @endphp
                            <tr>
                                <td data-label="{{ __('Data') }}" style="white-space:nowrap">
                                    {{ $error->occurred_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td data-label="{{ __('Sito') }}">
                                    @if($error->site)
                                        <a href="{{ route('sites.show', $error->site) }}">{{ $error->site->name }}</a>
                                    @else
                                        <span class="text-muted">{{ __('Sito rimosso') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Codice') }}">
                                    <span style="display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap;{{ $codeBadgeStyle }}">
                                        {{ $error->code ?? '-' }}
                                    </span>
                                </td>
                                <td data-label="{{ __('HTTP') }}">{{ $error->http_status_code ?? '-' }}</td>
                                <td class="td-full" data-label="{{ __('Messaggio') }}" style="word-break:break-word;overflow-wrap:anywhere">{{ $error->message }}</td>
                                <td data-label="{{ __('Failures') }}">{{ $error->consecutive_failures }}</td>
                                <td class="td-actions" data-label="{{ __('Azioni') }}">
                                    @if($error->site)
                                        <a class="btn" href="{{ route('sites.show', $error->site) }}">{{ __('Dettaglio') }}</a>
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
        <div class="text-muted text-sm" style="margin-top:8px;text-align:right">
            {{ $syncErrors->count() }} {{ __('errori (max 100 per query)') }}
        </div>
    @else
        <div class="empty-state">
            <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </svg>
            <strong style="font-size:16px;color:var(--ink)">{{ __('Nessun errore di sincronizzazione.') }}</strong>
            @if(request('site_id') || request('code'))
                <span>{{ __('Nessun risultato per i filtri selezionati.') }}
                    <a href="{{ route('backoffice-settings.edit', ['tab' => 'errori']) }}">{{ __('Rimuovi filtri') }}</a>.
                </span>
            @else
                <span>{{ __('Ottimo!') }}</span>
            @endif
        </div>
    @endif

@endif

@endsection
