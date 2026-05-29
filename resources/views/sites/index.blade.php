@extends('layouts.app')

@section('content')
    <div class="actions" style="justify-content: space-between; margin-bottom: 18px;">
        <h1 style="margin: 0;">Sites</h1>
        <div class="actions">
            <form method="POST" action="{{ route('sync.all') }}">
                @csrf
                <button class="btn" type="submit">Sync all active</button>
            </form>
            <a class="btn primary" href="{{ route('sites.create') }}">New site</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Last sync</th>
                    <th>Last success</th>
                    <th>Last error</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                    <th>Reservations</th>
                    <th>Warnings</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    @php
                        $snapshot = $site->latestSnapshot;
                    @endphp
                    <tr>
                        <td><a href="{{ route('sites.show', $site) }}"><strong>{{ $site->name }}</strong></a></td>
                        <td><a href="{{ $site->url }}" target="_blank" rel="noopener noreferrer">{{ $site->url }}</a></td>
                        <td>
                            <span class="badge {{ $site->active ? '' : 'off' }}">{{ $site->active ? 'Active' : 'Inactive' }}</span>
                            <div class="muted">pack {{ $site->pack ?? '-' }}</div>
                            <div class="muted">failures {{ $site->consecutive_failures }}</div>
                        </td>
                        <td>{{ $site->last_sync_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $site->last_success_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            {{ $site->last_error_at?->format('Y-m-d H:i') ?? '-' }}
                            @if($site->latestError)
                                <div class="muted">
                                    {{ $site->latestError->code ?? 'ERROR' }}
                                    @if($site->latestError->http_status_code)
                                        / HTTP {{ $site->latestError->http_status_code }}
                                    @endif
                                </div>
                                <div class="muted">{{ $site->latestError->message }}</div>
                            @endif
                        </td>
                        <td>{{ $snapshot?->orders_total ?? '-' }}</td>
                        <td>
                            @if(! $snapshot)
                                -
                            @elseif($snapshot->orders_revenue === null)
                                Non verificato
                            @else
                                {{ $snapshot->orders_revenue }}
                            @endif
                            <div class="muted">{{ $snapshot?->revenue_unit ?? '-' }}</div>
                        </td>
                        <td>
                            {{ $snapshot?->reservations_total ?? '-' }}
                            <div class="muted">covers {{ $snapshot?->reservations_covers ?? '-' }}</div>
                        </td>
                        <td>
                            {{ $snapshot?->has_warnings ? 'yes' : 'no' }}
                            <div class="muted">HTTP {{ $snapshot?->http_status_code ?? '-' }}</div>
                            <div class="muted">{{ $snapshot?->response_time_ms ?? '-' }} ms</div>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn" href="{{ route('sites.show', $site) }}">Detail</a>
                                <form method="POST" action="{{ route('sites.sync', $site) }}">
                                    @csrf
                                    <button class="btn" type="submit">Sync</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">No sites configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
