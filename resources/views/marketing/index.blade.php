@extends('layouts.app')

@section('content')
@php
    $packNames = [
        1 => 'Essentials',
        2 => 'Work On',
        3 => 'Boost Up',
    ];
@endphp

<style>
    .kpi-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; align-items: stretch; }
    .kpi-card { flex: 1 1 190px; display: flex; gap: 13px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow-sm); min-width: 0; }
    .kpi-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--brand-soft); color: var(--brand); }
    .kpi-icon.green { background: var(--green-soft); color: var(--green); }
    .kpi-label { color: var(--muted); font-size: 11px; font-weight: 760; text-transform: uppercase; }
    .kpi-value { display: block; font-size: 24px; font-weight: 780; line-height: 1.15; margin-top: 5px; }
    .kpi-sub { margin-top: 5px; color: var(--muted); font-size: 12px; }
    @media (max-width: 768px) { .kpi-grid { flex-direction: column; } .kpi-card { flex: none; width: 100%; } }
</style>

<div class="page-header">
    <p class="page-subtitle">Panoramica distribuzione clienti e attività</p>
    <h1 class="page-title">Marketing</h1>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Siti attivi</div>
            <span class="kpi-value">{{ $activeCount }}</span>
            <div class="kpi-sub">Clienti con abbonamento attivo</div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon green">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Media ordini 30gg</div>
            <span class="kpi-value">{{ number_format($avgOrders30, 1, ',', '.') }}</span>
            <div class="kpi-sub">Media per sito attivo</div>
        </div>
    </div>
</div>

<div class="grid-3 mb-6" style="align-items: start;">

    {{-- Distribuzione piani --}}
    <div style="grid-column: span 1;">
        <div class="section-header">
            <h2 class="section-title">Distribuzione piani</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Piano</th>
                        <th>Siti</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packDistribution as $dist)
                    <tr>
                        <td>
                            @if(isset($packNames[$dist->pack]))
                                <strong>{{ $packNames[$dist->pack] }}</strong>
                                <span class="text-muted text-sm"> — pack {{ $dist->pack }}</span>
                            @elseif($dist->pack)
                                <span class="text-muted">Pack {{ $dist->pack }} — Prova</span>
                            @else
                                <span class="text-muted">Non assegnato</span>
                            @endif
                        </td>
                        <td><strong>{{ $dist->total }}</strong></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-muted" style="text-align:center;">Nessun dato</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top 5 siti più attivi --}}
    <div style="grid-column: span 2;">
        <div class="section-header">
            <h2 class="section-title">Siti piu attivi</h2>
            <span class="text-muted text-sm">Top 5 per ordini negli ultimi 30 giorni</span>
        </div>
        @if(count($top5) === 0)
            <div class="empty-state">
                <svg width="28" height="28" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>
                </svg>
                <p>Nessun sito attivo con dati disponibili.</p>
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sito</th>
                            <th>Ordini 30gg</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($top5 as $i => $item)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td><strong>{{ $item['site_name'] }}</strong></td>
                            <td>{{ number_format($item['orders30'], 0, ',', '.') }}</td>
                            <td>
                                <a href="{{ route('sites.show', $item['site_id']) }}" class="btn" style="font-size:12px;padding:5px 10px;min-height:28px;">
                                    Dettaglio
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
