@extends('layouts.app')

@section('content')
    @php
        $snapshot = $site->latestSnapshot;
        $payload  = $snapshot?->payload ?? [];
        $warnings = $snapshot?->data_warnings ?? [];
        $monthlyRows = $monthlyTrend['rows'] ?? [];
        $hasMonthlyTrend = count($monthlyRows) > 0;
        $hasBusiness = $usesOrders || $usesReservations;
        $savingsBenchmark = $savingsBenchmark ?? [
            'order_commission_rate' => 0.20,
            'order_commission_percent' => 20,
            'reservation_cover_fee' => 4,
        ];
        $benchmarkOrderRate = $savingsBenchmark['order_commission_rate'] ?? (($savingsBenchmark['order_commission_percent'] ?? 20) / 100);
        $formatBenchmarkNumber = function (float $value): string {
            $decimals = abs($value - round($value)) < 0.005 ? 0 : 2;

            return number_format($value, $decimals, ',', '.');
        };
        $benchmarkOrderPercent = $formatBenchmarkNumber((float) ($savingsBenchmark['order_commission_percent'] ?? ($benchmarkOrderRate * 100)));
        $benchmarkCoverFee = $formatBenchmarkNumber((float) ($savingsBenchmark['reservation_cover_fee'] ?? 4));
        $formatPercent = function (?float $percent): string {
            if ($percent === null) {
                return '-';
            }

            $decimals = abs($percent - round($percent)) < 0.05 ? 0 : 1;

            return ($percent > 0 ? '+' : '') . number_format($percent, $decimals, ',', '.') . '%';
        };
        $deltaBadge = function (array $change) use ($formatPercent): array {
            $state = $change['state'] ?? 'none';
            $percent = $change['percent'] ?? null;

            return match ($state) {
                'up' => [
                    'label' => $formatPercent($percent),
                    'style' => 'color:#027a48;background:#ecfdf3;border-color:#abefc6;',
                ],
                'down' => [
                    'label' => $formatPercent($percent),
                    'style' => 'color:#b42318;background:#fef3f2;border-color:#fecdca;',
                ],
                'flat' => [
                    'label' => '0%',
                    'style' => 'color:#475467;background:#f2f4f7;border-color:#d9dee7;',
                ],
                'new' => [
                    'label' => 'Nuovo',
                    'style' => 'color:#155eef;background:#eef4ff;border-color:#b2ccff;',
                ],
                default => [
                    'label' => 'Primo mese',
                    'style' => 'color:#667085;background:#f8fafc;border-color:#d9dee7;',
                ],
            };
        };
    @endphp

    <style>
        .collapsible { border: 1px solid var(--border-soft); border-radius: var(--radius); background: var(--surface); margin-bottom: 16px; box-shadow: var(--shadow-sm); }
        .collapsible summary { display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; cursor: pointer; font-weight: 700; font-size: 14px; list-style: none; user-select: none; }
        .collapsible summary::-webkit-details-marker { display: none; }
        .collapsible summary svg { transition: transform 0.2s; flex-shrink: 0; }
        details[open] .collapsible summary svg { transform: rotate(90deg); }
        .collapsible-body { padding: 0 16px 16px; border-top: 1px solid var(--border-soft); }
        .period-selector { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 12px 16px; border: 1px solid var(--border-soft); border-radius: var(--radius); background: var(--surface); margin-bottom: 12px; box-shadow: var(--shadow-sm); }
        .delta-badge { display: inline-block; padding: 2px 8px; border: 1px solid; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .show-metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 16px; }
        .show-metric { border: 1px solid var(--border-soft); border-radius: var(--radius); padding: 14px 16px; background: var(--surface); box-shadow: var(--shadow-sm); }
        .show-metric span { display: block; color: var(--muted); font-size: 12px; }
        .show-metric strong { display: block; font-size: 20px; line-height: 1.2; margin-top: 4px; font-weight: 760; }
        .site-url-link { word-break: break-all; overflow-wrap: anywhere; }
        .page-header-actions { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
        @media (max-width: 600px) {
            .show-metric-grid { grid-template-columns: repeat(2, 1fr); }
            .page-header-actions { flex-direction: column; align-items: stretch; }
            .page-header-actions .actions { flex-direction: row; }
            .page-header-actions .actions .btn,
            .page-header-actions .actions form { flex: 1; }
            .page-header-actions .actions form .btn { width: 100%; }
            .period-selector { flex-direction: column; align-items: flex-start; }
        }
    </style>

    {{-- Section: Breadcrumb + Page header --}}
    <div class="page-header">
        <nav class="breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <span>{{ $site->name }}</span>
        </nav>

        <div class="page-header-actions">
            <div style="min-width: 0; flex: 1;">
                <h1 class="page-title">{{ $site->name }}</h1>
                <div class="page-subtitle">
                    <a href="{{ $site->url }}" target="_blank" rel="noopener noreferrer" class="site-url-link" style="color: var(--muted);">{{ $site->url }}</a>
                </div>
            </div>
            <div class="actions" style="flex-shrink: 0;">
                <a class="btn" href="{{ route('sites.edit', $site) }}">{{ __('Modifica') }}</a>
                <form method="POST" action="{{ route('sites.toggle', $site) }}">
                    @csrf
                    <button class="btn {{ $site->active ? 'btn-danger' : 'btn-primary' }}" type="submit">
                        {{ $site->active ? __('Disattiva') : __('Attiva') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Section: Dati business --}}
    @php
        $hasPeriods = is_array($payload['periods'] ?? null) && ! empty($payload['periods']);
        $revUnit    = $snapshot?->revenue_unit ?? 'unknown';
    @endphp

    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Dati business') }}</h2>
    </div>

    @if($snapshot)
        @if(! $hasBusiness)
            <div class="panel text-muted mb-4" style="font-size: 13px;">
                {{ __('Nessun ordine o prenotazione registrati per questo sito.') }}
            </div>
        @elseif($hasPeriods)
            {{-- V2: selettore periodo dinamico --}}
            <div class="period-selector">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="periodSelect" style="margin: 0; font-weight: 600; white-space: nowrap;">{{ __('Periodo:') }}</label>
                    <select id="periodSelect" style="width:auto; border:1px solid var(--border); border-radius:var(--radius-sm); padding:7px 10px; font:inherit; font-size:13px; background:#fff; cursor:pointer;">
                        <option value="current_month" selected>{{ __('Mese corrente') }}</option>
                        <option value="current_year">{{ __('Anno corrente') }}</option>
                        <option value="all_time">{{ __('Storico') }}</option>
                        <option value="today">{{ __('Oggi') }}</option>
                        <option value="last_7_days">{{ __('Ultimi 7 giorni') }}</option>
                    </select>
                </div>
                <div class="text-muted text-sm">
                    {{ __('Da:') }} <strong id="periodFrom">-</strong> &ndash; {{ __('a:') }} <strong id="periodTo">-</strong>
                </div>
            </div>

            <div class="show-metric-grid mb-4">
                @if($usesOrders)
                    <div class="show-metric">
                        <span>{{ __('Ordini') }}</span>
                        <strong id="periodOrders">-</strong>
                    </div>
                    <div class="show-metric">
                        <span>{{ __('Ricavi') }}</span>
                        <strong id="periodRevenue">-</strong>
                        @if($revUnit !== 'euros')
                            <div class="text-muted text-sm" style="margin-top: 2px;">revenue_unit = {{ $revUnit }}</div>
                        @endif
                    </div>
                    <div class="show-metric">
                        <span>{{ __('Media ordine') }}</span>
                        <strong id="periodAverage">-</strong>
                    </div>
                @endif
                @if($usesReservations)
                    <div class="show-metric">
                        <span>{{ __('Prenotazioni') }}</span>
                        <strong id="periodReservations">-</strong>
                    </div>
                    <div class="show-metric">
                        <span>{{ __('Coperti') }}</span>
                        <strong id="periodCovers">-</strong>
                    </div>
                @endif
                <div class="show-metric" style="border-color: #fedf89; background: #fffbeb;">
                    <span>{{ __('Risparmio stimato') }}</span>
                    <strong id="periodSavings">-</strong>
                </div>
            </div>
        @else
            {{-- V1: valori statici dall'ultimo snapshot --}}
            <div class="show-metric-grid mb-4">
                @if($usesOrders)
                    <div class="show-metric">
                        <span>{{ __('Ordini') }}</span>
                        <strong>{{ number_format($snapshot->orders_total ?? 0) }}</strong>
                    </div>
                    <div class="show-metric">
                        <span>{{ __('Ricavi') }}</span>
                        @if($revUnit !== 'euros' || $snapshot->orders_revenue === null)
                            <strong>N/D</strong>
                            <div class="text-muted text-sm">revenue_unit = {{ $revUnit }}</div>
                        @else
                            <strong>€ {{ number_format($snapshot->orders_revenue, 2) }}</strong>
                            <div class="text-muted text-sm">revenue_unit = euros</div>
                        @endif
                    </div>
                @endif
                @if($usesReservations)
                    <div class="show-metric">
                        <span>{{ __('Prenotazioni') }}</span>
                        <strong>{{ number_format($snapshot->reservations_total ?? 0) }}</strong>
                    </div>
                    <div class="show-metric">
                        <span>{{ __('Coperti') }}</span>
                        <strong>{{ number_format($snapshot->reservations_covers ?? 0) }}</strong>
                    </div>
                @endif
                @if(($businessMetrics['estimated_total_savings'] ?? 0) > 0)
                    <div class="show-metric" style="border-color: #fedf89; background: #fffbeb;">
                        <span>{{ __('Risparmio stimato') }}</span>
                        <strong>€ {{ number_format($businessMetrics['estimated_total_savings'], 2) }}</strong>
                        <div class="text-muted text-sm" style="margin-top: 2px;">
                            {{ __('ordini') }} € {{ number_format($businessMetrics['estimated_order_savings'] ?? 0, 2) }}
                            / {{ __('pren.') }} € {{ number_format($businessMetrics['estimated_reservation_savings'] ?? 0, 2) }}
                        </div>
                    </div>
                @endif
            </div>
            <div class="panel text-muted mb-3" style="font-size: 13px;">
                {{ __('Filtro periodo disponibile dopo snapshot api_version=2.') }}
            </div>
        @endif

        @if($hasBusiness)
            <div class="panel text-muted mb-3" style="font-size: 12px;">
                {{ __('Benchmark risparmio: Just Eat/Deliveroo/Glovo') }} {{ $benchmarkOrderPercent }}%, TheFork € {{ $benchmarkCoverFee }}/{{ __('coperto') }}.
            </div>
        @endif

        <div class="panel text-muted mb-5" style="font-size: 13px;">
            {{ __('Snapshot: periodo') }}
            <strong>{{ $snapshot->period_from?->toDateString() ?? '-' }}</strong>
            &ndash;
            <strong>{{ $snapshot->period_to?->toDateString() ?? '-' }}</strong>,
            {{ __('recuperato il') }} {{ $snapshot->fetched_at?->format('d/m/Y H:i') ?? '-' }}
        </div>

        @if(is_array($warnings) && count($warnings) > 0)
            <div class="panel mb-5" style="border-color: var(--amber-border); background: var(--amber-soft);">
                <strong style="color: #93370d;">{{ __('Attenzione: data warnings presenti') }}</strong>
                <ul style="margin: 8px 0 0; padding-left: 18px; color: #93370d;">
                    @foreach($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    @else
        <div class="panel text-muted mb-5" style="text-align: center; padding: 28px;">
            {{ __('Nessun snapshot ancora. Esegui una sync per raccogliere i dati.') }}
        </div>
    @endif

    {{-- Section: Attivita gestionale (payload V2) --}}
    @php
        $menuActivityBadge = 'grey';
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

    <div class="section-header" style="margin-bottom: 12px; margin-top: 8px;">
        <h2 class="section-title">
            {{ __('Attivita gestionale') }}
            <span style="display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; border: 1px solid; margin-left: 8px; vertical-align: middle; {{ $badgeStyle }}">
                {{ $menuActivityLabel }}
            </span>
        </h2>
    </div>

    @if(isset($payload['usage']))
        @php
            $usageMenu    = $payload['usage']['menu']    ?? [];
            $usageContent = $payload['usage']['content'] ?? [];
            $usageAdmin   = $payload['usage']['admin']   ?? [];
        @endphp
        <div class="show-metric-grid mb-3">
            <div class="show-metric">
                <span>{{ __('Prodotti') }}</span>
                <strong>{{ $usageMenu['products_count'] ?? '-' }}</strong>
            </div>
            <div class="show-metric">
                <span>{{ __('Categorie') }}</span>
                <strong>{{ $usageMenu['categories_count'] ?? '-' }}</strong>
            </div>
            <div class="show-metric">
                <span>{{ __('Ingredienti') }}</span>
                <strong>{{ $usageMenu['ingredients_count'] ?? '-' }}</strong>
            </div>
            <div class="show-metric">
                <span>{{ __('Post totali') }}</span>
                <strong>{{ $usageContent['posts_count'] ?? '-' }}</strong>
            </div>
            <div class="show-metric">
                <span>{{ __('Post attivi') }}</span>
                <strong>{{ $usageContent['posts_active'] ?? '-' }}</strong>
            </div>
            <div class="show-metric">
                <span>{{ __('Promo attive') }}</span>
                <strong>{{ $usageContent['posts_promo'] ?? '-' }}</strong>
            </div>
        </div>

        <div class="card-table mb-5">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Elemento') }}</th>
                            <th>{{ __('Ultimo aggiornamento') }}</th>
                            <th>{{ __('Aggiornati 7gg') }}</th>
                            <th>{{ __('Aggiornati 30gg') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td data-label="{{ __('Elemento') }}">{{ __('Prodotti') }}</td>
                            <td data-label="{{ __('Ultimo aggiornamento') }}" class="text-muted">{{ !empty($usageMenu['last_product_updated_at']) ? \Carbon\Carbon::parse($usageMenu['last_product_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                            <td data-label="{{ __('Aggiornati 7gg') }}">{{ $usageMenu['products_updated_last_7_days'] ?? '-' }}</td>
                            <td data-label="{{ __('Aggiornati 30gg') }}">{{ $usageMenu['products_updated_last_30_days'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td data-label="{{ __('Elemento') }}">{{ __('Categorie') }}</td>
                            <td data-label="{{ __('Ultimo aggiornamento') }}" class="text-muted">{{ !empty($usageMenu['last_category_updated_at']) ? \Carbon\Carbon::parse($usageMenu['last_category_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                            <td data-label="{{ __('Aggiornati 7gg') }}">{{ $usageMenu['categories_updated_last_7_days'] ?? '-' }}</td>
                            <td data-label="{{ __('Aggiornati 30gg') }}">{{ $usageMenu['categories_updated_last_30_days'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td data-label="{{ __('Elemento') }}">{{ __('Ingredienti') }}</td>
                            <td data-label="{{ __('Ultimo aggiornamento') }}" class="text-muted">{{ !empty($usageMenu['last_ingredient_updated_at']) ? \Carbon\Carbon::parse($usageMenu['last_ingredient_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                            <td data-label="{{ __('Aggiornati 7gg') }}">{{ $usageMenu['ingredients_updated_last_7_days'] ?? '-' }}</td>
                            <td data-label="{{ __('Aggiornati 30gg') }}">{{ $usageMenu['ingredients_updated_last_30_days'] ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td data-label="{{ __('Elemento') }}">{{ __('Post') }}</td>
                            <td data-label="{{ __('Ultimo aggiornamento') }}" class="text-muted">{{ !empty($usageContent['last_post_updated_at']) ? \Carbon\Carbon::parse($usageContent['last_post_updated_at'])->format('d/m/Y H:i') : '-' }}</td>
                            <td data-label="{{ __('Aggiornati 7gg') }}">{{ $usageContent['posts_updated_last_7_days'] ?? '-' }}</td>
                            <td data-label="{{ __('Aggiornati 30gg') }}">{{ $usageContent['posts_updated_last_30_days'] ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @if(! empty($usageAdmin['last_admin_login_at']))
            <div class="panel text-muted mb-5" style="font-size: 13px;">
                {{ __('Ultimo accesso admin:') }} <strong>{{ \Carbon\Carbon::parse($usageAdmin['last_admin_login_at'])->format('d/m/Y H:i') }}</strong>
            </div>
        @endif
    @else
        <div class="panel text-muted mb-5" style="font-size: 13px;">
            {{ __('Dati usage disponibili dopo aggiornamento dashboard a api_version=2.') }}
        </div>
    @endif

    {{-- Section: Andamento mensile --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Andamento mensile') }}</h2>
    </div>

    @if($hasBusiness && $hasMonthlyTrend)
        <div class="panel mb-3" style="padding: 16px;">
            <canvas id="chartMonthly" style="max-height: 280px;"></canvas>
        </div>

        @if(($monthlyTrend['source'] ?? null) === 'daily')
            <div class="panel text-muted mb-3" style="font-size: 12px;">
                {{ __('Vista mensile costruita dai dati disponibili nel payload corrente.') }}
            </div>
        @endif

        <div class="card-table mb-5">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Mese') }}</th>
                            @if($usesOrders)
                                <th>{{ __('Ordini') }}</th>
                                <th>&Delta; {{ __('ordini') }}</th>
                                <th>{{ __('Ricavi') }}</th>
                                <th>&Delta; {{ __('ricavi') }}</th>
                            @endif
                            @if($usesReservations)
                                <th>{{ __('Prenotazioni') }}</th>
                                <th>&Delta; {{ __('prenotazioni') }}</th>
                                <th>{{ __('Coperti') }}</th>
                                <th>&Delta; {{ __('coperti') }}</th>
                            @endif
                            <th>{{ __('Risparmio stimato') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_reverse($monthlyRows) as $row)
                            <tr>
                                <td data-label="{{ __('Mese') }}">
                                    <strong>{{ ucfirst($row['label']) }}</strong>
                                </td>
                                @if($usesOrders)
                                    @php
                                        $ordersBadge = $deltaBadge($row['changes']['orders'] ?? []);
                                        $revenueBadge = $deltaBadge($row['changes']['revenue'] ?? []);
                                    @endphp
                                    <td data-label="{{ __('Ordini') }}">{{ number_format($row['orders'] ?? 0) }}</td>
                                    <td data-label="&Delta; {{ __('ordini') }}">
                                        <span class="delta-badge" style="{{ $ordersBadge['style'] }}">{{ $ordersBadge['label'] }}</span>
                                    </td>
                                    <td data-label="{{ __('Ricavi') }}">{{ $row['revenue'] !== null ? '€ ' . number_format($row['revenue'], 2) : '-' }}</td>
                                    <td data-label="&Delta; {{ __('ricavi') }}">
                                        @if($row['revenue'] !== null)
                                            <span class="delta-badge" style="{{ $revenueBadge['style'] }}">{{ $revenueBadge['label'] }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                                @if($usesReservations)
                                    @php
                                        $reservationsBadge = $deltaBadge($row['changes']['reservations'] ?? []);
                                        $coversBadge = $deltaBadge($row['changes']['covers'] ?? []);
                                    @endphp
                                    <td data-label="{{ __('Prenotazioni') }}">{{ number_format($row['reservations'] ?? 0) }}</td>
                                    <td data-label="&Delta; {{ __('prenotazioni') }}">
                                        <span class="delta-badge" style="{{ $reservationsBadge['style'] }}">{{ $reservationsBadge['label'] }}</span>
                                    </td>
                                    <td data-label="{{ __('Coperti') }}">{{ number_format($row['covers'] ?? 0) }}</td>
                                    <td data-label="&Delta; {{ __('coperti') }}">
                                        <span class="delta-badge" style="{{ $coversBadge['style'] }}">{{ $coversBadge['label'] }}</span>
                                    </td>
                                @endif
                                <td data-label="{{ __('Risparmio stimato') }}">€ {{ number_format($row['savings'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($hasBusiness)
        <div class="panel text-muted mb-5" style="font-size: 13px;">
            {{ __('Dati mensili disponibili dopo uno snapshot con andamento mensile.') }}
        </div>
    @endif

    {{-- Section: Info sito --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Info sito') }}</h2>
    </div>

    <div class="show-metric-grid mb-4">
        <div class="show-metric">
            <span>{{ __('Pack') }}</span>
            <strong>{{ $site->pack ?? '-' }}</strong>
        </div>
        <div class="show-metric">
            <span>{{ __('Stato') }}</span>
            <strong>
                <span class="badge {{ $site->active ? 'badge-green' : 'badge-muted' }}">{{ $site->active ? __('Attivo') : __('Inattivo') }}</span>
            </strong>
        </div>
        <div class="show-metric">
            <span>{{ __('Ultima sync riuscita') }}</span>
            <strong>{{ $site->last_success_at?->format('d/m/Y H:i') ?? 'Mai' }}</strong>
        </div>
        <div class="show-metric" style="{{ $site->consecutive_failures > 0 ? 'border-color: var(--red-border); background: var(--red-soft);' : '' }}">
            <span>{{ __('Failures consecutive') }}</span>
            <strong style="{{ $site->consecutive_failures > 0 ? 'color: var(--red);' : '' }}">{{ $site->consecutive_failures }}</strong>
        </div>
        @if($snapshot)
            <div class="show-metric">
                <span>{{ __('Response time ultimo snapshot') }}</span>
                <strong>{{ $snapshot->response_time_ms ?? '-' }} ms</strong>
            </div>
            <div class="show-metric">
                <span>{{ __('HTTP status ultimo snapshot') }}</span>
                <strong>{{ $snapshot->http_status_code ?? '-' }}</strong>
            </div>
        @endif
    </div>

    @if($site->notes)
        <div class="panel text-muted mb-5" style="font-size: 13px;">
            <strong style="color: var(--ink);">{{ __('Note') }}</strong><br>
            {{ $site->notes }}
        </div>
    @endif

    {{-- Section: Sincronizzazione manuale --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Sincronizzazione manuale') }}</h2>
    </div>

    <div class="panel mb-5">
        <form method="POST" action="{{ route('sites.sync', $site) }}">
            @csrf
            <div class="actions" style="flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div class="field" style="margin: 0;">
                    <label for="from">{{ __('Da') }}</label>
                    <input type="date" id="from" name="from" value="{{ old('from') }}" style="width: auto; min-width: 150px;">
                </div>
                <div class="field" style="margin: 0;">
                    <label for="to">{{ __('A') }}</label>
                    <input type="date" id="to" name="to" value="{{ old('to') }}" style="width: auto; min-width: 150px;">
                </div>
                <button class="btn btn-primary" type="submit">{{ __('Esegui sync') }}</button>
            </div>
            <div class="text-muted text-sm mt-2">{{ __('Lascia vuoti per usare il periodo di default del servizio.') }}</div>
        </form>
    </div>

    {{-- Section: Errori sync (collassabile) --}}
    <details style="margin-bottom: 18px;">
        <div class="collapsible">
            <summary>
                {{ __('Errori di sincronizzazione') }} ({{ $site->syncErrors->count() }})
                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                </svg>
            </summary>
            <div class="collapsible-body">
                @if($site->syncErrors->count() > 0)
                    <div class="card-table" style="margin-top: 12px;">
                        <div class="table-wrap" style="margin-bottom: 10px;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>{{ __('Data') }}</th>
                                        <th>{{ __('Codice') }}</th>
                                        <th>{{ __('HTTP') }}</th>
                                        <th>{{ __('Messaggio') }}</th>
                                        <th>{{ __('Failures al momento') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($site->syncErrors as $error)
                                        @php
                                            $codeBadgeStyle = match($error->code ?? '') {
                                                'TIMEOUT'      => 'color: #93370d; background: #fffaeb;',
                                                'HTTP_ERROR'   => 'color: #7a2e0e; background: #fff4ed;',
                                                'INVALID_JSON' => 'color: #b42318; background: #fef3f2;',
                                                'EXCEPTION'    => 'color: #6b2737; background: #fff1f3;',
                                                default        => 'color: #475467; background: #f2f4f7;',
                                            };
                                        @endphp
                                        <tr>
                                            <td data-label="{{ __('Data') }}" style="white-space: nowrap;">{{ $error->occurred_at?->format('d/m/Y H:i') ?? $error->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                            <td data-label="{{ __('Codice') }}">
                                                <span style="display:inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 600; {{ $codeBadgeStyle }}">
                                                    {{ $error->code ?? '-' }}
                                                </span>
                                            </td>
                                            <td data-label="{{ __('HTTP') }}">{{ $error->http_status_code ?? '-' }}</td>
                                            <td data-label="{{ __('Messaggio') }}">{{ $error->message }}</td>
                                            <td data-label="{{ __('Failures al momento') }}">{{ $error->consecutive_failures }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <a href="{{ route('sync-errors.index', ['site_id' => $site->id]) }}" style="font-size: 13px;">
                        {{ __('Vedi tutti gli errori di questo sito') }} &rarr;
                    </a>
                @else
                    <div class="panel text-muted mt-3" style="text-align: center; padding: 20px;">{{ __('Nessun errore di sincronizzazione.') }}</div>
                @endif
            </div>
        </div>
    </details>

    {{-- Section: Payload grezzo (collassabile) --}}
    @if($snapshot)
        <details style="margin-bottom: 18px;">
            <div class="collapsible">
                <summary>
                    {{ __('Payload grezzo (JSON)') }}
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </summary>
                <div class="collapsible-body" style="padding-top: 12px;">
                    <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </details>
    @endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@if($hasPeriods && $hasBusiness)
<script>
(function () {
    const periods    = @json($payload['periods']);
    const revUnit    = @json($revUnit);
    const usesOrders = @json($usesOrders);
    const usesReservations = @json($usesReservations);
    const orderCommissionRate = @json($benchmarkOrderRate);
    const reservationCoverFee = @json($savingsBenchmark['reservation_cover_fee'] ?? 4);
    const select     = document.getElementById('periodSelect');
    const elFrom     = document.getElementById('periodFrom');
    const elTo       = document.getElementById('periodTo');
    const elOrders   = document.getElementById('periodOrders');
    const elRevenue  = document.getElementById('periodRevenue');
    const elAverage  = document.getElementById('periodAverage');
    const elRes      = document.getElementById('periodReservations');
    const elCovers   = document.getElementById('periodCovers');
    const elSavings  = document.getElementById('periodSavings');

    if (!select || !elFrom || !elTo) {
        return;
    }

    function fmtN(n) {
        if (n === null || n === undefined) return 'N/D';
        return new Intl.NumberFormat('it-IT').format(n);
    }
    function fmtMoney(n) {
        if (n === null || n === undefined) return 'N/D';
        return '€ ' + new Intl.NumberFormat('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
    }
    function fmtEur(n) {
        if (n === null || n === undefined || revUnit !== 'euros') return 'N/D';
        return fmtMoney(n);
    }
    function numeric(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : null;
    }
    function estimatedSavings(p) {
        let total = 0;
        let known = false;
        const revenue = numeric(p.orders_revenue);
        const covers = numeric(p.reservations_covers);

        if (usesOrders && revUnit === 'euros' && revenue !== null) {
            total += revenue * orderCommissionRate;
            known = true;
        }

        if (usesReservations && covers !== null) {
            total += covers * reservationCoverFee;
            known = true;
        }

        return known ? total : null;
    }

    function update(key) {
        const p = periods[key];
        if (!p) return;
        elFrom.textContent         = p.from  || '-';
        elTo.textContent           = p.to    || '-';
        if (elOrders) elOrders.textContent   = fmtN(p.orders_total);
        if (elRevenue) elRevenue.textContent = fmtEur(p.orders_revenue);
        if (elAverage) elAverage.textContent = fmtEur(p.orders_average);
        if (elRes) elRes.textContent         = fmtN(p.reservations_total);
        if (elCovers) elCovers.textContent   = fmtN(p.reservations_covers);
        if (elSavings) elSavings.textContent = fmtMoney(estimatedSavings(p));
    }

    select.addEventListener('change', function () { update(this.value); });
    update(select.value);
})();
</script>
@endif

@if($hasBusiness && $hasMonthlyTrend)
<script>
(function () {
    const rows = @json($monthlyRows);
    const labels = rows.map(row => row.label.charAt(0).toUpperCase() + row.label.slice(1));
    const datasets = [];

    @if($usesOrders)
        datasets.push({
            type: 'bar',
            label: 'Ordini',
            data: rows.map(row => row.orders ?? 0),
            backgroundColor: 'rgba(21,94,239,0.72)',
            yAxisID: 'count',
        });
        datasets.push({
            type: 'line',
            label: 'Ricavi (€)',
            data: rows.map(row => row.revenue ?? 0),
            borderColor: '#039855',
            backgroundColor: 'rgba(3,152,85,0.12)',
            tension: 0.35,
            pointRadius: 3,
            yAxisID: 'money',
        });
    @endif

    @if($usesReservations)
        datasets.push({
            type: 'bar',
            label: 'Prenotazioni',
            data: rows.map(row => row.reservations ?? 0),
            backgroundColor: 'rgba(122,90,248,0.68)',
            yAxisID: 'count',
        });
        datasets.push({
            type: 'line',
            label: 'Coperti',
            data: rows.map(row => row.covers ?? 0),
            borderColor: '#f04438',
            backgroundColor: 'rgba(240,68,56,0.1)',
            tension: 0.35,
            pointRadius: 3,
            yAxisID: 'count',
        });
    @endif

    datasets.push({
        type: 'line',
        label: 'Risparmio stimato (€)',
        data: rows.map(row => row.savings ?? 0),
        borderColor: '#dc6803',
        backgroundColor: 'rgba(220,104,3,0.12)',
        tension: 0.35,
        pointRadius: 3,
        yAxisID: 'money',
    });

    new Chart(document.getElementById('chartMonthly'), {
        type: 'line',
        data: {
            labels,
            datasets
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                count: { beginAtZero: true, ticks: { precision: 0 }, position: 'left' },
                money: {
                    beginAtZero: true,
                    position: 'right',
                    display: @json($hasBusiness),
                    grid: { drawOnChartArea: false },
                    ticks: {
                        callback: value => '€ ' + new Intl.NumberFormat('it-IT').format(value),
                    },
                },
            },
        }
    });
})();
</script>
@endif
@endpush
