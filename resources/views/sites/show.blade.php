@extends('layouts.app')

@section('content')
    @php
        $snapshot = $site->latestSnapshot;
        $payload = $snapshot?->payload ?? [];
        $warnings = $snapshot?->data_warnings ?? [];
    @endphp

    <div class="actions" style="justify-content: space-between; margin-bottom: 18px;">
        <div>
            <h1 style="margin-bottom: 6px;">{{ $site->name }}</h1>
            <div class="muted">{{ $site->url }}</div>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('sites.sync', $site) }}">
                @csrf
                <button class="btn primary" type="submit">Sync now</button>
            </form>
            <form method="POST" action="{{ route('sites.toggle', $site) }}">
                @csrf
                <button class="btn" type="submit">{{ $site->active ? 'Deactivate' : 'Activate' }}</button>
            </form>
            <a class="btn" href="{{ route('sites.edit', $site) }}">Edit</a>
            <a class="btn" href="{{ route('dashboard') }}">Back</a>
        </div>
    </div>

    <div class="grid">
        <div class="metric"><span class="muted">Active</span><strong>{{ $site->active ? 'Yes' : 'No' }}</strong></div>
        <div class="metric"><span class="muted">Pack</span><strong>{{ $site->pack ?? '-' }}</strong></div>
        <div class="metric"><span class="muted">Consecutive failures</span><strong>{{ $site->consecutive_failures }}</strong></div>
        <div class="metric"><span class="muted">Retention days</span><strong>{{ $site->retention_days ?? '-' }}</strong></div>
        <div class="metric"><span class="muted">Last sync</span><strong>{{ $site->last_sync_at?->format('Y-m-d H:i') ?? '-' }}</strong></div>
        <div class="metric"><span class="muted">Last success</span><strong>{{ $site->last_success_at?->format('Y-m-d H:i') ?? '-' }}</strong></div>
        <div class="metric"><span class="muted">Last error</span><strong>{{ $site->last_error_at?->format('Y-m-d H:i') ?? '-' }}</strong></div>
    </div>

    <h2>Notes</h2>
    <div class="panel">{{ $site->notes ?: 'No notes.' }}</div>

    <h2>Latest snapshot</h2>
    @if($snapshot)
        <div class="grid">
            <div class="metric"><span class="muted">Period</span><strong>{{ $snapshot->period_from?->toDateString() ?? '-' }} / {{ $snapshot->period_to?->toDateString() ?? '-' }}</strong></div>
            <div class="metric"><span class="muted">API version</span><strong>{{ $snapshot->api_version ?? '-' }}</strong></div>
            <div class="metric"><span class="muted">Revenue unit</span><strong>{{ $snapshot->revenue_unit ?? '-' }}</strong></div>
            <div class="metric"><span class="muted">Fetched at</span><strong>{{ $snapshot->fetched_at?->format('Y-m-d H:i') ?? '-' }}</strong></div>
            <div class="metric"><span class="muted">HTTP status</span><strong>{{ $snapshot->http_status_code ?? '-' }}</strong></div>
            <div class="metric"><span class="muted">Response time</span><strong>{{ $snapshot->response_time_ms ?? '-' }} ms</strong></div>
            <div class="metric"><span class="muted">Has warnings</span><strong>{{ $snapshot->has_warnings ? 'Yes' : 'No' }}</strong></div>
        </div>

        <div class="grid" style="margin-top: 12px;">
            <div class="metric"><span class="muted">Orders total</span><strong>{{ $snapshot->orders_total ?? 0 }}</strong></div>
            <div class="metric"><span class="muted">Revenue confirmed</span><strong>{{ $snapshot->orders_revenue === null ? 'Non verificato' : $snapshot->orders_revenue }}</strong></div>
            <div class="metric"><span class="muted">Reservations total</span><strong>{{ $snapshot->reservations_total ?? 0 }}</strong></div>
            <div class="metric"><span class="muted">Covers total</span><strong>{{ $snapshot->reservations_covers ?? 0 }}</strong></div>
        </div>

        <h2>Data warnings</h2>
        <div class="panel">
            @if(is_array($warnings) && count($warnings) > 0)
                <ul>
                    @foreach($warnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            @else
                <span class="muted">No warnings in latest snapshot.</span>
            @endif
        </div>

        <h2>Raw payload</h2>
        <pre>{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
    @else
        <div class="panel muted">No snapshot fetched yet.</div>
    @endif

    <h2>Last 10 snapshots</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fetched at</th>
                    <th>Period</th>
                    <th>HTTP</th>
                    <th>Time</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                    <th>Reservations</th>
                    <th>Warnings</th>
                </tr>
            </thead>
            <tbody>
                @forelse($site->reportSnapshots as $row)
                    <tr>
                        <td>{{ $row->fetched_at?->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $row->period_from?->toDateString() ?? '-' }} / {{ $row->period_to?->toDateString() ?? '-' }}</td>
                        <td>{{ $row->http_status_code ?? '-' }}</td>
                        <td>{{ $row->response_time_ms ?? '-' }} ms</td>
                        <td>{{ $row->orders_total ?? '-' }}</td>
                        <td>{{ $row->orders_revenue === null ? 'Non verificato' : $row->orders_revenue }} <span class="muted">{{ $row->revenue_unit }}</span></td>
                        <td>{{ $row->reservations_total ?? '-' }} <span class="muted">covers {{ $row->reservations_covers ?? '-' }}</span></td>
                        <td>{{ $row->has_warnings ? 'yes' : 'no' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No snapshots.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Last 10 sync errors</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Created at</th>
                    <th>Code</th>
                    <th>HTTP</th>
                    <th>Message</th>
                    <th>Context</th>
                </tr>
            </thead>
            <tbody>
                @forelse($site->syncErrors as $error)
                    <tr>
                        <td>{{ $error->created_at?->format('Y-m-d H:i:s') ?? $error->occurred_at?->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $error->code ?? '-' }}</td>
                        <td>{{ $error->http_status_code ?? '-' }}</td>
                        <td>{{ $error->message }}</td>
                        <td><pre style="margin: 0;">{{ json_encode($error->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No sync errors.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
