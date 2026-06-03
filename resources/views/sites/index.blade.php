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
                    @if($kpis['orders_monthly_avg'] !== null)
                        <strong>{{ number_format($kpis['orders_monthly_avg']) }}</strong>
                    @else
                        <strong class="muted" style="font-size:14px;">—</strong>
                        <div class="muted" style="font-size:11px;margin-top:2px;">Sync per aggiornare</div>
                    @endif
                </div>
                <div class="metric">
                    <span class="muted">Ricavi totali</span>
                    <strong>€ {{ number_format($kpis['revenue_all_time'], 2) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Media mensile ricavi</span>
                    @if($kpis['revenue_monthly_avg'] !== null)
                        <strong>€ {{ number_format($kpis['revenue_monthly_avg'], 2) }}</strong>
                    @else
                        <strong class="muted" style="font-size:14px;">—</strong>
                        <div class="muted" style="font-size:11px;margin-top:2px;">Sync per aggiornare</div>
                    @endif
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
                    @if($kpis['reservations_monthly_avg'] !== null)
                        <strong>{{ number_format($kpis['reservations_monthly_avg']) }}</strong>
                    @else
                        <strong class="muted" style="font-size:14px;">—</strong>
                        <div class="muted" style="font-size:11px;margin-top:2px;">Sync per aggiornare</div>
                    @endif
                </div>
                <div class="metric">
                    <span class="muted">Coperti totali</span>
                    <strong>{{ number_format($kpis['covers_all_time']) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Media mensile coperti</span>
                    @if($kpis['covers_monthly_avg'] !== null)
                        <strong>{{ number_format($kpis['covers_monthly_avg']) }}</strong>
                    @else
                        <strong class="muted" style="font-size:14px;">—</strong>
                        <div class="muted" style="font-size:11px;margin-top:2px;">Sync per aggiornare</div>
                    @endif
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
    <div class="actions" style="justify-content: space-between; margin-top: 28px; margin-bottom: 12px;">
        <h2 style="margin: 0;">Siti</h2>
        @if($sites->count() > 1)
            <form id="siteOrderForm" method="POST" action="{{ route('sites.reorder') }}">
                @csrf
                <button id="saveSiteOrder" class="btn" type="submit" disabled>Salva ordine</button>
            </form>
        @endif
    </div>
    <div class="panel muted" style="font-size: 13px; margin-bottom: 12px; padding: 10px 14px;">
        Ultimo sync registrato:
        <strong style="color: var(--ink);">{{ $lastGlobalSyncAt ? $lastGlobalSyncAt->format('d/m/Y H:i') : 'Mai' }}</strong>
    </div>
    <div class="table-wrap" style="margin-bottom: 16px;">
        <table>
            <thead>
                <tr>
                    <th style="width: 88px;">Ordine</th>
                    <th>Sito</th>
                    <th>Ordini<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Ricavi<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Prenotazioni<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Coperti<br><span style="font-weight:400;font-size:11px;">totale / media mese</span></th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="sitesTableBody">
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
                    @endphp
                    <tr data-site-row data-site-id="{{ $site->id }}" draggable="{{ $sites->count() > 1 ? 'true' : 'false' }}">
                        {{-- Ordine --}}
                        <td>
                            <div class="actions" style="gap: 4px; flex-wrap: nowrap;">
                                <button class="btn site-order-btn" type="button" data-move="up" title="Sposta su" aria-label="Sposta {{ $site->name }} su" style="padding: 5px 8px;" @disabled($sites->count() <= 1)>↑</button>
                                <button class="btn site-order-btn" type="button" data-move="down" title="Sposta giu" aria-label="Sposta {{ $site->name }} giu" style="padding: 5px 8px;" @disabled($sites->count() <= 1)>↓</button>
                            </div>
                        </td>

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
                                @if($avgOrd !== null)
                                    <div class="muted" style="font-size:12px;">~{{ number_format($avgOrd) }}/mese</div>
                                @else
                                    <div class="muted" style="font-size:11px;">Sync per aggiornare media</div>
                                @endif
                            @elseif($hasAllTime)
                                <span class="muted" style="font-size:12px;">Non usa ordini</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Ricavi --}}
                        <td>
                            @if($siteRev !== null && $siteRev > 0)
                                <strong>€ {{ number_format($siteRev, 2) }}</strong>
                                @if($avgRev !== null)
                                    <div class="muted" style="font-size:12px;">€ {{ number_format($avgRev, 2) }}/mese</div>
                                @else
                                    <div class="muted" style="font-size:11px;">Sync per aggiornare media</div>
                                @endif
                            @elseif($hasAllTime)
                                <span class="muted" style="font-size:12px;">—</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Prenotazioni --}}
                        <td>
                            @if($siteRes > 0)
                                <strong>{{ number_format($siteRes) }}</strong>
                                @if($avgRes !== null)
                                    <div class="muted" style="font-size:12px;">~{{ number_format($avgRes) }}/mese</div>
                                @else
                                    <div class="muted" style="font-size:11px;">Sync per aggiornare media</div>
                                @endif
                            @elseif($hasAllTime)
                                <span class="muted" style="font-size:12px;">Non usa prenotazioni</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>

                        {{-- Coperti --}}
                        <td>
                            @if($siteCov > 0)
                                <strong>{{ number_format($siteCov) }}</strong>
                                @if($avgCov !== null)
                                    <div class="muted" style="font-size:12px;">~{{ number_format($avgCov) }}/mese</div>
                                @else
                                    <div class="muted" style="font-size:11px;">Sync per aggiornare media</div>
                                @endif
                            @elseif($hasAllTime)
                                <span class="muted" style="font-size:12px;">—</span>
                            @else
                                <span class="muted">-</span>
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
@if($sites->count() > 1)
<script>
(function () {
    const tbody = document.getElementById('sitesTableBody');
    const form = document.getElementById('siteOrderForm');
    const saveButton = document.getElementById('saveSiteOrder');
    let draggedRow = null;

    if (!tbody || !form || !saveButton) {
        return;
    }

    function rows() {
        return Array.from(tbody.querySelectorAll('tr[data-site-id]'));
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
        updateHiddenInputs();
        updateButtonStates();
        saveButton.disabled = false;
    }

    tbody.addEventListener('click', function (event) {
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
    updateHiddenInputs();
    updateButtonStates();
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
