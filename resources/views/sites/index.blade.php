@extends('layouts.app')

@section('content')
    @php
        $canReorderSites = $canReorderSites ?? false;
        $savingsBenchmark = $savingsBenchmark ?? [
            'order_commission_percent' => 20,
            'reservation_cover_fee' => 4,
        ];
        $formatBenchmarkNumber = function (float $value): string {
            $decimals = abs($value - round($value)) < 0.005 ? 0 : 2;

            return number_format($value, $decimals, ',', '.');
        };
        $benchmarkOrderPercent = $formatBenchmarkNumber((float) $savingsBenchmark['order_commission_percent']);
        $benchmarkCoverFee = $formatBenchmarkNumber((float) $savingsBenchmark['reservation_cover_fee']);
        $iconSvg = function (string $name): \Illuminate\Support\HtmlString {
            $icons = [
                'activity' => '<path d="M3 12h4l3-8 4 16 3-8h4"></path>',
                'alert' => '<path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.3 3.9 2.4 17.5A2 2 0 0 0 4.1 20h15.8a2 2 0 0 0 1.7-2.5L13.7 3.9a2 2 0 0 0-3.4 0Z"></path>',
                'arrow-down' => '<path d="M12 5v14"></path><path d="m19 12-7 7-7-7"></path>',
                'arrow-up' => '<path d="M12 19V5"></path><path d="m5 12 7-7 7 7"></path>',
                'chart' => '<path d="M3 3v18h18"></path><path d="M8 17V9"></path><path d="M13 17V5"></path><path d="M18 17v-6"></path>',
                'check' => '<path d="m20 6-11 11-5-5"></path>',
                'cover' => '<path d="M5 3v18"></path><path d="M19 3v18"></path><path d="M9 8h6"></path><path d="M9 12h6"></path><path d="M9 16h6"></path>',
                'external' => '<path d="M14 3h7v7"></path><path d="M10 14 21 3"></path><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"></path>',
                'list' => '<path d="M8 6h13"></path><path d="M8 12h13"></path><path d="M8 18h13"></path><path d="M3 6h.01"></path><path d="M3 12h.01"></path><path d="M3 18h.01"></path>',
                'plus' => '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
                'reservation' => '<path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M3 10h18"></path><path d="M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path>',
                'revenue' => '<path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path>',
                'savings' => '<path d="M19 5c-1.5-1.8-4.1-2-6-1-2.5 1.4-4 4.1-4 7.3V21h8v-5h2a3 3 0 0 0 3-3v-1h-3"></path><path d="M8 10H4a2 2 0 0 0 0 4h4"></path><path d="M13 8h.01"></path>',
                'sync' => '<path d="M21 12a9 9 0 0 0-15.2-6.4L3 8"></path><path d="M3 3v5h5"></path><path d="M3 12a9 9 0 0 0 15.2 6.4L21 16"></path><path d="M16 16h5v5"></path>',
            ];

            $path = $icons[$name] ?? $icons['activity'];

            return new \Illuminate\Support\HtmlString('<svg class="icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>');
        };
    @endphp

    <style>
        .icon { width: 16px; height: 16px; flex-shrink: 0; }
        .dashboard-hero { display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 18px; align-items: end; margin-bottom: 24px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .hero-copy { margin-top: 8px; color: var(--muted); max-width: 680px; line-height: 1.5; font-size: 13.5px; }
        .hero-actions { justify-content: flex-end; }
        .kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 20px; }
        .kpi-card { display: flex; gap: 13px; align-items: flex-start; background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 16px; box-shadow: var(--shadow-sm); }
        .kpi-card.featured { border-color: #fedf89; background: #fffdf5; }
        .kpi-icon { width: 36px; height: 36px; border-radius: var(--radius-sm); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--brand-soft); color: var(--brand); }
        .kpi-icon.green { background: var(--green-soft); color: var(--green); }
        .kpi-icon.amber { background: var(--amber-soft); color: var(--amber); }
        .kpi-icon.red { background: var(--red-soft); color: var(--red); }
        .kpi-label { color: var(--muted); font-size: 11px; font-weight: 760; text-transform: uppercase; }
        .kpi-value { display: block; font-size: 24px; font-weight: 780; line-height: 1.15; margin-top: 5px; }
        .kpi-sub { margin-top: 5px; color: var(--muted); font-size: 12px; }
        .sync-chip { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border: 1px solid var(--border-soft); border-radius: var(--radius-sm); background: var(--surface); color: var(--muted); font-size: 12px; box-shadow: var(--shadow-sm); }
        .info-strip { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-soft); border-radius: var(--radius); background: var(--surface); box-shadow: var(--shadow-sm); color: var(--muted); font-size: 13px; margin-bottom: 20px; }
        .site-name { font-weight: 700; font-size: 13px; }
        .site-url-text { display: block; font-size: 11px; color: var(--muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px; }
        .metric-main { display: block; font-weight: 760; }
        .metric-note { color: var(--muted); font-size: 11px; margin-top: 2px; }
        .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 760; }
        .chart-box { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 18px; box-shadow: var(--shadow-sm); height: 320px; margin-bottom: 20px; }
        .chart-box canvas { width: 100% !important; height: 100% !important; }
        .order-handle { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: 11px; font-weight: 700; }
        .footer-note { text-align: right; font-size: 12px; margin-top: 8px; }
        .compact-table { min-width: 0; }
        .dashboard-table th { white-space: nowrap; }
        .site-order-controls { gap: 4px; flex-wrap: nowrap; }
        @media (max-width: 980px) { .kpi-grid { grid-template-columns: repeat(2,1fr); } .dashboard-hero { grid-template-columns: 1fr; } .hero-actions { justify-content: flex-start; } }
        @media (max-width: 768px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .dashboard-hero { gap: 14px; margin-bottom: 18px; }
            .hero-actions { width: 100%; flex-direction: column; }
            .hero-actions .btn,
            .hero-actions form,
            .hero-actions form .btn,
            .hero-actions .sync-chip { width: 100%; box-sizing: border-box; }
            .sync-chip { white-space: normal; }
            .chart-box { height: 240px; padding: 12px; }
            .site-url-text { max-width: 100%; white-space: normal; word-break: break-word; }
            .footer-note { text-align: left; }
            .info-strip { flex-direction: column; align-items: flex-start; }
        }
    </style>

    {{-- Section: Dashboard hero --}}
    <section class="dashboard-hero">
        <div>
            <div class="eyebrow">
                {!! $iconSvg('activity') !!}
                {{ __('Monitoraggio centrale') }}
            </div>
            <h1 class="page-title">{{ __('Dashboard') }}</h1>
            <div class="hero-copy">
                {{ __('Controllo rapido dei siti collegati, dati business e stato delle sincronizzazioni.') }}
            </div>
        </div>
        <div class="actions hero-actions">
            <span class="sync-chip">
                {!! $iconSvg('sync') !!}
                {{ __('Ultimo sync:') }}
                <strong style="color: var(--ink);">{{ $lastGlobalSyncAt ? $lastGlobalSyncAt->format('d/m/Y H:i') : 'Mai' }}</strong>
            </span>
            <form method="POST" action="{{ route('sync.all') }}">
                @csrf
                <button class="btn" type="submit">{!! $iconSvg('sync') !!}{{ __('Sync tutti') }}</button>
            </form>
            <a class="btn btn-primary" href="{{ route('sites.create') }}">{!! $iconSvg('plus') !!}{{ __('Nuovo sito') }}</a>
        </div>
    </section>

    {{-- Section: KPI globali --}}
    @if(! $kpis['has_v2_data'])
        <div class="empty-state" style="margin-bottom: 20px;">
            {!! $iconSvg('chart') !!}
            <span>{{ __('Nessun dato aggregato disponibile ancora. Esegui una sync per raccogliere i dati.') }}</span>
        </div>
    @else
        <section class="kpi-grid" aria-label="{{ __('KPI globali') }}">
            @if(($kpis['uses_orders'] || $kpis['uses_reservations']) && ($kpis['estimated_total_savings'] ?? 0) > 0)
                <article class="kpi-card featured">
                    <span class="kpi-icon amber">{!! $iconSvg('savings') !!}</span>
                    <div>
                        <span class="kpi-label">{{ __('Risparmio stimato Future Plus') }}</span>
                        <strong class="kpi-value">€ {{ number_format($kpis['estimated_total_savings'], 2) }}</strong>
                        <div class="kpi-sub">{{ __('Benchmark: Just Eat/Deliveroo/Glovo') }} {{ $benchmarkOrderPercent }}%, TheFork € {{ $benchmarkCoverFee }}/{{ __('coperto') }}.</div>
                    </div>
                </article>
            @endif

            @if($kpis['uses_orders'])
                <article class="kpi-card">
                    <span class="kpi-icon">{!! $iconSvg('list') !!}</span>
                    <div>
                        <span class="kpi-label">{{ __('Ordini') }}</span>
                        <strong class="kpi-value">{{ number_format($kpis['orders_all_time']) }}</strong>
                        <div class="kpi-sub">
                            {{ __('Media mese:') }}
                            <strong>{{ $kpis['orders_monthly_avg'] !== null ? number_format($kpis['orders_monthly_avg']) : '-' }}</strong>
                        </div>
                    </div>
                </article>
                <article class="kpi-card">
                    <span class="kpi-icon green">{!! $iconSvg('revenue') !!}</span>
                    <div>
                        <span class="kpi-label">{{ __('Ricavi ordini') }}</span>
                        <strong class="kpi-value">€ {{ number_format($kpis['revenue_all_time'], 2) }}</strong>
                        <div class="kpi-sub">
                            {{ __('Media mese:') }}
                            <strong>{{ $kpis['revenue_monthly_avg'] !== null ? '€ ' . number_format($kpis['revenue_monthly_avg'], 2) : '-' }}</strong>
                        </div>
                    </div>
                </article>
            @endif

            @if($kpis['uses_reservations'])
                <article class="kpi-card">
                    <span class="kpi-icon">{!! $iconSvg('reservation') !!}</span>
                    <div>
                        <span class="kpi-label">{{ __('Prenotazioni') }}</span>
                        <strong class="kpi-value">{{ number_format($kpis['reservations_all_time']) }}</strong>
                        <div class="kpi-sub">
                            {{ __('Media mese:') }}
                            <strong>{{ $kpis['reservations_monthly_avg'] !== null ? number_format($kpis['reservations_monthly_avg']) : '-' }}</strong>
                        </div>
                    </div>
                </article>
                <article class="kpi-card">
                    <span class="kpi-icon green">{!! $iconSvg('cover') !!}</span>
                    <div>
                        <span class="kpi-label">{{ __('Coperti') }}</span>
                        <strong class="kpi-value">{{ number_format($kpis['covers_all_time']) }}</strong>
                        <div class="kpi-sub">
                            {{ __('Media mese:') }}
                            <strong>{{ $kpis['covers_monthly_avg'] !== null ? number_format($kpis['covers_monthly_avg']) : '-' }}</strong>
                        </div>
                    </div>
                </article>
            @endif
        </section>

        @if(! $kpis['uses_orders'] || ! $kpis['uses_reservations'])
            <div class="info-strip">
                <span>
                    @if(! $kpis['uses_orders'] && ! $kpis['uses_reservations'])
                        {{ __('Nessun ristorante sta usando ordini o prenotazioni.') }}
                    @elseif(! $kpis['uses_orders'])
                        {{ __('Nessun ristorante sta usando il servizio ordini/asporto.') }}
                    @else
                        {{ __('Nessun ristorante sta usando il servizio prenotazioni.') }}
                    @endif
                </span>
            </div>
        @endif
    @endif

    {{-- Section: Alert errori sync --}}
    @if($kpis['sites_with_failures'] > 0)
        <div class="alert-strip" style="margin-bottom: 20px;" role="alert">
            <strong>
                {!! $iconSvg('alert') !!}
                {{ $kpis['sites_with_failures'] }} {{ $kpis['sites_with_failures'] === 1 ? __('sito ha') : __('siti hanno') }} {{ __('errori di sincronizzazione') }}
            </strong>
            <a class="btn btn-danger" href="{{ route('sync-errors.index') }}">{{ __('Vedi errori') }}</a>
        </div>
    @endif

    @php
        $chartSites = $sites->filter(fn($s) => $s->latestSnapshot !== null)->values();
        $hasBarData = $chartSites->contains(
            fn($s) => ($s->latestSnapshot->orders_today ?? 0) > 0
                   || ($s->latestSnapshot->orders_last_7_days ?? 0) > 0
                   || ($s->latestSnapshot->reservations_today ?? 0) > 0
                   || ($s->latestSnapshot->reservations_last_7_days ?? 0) > 0
        );
    @endphp

    {{-- Section: Confronto siti (grafico) --}}
    <section style="margin-bottom: 4px;">
        <div class="section-header">
            <h2 class="section-title">{{ __('Confronto siti') }}</h2>
            <span class="text-muted text-sm">{{ __('Oggi e ultimi 7 giorni') }}</span>
        </div>
        @if($chartSites->isNotEmpty() && $hasBarData)
            <div class="chart-box">
                <canvas id="chartSites"></canvas>
            </div>
        @else
            <div class="empty-state" style="margin-bottom: 20px;">
                {!! $iconSvg('chart') !!}
                <span>{{ __('Dati grafico disponibili dopo il primo snapshot V2.') }}</span>
            </div>
        @endif
    </section>

    {{-- Section: Dashboard da controllare --}}
    @if(! empty($inactiveSites))
        <section style="margin-bottom: 4px;">
            <div class="section-header">
                <h2 class="section-title">{{ __('Dashboard da controllare') }}</h2>
                <span class="text-muted text-sm">{{ __('Massimo 5 prioritarie') }}</span>
            </div>
            <div class="card-table">
                <div class="table-wrap">
                    <table class="dashboard-table compact-table">
                        <thead>
                            <tr>
                                <th>{{ __('Sito') }}</th>
                                <th>{{ __('Ultima attivita menu') }}</th>
                                <th>{{ __('Motivo') }}</th>
                                <th>{{ __('Azione') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inactiveSites as $item)
                                @php
                                    $reason = match($item['reason']) {
                                        'no_snapshot' => ['label' => 'Nessuno snapshot disponibile', 'color' => '#b42318'],
                                        'no_v2' => ['label' => 'Snapshot non aggiornato a V2', 'color' => '#667085'],
                                        'no_menu_activity' => ['label' => 'Nessun aggiornamento menu registrato', 'color' => '#b45309'],
                                        'stale_menu' => ['label' => 'Ultima attivita menu oltre 30 giorni fa', 'color' => '#b45309'],
                                        default => ['label' => 'Da verificare', 'color' => '#667085'],
                                    };
                                @endphp
                                <tr>
                                    <td class="td-primary" data-label="{{ __('Sito') }}"><strong>{{ $item['site']->name }}</strong></td>
                                    <td data-label="{{ __('Ultima attivita') }}">{{ $item['last_activity'] ? \Carbon\Carbon::parse($item['last_activity'])->format('d/m/Y') : '-' }}</td>
                                    <td data-label="{{ __('Motivo') }}"><span style="color: {{ $reason['color'] }}; font-weight: 700;">{{ $reason['label'] }}</span></td>
                                    <td class="td-actions" data-label="{{ __('Azione') }}">
                                        <a class="btn" href="{{ route('sites.show', $item['site']) }}">{!! $iconSvg('external') !!}{{ __('Dettaglio') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- Section: Tabella siti --}}
    <section>
        <div class="section-header">
            <h2 class="section-title">{{ __('Siti') }}</h2>
            @if($canReorderSites && $sites->count() > 1)
                <div class="actions">
                    <button id="editSiteOrder" class="btn" type="button">{!! $iconSvg('list') !!}{{ __('Modifica ordine') }}</button>
                    <form id="siteOrderForm" method="POST" action="{{ route('sites.reorder') }}" style="display: none;">
                        @csrf
                        <button id="saveSiteOrder" class="btn btn-primary" type="submit" disabled>{!! $iconSvg('check') !!}{{ __('Salva ordine') }}</button>
                    </form>
                    <button id="cancelSiteOrder" class="btn" type="button" style="display: none;">{{ __('Annulla') }}</button>
                </div>
            @endif
        </div>

        <div class="card-table">
            <div class="table-wrap">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            @if($canReorderSites)
                                <th data-order-cell style="width: 110px; display: none;">{{ __('Ordine') }}</th>
                            @endif
                            <th>{{ __('Sito') }}</th>
                            <th>{{ __('Ordini') }}</th>
                            <th>{{ __('Ricavi') }}</th>
                            <th>{{ __('Prenotazioni') }}</th>
                            <th>{{ __('Coperti') }}</th>
                            <th>{{ __('Risparmio') }}</th>
                            <th>{{ __('Stato') }}</th>
                            <th>{{ __('Azioni') }}</th>
                        </tr>
                    </thead>
                    <tbody id="sitesTableBody">
                        @forelse($sites as $site)
                            @php
                                $snap = $site->latestSnapshot;
                                $hasSnapshot = $snap !== null && $site->last_success_at !== null;
                                $hasFailures = $site->consecutive_failures > 0;

                                if (! $hasSnapshot) {
                                    $sc = '#b42318'; $sb = '#fef3f2'; $st = 'Nessun dato';
                                } elseif ($hasFailures) {
                                    $sc = '#93370d'; $sb = '#fffaeb'; $st = 'Problemi (' . $site->consecutive_failures . ')';
                                } else {
                                    $sc = '#027a48'; $sb = '#ecfdf3'; $st = 'OK';
                                }

                                $metric = $siteMetrics[$site->id] ?? [];
                                $hasAllTime = (bool) ($metric['has_all_time'] ?? false);
                                $siteOrders = (int) ($metric['orders_total'] ?? 0);
                                $siteRev = $metric['orders_revenue'] ?? null;
                                $siteRes = (int) ($metric['reservations_total'] ?? 0);
                                $siteCov = (int) ($metric['reservations_covers'] ?? 0);
                                $avgOrd = $metric['orders_monthly_avg'] ?? null;
                                $avgRev = $metric['revenue_monthly_avg'] ?? null;
                                $avgRes = $metric['reservations_monthly_avg'] ?? null;
                                $avgCov = $metric['covers_monthly_avg'] ?? null;
                                $siteOrderSavings = $metric['estimated_order_savings'] ?? 0.0;
                                $siteReservationSavings = $metric['estimated_reservation_savings'] ?? 0.0;
                                $siteSavings = $metric['estimated_total_savings'] ?? 0.0;
                            @endphp
                            <tr data-site-row data-site-id="{{ $site->id }}" draggable="false">
                                @if($canReorderSites)
                                    <td data-order-cell data-label="{{ __('Ordine') }}" style="display: none;">
                                        <span class="order-handle">{{ __('Trascina') }}</span>
                                        <div class="actions site-order-controls">
                                            <button class="btn btn-icon site-order-btn" type="button" data-move="up" title="{{ __('Sposta su') }}" aria-label="{{ __('Sposta') }} {{ $site->name }} {{ __('su') }}" @disabled($sites->count() <= 1)>{!! $iconSvg('arrow-up') !!}</button>
                                            <button class="btn btn-icon site-order-btn" type="button" data-move="down" title="{{ __('Sposta giu') }}" aria-label="{{ __('Sposta') }} {{ $site->name }} {{ __('giu') }}" @disabled($sites->count() <= 1)>{!! $iconSvg('arrow-down') !!}</button>
                                        </div>
                                    </td>
                                @endif

                                <td class="td-primary" data-label="{{ __('Sito') }}">
                                    <a href="{{ route('sites.show', $site) }}"><strong class="site-name">{{ $site->name }}</strong></a>
                                    <a class="site-url-text" href="{{ $site->url }}" target="_blank" rel="noopener noreferrer">{{ $site->url }}</a>
                                </td>

                                <td data-label="{{ __('Ordini') }}">
                                    @if($siteOrders > 0)
                                        <span class="metric-main">{{ number_format($siteOrders) }}</span>
                                        <div class="metric-note">{{ $avgOrd !== null ? '~' . number_format($avgOrd) . '/mese' : 'Media da aggiornare' }}</div>
                                    @elseif($hasAllTime)
                                        <span class="metric-note">{{ __('Non usa ordini') }}</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="{{ __('Ricavi') }}">
                                    @if($siteRev !== null && $siteRev > 0)
                                        <span class="metric-main">€ {{ number_format($siteRev, 2) }}</span>
                                        <div class="metric-note">{{ $avgRev !== null ? '€ ' . number_format($avgRev, 2) . '/mese' : 'Media da aggiornare' }}</div>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="{{ __('Prenotazioni') }}">
                                    @if($siteRes > 0)
                                        <span class="metric-main">{{ number_format($siteRes) }}</span>
                                        <div class="metric-note">{{ $avgRes !== null ? '~' . number_format($avgRes) . '/mese' : 'Media da aggiornare' }}</div>
                                    @elseif($hasAllTime)
                                        <span class="metric-note">{{ __('Non usa prenotazioni') }}</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="{{ __('Coperti') }}">
                                    @if($siteCov > 0)
                                        <span class="metric-main">{{ number_format($siteCov) }}</span>
                                        <div class="metric-note">{{ $avgCov !== null ? '~' . number_format($avgCov) . '/mese' : 'Media da aggiornare' }}</div>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="{{ __('Risparmio') }}">
                                    @if($siteSavings > 0)
                                        <span class="metric-main">€ {{ number_format($siteSavings, 2) }}</span>
                                        <div class="metric-note">
                                            @if($siteOrderSavings > 0)
                                                {{ __('ordini') }} € {{ number_format($siteOrderSavings, 2) }}
                                            @endif
                                            @if($siteReservationSavings > 0)
                                                {{ $siteOrderSavings > 0 ? ' / ' : '' }}{{ __('pren.') }} € {{ number_format($siteReservationSavings, 2) }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="{{ __('Stato') }}">
                                    <span class="status-pill" style="color:{{ $sc }};background:{{ $sb }};">
                                        {{ $st }}
                                    </span>
                                </td>

                                <td class="td-actions" data-label="{{ __('Azioni') }}">
                                    <div class="actions">
                                        <a class="btn" href="{{ route('sites.show', $site) }}">{!! $iconSvg('external') !!}{{ __('Dettaglio') }}</a>
                                        <form method="POST" action="{{ route('sites.sync', $site) }}">
                                            @csrf
                                            <button class="btn" type="submit">{!! $iconSvg('sync') !!}{{ __('Sync') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canReorderSites ? 9 : 8 }}" class="td-primary" data-label="{{ __('Siti') }}" style="text-align:center;padding:28px;">
                                    {{ __('Nessun sito configurato.') }} <a href="{{ route('sites.create') }}">{{ __('Aggiungine uno') }}</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="footer-note">
            <a href="{{ route('sync-errors.index') }}" class="muted">{{ __('Log errori di sincronizzazione') }}</a>
        </div>
    </section>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if($canReorderSites && $sites->count() > 1)
<script>
(function () {
    const tbody = document.getElementById('sitesTableBody');
    const form = document.getElementById('siteOrderForm');
    const editButton = document.getElementById('editSiteOrder');
    const saveButton = document.getElementById('saveSiteOrder');
    const cancelButton = document.getElementById('cancelSiteOrder');
    let draggedRow = null;
    let editingOrder = false;

    if (!tbody || !form || !editButton || !saveButton || !cancelButton) {
        return;
    }

    function rows() {
        return Array.from(tbody.querySelectorAll('tr[data-site-id]'));
    }

    const initialOrder = rows().map(row => row.dataset.siteId);

    function orderCells() {
        return Array.from(document.querySelectorAll('[data-order-cell]'));
    }

    function setOrderMode(enabled) {
        editingOrder = enabled;
        orderCells().forEach(cell => {
            cell.style.display = enabled ? '' : 'none';
        });
        rows().forEach(row => {
            row.draggable = enabled;
        });
        editButton.style.display = enabled ? 'none' : '';
        form.style.display = enabled ? '' : 'none';
        cancelButton.style.display = enabled ? '' : 'none';
        saveButton.disabled = true;
        updateHiddenInputs();
        updateButtonStates();
    }

    function restoreInitialOrder() {
        const rowsById = new Map(rows().map(row => [row.dataset.siteId, row]));
        initialOrder.forEach(siteId => {
            const row = rowsById.get(siteId);
            if (row) {
                tbody.appendChild(row);
            }
        });
    }

    function updateHiddenInputs() {
        form.querySelectorAll('input[name="site_ids[]"]').forEach(input => input.remove());
        rows().forEach(row => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'site_ids[]';
            input.value = row.dataset.siteId;
            form.appendChild(input);
        });
    }

    function updateButtonStates() {
        const allRows = rows();
        allRows.forEach((row, index) => {
            const up = row.querySelector('[data-move="up"]');
            const down = row.querySelector('[data-move="down"]');
            if (up) up.disabled = index === 0;
            if (down) down.disabled = index === allRows.length - 1;
        });
    }

    function markChanged() {
        if (!editingOrder) {
            return;
        }

        updateHiddenInputs();
        updateButtonStates();
        saveButton.disabled = false;
    }

    editButton.addEventListener('click', function () {
        setOrderMode(true);
    });

    cancelButton.addEventListener('click', function () {
        restoreInitialOrder();
        setOrderMode(false);
    });

    tbody.addEventListener('click', function (event) {
        if (!editingOrder) {
            return;
        }

        const button = event.target.closest('[data-move]');
        if (!button) {
            return;
        }

        const row = button.closest('tr[data-site-id]');
        if (!row) {
            return;
        }

        if (button.dataset.move === 'up' && row.previousElementSibling) {
            tbody.insertBefore(row, row.previousElementSibling);
            markChanged();
        }

        if (button.dataset.move === 'down' && row.nextElementSibling) {
            tbody.insertBefore(row.nextElementSibling, row);
            markChanged();
        }
    });

    tbody.addEventListener('dragstart', function (event) {
        if (!editingOrder) {
            event.preventDefault();

            return;
        }

        draggedRow = event.target.closest('tr[data-site-id]');
        if (!draggedRow) {
            return;
        }

        draggedRow.style.opacity = '0.55';
        event.dataTransfer.effectAllowed = 'move';
    });

    tbody.addEventListener('dragend', function () {
        if (draggedRow) {
            draggedRow.style.opacity = '';
        }
        draggedRow = null;
    });

    tbody.addEventListener('dragover', function (event) {
        if (!editingOrder) {
            return;
        }

        if (!draggedRow) {
            return;
        }

        event.preventDefault();
        const target = event.target.closest('tr[data-site-id]');
        if (!target || target === draggedRow) {
            return;
        }

        const box = target.getBoundingClientRect();
        const after = event.clientY > box.top + box.height / 2;
        tbody.insertBefore(draggedRow, after ? target.nextElementSibling : target);
        markChanged();
    });

    form.addEventListener('submit', updateHiddenInputs);
    setOrderMode(false);
})();
</script>
@endif
@if($chartSites->isNotEmpty() && $hasBarData)
<script>
(function () {
    const labels  = @json($chartSites->pluck('name'));
    const ordToday  = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->orders_today ?? 0)));
    const ordLast7  = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->orders_last_7_days ?? 0)));
    const resToday  = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->reservations_today ?? 0)));
    const resLast7  = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->reservations_last_7_days ?? 0)));

    new Chart(document.getElementById('chartSites'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Ordini oggi', data: ordToday, backgroundColor: 'rgba(21,94,239,0.75)', borderRadius: 5 },
                { label: 'Ordini ultimi 7 giorni', data: ordLast7, backgroundColor: 'rgba(3,152,85,0.75)', borderRadius: 5 },
                { label: 'Prenotazioni oggi', data: resToday, backgroundColor: 'rgba(181,71,8,0.68)', borderRadius: 5 },
                { label: 'Prenotazioni ultimi 7 giorni', data: resLast7, backgroundColor: 'rgba(180,35,24,0.58)', borderRadius: 5 },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12 } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endif
@endpush
