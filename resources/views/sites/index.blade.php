@extends('layouts.app')

@section('content')

    {{-- Page header --}}
    <div class="actions" style="justify-content: space-between; margin-bottom: 24px;">
        <h1 style="margin: 0;">Dashboard</h1>
        <div class="actions">
            <form method="POST" action="{{ route('sync.all') }}">
                @csrf
                <button class="btn" type="submit">Sync tutti</button>
            </form>
            <a class="btn primary" href="{{ route('sites.create') }}">Nuovo sito</a>
        </div>
    </div>

    {{-- Section A: KPI globali --}}
    @if(! $kpis['has_v2_data'])
        <div class="panel muted" style="margin-bottom: 20px; font-size: 13px; text-align: center; padding: 20px;">
            Nessun dato aggregato disponibile ancora. Esegui una sync per raccogliere i dati.
        </div>
    @else
        {{-- Ordini --}}
        @if($kpis['uses_orders'])
            <div class="grid" style="margin-bottom: 12px;">
                <div class="metric">
                    <span class="muted">Ordini totali</span>
                    <strong>{{ number_format($kpis['orders_all_time']) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Media mensile ordini</span>
                    <strong>{{ number_format($kpis['orders_monthly_avg']) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Ricavi totali</span>
                    <strong>€ {{ number_format($kpis['revenue_all_time'], 2) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Media mensile ricavi</span>
                    <strong>€ {{ number_format($kpis['revenue_monthly_avg'], 2) }}</strong>
                </div>
            </div>
        @else
            <div class="panel muted" style="margin-bottom: 12px; font-size: 13px; padding: 12px 16px;">
                Nessun ristorante sta utilizzando il servizio ordini/asporto.
            </div>
        @endif

        {{-- Prenotazioni --}}
        @if($kpis['uses_reservations'])
            <div class="grid" style="margin-bottom: 20px;">
                <div class="metric">
                    <span class="muted">Prenotazioni totali</span>
                    <strong>{{ number_format($kpis['reservations_all_time']) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Media mensile prenotazioni</span>
                    <strong>{{ number_format($kpis['reservations_monthly_avg']) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Coperti totali</span>
                    <strong>{{ number_format($kpis['covers_all_time']) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Media mensile coperti</span>
                    <strong>{{ number_format($kpis['covers_monthly_avg']) }}</strong>
                </div>
            </div>
        @else
            <div class="panel muted" style="margin-bottom: 20px; font-size: 13px; padding: 12px 16px;">
                Nessun ristorante sta utilizzando il servizio prenotazioni.
            </div>
        @endif
    @endif

    {{-- Siti con problemi --}}
    @if($kpis['sites_with_failures'] > 0)
        <div class="panel" style="border-color: #fecdca; background: #fef3f2; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <strong style="color: #b42318;">
                {{ $kpis['sites_with_failures'] }} {{ $kpis['sites_with_failures'] === 1 ? 'sito ha' : 'siti hanno' }} errori di sincronizzazione
            </strong>
            <a href="{{ route('sync-errors.index') }}" style="color: #b42318; white-space: nowrap; font-size: 13px;">
                Vedi errori &rarr;
            </a>
        </div>
    @endif

    {{-- Section B: Confronto rapido siti --}}
    @php
        $chartSites = $sites->filter(fn($s) => $s->latestSnapshot !== null)->values();
        $hasBarData = $chartSites->contains(
            fn($s) => ($s->latestSnapshot->orders_today ?? 0) > 0
                   || ($s->latestSnapshot->orders_last_7_days ?? 0) > 0
                   || ($s->latestSnapshot->reservations_today ?? 0) > 0
                   || ($s->latestSnapshot->reservations_last_7_days ?? 0) > 0
        );
    @endphp
    <h2>Confronto siti</h2>
    @if($chartSites->isNotEmpty() && $hasBarData)
        <div class="panel" style="margin-bottom: 18px;">
            <canvas id="chartSites" style="max-height: 280px;"></canvas>
        </div>
    @else
        <div class="panel muted" style="margin-bottom: 18px; font-size: 13px;">
            Dati grafico disponibili dopo il primo snapshot V2.
        </div>
    @endif

    {{-- Section C: Dashboard da controllare --}}
    @if(! empty($inactiveSites))
        <h2>Dashboard da controllare</h2>
        <div class="panel" style="margin-bottom: 18px; padding: 0; overflow: hidden;">
            <table style="min-width: unset; width: 100%;">
                <thead>
                    <tr>
                        <th>Sito</th>
                        <th>Ultima attività menu</th>
                        <th>Motivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inactiveSites as $item)
                        <tr>
                            <td><strong>{{ $item['site']->name }}</strong></td>
                            <td class="muted">
                                {{ $item['last_activity'] ? \Carbon\Carbon::parse($item['last_activity'])->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                @switch($item['reason'])
                                    @case('no_snapshot')
                                        <span style="color:#b42318;font-size:13px;">Nessuno snapshot disponibile</span>@break
                                    @case('no_v2')
                                        <span style="color:#667085;font-size:13px;">Snapshot non aggiornato a V2</span>@break
                                    @case('no_menu_activity')
                                        <span style="color:#b45309;font-size:13px;">Nessun aggiornamento menu registrato</span>@break
                                    @case('stale_menu')
                                        <span style="color:#b45309;font-size:13px;">Ultima attività menu oltre 30 giorni fa</span>@break
                                @endswitch
                            </td>
                            <td><a class="btn" href="{{ route('sites.show', $item['site']) }}">Dettaglio</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Section D: Tabella siti --}}
    <h2>Siti</h2>
    <div class="table-wrap" style="margin-bottom: 16px;">
        <table>
            <thead>
                <tr>
                    <th>Sito</th>
                    <th>Ordini<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Ricavi<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Prenotazioni<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Coperti<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Ultima sync</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    @php
                        $snap = $site->latestSnapshot;

                        // Status badge
                        $hasSnapshot = $snap !== null && $site->last_success_at !== null;
                        $hasFailures = $site->consecutive_failures > 0;
                        if (! $hasSnapshot) {
                            $sc = '#b42318'; $sb = '#fef3f2'; $st = 'Nessun dato';
                        } elseif ($hasFailures) {
                            $sc = '#93370d'; $sb = '#fffaeb'; $st = 'Problemi (' . $site->consecutive_failures . ')';
                        } else {
                            $sc = '#027a48'; $sb = '#ecfdf3'; $st = 'OK';
                        }

                        // All-time per-sito dal payload
                        $atBlock = is_array($snap?->payload) ? ($snap->payload['periods']['all_time'] ?? null) : null;
                        $siteOrders = (int)   ($atBlock['orders_total']       ?? 0);
                        $siteRev    = (float) ($atBlock['orders_revenue']      ?? 0);
                        $siteRes    = (int)   ($atBlock['reservations_total']  ?? 0);
                        $siteCov    = (int)   ($atBlock['reservations_covers'] ?? 0);

                        // Mesi attivi (cap 5 anni)
                        $months = 1;
                        if ($snap && $snap->period_from && $snap->period_to) {
                            $cap  = $snap->period_to->copy()->subYears(5);
                            $pfr  = $snap->period_from->lt($cap) ? $cap : $snap->period_from;
                            $months = max(1, (int) ceil($pfr->diffInMonths($snap->period_to)));
                        }
                        $avgOrd = $siteOrders > 0 ? round($siteOrders / $months) : null;
                        $avgRev = $siteRev    > 0 ? round($siteRev    / $months, 2) : null;
                        $avgRes = $siteRes    > 0 ? round($siteRes    / $months) : null;
                        $avgCov = $siteCov    > 0 ? round($siteCov    / $months) : null;
                    @endphp
                    <tr>
                        {{-- Sito --}}
                        <td>
                            <a href="{{ route('sites.show', $site) }}"><strong>{{ $site->name }}</strong></a>
                            <div class="muted" style="font-size: 12px;">
                                <a href="{{ $site->url }}" target="_blank" rel="noopener noreferrer" style="color:var(--muted);">{{ $site->url }}</a>
                            </div>
                        </td>

                        {{-- Ordini --}}
                        <td>
                            @if($siteOrders > 0)
                                <strong>{{ number_format($siteOrders) }}</strong>
                                <div class="muted" style="font-size:12px;">~{{ number_format($avgOrd) }}/mese</div>
                            @elseif($atBlock)
                                <span class="muted" style="font-size:12px;">Non usa ordini</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Ricavi --}}
                        <td>
                            @if($siteRev > 0)
                                <strong>€ {{ number_format($siteRev, 2) }}</strong>
                                <div class="muted" style="font-size:12px;">€ {{ number_format($avgRev, 2) }}/mese</div>
                            @elseif($atBlock)
                                <span class="muted" style="font-size:12px;">—</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Prenotazioni --}}
                        <td>
                            @if($siteRes > 0)
                                <strong>{{ number_format($siteRes) }}</strong>
                                <div class="muted" style="font-size:12px;">~{{ number_format($avgRes) }}/mese</div>
                            @elseif($atBlock)
                                <span class="muted" style="font-size:12px;">Non usa prenotazioni</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Coperti --}}
                        <td>
                            @if($siteCov > 0)
                                <strong>{{ number_format($siteCov) }}</strong>
                                <div class="muted" style="font-size:12px;">~{{ number_format($avgCov) }}/mese</div>
                            @elseif($atBlock)
                                <span class="muted" style="font-size:12px;">—</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Ultima sync --}}
                        <td>
                            @if($site->last_success_at)
                                {{ $site->last_success_at->format('d/m/Y H:i') }}
                            @else
                                <span class="muted">Mai</span>
                            @endif
                        </td>

                        {{-- Stato --}}
                        <td>
                            <span style="display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:600;color:{{ $sc }};background:{{ $sb }};">
                                {{ $st }}
                            </span>
                        </td>

                        {{-- Azioni --}}
                        <td>
                            <div class="actions">
                                <a class="btn" href="{{ route('sites.show', $site) }}">Dettaglio</a>
                                <form method="POST" action="{{ route('sites.sync', $site) }}">
                                    @csrf
                                    <button class="btn" type="submit">Sync</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="muted" style="text-align:center;padding:28px;">
                            Nessun sito configurato. <a href="{{ route('sites.create') }}">Aggiungine uno</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="text-align:right;font-size:13px;margin-top:4px;">
        <a href="{{ route('sync-errors.index') }}" class="muted">Log errori di sincronizzazione &rarr;</a>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                { label: 'Ordini oggi',                 data: ordToday, backgroundColor: 'rgba(21,94,239,0.75)' },
                { label: 'Ordini ultimi 7 giorni',      data: ordLast7, backgroundColor: 'rgba(3,152,85,0.75)' },
                { label: 'Prenotazioni oggi',           data: resToday, backgroundColor: 'rgba(122,90,248,0.75)' },
                { label: 'Prenotazioni ultimi 7 giorni',data: resLast7, backgroundColor: 'rgba(240,68,56,0.6)' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
@endif
@endpush
