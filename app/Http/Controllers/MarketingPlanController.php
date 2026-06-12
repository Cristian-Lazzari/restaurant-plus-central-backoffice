<?php

namespace App\Http\Controllers;

use App\Models\MarketingItem;
use App\Models\Site;
use App\Models\User;
use App\Services\MarketingPlanImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MarketingPlanController extends Controller
{
    /**
     * Overview CEO: stato del piano marketing di tutti i ristoranti.
     */
    public function index()
    {
        if (! Schema::hasTable('marketing_plans')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Esegui le migration per abilitare la pipeline marketing.');
        }

        $sites = Site::with(['marketingPlan'])
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Conteggi per piano in una sola query.
        $stats = MarketingItem::selectRaw('marketing_plan_id, type, count(*) as total, sum(completed) as done')
            ->groupBy('marketing_plan_id', 'type')
            ->get()
            ->groupBy('marketing_plan_id');

        $planStats = [];
        foreach ($stats as $planId => $rows) {
            $byType = [];
            $total = 0;
            $done = 0;
            foreach ($rows as $row) {
                $byType[$row->type] = ['total' => (int) $row->total, 'done' => (int) $row->done];
                $total += (int) $row->total;
                $done += (int) $row->done;
            }
            $planStats[$planId] = ['by_type' => $byType, 'total' => $total, 'done' => $done];
        }

        return view('marketing.index', compact('sites', 'planStats'));
    }

    /**
     * Pagina piano marketing di un singolo ristorante.
     */
    public function show(Site $site)
    {
        $this->authorizeSite($site);

        if (! Schema::hasTable('marketing_plans')) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Esegui le migration per abilitare la pipeline marketing.');
        }

        $plan = $site->marketingPlan;
        $items = $plan
            ? $plan->items()->orderByRaw("week is null, week, day_index, slot = 'pomeriggio', length(code), code")->get()
            : collect();

        $itemsByType = $items->groupBy('type');

        // Griglia calendario: [week][day_index][slot] => collection di item
        $calendar = [];
        if ($plan) {
            for ($w = 1; $w <= $plan->weeks; $w++) {
                for ($d = 0; $d < 7; $d++) {
                    foreach (MarketingItem::SLOTS as $slot) {
                        $calendar[$w][$d][$slot] = [];
                    }
                }
            }
            foreach ($items as $item) {
                if ($item->week !== null && $item->day_index !== null && $item->slot !== null
                    && isset($calendar[$item->week][$item->day_index][$item->slot])) {
                    $calendar[$item->week][$item->day_index][$item->slot][] = $item;
                }
            }
        }

        $isRestaurantViewer = Auth::user()?->isRestaurant() ?? false;

        return view('marketing.show', compact('site', 'plan', 'items', 'itemsByType', 'calendar', 'isRestaurantViewer'));
    }

    /**
     * Import/sostituzione piano da JSON strategia (solo CEO).
     */
    public function import(Request $request, Site $site, MarketingPlanImportService $importer)
    {
        $data = $request->validate([
            'strategy_json' => ['required', 'string'],
        ], [
            'strategy_json.required' => 'Incolla il JSON della strategia.',
        ]);

        try {
            $importer->import($site, $data['strategy_json']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Import fallito: ' . $e->getMessage());
        }

        return redirect()
            ->route('marketing.show', $site)
            ->with('success', 'Piano marketing importato per ' . $site->name . '.');
    }

    /**
     * Elimina il piano (solo CEO).
     */
    public function destroy(Site $site)
    {
        $site->marketingPlan()?->delete();

        return redirect()
            ->route('marketing.index')
            ->with('success', 'Piano marketing eliminato per ' . $site->name . '.');
    }

    /**
     * Aggiorna data di inizio e KPI risultati (AJAX).
     */
    public function updateMeta(Request $request, Site $site)
    {
        $this->authorizeSite($site);

        $plan = $site->marketingPlan;
        abort_unless($plan, 404);

        $data = $request->validate([
            'start_date' => ['sometimes', 'nullable', 'date'],
            'kpis' => ['sometimes', 'array'],
            'kpis.*' => ['nullable', 'integer', 'min:0'],
        ]);

        if (array_key_exists('start_date', $data)) {
            $plan->start_date = $data['start_date'];
        }

        if (array_key_exists('kpis', $data)) {
            $allowed = ['clienti_online', 'consenso', 'tot_ordini', 'tot_prenotazioni'];
            $kpis = $plan->kpis ?? [];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $data['kpis'])) {
                    $kpis[$key] = (int) ($data['kpis'][$key] ?? 0);
                }
            }
            $plan->kpis = $kpis;
        }

        $plan->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Spunta/de-spunta un contenuto come pubblicato (AJAX).
     */
    public function toggleItem(Request $request, MarketingItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate([
            'completed' => ['required', 'boolean'],
        ]);

        $item->completed = $data['completed'];

        if ($item->completed && ! $item->completed_date) {
            $item->completed_date = $item->scheduledDate() ?? now();
        }

        $item->save();

        return response()->json([
            'ok' => true,
            'completed' => $item->completed,
            'completed_date' => $item->completed_date?->format('Y-m-d'),
        ]);
    }

    /**
     * Aggiorna note risultati e data di un contenuto (AJAX).
     */
    public function updateItem(Request $request, MarketingItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate([
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'completed_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if (array_key_exists('notes', $data)) {
            $item->notes = $data['notes'];
        }

        if (array_key_exists('completed_date', $data)) {
            $item->completed_date = $data['completed_date'];
        }

        $item->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Sposta un contenuto nel calendario (drag & drop, AJAX).
     */
    public function moveItem(Request $request, MarketingItem $item)
    {
        $this->authorizeItem($item);

        $plan = $item->plan;

        $data = $request->validate([
            'week' => ['required', 'integer', 'min:1', 'max:' . ($plan->weeks ?? 12)],
            'day_index' => ['required', 'integer', 'min:0', 'max:6'],
            'slot' => ['required', Rule::in(MarketingItem::SLOTS)],
        ]);

        $item->update($data);

        return response()->json(['ok' => true]);
    }

    private function authorizeSite(Site $site): void
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isRestaurant() && (int) $user->site_id !== (int) $site->id) {
            abort(403);
        }
    }

    private function authorizeItem(MarketingItem $item): void
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isRestaurant() && (int) $user->site_id !== (int) $item->plan?->site_id) {
            abort(403);
        }
    }
}
