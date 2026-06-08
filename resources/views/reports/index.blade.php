@extends('layouts.app')

@section('content')
<style>
    .kpi-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; align-items: stretch; }
    .kpi-card { flex: 1 1 190px; display: flex; gap: 13px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow-sm); min-width: 0; }
    .kpi-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--brand-soft); color: var(--brand); }
    .kpi-icon.green { background: var(--green-soft); color: var(--green); }
    .kpi-icon.amber { background: var(--amber-soft); color: var(--amber); }
    .kpi-label { color: var(--muted); font-size: 11px; font-weight: 760; text-transform: uppercase; }
    .kpi-value { display: block; font-size: 24px; font-weight: 780; line-height: 1.15; margin-top: 5px; }
    .kpi-sub { margin-top: 5px; color: var(--muted); font-size: 12px; }
    @media (max-width: 768px) { .kpi-grid { flex-direction: column; } .kpi-card { flex: none; width: 100%; } }
</style>

<div class="page-header">
    <p class="page-subtitle">Riepilogo attività ultimi 30 giorni — siti attivi</p>
    <h1 class="page-title">Report aggregato</h1>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Ordini 30gg</div>
            <span class="kpi-value">{{ number_format($totals['orders30'], 0, ',', '.') }}</span>
            <div class="kpi-sub">Somma tutti i siti attivi</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon green">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M4 9.42h1.063C5.4 12.323 7.317 14 10.34 14c.622 0 1.167-.068 1.659-.185v-1.3c-.484.119-1.045.17-1.659.17-2.1 0-3.455-1.198-3.775-3.264h4.017v-.928H6.497v-.936c0-.11 0-.219.008-.329h4.116v-.93H6.618c.388-1.898 1.719-2.985 3.723-2.985.614 0 1.175.05 1.659.177V2.194A6.617 6.617 0 0 0 10.34 2c-2.928 0-4.813 1.569-5.244 4.3H4v.928h1.01v.936c0 .11 0 .219-.008.328H4v.928z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Revenue 30gg</div>
            <span class="kpi-value">€ {{ number_format($totals['revenue30'], 2, ',', '.') }}</span>
            <div class="kpi-sub">Somma tutti i siti attivi</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon amber">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zm9.954 3H2.545c-.3 0-.545.224-.545.5v1c0 .276.244.5.545.5h10.91c.3 0 .545-.224.545-.5v-1c0-.276-.244-.5-.546-.5zm-2.6 5.854-3 3a.5.5 0 0 1-.707 0l-1.5-1.5a.5.5 0 0 1 .707-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Prenotazioni 30gg</div>
            <span class="kpi-value">{{ number_format($totals['reservations30'], 0, ',', '.') }}</span>
            <div class="kpi-sub">Somma tutti i siti attivi</div>
        </div>
    </div>
</div>

@if(count($rows) === 0)
    <div class="empty-state">
        <svg width="32" height="32" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>
        </svg>
        <p>Nessun sito attivo con dati disponibili.</p>
    </div>
@else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Sito</th>
                    <th>Ordini 30gg</th>
                    <th>Revenue 30gg</th>
                    <th>Prenotazioni 30gg</th>
                    <th>Ultimo sync</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td><strong>{{ $row['site_name'] }}</strong></td>
                    <td>{{ number_format($row['orders30'], 0, ',', '.') }}</td>
                    <td>
                        @if($row['revenue30'] !== null)
                            € {{ number_format($row['revenue30'], 2, ',', '.') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ number_format($row['reservations30'], 0, ',', '.') }}</td>
                    <td>
                        @if($row['last_sync'])
                            <span class="text-muted">{{ $row['last_sync']->diffForHumans() }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('sites.show', $row['site_id']) }}" class="btn btn-sm" style="font-size:12px;padding:5px 10px;min-height:28px;">
                            Dettaglio
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
