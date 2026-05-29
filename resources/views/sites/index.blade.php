@extends('layouts.app')

@section('content')

    {{-- Page header --}}
    <div class="actions" style="justify-content: space-between; margin-bottom: 18px;">
        <div>
            <h1 style="margin-bottom: 4px;">Dashboard</h1>
            <div class="muted" style="font-size: 13px;">Ultimo periodo sincronizzato: <strong>{{ $kpis['period_label'] }}</strong></div>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('sync.all') }}">
                @csrf
                <button class="btn" type="submit">Sync tutti i siti attivi</button>
            </form>
            <a class="btn primary" href="{{ route('sites.create') }}">Nuovo sito</a>
        </div>
    </div>

    {{-- Section A: KPI globali --}}
    <div class="grid" style="margin-bottom: 20px;">
        <div class="metric">
            <span class="muted">Siti attivi</span>
            <strong>{{ $kpis['active_count'] }}</strong>
        </div>
        <div class="metric">
            <span class="muted">Ordini oggi</span>
            <strong>{{ number_format($kpis['orders_today']) }}</strong>
            @if(! $kpis['has_today_data'])
                <div class="muted" style="font-size: 11px; margin-top: 4px;">In attesa snapshot V2</div>
            @endif
        </div>
        <div class="metric">
            <span class="muted">Prenotazioni oggi</span>
            <strong>{{ number_format($kpis['reservations_today']) }}</strong>
            @if(! $kpis['has_today_data'])
                <div class="muted" style="font-size: 11px; margin-top: 4px;">In attesa snapshot V2</div>
            @endif
        </div>
        <div class="metric">
            <span class="muted">Ordini 7 giorni</span>
            <strong>{{ number_format($kpis['orders_last_7']) }}</strong>
        </div>
        <div class="metric">
            <span class="muted">Ordini periodo snapshot</span>
            <strong>{{ number_format($kpis['orders_total']) }}</strong>
        </div>
        <div class="metric">
            <span class="muted">Ricavi periodo snapshot</span>
            <strong>€ {{ number_format($kpis['revenue_total']) }}</strong>
            <div class="muted" style="font-size: 11px; margin-top: 4px;">Solo dashboard con revenue_unit=euros</div>
            @if($kpis['revenue_unavailable_count'] > 0)
                <div class="muted" style="font-size: 11px;">{{ $kpis['revenue_unavailable_count'] }} {{ $kpis['revenue_unavailable_count'] === 1 ? 'sito' : 'siti' }} con revenue non disponibile</div>
            @endif
        </div>
        <div class="metric" style="{{ $kpis['sites_with_failures'] > 0 ? 'border-color: #fecdca; background: #fff8f8;' : '' }}">
            <span class="muted">Siti con problemi sync</span>
            <strong style="{{ $kpis['sites_with_failures'] > 0 ? 'color: #b42318;' : '' }}">{{ $kpis['sites_with_failures'] }}</strong>
        </div>
    </div>

    {{-- Section B: Confronto rapido siti --}}
    @php
        $chartSites   = $sites->filter(fn($s) => $s->latestSnapshot !== null)->values();
        $hasBarData   = $chartSites->contains(
            fn($s) => ($s->latestSnapshot->orders_today ?? 0) > 0
                   || ($s->latestSnapshot->orders_last_7_days ?? 0) > 0
        );
    @endphp
    <h2>Confronto rapido siti</h2>
    @if($chartSites->isNotEmpty() && $hasBarData)
        <div class="panel" style="margin-bottom: 18px;">
            <canvas id="chartSites" style="max-height: 260px;"></canvas>
        </div>
    @else
        <div class="panel muted" style="margin-bottom: 18px; font-size: 13px;">
            Dati grafico disponibili dopo il primo snapshot V2 con periods.
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
                                @if($item['last_activity'])
                                    {{ \Carbon\Carbon::parse($item['last_activity'])->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @switch($item['reason'])
                                    @case('no_snapshot')
                                        <span style="color: #b42318; font-size: 13px;">Nessuno snapshot disponibile</span>
                                        @break
                                    @case('no_v2')
                                        <span style="color: #667085; font-size: 13px;">Snapshot non aggiornato a V2</span>
                                        @break
                                    @case('no_menu_activity')
                                        <span style="color: #b45309; font-size: 13px;">Nessun aggiornamento menu registrato</span>
                                        @break
                                    @case('stale_menu')
                                        <span style="color: #b45309; font-size: 13px;">Ultima attività menu oltre 30 giorni fa</span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                <a class="btn" href="{{ route('sites.show', $item['site']) }}">Dettaglio</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Section D: Tabella siti --}}
    <div class="table-wrap" style="margin-bottom: 16px;">
        <table>
            <thead>
                <tr>
                    <th>Sito</th>
                    <th>Pack</th>
                    <th>Ordini oggi</th>
                    <th>Ordini 7gg</th>
                    <th>Ricavi periodo</th>
                    <th>Prenotazioni oggi</th>
                    <th>Attività menu</th>
                    <th>Periodo snapshot</th>
                    <th>Ultima sync riuscita</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    @php
                        $snap = $site->latestSnapshot;

                        // Determine row status
                        $hasSnapshot = $snap !== null && $site->last_success_at !== null;
                        $hasFailures = $site->consecutive_failures > 0;

                        if (! $hasSnapshot) {
                            $statusColor = '#b42318';
                            $statusBg    = '#fef3f2';
                            $statusText  = 'Nessun dato';
                        } elseif ($hasFailures) {
                            $statusColor = '#93370d';
                            $statusBg    = '#fffaeb';
                            $statusText  = 'Problemi (' . $site->consecutive_failures . ')';
                        } else {
                            $statusColor = '#027a48';
                            $statusBg    = '#ecfdf3';
                            $statusText  = 'OK';
                        }

                        // Revenue display
                        if (! $snap) {
                            $revenueDisplay = '-';
                        } elseif ($snap->revenue_unit !== 'euros' || $snap->orders_revenue === null) {
                            $revenueDisplay = 'N/D';
                        } else {
                            $revenueDisplay = '€ ' . number_format($snap->orders_revenue);
                        }

                        // Data piu recente di attivita menu (solo payload V2 con usage.menu).
                        $menuDates = array_filter([
                            $snap?->payload['usage']['menu']['last_product_updated_at'] ?? null,
                            $snap?->payload['usage']['menu']['last_category_updated_at'] ?? null,
                            $snap?->payload['usage']['menu']['last_ingredient_updated_at'] ?? null,
                        ]);
                        $lastMenuActivity = ! empty($menuDates)
                            ? \Carbon\Carbon::parse(max($menuDates))->format('d/m/Y')
                            : null;
                    @endphp
                    <tr>
                        {{-- Sito --}}
                        <td>
                            <a href="{{ route('sites.show', $site) }}"><strong>{{ $site->name }}</strong></a>
                            <div class="muted" style="font-size: 12px;">
                                <a href="{{ $site->url }}" target="_blank" rel="noopener noreferrer" style="color: var(--muted);">{{ $site->url }}</a>
                            </div>
                        </td>

                        {{-- Pack --}}
                        <td>{{ $site->pack ?? '-' }}</td>

                        {{-- Ordini oggi --}}
                        <td>{{ $snap ? ($snap->orders_today ?? 'N/D') : '-' }}</td>

                        {{-- Ordini 7gg --}}
                        <td>{{ $snap ? ($snap->orders_last_7_days ?? 'N/D') : '-' }}</td>

                        {{-- Ricavi periodo --}}
                        <td>{{ $revenueDisplay }}</td>

                        {{-- Prenotazioni oggi --}}
                        <td>{{ $snap ? ($snap->reservations_today ?? 'N/D') : '-' }}</td>

                        {{-- Attivita menu --}}
                        <td>
                            @if($lastMenuActivity)
                                {{ $lastMenuActivity }}
                            @else
                                <span class="muted">N/D</span>
                            @endif
                        </td>

                        {{-- Periodo snapshot --}}
                        <td>
                            @if($snap && $snap->period_from && $snap->period_to)
                                <span style="white-space: nowrap;">{{ $snap->period_from->toDateString() }}</span>
                                <span class="muted">→</span>
                                <span style="white-space: nowrap;">{{ $snap->period_to->toDateString() }}</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Ultima sync riuscita --}}
                        <td>
                            @if($site->last_success_at)
                                {{ $site->last_success_at->format('d/m/Y H:i') }}
                            @else
                                <span class="muted">Mai</span>
                            @endif
                        </td>

                        {{-- Stato --}}
                        <td>
                            <span style="
                                display: inline-block;
                                padding: 3px 8px;
                                border-radius: 999px;
                                font-size: 12px;
                                font-weight: 600;
                                color: {{ $statusColor }};
                                background: {{ $statusBg }};
                            ">{{ $statusText }}</span>
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
                        <td colspan="11" class="muted" style="text-align: center; padding: 28px;">
                            Nessun sito configurato. <a href="{{ route('sites.create') }}">Aggiungine uno</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Section C: Alert errori --}}
    @if($kpis['sites_with_failures'] > 0)
        <div class="panel" style="border-color: #fecdca; background: #fef3f2; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="color: #b42318;">
                <strong>{{ $kpis['sites_with_failures'] }} {{ $kpis['sites_with_failures'] === 1 ? 'sito ha' : 'siti hanno' }} errori di sincronizzazione</strong>
            </div>
            <a href="{{ route('sync-errors.index') }}" style="color: #b42318; white-space: nowrap;">
                Vedi tutti gli errori di sincronizzazione &rarr;
            </a>
        </div>
    @else
        <div style="text-align: right; font-size: 13px; margin-top: 4px;">
            <a href="{{ route('sync-errors.index') }}" class="muted">Vedi log errori di sincronizzazione &rarr;</a>
        </div>
    @endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@if($chartSites->isNotEmpty() && $hasBarData)
<script>
(function () {
    const labels     = @json($chartSites->pluck('name'));
    const today      = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->orders_today ?? 0)));
    const last7      = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->orders_last_7_days ?? 0)));
    const resToday   = @json($chartSites->map(fn($s) => (int) ($s->latestSnapshot->reservations_today ?? 0)));

    new Chart(document.getElementById('chartSites'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Ordini oggi',
                    data: today,
                    backgroundColor: 'rgba(21,94,239,0.75)',
                },
                {
                    label: 'Ordini ultimi 7 giorni',
                    data: last7,
                    backgroundColor: 'rgba(3,152,85,0.75)',
                },
                {
                    label: 'Prenotazioni oggi',
                    data: resToday,
                    backgroundColor: 'rgba(122,90,248,0.75)',
                }
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
