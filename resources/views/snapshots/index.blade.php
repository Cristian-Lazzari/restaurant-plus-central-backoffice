@extends('layouts.app')

@section('content')
<div class="page-header">
    <div class="breadcrumb">
        <a href="{{ route('sites.show', $site) }}">{{ $site->name }}</a>
        <span class="breadcrumb-sep">/</span>
        <span>Storico snapshot</span>
    </div>
    <h1 class="page-title">Storico snapshot</h1>
    <p class="page-subtitle">{{ $site->name }}</p>
</div>

<div class="mb-4">
    <a href="{{ route('sites.show', $site) }}" class="btn">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
        </svg>
        Torna al sito
    </a>
</div>

@if($snapshots->isEmpty())
    <div class="empty-state">
        <svg width="32" height="32" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
        </svg>
        <p>Nessuno snapshot disponibile per questo sito.</p>
    </div>
@else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data fetch</th>
                    <th>Periodo from</th>
                    <th>Periodo to</th>
                    <th>Ordini totali</th>
                    <th>Ordini 30gg</th>
                    <th>Revenue 30gg</th>
                    <th>Prenotazioni 30gg</th>
                    <th>HTTP</th>
                    <th>Warnings</th>
                </tr>
            </thead>
            <tbody>
                @foreach($snapshots as $snap)
                @php
                    $revenueRaw = $snap->orders_revenue ?? null;
                    if ($revenueRaw !== null && ($snap->revenue_unit ?? '') === 'cents') {
                        $revenue = round((float) $revenueRaw / 100, 2);
                    } elseif ($revenueRaw !== null) {
                        $revenue = round((float) $revenueRaw, 2);
                    } else {
                        $revenue = null;
                    }
                @endphp
                <tr>
                    <td>
                        @if($snap->fetched_at)
                            <span>{{ $snap->fetched_at->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($snap->period_from)
                            {{ \Carbon\Carbon::parse($snap->period_from)->format('d/m/Y') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($snap->period_to)
                            {{ \Carbon\Carbon::parse($snap->period_to)->format('d/m/Y') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ number_format((int) ($snap->orders_total ?? 0), 0, ',', '.') }}</td>
                    <td>{{ number_format((int) ($snap->orders_last_30_days ?? 0), 0, ',', '.') }}</td>
                    <td>
                        @if($revenue !== null)
                            € {{ number_format($revenue, 2, ',', '.') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ number_format((int) ($snap->reservations_last_30_days ?? 0), 0, ',', '.') }}</td>
                    <td>
                        @if($snap->http_status_code)
                            @if($snap->http_status_code >= 200 && $snap->http_status_code < 300)
                                <span class="badge badge-green">{{ $snap->http_status_code }}</span>
                            @else
                                <span class="badge badge-red">{{ $snap->http_status_code }}</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($snap->has_warnings)
                            <span class="badge badge-amber">Warnings</span>
                        @else
                            <span class="badge badge-green">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($snapshots->hasPages())
        <div class="mt-3">
            {{ $snapshots->links() }}
        </div>
    @endif
@endif
@endsection
