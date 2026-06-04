<?php

namespace App\Http\Controllers;

use App\Services\BackofficeSettingsService;
use Illuminate\Http\Request;

class BackofficeSettingsController extends Controller
{
    public function edit(BackofficeSettingsService $settings)
    {
        return view('backoffice-settings.edit', [
            'benchmark' => $settings->savingsBenchmark(),
            'settingsTableExists' => $settings->settingsTableExists(),
        ]);
    }

    public function update(Request $request, BackofficeSettingsService $settings)
    {
        if (! $settings->settingsTableExists()) {
            return back()->with('error', 'Esegui le migration per abilitare le impostazioni del benchmark.');
        }

        $data = $request->validate([
            'order_commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'reservation_cover_fee' => ['required', 'numeric', 'min:0', 'max:1000'],
        ], [
            'order_commission_percent.required' => 'Inserisci la percentuale commissione ordini.',
            'order_commission_percent.numeric' => 'La commissione ordini deve essere un numero.',
            'order_commission_percent.min' => 'La commissione ordini non puo essere negativa.',
            'order_commission_percent.max' => 'La commissione ordini non puo superare 100%.',
            'reservation_cover_fee.required' => 'Inserisci il costo per coperto prenotazione.',
            'reservation_cover_fee.numeric' => 'Il costo per coperto deve essere un numero.',
            'reservation_cover_fee.min' => 'Il costo per coperto non puo essere negativo.',
            'reservation_cover_fee.max' => 'Il costo per coperto non puo superare 1000 euro.',
        ]);

        $settings->updateSavingsBenchmark(
            (float) $data['order_commission_percent'],
            (float) $data['reservation_cover_fee']
        );

        return redirect()
            ->route('backoffice-settings.edit')
            ->with('success', 'Benchmark risparmio aggiornato.');
    }
}
