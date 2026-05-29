@php
    $type = $type ?? 'reservation';
    $status = (int) ($status ?? 2);
    $name = trim(($name ?? '') . ' ' . ($surname ?? ''));
    $nPerson = $nPerson ?? null;
    $priceCents = $priceCents ?? null;

    $statusClass = match (true) {
        in_array($status, [0, 6], true) => 'null',
        in_array($status, [1, 5], true) => 'okk',
        default => 'to_see',
    };

    $nPerson = is_array($nPerson) ? $nPerson : (is_string($nPerson) ? json_decode($nPerson, true) : null);
@endphp

<div class="res-item {{ $statusClass }}" data-type="{{ $type }}">
    <div class="top">
        @if (in_array($status, [0, 6], true))
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi null bi-x-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
            </svg>
        @elseif (in_array($status, [1, 5], true))
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi okk bi-check-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi to_see bi-exclamation-circle-fill" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4m.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
            </svg>
        @endif

        <div class="name">{{ $name }}</div>

        @if ($nPerson)
            <div class="guest">
                @if (($nPerson['adult'] ?? 0) > 0)
                    {{ $nPerson['adult'] }}
                    <i class="bi bi-person-standing"></i>
                @endif

                @if (($nPerson['child'] ?? 0) > 0)
                    {{ $nPerson['child'] }}
                    <i class="bi bi-person-arms-up"></i>
                @endif
            </div>
        @endif

        @if (in_array($status, [3, 5, 6], true))
            <div class="{{ $status === 6 ? 'refound' : 'paid' }} status">
                <i class="bi bi-credit-card-2-back"></i>
                {{ $status === 6 ? 'Rimborsato' : 'Pagato' }}
            </div>
        @endif

        @if (!is_null($priceCents))
            <div class="price">{{ \App\Support\Currency::formatCents($priceCents) }}</div>
        @endif
    </div>
</div>
