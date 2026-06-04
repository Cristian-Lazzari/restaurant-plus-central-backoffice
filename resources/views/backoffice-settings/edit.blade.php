@extends('layouts.app')

@section('content')
    @php
        $orderCommissionPercent = old('order_commission_percent', $benchmark['order_commission_percent'] ?? 20);
        $reservationCoverFee = old('reservation_cover_fee', $benchmark['reservation_cover_fee'] ?? 4);
    @endphp

    <div class="actions" style="justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h1 style="margin-bottom: 4px;">Impostazioni backoffice</h1>
            <div class="muted" style="font-size: 13px;">Benchmark usati per stimare il risparmio Future Plus.</div>
        </div>
        <a class="btn" href="{{ route('dashboard') }}">Dashboard</a>
    </div>

    @if(! $settingsTableExists)
        <div class="panel" style="border-color: #fedf89; background: #fffaeb; color: #93370d;">
            Esegui le migration per salvare le impostazioni benchmark. Nel frattempo vengono usati i valori default.
        </div>
    @endif

    <form method="POST" action="{{ route('backoffice-settings.update') }}">
        @csrf
        <div class="panel">
            <h2 style="margin-top: 0;">Risparmio stimato</h2>

            <div class="grid" style="margin-bottom: 12px;">
                <div class="field" style="margin: 0;">
                    <label for="order_commission_percent">Commissione ordini marketplace (%)</label>
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
                    <div class="muted" style="font-size: 12px; margin-top: 6px;">Just Eat / Deliveroo / Glovo.</div>
                </div>

                <div class="field" style="margin: 0;">
                    <label for="reservation_cover_fee">Costo prenotazione per coperto (€)</label>
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
                    <div class="muted" style="font-size: 12px; margin-top: 6px;">Benchmark TheFork.</div>
                </div>
            </div>

            <div class="muted" style="font-size: 13px; margin: 0 0 16px; padding: 10px 0;">
                Formula: ricavi ordini × {{ number_format((float) $orderCommissionPercent, 2, ',', '.') }}%
                + coperti prenotati × € {{ number_format((float) $reservationCoverFee, 2, ',', '.') }}.
            </div>

            <div class="actions">
                <button class="btn primary" type="submit" @disabled(! $settingsTableExists)>Salva benchmark</button>
                <a class="btn" href="{{ route('dashboard') }}">Annulla</a>
            </div>
        </div>
    </form>
@endsection
