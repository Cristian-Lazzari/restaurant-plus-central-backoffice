@extends('layouts.app')

@section('content')
@php
    $periodLabels = ['1' => 'Oggi', '7' => '7 giorni', '30' => '30 giorni', 'all' => 'Tutto'];
    $hasCoverRevenue = in_array($period, ['30', 'all']);
    $hasData         = count($ordersRows) > 0;
    $mesiShort       = ['', 'Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
@endphp

<style>
.period-pills { display: flex; gap: 6px; flex-wrap: wrap; }
.period-pill {
    padding: 6px 16px; border: 1px solid var(--border); border-radius: 999px;
    font-size: 13px; font-weight: 600; color: var(--muted);
    text-decoration: none; background: var(--surface); transition: all .15s;
}
.period-pill:hover  { color: var(--ink); border-color: var(--brand); }
.period-pill.active { background: var(--brand); border-color: var(--brand); color: #fff; }

.kpi-grid { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
.kpi-card  { flex: 1 1 190px; display: flex; gap: 13px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow-sm); }
.kpi-icon  { width: 36px; height: 36px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--brand-soft); color: var(--brand); }
.kpi-icon.green  { background: var(--green-soft);  color: var(--green);  }
.kpi-icon.amber  { background: var(--amber-soft);  color: var(--amber);  }
.kpi-icon.purple { background: #f4f3ff; color: #6941c6; }
.kpi-label { color: var(--muted); font-size: 11px; font-weight: 760; text-transform: uppercase; }
.kpi-value { display: block; font-size: 24px; font-weight: 780; line-height: 1.15; margin-top: 5px; }
.kpi-sub   { margin-top: 5px; color: var(--muted); font-size: 12px; }

.report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 28px; }
@media (max-width: 900px) { .report-grid { grid-template-columns: 1fr; } }

.section-label { font-size: 11px; font-weight: 760; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 10px; }
.total-row td  { border-top: 2px solid var(--border); font-weight: 700; }
.month-separator td { background: var(--surface-2); border-top: 2px solid var(--border-soft); }
</style>

{{-- Header --}}
<div class="page-header" style="align-items: flex-start; margin-bottom: 20px;">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <span>Report</span>
        </nav>
        <h1 class="page-title">Report aggregato</h1>
    </div>
    <div class="period-pills" style="margin-top: 6px;">
        @foreach($periodLabels as $val => $label)
            <a href="{{ route('reports.index', ['period' => $val]) }}"
               class="period-pill {{ $period === $val ? 'active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

@if($allTimeMissing)
<div style="margin-bottom:16px;padding:10px 14px;background:var(--amber-soft);border:1px solid var(--amber-border);border-radius:var(--radius);font-size:13px;color:#92400e;">
    I dati storici totali non sono ancora disponibili — vengono mostrati i valori degli ultimi 30 giorni.
    Esegui una sincronizzazione per aggiornare i totali.
</div>
@endif

{{-- KPI cards --}}
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Ordini — {{ $periodLabels[$period] }}</div>
            <span class="kpi-value">{{ number_format($totals['orders'], 0, ',', '.') }}</span>
            <div class="kpi-sub">Totale siti attivi</div>
        </div>
    </div>

    @if($hasCoverRevenue)
    <div class="kpi-card">
        <div class="kpi-icon green">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M4 9.42h1.063C5.4 12.323 7.317 14 10.34 14c.622 0 1.167-.068 1.659-.185v-1.3c-.484.119-1.045.17-1.659.17-2.1 0-3.455-1.198-3.775-3.264h4.017v-.928H6.497v-.936c0-.11 0-.219.008-.329h4.116v-.93H6.618c.388-1.898 1.719-2.985 3.723-2.985.614 0 1.175.05 1.659.177V2.194A6.617 6.617 0 0 0 10.34 2c-2.928 0-4.813 1.569-5.244 4.3H4v.928h1.01v.936c0 .11 0 .219-.008.328H4v.928z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Revenue — {{ $periodLabels[$period] }}</div>
            @if($hasRevenue)
                <span class="kpi-value">€ {{ number_format($totals['revenue'], 2, ',', '.') }}</span>
                <div class="kpi-sub">Totale siti attivi</div>
            @else
                <span class="kpi-value" style="font-size:15px;color:var(--muted)">Non disp.</span>
                <div class="kpi-sub">Sync prossima sincronizzazione</div>
            @endif
        </div>
    </div>
    @endif

    <div class="kpi-card">
        <div class="kpi-icon amber">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zm9.954 3H2.545c-.3 0-.545.224-.545.5v1c0 .276.244.5.545.5h10.91c.3 0 .545-.224.545-.5v-1c0-.276-.244-.5-.546-.5zm-2.6 5.854-3 3a.5.5 0 0 1-.707 0l-1.5-1.5a.5.5 0 0 1 .707-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Prenotazioni — {{ $periodLabels[$period] }}</div>
            <span class="kpi-value">{{ number_format($totals['reservations'], 0, ',', '.') }}</span>
            <div class="kpi-sub">Totale siti attivi</div>
        </div>
    </div>

    @if($hasCoverRevenue)
    <div class="kpi-card">
        <div class="kpi-icon purple">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
            </svg>
        </div>
        <div>
            <div class="kpi-label">Coperti — {{ $periodLabels[$period] }}</div>
            @if($hasCovers)
                <span class="kpi-value">{{ number_format($totals['covers'], 0, ',', '.') }}</span>
                <div class="kpi-sub">Totale siti attivi</div>
            @else
                <span class="kpi-value" style="font-size:15px;color:var(--muted)">Non disp.</span>
                <div class="kpi-sub">Sync prossima sincronizzazione</div>
            @endif
        </div>
    </div>
    @endif

</div>

{{-- Due tabelle affiancate --}}
@if(! $hasData)
    <div class="empty-state">
        <p>Nessun sito attivo con dati disponibili.</p>
    </div>
@else

<div class="report-grid">

    {{-- Ordini --}}
    <div>
        <div class="section-label">Ordini per sito</div>
        <div class="card-table">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Sito</th>
                            <th style="text-align:right">Ordini</th>
                            @if($hasCoverRevenue)
                                <th style="text-align:right">Revenue</th>
                            @endif
                            <th style="text-align:right">Sync</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordersRows as $row)
                        <tr>
                            <td><strong>{{ $row['site_name'] }}</strong></td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums">
                                {{ number_format($row['orders'], 0, ',', '.') }}
                            </td>
                            @if($hasCoverRevenue)
                            <td style="text-align:right;font-variant-numeric:tabular-nums">
                                @if($row['revenue'] !== null)
                                    € {{ number_format($row['revenue'], 2, ',', '.') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endif
                            <td style="text-align:right">
                                @if($row['last_sync'])
                                    <span class="text-muted" style="font-size:12px">{{ $row['last_sync']->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('sites.show', $row['site_id']) }}" class="btn"
                                   style="font-size:12px;padding:4px 10px;min-height:26px">→</a>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Totale</td>
                            <td style="text-align:right">{{ number_format($totals['orders'], 0, ',', '.') }}</td>
                            @if($hasCoverRevenue)
                            <td style="text-align:right">
                                @if($totals['revenue'] > 0)
                                    € {{ number_format($totals['revenue'], 2, ',', '.') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endif
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Prenotazioni --}}
    <div>
        <div class="section-label">Prenotazioni per sito</div>
        <div class="card-table">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Sito</th>
                            <th style="text-align:right">Prenotaz.</th>
                            @if($hasCoverRevenue)
                                <th style="text-align:right">Coperti</th>
                            @endif
                            <th style="text-align:right">Sync</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservationRows as $row)
                        <tr>
                            <td><strong>{{ $row['site_name'] }}</strong></td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums">
                                {{ number_format($row['reservations'], 0, ',', '.') }}
                            </td>
                            @if($hasCoverRevenue)
                            <td style="text-align:right;font-variant-numeric:tabular-nums">
                                @if($row['covers'] !== null)
                                    {{ number_format($row['covers'], 0, ',', '.') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endif
                            <td style="text-align:right">
                                @if($row['last_sync'])
                                    <span class="text-muted" style="font-size:12px">{{ $row['last_sync']->diffForHumans() }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('sites.show', $row['site_id']) }}" class="btn"
                                   style="font-size:12px;padding:4px 10px;min-height:26px">→</a>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Totale</td>
                            <td style="text-align:right">{{ number_format($totals['reservations'], 0, ',', '.') }}</td>
                            @if($hasCoverRevenue)
                            <td style="text-align:right">{{ number_format($totals['covers'], 0, ',', '.') }}</td>
                            @endif
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endif

{{-- Tabella mensile --}}
<div style="margin-bottom: 32px;">
    <div class="section-label">Riepilogo mese per mese</div>

    @if(count($monthlyData) === 0)
        <div class="empty-state" style="padding: 28px 0;">
            <p>Nessuno storico mensile disponibile. I dati appariranno man mano che vengono effettuate le sincronizzazioni.</p>
        </div>
    @else
        <div class="card-table">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="white-space:nowrap">Mese</th>
                            <th>Sito</th>
                            <th style="text-align:right">Ordini</th>
                            <th style="text-align:right">Revenue</th>
                            <th style="text-align:right">Prenotaz.</th>
                            <th style="text-align:right">Coperti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyData as $month => $sitesData)
                            @php
                                [$y, $m]   = explode('-', $month);
                                $mLabel    = $mesiShort[(int) $m] . ' ' . $y;
                                $isFirst   = true;
                                $totM      = ['orders' => 0, 'revenue' => 0.0, 'reservations' => 0, 'covers' => 0];
                                foreach ($sitesData as $d) {
                                    $totM['orders']       += $d['orders'];
                                    $totM['revenue']      += $d['revenue'] ?? 0;
                                    $totM['reservations'] += $d['reservations'];
                                    $totM['covers']       += $d['covers'];
                                }
                            @endphp

                            @foreach($sitesData as $siteId => $data)
                            <tr @if($isFirst) class="month-separator" @endif>
                                <td style="white-space:nowrap;font-weight:600">
                                    @if($isFirst){{ $mLabel }}@endif
                                </td>
                                <td>{{ $siteNames[$siteId] ?? "Sito #$siteId" }}</td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums">
                                    {{ number_format($data['orders'], 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums">
                                    @if($data['revenue'] !== null)
                                        € {{ number_format($data['revenue'], 2, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums">
                                    {{ number_format($data['reservations'], 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-variant-numeric:tabular-nums">
                                    {{ number_format($data['covers'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @php $isFirst = false; @endphp
                            @endforeach

                            @if(count($sitesData) > 1)
                            <tr style="background:var(--surface-2);">
                                <td></td>
                                <td style="font-size:12px;color:var(--muted);font-weight:700">Totale {{ $mLabel }}</td>
                                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums">
                                    {{ number_format($totM['orders'], 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums">
                                    @if($totM['revenue'] > 0)
                                        € {{ number_format($totM['revenue'], 2, ',', '.') }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums">
                                    {{ number_format($totM['reservations'], 0, ',', '.') }}
                                </td>
                                <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums">
                                    {{ number_format($totM['covers'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @endif

                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@endsection
