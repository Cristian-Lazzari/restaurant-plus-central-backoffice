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
        .dashboard-page { display: flex; flex-direction: column; gap: 22px; }
        .dashboard-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: end;
            padding: 8px 0 2px;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .hero-copy { margin-top: 8px; color: var(--muted); max-width: 680px; line-height: 1.5; }
        .hero-actions { justify-content: flex-end; }
        .sync-summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 11px;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            background: #fff;
            color: var(--muted);
            box-shadow: var(--shadow);
            font-size: 13px;
        }
        .dashboard-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .kpi-card {
            display: flex;
            gap: 13px;
            align-items: flex-start;
            min-height: 124px;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            background: #fff;
            padding: 16px;
            box-shadow: var(--shadow);
        }
        .kpi-card.featured {
            border-color: #fedf89;
            background: #fffdf5;
        }
        .kpi-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 38px;
            background: var(--brand-soft);
            color: var(--brand);
        }
        .kpi-icon.green { background: var(--green-soft); color: var(--green); }
        .kpi-icon.amber { background: var(--amber-soft); color: var(--amber); }
        .kpi-icon.red { background: var(--red-soft); color: var(--red); }
        .kpi-label { color: var(--muted); font-size: 12px; font-weight: 760; text-transform: uppercase; }
        .kpi-value { display: block; margin-top: 6px; font-size: 25px; line-height: 1.15; font-weight: 780; }
        .kpi-sub { margin-top: 6px; color: var(--muted); font-size: 12px; line-height: 1.35; }
        .info-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--border-soft);
            border-radius: 8px;
            background: #fff;
            box-shadow: var(--shadow);
            color: var(--muted);
            font-size: 13px;
        }
        .alert-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid #fecdca;
            border-radius: 8px;
            background: var(--red-soft);
            padding: 13px 14px;
            color: var(--red);
        }
        .alert-strip strong { display: inline-flex; align-items: center; gap: 8px; }
        .chart-panel { height: 320px; }
        .chart-panel canvas { width: 100%; height: 100% !important; }
        .empty-state {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            border: 1px dashed var(--border);
            border-radius: 8px;
            background: #fff;
            color: var(--muted);
        }
        .dashboard-table { min-width: 0; }
        .dashboard-table th { white-space: nowrap; }
        .site-cell strong { font-size: 14px; }
        .site-url {
            display: block;
            max-width: 280px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--muted);
            font-size: 12px;
            margin-top: 3px;
        }
        .metric-main { display: block; font-weight: 760; }
        .metric-note { color: var(--muted); font-size: 12px; margin-top: 3px; }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 760;
        }
        .site-order-controls { gap: 4px; flex-wrap: nowrap; }
        .order-handle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            margin-right: 4px;
        }
        .mobile-card-table { width: 100%; }
        .compact-table { min-width: 0; }
        .footer-link { text-align: right; font-size: 13px; margin-top: -6px; }
        @media (max-width: 980px) {
            .dashboard-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .dashboard-hero { grid-template-columns: 1fr; align-items: start; }
            .hero-actions { justify-content: flex-start; }
        }
        @media (max-width: 760px) {
            .dashboard-page { gap: 18px; }
            .hero-actions .btn, .hero-actions form { width: 100%; }
            .hero-actions form .btn { width: 100%; }
            .sync-summary, .info-strip { width: 100%; align-items: flex-start; }
            .dashboard-kpis { grid-template-columns: 1fr; }
            .kpi-card { min-height: 0; padding: 14px; }
            .kpi-value { font-size: 23px; }
            .alert-strip, .info-strip { flex-direction: column; align-items: flex-start; }
            .chart-panel { height: 280px; }
            .table-wrap.mobile-card-table {
                border: 0;
                box-shadow: none;
                background: transparent;
                border-radius: 0;
                overflow-x: visible;
            }
            .mobile-card-table table,
            .mobile-card-table thead,
            .mobile-card-table tbody,
            .mobile-card-table tr,
            .mobile-card-table td {
                display: block;
                width: 100%;
            }
            .mobile-card-table thead { display: none; }
            .mobile-card-table tr {
                background: #fff;
                border: 1px solid var(--border-soft);
                border-radius: 8px;
                box-shadow: var(--shadow);
                padding: 13px 14px;
                margin-bottom: 12px;
            }
            .mobile-card-table td {
                border: 0;
                padding: 7px 0;
                display: grid;
                grid-template-columns: minmax(96px, 36%) minmax(0, 1fr);
                gap: 10px;
                align-items: start;
                font-size: 13px;
            }
            .mobile-card-table td::before {
                content: attr(data-label);
                color: var(--muted);
                font-weight: 760;
            }
            .mobile-card-table td.primary-cell {
                display: block;
                padding-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid var(--border-soft);
                margin-bottom: 5px;
            }
            .mobile-card-table td.primary-cell::before { content: ""; display: none; }
            .mobile-card-table td.actions-cell {
                display: block;
                padding-top: 10px;
            }
            .mobile-card-table td.actions-cell::before { content: ""; display: none; }
            .mobile-card-table .actions-cell .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .mobile-card-table .actions-cell .btn { width: 100%; }
            .site-url { max-width: 100%; white-space: normal; word-break: break-word; }
            .footer-link { text-align: left; }
        }
    </style>

    <div class="dashboard-page">
        <section class="dashboard-hero">
            <div>
                <div class="eyebrow">
                    {!! $iconSvg('activity') !!}
                    Monitoraggio centrale
                </div>
                <h1>Dashboard</h1>
                <div class="hero-copy">
                    Controllo rapido dei siti collegati, dati business e stato delle sincronizzazioni.
                </div>
            </div>
            <div class="actions hero-actions">
                <span class="sync-summary">
                    {!! $iconSvg('sync') !!}
                    Ultimo sync:
                    <strong style="color: var(--ink);">{{ $lastGlobalSyncAt ? $lastGlobalSyncAt->format('d/m/Y H:i') : 'Mai' }}</strong>
                </span>
                <form method="POST" action="{{ route('sync.all') }}">
                    @csrf
                    <button class="btn" type="submit">{!! $iconSvg('sync') !!}Sync tutti</button>
                </form>
                <a class="btn primary" href="{{ route('sites.create') }}">{!! $iconSvg('plus') !!}Nuovo sito</a>
            </div>
        </section>

        @if(! $kpis['has_v2_data'])
            <div class="empty-state">
                {!! $iconSvg('chart') !!}
                <span>Nessun dato aggregato disponibile ancora. Esegui una sync per raccogliere i dati.</span>
            </div>
        @else
            <section class="dashboard-kpis" aria-label="KPI globali">
                @if(($kpis['uses_orders'] || $kpis['uses_reservations']) && ($kpis['estimated_total_savings'] ?? 0) > 0)
                    <article class="kpi-card featured">
                        <span class="kpi-icon amber">{!! $iconSvg('savings') !!}</span>
                        <div>
                            <span class="kpi-label">Risparmio stimato Future Plus</span>
                            <strong class="kpi-value">€ {{ number_format($kpis['estimated_total_savings'], 2) }}</strong>
                            <div class="kpi-sub">Benchmark: Just Eat/Deliveroo/Glovo {{ $benchmarkOrderPercent }}%, TheFork € {{ $benchmarkCoverFee }}/coperto.</div>
                        </div>
                    </article>
                @endif

                @if($kpis['uses_orders'])
                    <article class="kpi-card">
                        <span class="kpi-icon">{!! $iconSvg('list') !!}</span>
                        <div>
                            <span class="kpi-label">Ordini</span>
                            <strong class="kpi-value">{{ number_format($kpis['orders_all_time']) }}</strong>
                            <div class="kpi-sub">
                                Media mese:
                                <strong>{{ $kpis['orders_monthly_avg'] !== null ? number_format($kpis['orders_monthly_avg']) : '-' }}</strong>
                            </div>
                        </div>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-icon green">{!! $iconSvg('revenue') !!}</span>
                        <div>
                            <span class="kpi-label">Ricavi ordini</span>
                            <strong class="kpi-value">€ {{ number_format($kpis['revenue_all_time'], 2) }}</strong>
                            <div class="kpi-sub">
                                Media mese:
                                <strong>{{ $kpis['revenue_monthly_avg'] !== null ? '€ ' . number_format($kpis['revenue_monthly_avg'], 2) : '-' }}</strong>
                            </div>
                        </div>
                    </article>
                @endif

                @if($kpis['uses_reservations'])
                    <article class="kpi-card">
                        <span class="kpi-icon">{!! $iconSvg('reservation') !!}</span>
                        <div>
                            <span class="kpi-label">Prenotazioni</span>
                            <strong class="kpi-value">{{ number_format($kpis['reservations_all_time']) }}</strong>
                            <div class="kpi-sub">
                                Media mese:
                                <strong>{{ $kpis['reservations_monthly_avg'] !== null ? number_format($kpis['reservations_monthly_avg']) : '-' }}</strong>
                            </div>
                        </div>
                    </article>
                    <article class="kpi-card">
                        <span class="kpi-icon green">{!! $iconSvg('cover') !!}</span>
                        <div>
                            <span class="kpi-label">Coperti</span>
                            <strong class="kpi-value">{{ number_format($kpis['covers_all_time']) }}</strong>
                            <div class="kpi-sub">
                                Media mese:
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
                            Nessun ristorante sta usando ordini o prenotazioni.
                        @elseif(! $kpis['uses_orders'])
                            Nessun ristorante sta usando il servizio ordini/asporto.
                        @else
                            Nessun ristorante sta usando il servizio prenotazioni.
                        @endif
                    </span>
                </div>
            @endif
        @endif

        @if($kpis['sites_with_failures'] > 0)
            <div class="alert-strip">
                <strong>
                    {!! $iconSvg('alert') !!}
                    {{ $kpis['sites_with_failures'] }} {{ $kpis['sites_with_failures'] === 1 ? 'sito ha' : 'siti hanno' }} errori di sincronizzazione
                </strong>
                <a class="btn danger" href="{{ route('sync-errors.index') }}">Vedi errori</a>
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

        <section>
            <div class="section-head">
                <h2>Confronto siti</h2>
                <span class="muted" style="font-size: 13px;">Oggi e ultimi 7 giorni</span>
            </div>
            @if($chartSites->isNotEmpty() && $hasBarData)
                <div class="panel chart-panel">
                    <canvas id="chartSites"></canvas>
                </div>
            @else
                <div class="empty-state">
                    {!! $iconSvg('chart') !!}
                    <span>Dati grafico disponibili dopo il primo snapshot V2.</span>
                </div>
            @endif
        </section>

        @if(! empty($inactiveSites))
            <section>
                <div class="section-head">
                    <h2>Dashboard da controllare</h2>
                    <span class="muted" style="font-size: 13px;">Massimo 5 prioritarie</span>
                </div>
                <div class="table-wrap mobile-card-table">
                    <table class="dashboard-table compact-table">
                        <thead>
                            <tr>
                                <th>Sito</th>
                                <th>Ultima attivita menu</th>
                                <th>Motivo</th>
                                <th>Azione</th>
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
                                    <td class="primary-cell" data-label="Sito"><strong>{{ $item['site']->name }}</strong></td>
                                    <td data-label="Ultima attivita">{{ $item['last_activity'] ? \Carbon\Carbon::parse($item['last_activity'])->format('d/m/Y') : '-' }}</td>
                                    <td data-label="Motivo"><span style="color: {{ $reason['color'] }}; font-weight: 700;">{{ $reason['label'] }}</span></td>
                                    <td class="actions-cell" data-label="Azione">
                                        <a class="btn" href="{{ route('sites.show', $item['site']) }}">{!! $iconSvg('external') !!}Dettaglio</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section>
            <div class="section-head">
                <h2>Siti</h2>
                @if($canReorderSites && $sites->count() > 1)
                    <div class="actions">
                        <button id="editSiteOrder" class="btn" type="button">{!! $iconSvg('list') !!}Modifica ordine</button>
                        <form id="siteOrderForm" method="POST" action="{{ route('sites.reorder') }}" style="display: none;">
                            @csrf
                            <button id="saveSiteOrder" class="btn primary" type="submit" disabled>{!! $iconSvg('check') !!}Salva ordine</button>
                        </form>
                        <button id="cancelSiteOrder" class="btn" type="button" style="display: none;">Annulla</button>
                    </div>
                @endif
            </div>

            <div class="table-wrap mobile-card-table">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            @if($canReorderSites)
                                <th data-order-cell style="width: 110px; display: none;">Ordine</th>
                            @endif
                            <th>Sito</th>
                            <th>Ordini</th>
                            <th>Ricavi</th>
                            <th>Prenotazioni</th>
                            <th>Coperti</th>
                            <th>Risparmio</th>
                            <th>Stato</th>
                            <th>Azioni</th>
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
                                    <td data-order-cell data-label="Ordine" style="display: none;">
                                        <span class="order-handle">Trascina</span>
                                        <div class="actions site-order-controls">
                                            <button class="btn icon-only site-order-btn" type="button" data-move="up" title="Sposta su" aria-label="Sposta {{ $site->name }} su" @disabled($sites->count() <= 1)>{!! $iconSvg('arrow-up') !!}</button>
                                            <button class="btn icon-only site-order-btn" type="button" data-move="down" title="Sposta giu" aria-label="Sposta {{ $site->name }} giu" @disabled($sites->count() <= 1)>{!! $iconSvg('arrow-down') !!}</button>
                                        </div>
                                    </td>
                                @endif

                                <td class="primary-cell site-cell" data-label="Sito">
                                    <a href="{{ route('sites.show', $site) }}"><strong>{{ $site->name }}</strong></a>
                                    <a class="site-url" href="{{ $site->url }}" target="_blank" rel="noopener noreferrer">{{ $site->url }}</a>
                                </td>

                                <td data-label="Ordini">
                                    @if($siteOrders > 0)
                                        <span class="metric-main">{{ number_format($siteOrders) }}</span>
                                        <div class="metric-note">{{ $avgOrd !== null ? '~' . number_format($avgOrd) . '/mese' : 'Media da aggiornare' }}</div>
                                    @elseif($hasAllTime)
                                        <span class="metric-note">Non usa ordini</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="Ricavi">
                                    @if($siteRev !== null && $siteRev > 0)
                                        <span class="metric-main">€ {{ number_format($siteRev, 2) }}</span>
                                        <div class="metric-note">{{ $avgRev !== null ? '€ ' . number_format($avgRev, 2) . '/mese' : 'Media da aggiornare' }}</div>
                                    @elseif($hasAllTime)
                                        <span class="muted">-</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="Prenotazioni">
                                    @if($siteRes > 0)
                                        <span class="metric-main">{{ number_format($siteRes) }}</span>
                                        <div class="metric-note">{{ $avgRes !== null ? '~' . number_format($avgRes) . '/mese' : 'Media da aggiornare' }}</div>
                                    @elseif($hasAllTime)
                                        <span class="metric-note">Non usa prenotazioni</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="Coperti">
                                    @if($siteCov > 0)
                                        <span class="metric-main">{{ number_format($siteCov) }}</span>
                                        <div class="metric-note">{{ $avgCov !== null ? '~' . number_format($avgCov) . '/mese' : 'Media da aggiornare' }}</div>
                                    @elseif($hasAllTime)
                                        <span class="muted">-</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="Risparmio">
                                    @if($siteSavings > 0)
                                        <span class="metric-main">€ {{ number_format($siteSavings, 2) }}</span>
                                        <div class="metric-note">
                                            @if($siteOrderSavings > 0)
                                                ordini € {{ number_format($siteOrderSavings, 2) }}
                                            @endif
                                            @if($siteReservationSavings > 0)
                                                {{ $siteOrderSavings > 0 ? ' / ' : '' }}pren. € {{ number_format($siteReservationSavings, 2) }}
                                            @endif
                                        </div>
                                    @elseif($hasAllTime)
                                        <span class="muted">-</span>
                                    @else
                                        <span class="muted">-</span>
                                    @endif
                                </td>

                                <td data-label="Stato">
                                    <span class="status-pill" style="color:{{ $sc }};background:{{ $sb }};">
                                        {{ $st }}
                                    </span>
                                </td>

                                <td class="actions-cell" data-label="Azioni">
                                    <div class="actions">
                                        <a class="btn" href="{{ route('sites.show', $site) }}">{!! $iconSvg('external') !!}Dettaglio</a>
                                        <form method="POST" action="{{ route('sites.sync', $site) }}">
                                            @csrf
                                            <button class="btn" type="submit">{!! $iconSvg('sync') !!}Sync</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canReorderSites ? 9 : 8 }}" class="primary-cell" data-label="Siti" style="text-align:center;padding:28px;">
                                    Nessun sito configurato. <a href="{{ route('sites.create') }}">Aggiungine uno</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="footer-link">
                <a href="{{ route('sync-errors.index') }}" class="muted">Log errori di sincronizzazione</a>
            </div>
        </section>
    </div>
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
