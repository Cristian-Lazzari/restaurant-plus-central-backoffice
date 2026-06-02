@extends('layouts.app')

@section('content')
    @php
        $snapshot = $site->latestSnapshot;
        $payload  = $snapshot?->payload ?? [];
        $warnings = $snapshot?->data_warnings ?? [];
    @endphp

    {{-- Page header --}}
    <div class="actions" style="justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="margin-bottom: 6px;">
                <a href="{{ route('dashboard') }}" class="muted" style="font-size: 13px;">&larr; Dashboard</a>
            </div>
            <h1 style="margin-bottom: 4px;">{{ $site->name }}</h1>
            <div class="muted">
                <a href="{{ $site->url }}" target="_blank" rel="noopener noreferrer" style="color: var(--muted);">{{ $site->url }}</a>
            </div>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('sites.edit', $site) }}">Modifica</a>
            <form method="POST" action="{{ route('sites.toggle', $site) }}">
                @csrf
                <button class="btn" type="submit">{{ $site->active ? 'Disattiva' : 'Attiva' }}</button>
            </form>
        </div>
    </div>

    {{-- Sezione 1+2: Dati business con filtro periodo --}}
    @php
        $hasPeriods = is_array($payload['periods'] ?? null) && ! empty($payload['periods']);
        $revUnit    = $snapshot?->revenue_unit ?? 'unknown';
    @endphp
    <h2 style="margin-top: 0;">Dati business</h2>

    @if($snapshot)
        @if($hasPeriods)
            {{-- V2: selettore periodo dinamico --}}
            <div class="panel" style="margin-bottom: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="periodSelect" style="margin: 0; font-weight: 600; white-space: nowrap;">Periodo:</label>
                    <select id="periodSelect" class="btn" style="cursor: pointer; padding: 6px 10px;">
                        <option value="today">Oggi</option>
                        <option value="last_7_days">Ultimi 7 giorni</option>
                        <option value="last_30_days">Ultimi 30 giorni</option>
                        <option value="current_month" selected>Mese corrente</option>
                        <option value="current_year">Anno corrente</option>
                        <option value="all_time">Storico</option>
                    </select>
                </div>
                <div class="muted" style="font-size: 13px;">
                    Da: <strong id="periodFrom">-</strong> &ndash; a: <strong id="periodTo">-</strong>
                </div>
            </div>

            <div class="grid" style="margin-bottom: 12px;">
                <div class="metric">
                    <span class="muted">Ordini</span>
                    <strong id="periodOrders">-</strong>
                </div>
                <div class="metric">
                    <span class="muted">Ricavi</span>
                    <strong id="periodRevenue">-</strong>
                    @if($revUnit !== 'euros')
                        <div class="muted" style="font-size: 11px; margin-top: 2px;">revenue_unit = {{ $revUnit }}</div>
                    @endif
                </div>
                <div class="metric">
                    <span class="muted">Media ordine</span>
                    <strong id="periodAverage">-</strong>
                </div>
                <div class="metric">
                    <span class="muted">Prenotazioni</span>
                    <strong id="periodReservations">-</strong>
                </div>
                <div class="metric">
                    <span class="muted">Coperti</span>
                    <strong id="periodCovers">-</strong>
                </div>
            </div>
        @else
            {{-- V1: valori statici dall'ultimo snapshot --}}
            <div class="grid" style="margin-bottom: 12px;">
                <div class="metric">
                    <span class="muted">Ordini</span>
                    <strong>{{ number_format($snapshot->orders_total ?? 0) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Ricavi</span>
                    @if($revUnit !== 'euros' || $snapshot->orders_revenue === null)
                        <strong>N/D</strong>
                        <div class="muted" style="font-size: 12px;">revenue_unit = {{ $revUnit }}</div>
                    @else
                        <strong>€ {{ number_format($snapshot->orders_revenue, 2) }}</strong>
                        <div class="muted" style="font-size: 12px;">revenue_unit = euros</div>
                    @endif
                </div>
                <div class="metric">
                    <span class="muted">Prenotazioni</span>
                    <strong>{{ number_format($snapshot->reservations_total ?? 0) }}</strong>
                </div>
                <div class="metric">
                    <span class="muted">Coperti</span>
                    <strong>{{ number_format($snapshot->reservations_covers ?? 0) }}</strong>
                </div>
            </div>
            <div class="panel muted" style="font-size: 13px; margin-bottom: 12px; padding: 8px 12px;">
                Filtro periodo disponibile dopo snapshot api_version=2.
            </div>
        @endif

        <div class="panel muted" style="font-size: 13px; margin-bottom: 18px; padding: 10px 14px;">
            Snapshot: periodo
            <strong>{{ $snapshot->period_from?->toDateString() ?? '-' }}</strong>
            &ndash;
            <strong>{{ $snapshot->period_to?->toDateString() ?? '-' }}</strong>,
            recuperato il {{ $snapshot->fetched_at?->format('d/m/Y H:i') ?? '-' }}
        </div>

        @if(is_array($warnings) && count($warnings) > 0)
            <div class="panel" style="border-color: #fedf89; background: #fffaeb; margin-bottom: 18px;">
                <strong style="color: #93370d;">Attenzione: data warnings presenti</strong>
                <ul style="margin: 8px 0 0; padding-left: 18px; color: #93370d;">
                    @foreach($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @else
        <div class="panel muted" style="text-align: center; padding: 28px; margin-bottom: 18px;">
            Nessun snapshot ancora. Esegui una sync per raccogliere i dati.
        </div>
    @endif

    {{-- Sezione 3: Attivita gestionale (solo payload V2) --}}
    @php
        // Calcola stato inattività menu per il badge.
        $menuActivityBadge = 'grey'; // default: dato non disponibile
        $menuActivityLabel = 'Dato non disponibile';

        if (isset($payload['usage']['menu'])) {
            $um = $payload['usage']['menu'];
            $menuDatesRaw = array_filter([
                $um['last_product_updated_at']    ?? null,
                $um['last_category_updated_at']   ?? null,
                $um['last_ingredient_updated_at'] ?? null,
            ]);

            if (empty($menuDatesRaw)) {
                $menuActivityBadge = 'yellow';
                $menuActivityLabel = 'Nessun aggiornamento menu registrato';
            } else {
                try {
                    $lastMenuDate = \Carbon\Carbon::parse(max($menuDatesRaw));
                    if ($lastMenuDate->gte(now()->subDays(30))) {
                        $menuActivityBadge = 'green';
                        $menuActivityLabel = 'Attività recente (' . $lastMenuDate->format('d/m/Y') . ')';
                    } else {
                        $menuActivityBadge = 'yellow';
                        $menuActivityLabel = 'Ultima attività: ' . $lastMenuDate->format('d/m/Y') . ' (oltre 30 giorni fa)';
                    }
                } catch (\Throwable $e) {
                    $menuActivityBadge = 'grey';
                }
            }
        }

        $badgeStyle = match($menuActivityBadge) {
            'green'  => 'background: #ecfdf3; border-color: #abefc6; color: #027a48;',
            'yellow' => 'background: #fffaeb; border-color: #fedf89; color: #b45309;',
            default  => 'background: #f2f4f7; border-color: #d9dee7; color: #667085;',
        };
    @endphp
    <h2>
        Attivita gestionale
        <span style="display: inline-block; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; border: 1px solid; margin-left: 8px; vertical-align: middle; {{ $badgeStyle }}">
            {{ $menuActivityLabel }}
        </span>
    </h2>
    @if(isset($payload['usage']))
        @php
            $usageMenu    = $payload['usage']['menu']    ?? [];
            $usageContent = $payload['usage']['content'] ?? [];
            $usageAdmin   = $payload['usage']['admin']   ?? [];
        @endphp
        <div class="grid" style="margin-bottom: 12px;">
            <div class="metric">
                <span class="muted">Prodotti</span>
                <strong>{{ $usageMenu['products_count'] ?? '-' }}</strong>
            </div>
            <div class="metric">
                <span class="muted">Categorie</span>
                <strong>{{ $usageMenu['categories_count'] ?? '-' }}</strong>
            </div>
            <div class="metric">
                <span class="muted">Ingredienti</span>
                <strong>{{ $usageMenu['ingredients_count'] ?? '-' }}</strong>
            </div>
            <div class="metric">
                <span class="muted">Post totali</span>
                <strong>{{ $usageContent['posts_count'] ?? '-' }}</strong>
            </div>
            <div class="metric">
                <span class="muted">Post attivi</span>
                <strong>{{ $usageContent['posts_active'] ?? '-' }}</strong>
            </div>
            <div class="metric">
                <span class="muted">Promo attive</span>
                <strong>{{ $usageContent['posts_promo'] ?? '-' }}</strong>
            </div>
        </div>

        <div class="table-wrap" style="margin-bottom: 18px;">
            <table>
                <thead>
                    <tr>
                        <th>Elemento</th>
                        <th>Ultimo aggiornamento</th>
                        <th>Aggiornati 7gg</th>
                        <th>Aggiornati 30gg</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Prodotti</td>
                        <td class="muted">{{ !empty($usageMenu['last_product_updated_at']) ? \Carbon\Carbon::parse($usageMenu['last_product_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $usageMenu['products_updated_last_7_days'] ?? '-' }}</td>
                        <td>{{ $usageMenu['products_updated_last_30_days'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Categorie</td>
                        <td class="muted">{{ !empty($usageMenu['last_category_updated_at']) ? \Carbon\Carbon::parse($usageMenu['last_category_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $usageMenu['categories_updated_last_7_days'] ?? '-' }}</td>
                        <td>{{ $usageMenu['categories_updated_last_30_days'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Ingredienti</td>
                        <td class="muted">{{ !empty($usageMenu['last_ingredient_updated_at']) ? \Carbon\Carbon::parse($usageMenu['last_ingredient_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $usageMenu['ingredients_updated_last_7_days'] ?? '-' }}</td>
                        <td>{{ $usageMenu['ingredients_updated_last_30_days'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Post</td>
                        <td class="muted">{{ !empty($usageContent['last_post_updated_at']) ? \Carbon\Carbon::parse($usageContent['last_post_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $usageContent['posts_updated_last_7_days'] ?? '-' }}</td>
                        <td>{{ $usageContent['posts_updated_last_30_days'] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(! empty($usageAdmin['last_admin_login_at']))
            <div class="panel muted" style="font-size: 13px; margin-bottom: 18px;">
                Ultimo accesso admin: <strong>{{ \Carbon\Carbon::parse($usageAdmin['last_admin_login_at'])->format('d/m/Y H:i') }}</strong>
            </div>
        @endif
    @else
        <div class="panel muted" style="margin-bottom: 18px; font-size: 13px;">
            Dati usage disponibili dopo aggiornamento dashboard a api_version=2.
        </div>
    @endif

    {{-- Sezione 4: Grafici andamento ultimi 30 giorni (solo payload V2) --}}
    @php
        $daily        = is_array($payload['daily'] ?? null) ? $payload['daily'] : [];
        $hasChartData = count($daily) > 0;
    @endphp
    <h2>Andamento ultimi 30 giorni</h2>
    @if($hasChartData)
        <div class="panel" style="margin-bottom: 12px;">
            <div class="muted" style="font-size: 12px; margin-bottom: 10px;">Ordini per giorno</div>
            <canvas id="chartOrders" style="max-height: 200px;"></canvas>
        </div>
        <div class="panel" style="margin-bottom: 12px;">
            <div class="muted" style="font-size: 12px; margin-bottom: 10px;">Ricavi per giorno (€)</div>
            <canvas id="chartRevenue" style="max-height: 200px;"></canvas>
        </div>
        <div class="panel" style="margin-bottom: 18px;">
            <div class="muted" style="font-size: 12px; margin-bottom: 10px;">Prenotazioni e coperti per giorno</div>
            <canvas id="chartReservations" style="max-height: 200px;"></canvas>
        </div>
    @else
        <div class="panel muted" style="margin-bottom: 18px; font-size: 13px;">
            Grafici disponibili dopo uno snapshot api_version=2 con dati giornalieri.
        </div>
    @endif

    {{-- Sezione 4b: Daily raw (collassabile, solo payload V2) --}}
    <details style="margin-bottom: 18px;">
        <summary style="cursor: pointer; font-size: 16px; font-weight: 700; padding: 10px 0; user-select: none; list-style: none;">
            &#9654; Andamento giornaliero (ultimi 30 giorni)
        </summary>
        @if(! empty($payload['daily']))
            <div class="table-wrap" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Ordini</th>
                            <th>Ricavi</th>
                            <th>Prenotazioni</th>
                            <th>Coperti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_reverse($payload['daily']) as $day)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }}</td>
                                <td>{{ $day['orders'] ?? 0 }}</td>
                                <td>{{ !empty($day['revenue']) ? '€ ' . number_format($day['revenue'], 2) : '-' }}</td>
                                <td>{{ $day['reservations'] ?? 0 }}</td>
                                <td>{{ $day['covers'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="muted" style="margin-top: 10px; font-size: 13px; padding: 10px 0;">
                Dati giornalieri disponibili dopo aggiornamento dashboard a api_version=2.
            </div>
        @endif
    </details>

    {{-- Sezione 5: Info sito --}}
    <h2>Info sito</h2>
    <div class="grid" style="margin-bottom: 18px;">
        <div class="metric">
            <span class="muted">Pack</span>
            <strong>{{ $site->pack ?? '-' }}</strong>
        </div>
        <div class="metric">
            <span class="muted">Stato</span>
            <strong>
                <span class="badge {{ $site->active ? '' : 'off' }}">{{ $site->active ? 'Attivo' : 'Inattivo' }}</span>
            </strong>
        </div>
        <div class="metric">
            <span class="muted">Ultima sync riuscita</span>
            <strong>{{ $site->last_success_at?->format('d/m/Y H:i') ?? 'Mai' }}</strong>
        </div>
        <div class="metric" style="{{ $site->consecutive_failures > 0 ? 'border-color: #fecdca; background: #fff8f8;' : '' }}">
            <span class="muted">Failures consecutive</span>
            <strong style="{{ $site->consecutive_failures > 0 ? 'color: #b42318;' : '' }}">{{ $site->consecutive_failures }}</strong>
        </div>
        @if($snapshot)
            <div class="metric">
                <span class="muted">Response time ultimo snapshot</span>
                <strong>{{ $snapshot->response_time_ms ?? '-' }} ms</strong>
            </div>
            <div class="metric">
                <span class="muted">HTTP status ultimo snapshot</span>
                <strong>{{ $snapshot->http_status_code ?? '-' }}</strong>
            </div>
        @endif
    </div>

    @if($site->notes)
        <div class="panel muted" style="font-size: 13px; margin-bottom: 18px;">
            <strong style="color: var(--ink);">Note</strong><br>
            {{ $site->notes }}
        </div>
    @endif

    {{-- Sezione 6: Sync manuale --}}
    <h2>Sincronizzazione manuale</h2>
    <div class="panel" style="margin-bottom: 18px;">
        <form method="POST" action="{{ route('sites.sync', $site) }}">
            @csrf
            <div class="actions" style="flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div class="field" style="margin: 0;">
                    <label for="from">Da</label>
                    <input type="date" id="from" name="from" value="{{ old('from') }}" style="width: auto; min-width: 150px;">
                </div>
                <div class="field" style="margin: 0;">
                    <label for="to">A</label>
                    <input type="date" id="to" name="to" value="{{ old('to') }}" style="width: auto; min-width: 150px;">
                </div>
                <button class="btn primary" type="submit">Esegui sync</button>
            </div>
            <div class="muted" style="font-size: 12px; margin-top: 8px;">Lascia vuoti per usare il periodo di default del servizio.</div>
        </form>
    </div>

    {{-- Sezione 8: Errori sync (collassabile) --}}
    <details style="margin-bottom: 18px;">
        <summary style="cursor: pointer; font-size: 16px; font-weight: 700; padding: 10px 0; user-select: none; list-style: none;">
            &#9654; Errori di sincronizzazione ({{ $site->syncErrors->count() }})
        </summary>
        <div style="margin-top: 12px;">
            @if($site->syncErrors->count() > 0)
                <div class="table-wrap" style="margin-bottom: 10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Codice</th>
                                <th>HTTP</th>
                                <th>Messaggio</th>
                                <th>Failures al momento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($site->syncErrors as $error)
                                <tr>
                                    <td style="white-space: nowrap;">{{ $error->occurred_at?->format('d/m/Y H:i') ?? $error->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        @php
                                            $codeBadgeStyle = match($error->code ?? '') {
                                                'TIMEOUT'      => 'color: #93370d; background: #fffaeb;',
                                                'HTTP_ERROR'   => 'color: #7a2e0e; background: #fff4ed;',
                                                'INVALID_JSON' => 'color: #b42318; background: #fef3f2;',
                                                'EXCEPTION'    => 'color: #6b2737; background: #fff1f3;',
                                                default        => 'color: #475467; background: #f2f4f7;',
                                            };
                                        @endphp
                                        <span style="display:inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; {{ $codeBadgeStyle }}">
                                            {{ $error->code ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ $error->http_status_code ?? '-' }}</td>
                                    <td>{{ $error->message }}</td>
                                    <td>{{ $error->consecutive_failures }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('sync-errors.index', ['site_id' => $site->id]) }}" style="font-size: 13px;">
                    Vedi tutti gli errori di questo sito &rarr;
                </a>
            @else
                <div class="panel muted" style="text-align: center; padding: 20px;">Nessun errore di sincronizzazione.</div>
            @endif
        </div>
    </details>

    {{-- Sezione 9: Payload grezzo (collassabile, chiuso di default) --}}
    @if($snapshot)
        <details style="margin-bottom: 18px;">
            <summary style="cursor: pointer; font-size: 16px; font-weight: 700; padding: 10px 0; user-select: none; list-style: none;">
                &#9654; Payload grezzo (JSON)
            </summary>
            <div style="margin-top: 12px;">
                <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </details>
    @endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@if($hasPeriods)
<script>
(function () {
    const periods    = @json($payload['periods']);
    const revUnit    = @json($revUnit);
    const select     = document.getElementById('periodSelect');
    const elFrom     = document.getElementById('periodFrom');
    const elTo       = document.getElementById('periodTo');
    const elOrders   = document.getElementById('periodOrders');
    const elRevenue  = document.getElementById('periodRevenue');
    const elAverage  = document.getElementById('periodAverage');
    const elRes      = document.getElementById('periodReservations');
    const elCovers   = document.getElementById('periodCovers');

    function fmtN(n) {
        if (n === null || n === undefined) return 'N/D';
        return new Intl.NumberFormat('it-IT').format(n);
    }
    function fmtEur(n) {
        if (n === null || n === undefined || revUnit !== 'euros') return 'N/D';
        return '€ ' + new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    }

    function update(key) {
        const p = periods[key];
        if (!p) return;
        elFrom.textContent         = p.from  || '-';
        elTo.textContent           = p.to    || '-';
        elOrders.textContent       = fmtN(p.orders_total);
        elRevenue.textContent      = fmtEur(p.orders_revenue);
        elAverage.textContent      = fmtEur(p.orders_average);
        elRes.textContent          = fmtN(p.reservations_total);
        elCovers.textContent       = fmtN(p.reservations_covers);
    }

    select.addEventListener('change', function () { update(this.value); });
    update(select.value); // init con current_month (selected by default)
})();
</script>
@endif

@if($hasChartData)
<script>
(function () {
    const daily  = @json($daily);
    const labels = daily.map(d => d.date);
    const def    = { tension: 0.35, pointRadius: 2, pointHoverRadius: 5, fill: true };

    new Chart(document.getElementById('chartOrders'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                ...def,
                label: 'Ordini',
                data: daily.map(d => d.orders ?? 0),
                borderColor: '#155eef',
                backgroundColor: 'rgba(21,94,239,0.08)',
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('chartRevenue'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                ...def,
                label: 'Ricavi (€)',
                data: daily.map(d => d.revenue ?? 0),
                borderColor: '#039855',
                backgroundColor: 'rgba(3,152,85,0.08)',
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartReservations'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    ...def,
                    fill: false,
                    label: 'Prenotazioni',
                    data: daily.map(d => d.reservations ?? 0),
                    borderColor: '#7a5af8',
                    backgroundColor: 'rgba(122,90,248,0.08)',
                },
                {
                    ...def,
                    fill: false,
                    label: 'Coperti',
                    data: daily.map(d => d.covers ?? 0),
                    borderColor: '#f04438',
                    backgroundColor: 'rgba(240,68,56,0.08)',
                }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
})();
</script>
@endif
@endpush
