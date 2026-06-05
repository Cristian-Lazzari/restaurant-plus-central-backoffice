@extends('layouts.app')

@section('content')
    @php
        $orderCommissionPercent = old('order_commission_percent', $benchmark['order_commission_percent'] ?? 20);
        $reservationCoverFee = old('reservation_cover_fee', $benchmark['reservation_cover_fee'] ?? 4);
    @endphp

    {{-- Section: Page header --}}
    <div class="page-header">
        <nav class="breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <span>{{ __('Impostazioni') }}</span>
        </nav>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
            <div>
                <h1 class="page-title">{{ __('Impostazioni backoffice') }}</h1>
                <div class="page-subtitle">{{ __('Benchmark usati per stimare il risparmio Future Plus.') }}</div>
            </div>
            <a class="btn" href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
        </div>
    </div>

    {{-- Section: Warning migration --}}
    @if(! $settingsTableExists)
        <div class="panel mb-4" style="border-color: var(--amber-border); background: var(--amber-soft); color: #93370d;">
            {{ __('Esegui le migration per salvare le impostazioni benchmark. Nel frattempo vengono usati i valori default.') }}
        </div>
    @endif

    {{-- Section: Form benchmark --}}
    <form method="POST" action="{{ route('backoffice-settings.update') }}">
        @csrf
        <div class="panel">
            <h2 class="section-title" style="margin-bottom: 16px;">{{ __('Risparmio stimato') }}</h2>

            <div class="grid-auto" style="margin-bottom: 16px;">
                <div class="field" style="margin: 0;">
                    <label for="order_commission_percent">{{ __('Commissione ordini marketplace (%)') }}</label>
                    <input
                        id="order_commission_percent"
                        name="order_commission_percent"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        value="{{ $orderCommissionPercent }}"
                        @disabled(! $settingsTableExists)
                    >
                    <div class="text-muted text-sm" style="margin-top: 6px;">{{ __('Just Eat / Deliveroo / Glovo.') }}</div>
                </div>

                <div class="field" style="margin: 0;">
                    <label for="reservation_cover_fee">{{ __('Costo prenotazione per coperto (€)') }}</label>
                    <input
                        id="reservation_cover_fee"
                        name="reservation_cover_fee"
                        type="number"
                        min="0"
                        max="1000"
                        step="0.01"
                        value="{{ $reservationCoverFee }}"
                        @disabled(! $settingsTableExists)
                    >
                    <div class="text-muted text-sm" style="margin-top: 6px;">{{ __('Benchmark TheFork.') }}</div>
                </div>
            </div>

            <div class="panel text-muted mb-4" style="font-size: 13px; background: var(--surface-2);">
                <strong>{{ __('Formula:') }}</strong>
                {{ __('ricavi ordini') }} &times; {{ number_format((float) $orderCommissionPercent, 2, ',', '.') }}%
                + {{ __('coperti prenotati') }} &times; € {{ number_format((float) $reservationCoverFee, 2, ',', '.') }}.
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" @disabled(! $settingsTableExists)>{{ __('Salva benchmark') }}</button>
                <a class="btn" href="{{ route('dashboard') }}">{{ __('Annulla') }}</a>
            </div>
        </div>
    </form>

@endsection
