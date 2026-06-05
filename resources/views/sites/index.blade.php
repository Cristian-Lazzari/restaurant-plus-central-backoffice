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
            // Bootstrap Icons — fill, viewBox 0 0 16 16
            $icons = [
                'activity'    => '<path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07Z"/>',
                'alert'       => '<path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>',
                'arrow-down'  => '<path fill-rule="evenodd" d="M8 1a.5.5 0 0 1 .5.5v11.793l3.146-3.147a.5.5 0 0 1 .708.708l-4 4a.5.5 0 0 1-.708 0l-4-4a.5.5 0 0 1 .708-.708L7.5 13.293V1.5A.5.5 0 0 1 8 1z"/>',
                'arrow-up'    => '<path fill-rule="evenodd" d="M8 15a.5.5 0 0 0 .5-.5V2.707l3.146 3.147a.5.5 0 0 0 .708-.708l-4-4a.5.5 0 0 0-.708 0l-4 4a.5.5 0 0 0 .708.708L7.5 2.707V14.5a.5.5 0 0 0 .5.5z"/>',
                'chart'       => '<path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2z"/>',
                'check'       => '<path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022z"/>',
                'cover'       => '<path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>',
                'external'    => '<path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/><path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0V.5z"/>',
                'list'        => '<path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5z"/>',
                'plus'        => '<path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>',
                'reservation' => '<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zm9.954 3H2.545c-.3 0-.545.224-.545.5v1c0 .276.244.5.545.5h10.91c.3 0 .545-.224.545-.5v-1c0-.276-.244-.5-.546-.5zm-2.6 5.854-3 3a.5.5 0 0 1-.707 0l-1.5-1.5a.5.5 0 0 1 .707-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>',
                'revenue'     => '<path d="M4 9.42h1.063C5.4 12.323 7.317 14 10.34 14c.622 0 1.167-.068 1.659-.185v-1.3c-.484.119-1.045.17-1.659.17-2.1 0-3.455-1.198-3.775-3.264h4.017v-.928H6.497v-.936c0-.11 0-.219.008-.329h4.116v-.93H6.618c.388-1.898 1.719-2.985 3.723-2.985.614 0 1.175.05 1.659.177V2.194A6.617 6.617 0 0 0 10.34 2c-2.928 0-4.813 1.569-5.244 4.3H4v.928h1.01v.936c0 .11 0 .219-.008.328H4v.928z"/>',
                'savings'     => '<path d="M8 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M0 4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V4zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V6a2 2 0 0 1-2-2H3z"/>',
                'sync'        => '<path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>',
            ];

            $path = $icons[$name] ?? $icons['activity'];

            return new \Illuminate\Support\HtmlString('<svg class="icon" aria-hidden="true" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">' . $path . '</svg>');
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
            .chart-box {
                height: clamp(260px, 72vw, 340px);
                margin: 0 -14px;
                border-left: 0; border-right: 0; border-radius: 0;
                padding: 14px 10px 10px;
            }
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
                { label: 'Ordini oggi', data: ordToday, backgroundColor: 'rgba(14,183,146,0.82)', borderRadius: 5 },
                { label: 'Ordini ultimi 7 giorni', data: ordLast7, backgroundColor: 'rgba(14,183,146,0.38)', borderRadius: 5 },
                { label: 'Prenotazioni oggi', data: resToday, backgroundColor: 'rgba(9,3,51,0.75)', borderRadius: 5 },
                { label: 'Prenotazioni ultimi 7 giorni', data: resLast7, backgroundColor: 'rgba(9,3,51,0.38)', borderRadius: 5 },
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
