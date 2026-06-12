@extends('layouts.app')

@section('content')

    <style>
        .mk-progress-wrap { background: #eef1f6; border-radius: 6px; height: 8px; overflow: hidden; min-width: 110px; }
        .mk-progress { height: 100%; background: var(--brand); transition: width .25s; }
        .mk-type-pills { display: flex; flex-wrap: wrap; gap: 4px; }
        .mk-type-pill { font-size: 11px; font-weight: 700; border-radius: 999px; padding: 2px 8px; background: #f2f4f7; color: #475467; white-space: nowrap; }
        .mk-type-pill.full { background: var(--green-soft); color: var(--green); }
    </style>

    @php
        $withPlan = $sites->filter(fn ($s) => $s->marketingPlan);
        $totItems = collect($planStats)->sum('total');
        $totDone = collect($planStats)->sum('done');
        $typeLabels = ['post' => 'Post', 'storia' => 'Storie', 'video' => 'Video', 'promo' => 'Promo', 'campagna' => 'Campagne', 'automazione' => 'Autom.', 'modello' => 'Modelli'];
    @endphp

    {{-- Section: Page header --}}
    <div class="page-header">
        <h1 class="page-title">{{ __('Pipeline Marketing') }}</h1>
        <div class="page-subtitle">{{ __('Strategia social e calendario editoriale di ogni ristorante, con avanzamento complessivo.') }}</div>
    </div>

    {{-- Section: KPI globali --}}
    <div class="grid-4 mb-5">
        <div class="metric-card">
            <span class="metric-label">{{ __('Piani attivi') }}</span>
            <span class="metric-value">{{ $withPlan->count() }} <span class="text-muted" style="font-size: 14px;">/ {{ $sites->count() }}</span></span>
            <div class="metric-sub">{{ __('ristoranti con strategia') }}</div>
        </div>
        <div class="metric-card">
            <span class="metric-label">{{ __('Contenuti totali') }}</span>
            <span class="metric-value">{{ $totItems }}</span>
            <div class="metric-sub">{{ __('post, storie, video, promo…') }}</div>
        </div>
        <div class="metric-card">
            <span class="metric-label">{{ __('Completati') }}</span>
            <span class="metric-value">{{ $totDone }}</span>
            <div class="metric-sub">{{ __('pubblicati / fatti') }}</div>
        </div>
        <div class="metric-card">
            <span class="metric-label">{{ __('Avanzamento globale') }}</span>
            <span class="metric-value">{{ $totItems > 0 ? round($totDone / $totItems * 100) : 0 }}%</span>
            <div class="mk-progress-wrap mt-2"><div class="mk-progress" style="width: {{ $totItems > 0 ? round($totDone / $totItems * 100) : 0 }}%;"></div></div>
        </div>
    </div>

    {{-- Section: Import strategia --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Importa strategia') }}</h2>
    </div>

    <div class="panel mb-5">
        <form method="POST" id="importForm" data-action-template="{{ route('marketing.import', ['site' => '__SITE__']) }}">
            @csrf
            <div class="field">
                <label for="import_site">{{ __('Ristorante') }}</label>
                <select id="import_site" required style="max-width: 360px;">
                    <option value="">{{ __('— Seleziona —') }}</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}{{ $site->marketingPlan ? ' — ' . __('piano già presente (verrà sostituito)') : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="strategy_json">{{ __('JSON strategia') }}</label>
                <textarea id="strategy_json" name="strategy_json" rows="6" required placeholder='{"obiettivo": "...", "posts": [...], "stories": [...], "grid": [...]}'>{{ old('strategy_json') }}</textarea>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button class="btn btn-primary" type="submit">{{ __('Importa piano') }}</button>
                <span class="text-muted text-sm">{{ __('Incolla il JSON generato dalla strategia social. Se il ristorante ha già un piano, verrà sostituito e l\'avanzamento azzerato.') }}</span>
            </div>
        </form>
    </div>

    {{-- Section: Stato per ristorante --}}
    <div class="section-header" style="margin-bottom: 12px;">
        <h2 class="section-title">{{ __('Stato per ristorante') }}</h2>
    </div>

    <div class="card-table">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Ristorante') }}</th>
                        <th>{{ __('Obiettivo') }}</th>
                        <th>{{ __('Avanzamento') }}</th>
                        <th>{{ __('Contenuti per tipo') }}</th>
                        <th>{{ __('Inizio piano') }}</th>
                        <th>{{ __('Azioni') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sites as $site)
                        @php
                            $plan = $site->marketingPlan;
                            $stat = $plan ? ($planStats[$plan->id] ?? ['total' => 0, 'done' => 0, 'by_type' => []]) : null;
                            $pct = $stat && $stat['total'] > 0 ? round($stat['done'] / $stat['total'] * 100) : 0;
                        @endphp
                        <tr>
                            <td class="td-primary" data-label="{{ __('Ristorante') }}">
                                <strong>{{ $site->name }}</strong>
                                @if($plan)
                                    <div class="text-muted text-sm">{{ $plan->timeline_label }}</div>
                                @endif
                            </td>
                            <td data-label="{{ __('Obiettivo') }}" style="max-width: 280px;">
                                @if($plan)
                                    <span style="font-size: 12.5px;">{{ \Illuminate\Support\Str::limit($plan->objective, 110) }}</span>
                                @else
                                    <span class="badge badge-muted">{{ __('Nessun piano') }}</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Avanzamento') }}">
                                @if($plan)
                                    <div class="flex items-center gap-2">
                                        <div class="mk-progress-wrap" style="flex: 1;"><div class="mk-progress" style="width: {{ $pct }}%;"></div></div>
                                        <span style="font-size: 12.5px; font-weight: 700; white-space: nowrap;">{{ $stat['done'] }}/{{ $stat['total'] }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Contenuti per tipo') }}">
                                @if($plan && ! empty($stat['by_type']))
                                    <div class="mk-type-pills">
                                        @foreach($typeLabels as $type => $label)
                                            @if(isset($stat['by_type'][$type]))
                                                @php $t = $stat['by_type'][$type]; @endphp
                                                <span class="mk-type-pill {{ $t['done'] >= $t['total'] && $t['total'] > 0 ? 'full' : '' }}">{{ $label }} {{ $t['done'] }}/{{ $t['total'] }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="{{ __('Inizio piano') }}">
                                {{ $plan?->start_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="td-actions" data-label="{{ __('Azioni') }}">
                                <div class="actions">
                                    @if($plan)
                                        <a class="btn btn-primary" href="{{ route('marketing.show', $site) }}">{{ __('Apri piano') }}</a>
                                        <form method="POST" action="{{ route('marketing.destroy', $site) }}" onsubmit="return confirm('{{ __('Eliminare il piano marketing di questo ristorante? Verranno persi avanzamento e note.') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger" type="submit">{{ __('Elimina') }}</button>
                                        </form>
                                    @else
                                        <button class="btn" type="button" onclick="document.getElementById('import_site').value='{{ $site->id }}'; document.getElementById('strategy_json').focus(); window.scrollTo({top: 0, behavior: 'smooth'});">{{ __('Importa strategia') }}</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function() {
    var form = document.getElementById('importForm');
    var siteSelect = document.getElementById('import_site');
    if (!form || !siteSelect) return;
    form.addEventListener('submit', function(e) {
        if (!siteSelect.value) { e.preventDefault(); siteSelect.focus(); return; }
        form.action = form.dataset.actionTemplate.replace('__SITE__', siteSelect.value);
    });
})();
</script>
@endpush
