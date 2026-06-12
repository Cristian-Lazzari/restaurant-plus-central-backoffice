@extends('layouts.app')

@section('content')

    <style>
        :root {
            --mk-post: #2563eb; --mk-storia: #8b5cf6; --mk-video: #dc2626;
            --mk-promo: #f97316; --mk-campagna: #0d9488; --mk-automazione: #64748b; --mk-modello: #6366f1;
        }

        /* Tab pills */
        .mk-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; }
        .mk-tab { border: 1px solid var(--border); background: var(--surface); border-radius: 999px; padding: 6px 13px; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--ink-2); display: inline-flex; align-items: center; gap: 6px; }
        .mk-tab:hover { background: var(--surface-2); }
        .mk-tab.active { background: var(--brand); border-color: var(--brand); color: #fff; }
        .mk-tab .cnt { font-size: 11px; background: rgba(0,0,0,0.08); border-radius: 999px; padding: 1px 7px; }
        .mk-tab.active .cnt { background: rgba(255,255,255,0.22); }
        .mk-tab .cnt.done { background: var(--green); color: #fff; }

        .mk-section { display: none; }
        .mk-section.active { display: block; }

        /* Progress */
        .mk-progress-wrap { background: #eef1f6; border-radius: 6px; height: 8px; overflow: hidden; }
        .mk-progress { height: 100%; background: var(--brand); transition: width .25s; }

        /* Calendar */
        .mk-week { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); margin-bottom: 18px; overflow: hidden; box-shadow: var(--shadow-sm); }
        .mk-week h3 { margin: 0; padding: 10px 16px; background: var(--surface-2); border-bottom: 1px solid var(--border-soft); font-size: 14px; }
        .mk-cal-scroll { overflow-x: auto; }
        table.mk-cal { width: 100%; border-collapse: collapse; font-size: 12px; min-width: 760px; }
        table.mk-cal th, table.mk-cal td { border: 1px solid var(--border-soft); padding: 0; vertical-align: top; width: 14.28%; }
        table.mk-cal th { background: var(--surface-2); font-weight: 600; text-align: center; padding: 6px; font-size: 11.5px; }
        table.mk-cal th .mk-cal-date { display: block; font-weight: 400; font-size: 10.5px; color: var(--muted); margin-top: 2px; }
        .mk-slot { min-height: 38px; padding: 4px; transition: background .12s, outline .12s; }
        .mk-slot.dragover { background: var(--brand-soft); outline: 2px dashed var(--brand); outline-offset: -2px; }
        .mk-slot + .mk-slot { border-top: 1px dashed var(--border-soft); }
        .mk-slot-label { font-size: 9.5px; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; margin-bottom: 2px; }

        .mk-badge { display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 700; color: #fff; border-radius: 5px; padding: 2px 5px 2px 4px; margin: 1px 2px 1px 0; cursor: grab; white-space: nowrap; }
        .mk-badge:active { cursor: grabbing; }
        .mk-badge input { width: 11px; height: 11px; margin: 0; cursor: pointer; accent-color: #fff; }
        .mk-badge .lbl { cursor: pointer; }
        .mk-badge.done .lbl { text-decoration: line-through; opacity: .65; }
        .mk-badge.b-post { background: var(--mk-post); } .mk-badge.b-storia { background: var(--mk-storia); }
        .mk-badge.b-video { background: var(--mk-video); } .mk-badge.b-promo { background: var(--mk-promo); }
        .mk-badge.b-campagna { background: var(--mk-campagna); }

        .mk-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 10px; font-size: 12px; color: var(--muted); align-items: center; }
        .mk-legend .dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: middle; }

        /* Item cards */
        .mk-card { background: var(--surface); border: 1px solid var(--border-soft); border-radius: var(--radius); margin-bottom: 14px; overflow: hidden; box-shadow: var(--shadow-sm); transition: box-shadow .2s, border-color .2s; }
        .mk-card.highlight { border-color: var(--brand); box-shadow: 0 0 0 3px rgba(14,183,146,0.22); }
        .mk-card-head { display: flex; align-items: center; gap: 10px; padding: 11px 16px; background: var(--surface-2); border-bottom: 1px solid var(--border-soft); flex-wrap: wrap; }
        .mk-card-head .id { font-weight: 700; font-size: 13px; }
        .mk-card-head .title { font-size: 13.5px; font-weight: 600; flex: 1; min-width: 140px; }
        .mk-type-tag { font-size: 10.5px; font-weight: 700; color: #fff; border-radius: 5px; padding: 3px 8px; text-transform: uppercase; letter-spacing: .03em; }
        .mk-type-tag.b-post { background: var(--mk-post); } .mk-type-tag.b-storia { background: var(--mk-storia); }
        .mk-type-tag.b-video { background: var(--mk-video); } .mk-type-tag.b-promo { background: var(--mk-promo); }
        .mk-type-tag.b-campagna { background: var(--mk-campagna); } .mk-type-tag.b-automazione { background: var(--mk-automazione); }
        .mk-type-tag.b-modello { background: var(--mk-modello); }
        .mk-card.done .mk-card-head { background: var(--green-soft); }
        .mk-card-body { padding: 14px 16px; font-size: 13px; line-height: 1.55; }
        .mk-card-body dl { margin: 0; display: grid; grid-template-columns: 140px 1fr; gap: 6px 12px; }
        .mk-card-body dt { color: var(--muted); font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; padding-top: 2px; }
        .mk-card-body dd { margin: 0; }
        .mk-card-body .longtext { grid-column: 1 / -1; background: var(--surface-2); border: 1px solid var(--border-soft); border-radius: 6px; padding: 8px 10px; font-size: 12.5px; }
        .mk-card-foot { border-top: 1px dashed var(--border-soft); padding: 12px 16px; background: #fcfcfd; }
        .mk-annotate { display: flex; gap: 14px; align-items: center; flex-wrap: wrap; margin-bottom: 8px; }
        .mk-annotate label { font-size: 12.5px; display: flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; font-weight: 600; }
        .mk-annotate input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--green); cursor: pointer; }
        .mk-annotate input[type="date"] { width: auto; font-size: 12px; padding: 3px 6px; }
        .mk-card-foot textarea { min-height: 54px; font-size: 12.5px; }

        .mk-hero { background: linear-gradient(135deg, #090333, #1f2937); color: #fff; border-radius: 12px; padding: 24px 28px; margin-bottom: 20px; }
        .mk-hero h2 { margin: 0 0 6px; font-size: 18px; }
        .mk-hero p { margin: 0; color: #cbd5e1; font-size: 13.5px; max-width: 680px; }

        .mk-pill { display: inline-block; font-size: 12px; background: var(--brand-soft); color: #066a52; border-radius: 6px; padding: 2px 8px; margin: 2px 4px 2px 0; }

        @media (max-width: 768px) {
            .mk-card-body dl { grid-template-columns: 1fr; gap: 2px; }
            .mk-card-body dt { padding-top: 8px; }
            /* I KPI restano grandi: già sopra i 16px, nessun rischio auto-zoom */
            input.mk-kpi { font-size: 22px !important; }
        }
    </style>

    @php
        $dayLabels = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
        $typeMeta = [
            'post' => ['nav' => 'Post', 'title' => 'Post Instagram / Facebook', 'tag' => 'Post'],
            'storia' => ['nav' => 'Storie', 'title' => 'Storie', 'tag' => 'Storia'],
            'video' => ['nav' => 'Video', 'title' => 'Video brevi', 'tag' => 'Video'],
            'promo' => ['nav' => 'Promozioni', 'title' => 'Promozioni', 'tag' => 'Promo'],
            'campagna' => ['nav' => 'Campagne', 'title' => 'Campagne', 'tag' => 'Campagna'],
            'automazione' => ['nav' => 'Automazioni', 'title' => 'Automazioni', 'tag' => 'Automazione'],
            'modello' => ['nav' => 'Modelli', 'title' => 'Modelli messaggio', 'tag' => 'Modello'],
        ];
        $payloadLabels = [
            'foto' => 'Foto / Visual', 'durata' => 'Durata (sec)', 'ambientazione' => 'Ambientazione',
            'tono' => 'Tono', 'cta' => 'CTA', 'promo' => 'Promo collegata', 'modello' => 'Modello messaggio',
            'trigger' => 'Trigger', 'minimo' => 'Minimo', 'applicabile' => 'Applicabile a',
            'tipo_sconto' => 'Tipo sconto', 'sconto' => 'Sconto', 'riusabile' => 'Riusabile',
            'segmento' => 'Segmento', 'canale' => 'Canale', 'tipo' => 'Tipo', 'conclusione' => 'Conclusione',
        ];
        $totalItems = $items->count();
        $doneItems = $items->where('completed', true)->count();
    @endphp

    {{-- Section: Breadcrumb + header --}}
    <div class="page-header">
        <nav class="breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            @if($isRestaurantViewer)
                <a href="{{ route('sites.show', $site) }}">{{ $site->name }}</a>
            @else
                <a href="{{ route('marketing.index') }}">{{ __('Pipeline Marketing') }}</a>
            @endif
            <span class="breadcrumb-sep" aria-hidden="true">›</span>
            <span>{{ __('Marketing') }} — {{ $site->name }}</span>
        </nav>
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="page-title">{{ __('Strategia Social') }} — {{ $site->name }}</h1>
                @if($plan)
                    <div class="page-subtitle">{{ $plan->timeline_label }} · {{ $totalItems }} {{ __('contenuti') }}</div>
                @endif
            </div>
            @if($plan && ! $isRestaurantViewer)
                <form method="POST" action="{{ route('marketing.destroy', $site) }}" onsubmit="return confirm('{{ __('Eliminare il piano marketing? Verranno persi avanzamento e note.') }}');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">{{ __('Elimina piano') }}</button>
                </form>
            @endif
        </div>
    </div>

    @if(! $plan)
        @if($isRestaurantViewer)
            <div class="empty-state">
                <span>{{ __('Il piano marketing del tuo ristorante non è ancora disponibile. Verrà pubblicato qui appena pronto.') }}</span>
            </div>
        @else
            <div class="panel">
                <h2 class="section-title mb-3">{{ __('Nessun piano per questo ristorante') }}</h2>
                <form method="POST" action="{{ route('marketing.import', $site) }}">
                    @csrf
                    <div class="field">
                        <label for="strategy_json">{{ __('Incolla il JSON della strategia social') }}</label>
                        <textarea id="strategy_json" name="strategy_json" rows="8" required placeholder='{"obiettivo": "...", "posts": [...], "grid": [...]}'>{{ old('strategy_json') }}</textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">{{ __('Importa piano') }}</button>
                </form>
            </div>
        @endif
    @else

    {{-- Section: Tab nav --}}
    <div class="mk-tabs" role="tablist">
        <button class="mk-tab active" data-mk-section="panoramica" type="button">{{ __('Panoramica') }}</button>
        <button class="mk-tab" data-mk-section="calendario" type="button">{{ __('Calendario') }}</button>
        @foreach($typeMeta as $type => $meta)
            @php $group = $itemsByType[$type] ?? collect(); @endphp
            @if($group->isNotEmpty())
                <button class="mk-tab" data-mk-section="{{ $type }}" type="button">
                    {{ $meta['nav'] }}
                    <span class="cnt {{ $group->where('completed', true)->count() >= $group->count() ? 'done' : '' }}" data-cnt-type="{{ $type }}">{{ $group->where('completed', true)->count() }}/{{ $group->count() }}</span>
                </button>
            @endif
        @endforeach
    </div>

    {{-- ══ PANORAMICA ══ --}}
    <div class="mk-section active" id="mk-sec-panoramica">
        <div class="mk-hero">
            <h2>{{ __('Obiettivo della strategia') }}</h2>
            <p>{{ $plan->objective ?? '—' }}</p>
        </div>

        <div class="section-header" style="margin-bottom: 4px;">
            <h2 class="section-title">{{ __('Risultati raggiunti') }}</h2>
        </div>
        <div class="page-subtitle mb-3">{{ __('Aggiorna questi numeri periodicamente: vengono salvati automaticamente.') }}</div>

        @php $kpis = $plan->kpis ?? []; @endphp
        <div class="grid-auto mb-2">
            <div class="metric-card">
                <span class="metric-label">{{ __('Data inizio piano (Lun Sett. 1)') }}</span>
                <input type="date" id="mk-start-date" value="{{ $plan->start_date?->format('Y-m-d') }}" style="border: none; font-size: 15px; font-weight: 600; padding: 4px 0; background: transparent;">
            </div>
            @foreach(['clienti_online' => __('Clienti online (tot.)'), 'consenso' => __('Consensi raccolti'), 'tot_ordini' => __('Ordini totali'), 'tot_prenotazioni' => __('Prenotazioni totali')] as $key => $label)
                <div class="metric-card">
                    <span class="metric-label">{{ $label }}</span>
                    <input type="number" min="0" class="mk-kpi" data-kpi="{{ $key }}" value="{{ (int) ($kpis[$key] ?? 0) }}" style="border: none; font-size: 22px; font-weight: 780; color: var(--brand); padding: 2px 0; background: transparent; width: 100%;">
                </div>
            @endforeach
        </div>
        <p class="text-muted text-sm mb-5">{{ __('La data di inizio determina le date del calendario. Dopo averla cambiata la pagina si ricarica per aggiornare le date.') }}</p>

        <div class="section-header" style="margin-bottom: 12px;">
            <h2 class="section-title">{{ __('Stato di partenza') }}</h2>
        </div>
        <div class="grid-auto mb-5">
            <div class="panel" style="margin: 0;">
                <div class="metric-label mb-2">{{ __('Tempistiche piano') }}</div>
                <div style="font-size: 13.5px;">{{ $plan->timeline_label }}</div>
            </div>
            <div class="panel" style="margin: 0;">
                <div class="metric-label mb-2">{{ __('Stato canali social') }}</div>
                <div>
                    @forelse(($plan->social_status ?? []) as $channel => $state)
                        <span class="mk-pill"><strong>{{ ucfirst($channel) }}</strong>: {{ $state }}</span>
                    @empty
                        <span class="text-muted">—</span>
                    @endforelse
                </div>
            </div>
            <div class="panel" style="margin: 0;">
                <div class="metric-label mb-2">{{ __('Contenuti da produrre') }}</div>
                <div style="font-size: 13.5px;">
                    {{ $plan->photos_needed !== null ? $plan->photos_needed . ' ' . __('foto') : '' }}
                    {{ $plan->photos_needed !== null && $plan->reels_needed !== null ? ' · ' : '' }}
                    {{ $plan->reels_needed !== null ? $plan->reels_needed . ' ' . __('reel/video') : '' }}
                </div>
            </div>
            <div class="panel" style="margin: 0;">
                <div class="metric-label mb-2">{{ __('Cosa contiene il piano') }}</div>
                <div>
                    @foreach($typeMeta as $type => $meta)
                        @php $group = $itemsByType[$type] ?? collect(); @endphp
                        @if($group->isNotEmpty())
                            <span class="mk-pill">{{ $group->count() }} {{ strtolower($meta['nav']) }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex items-center justify-between gap-3 mb-2">
                <span class="metric-label">{{ __('Avanzamento totale') }}</span>
                <strong id="mk-global-label" style="font-size: 13px;">{{ $doneItems }} / {{ $totalItems }} {{ __('completati') }}</strong>
            </div>
            <div class="mk-progress-wrap"><div class="mk-progress" id="mk-global-bar" style="width: {{ $totalItems > 0 ? round($doneItems / $totalItems * 100) : 0 }}%;"></div></div>
        </div>
    </div>

    {{-- ══ CALENDARIO ══ --}}
    <div class="mk-section" id="mk-sec-calendario">
        <div class="section-header">
            <h2 class="section-title">{{ __('Calendario editoriale') }}</h2>
        </div>
        <div class="page-subtitle mb-3">{{ __('Spunta per segnare come pubblicato, clicca sul codice per aprire il dettaglio, trascina per riprogrammare.') }}</div>
        <div class="mk-legend">
            <span><span class="dot" style="background: var(--mk-post);"></span>Post</span>
            <span><span class="dot" style="background: var(--mk-storia);"></span>Storia</span>
            <span><span class="dot" style="background: var(--mk-video);"></span>Video</span>
            <span><span class="dot" style="background: var(--mk-promo);"></span>Promo</span>
            <span><span class="dot" style="background: var(--mk-campagna);"></span>Campagna</span>
        </div>

        @for($w = 1; $w <= $plan->weeks; $w++)
            <div class="mk-week">
                <h3>{{ __('Settimana') }} {{ $w }}</h3>
                <div class="mk-cal-scroll">
                    <table class="mk-cal">
                        <thead>
                            <tr>
                                @foreach($dayLabels as $d => $label)
                                    <th>
                                        {{ $label }}
                                        @if($plan->start_date)
                                            <span class="mk-cal-date">{{ $plan->start_date->copy()->addDays(($w - 1) * 7 + $d)->format('d/m') }}</span>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                @foreach($dayLabels as $d => $label)
                                    <td>
                                        @foreach(\App\Models\MarketingItem::SLOTS as $slot)
                                            <div class="mk-slot" data-week="{{ $w }}" data-day="{{ $d }}" data-slot="{{ $slot }}">
                                                <div class="mk-slot-label">{{ $slot }}</div>
                                                @foreach(($calendar[$w][$d][$slot] ?? []) as $item)
                                                    <span class="mk-badge b-{{ $item->type }} {{ $item->completed ? 'done' : '' }}" draggable="true" data-item-id="{{ $item->id }}" data-item-type="{{ $item->type }}">
                                                        <input type="checkbox" class="mk-check" data-item-id="{{ $item->id }}" {{ $item->completed ? 'checked' : '' }} aria-label="{{ __('Completato') }}">
                                                        <span class="lbl" data-open-item="{{ $item->id }}" data-open-type="{{ $item->type }}">{{ $item->code }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endfor
    </div>

    {{-- ══ SEZIONI CONTENUTI ══ --}}
    @foreach($typeMeta as $type => $meta)
        @php $group = $itemsByType[$type] ?? collect(); @endphp
        @if($group->isNotEmpty())
            <div class="mk-section" id="mk-sec-{{ $type }}">
                <div class="section-header">
                    <h2 class="section-title">{{ __($meta['title']) }}</h2>
                </div>
                <div class="page-subtitle mb-3">{{ $group->count() }} {{ strtolower($meta['nav']) }} — {{ __('spunta, data e note risultati vengono salvati automaticamente.') }}</div>

                @foreach($group as $item)
                    @php
                        $scheduled = $item->week !== null && $item->day_index !== null && $plan->start_date
                            ? $plan->start_date->copy()->addDays(($item->week - 1) * 7 + $item->day_index)
                            : null;
                    @endphp
                    <div class="mk-card {{ $item->completed ? 'done' : '' }}" id="mk-card-{{ $item->id }}" data-card-type="{{ $type }}">
                        <div class="mk-card-head">
                            <span class="id">{{ $item->code }}</span>
                            <span class="mk-type-tag b-{{ $type }}">{{ $typeMeta[$type]['tag'] }}</span>
                            <span class="title">{{ $item->title }}</span>
                            @if($item->week !== null)
                                <span class="text-muted text-sm" style="white-space: nowrap;">
                                    {{ __('Sett.') }} {{ $item->week }} · {{ $dayLabels[$item->day_index] ?? '' }} {{ $item->slot }}
                                    {{ $scheduled ? '· ' . $scheduled->format('d/m/Y') : '' }}
                                </span>
                            @endif
                        </div>
                        <div class="mk-card-body">
                            <dl>
                                @if($item->description)
                                    <div class="longtext">{{ $item->description }}</div>
                                @endif
                                @foreach(($item->payload ?? []) as $key => $value)
                                    @if(is_scalar($value) && trim((string) $value) !== '')
                                        <dt>{{ $payloadLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                        <dd>{{ $value }}</dd>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                        <div class="mk-card-foot">
                            <div class="mk-annotate">
                                <label>
                                    <input type="checkbox" class="mk-check" data-item-id="{{ $item->id }}" {{ $item->completed ? 'checked' : '' }}>
                                    {{ __('Pubblicato / Fatto') }}
                                </label>
                                <label style="font-weight: 500;">
                                    {{ __('Data') }}
                                    <input type="date" class="mk-item-date" data-item-id="{{ $item->id }}" value="{{ $item->completed_date?->format('Y-m-d') }}">
                                </label>
                            </div>
                            <textarea class="mk-item-notes" data-item-id="{{ $item->id }}" placeholder="{{ __('Note risultati (like, commenti, ordini generati, prenotazioni...)') }}">{{ $item->notes }}</textarea>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach

    @endif

@endsection

@if($plan)
@push('scripts')
<script>
(function() {
    var CSRF = '{{ csrf_token() }}';
    var URLS = {
        meta: '{{ route('marketing.meta', $site) }}',
        toggle: '{{ route('marketing.items.toggle', ['item' => '__ID__']) }}',
        update: '{{ route('marketing.items.update', ['item' => '__ID__']) }}',
        move: '{{ route('marketing.items.move', ['item' => '__ID__']) }}'
    };

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        }).then(function(r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
    }

    // ── Tabs ──────────────────────────────────────────────────────────────
    var tabs = document.querySelectorAll('.mk-tab');
    function activate(section) {
        tabs.forEach(function(t) { t.classList.toggle('active', t.dataset.mkSection === section); });
        document.querySelectorAll('.mk-section').forEach(function(s) {
            s.classList.toggle('active', s.id === 'mk-sec-' + section);
        });
    }
    tabs.forEach(function(t) {
        t.addEventListener('click', function() { activate(t.dataset.mkSection); window.scrollTo({ top: 0 }); });
    });

    // Click sul codice nel calendario → apre la card dettaglio
    document.querySelectorAll('[data-open-item]').forEach(function(el) {
        el.addEventListener('click', function() {
            activate(el.dataset.openType);
            var card = document.getElementById('mk-card-' + el.dataset.openItem);
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.classList.add('highlight');
                setTimeout(function() { card.classList.remove('highlight'); }, 2200);
            }
        });
    });

    // ── Meta: data inizio + KPI ───────────────────────────────────────────
    var startDate = document.getElementById('mk-start-date');
    if (startDate) {
        startDate.addEventListener('change', function() {
            post(URLS.meta, { start_date: startDate.value || null })
                .then(function() { location.reload(); })
                .catch(function() { alert('Errore di salvataggio.'); });
        });
    }
    document.querySelectorAll('.mk-kpi').forEach(function(input) {
        input.addEventListener('change', function() {
            var kpis = {};
            kpis[input.dataset.kpi] = parseInt(input.value || '0', 10);
            post(URLS.meta, { kpis: kpis }).catch(function() { alert('Errore di salvataggio.'); });
        });
    });

    // ── Avanzamento ───────────────────────────────────────────────────────
    function refreshCounters() {
        var perType = {};
        var total = 0, done = 0;
        var seen = {};
        document.querySelectorAll('.mk-card[data-card-type]').forEach(function(card) {
            var type = card.dataset.cardType;
            perType[type] = perType[type] || { total: 0, done: 0 };
            perType[type].total++;
            total++;
            if (card.classList.contains('done')) { perType[type].done++; done++; }
        });
        Object.keys(perType).forEach(function(type) {
            var el = document.querySelector('[data-cnt-type="' + type + '"]');
            if (el) {
                el.textContent = perType[type].done + '/' + perType[type].total;
                el.classList.toggle('done', perType[type].done >= perType[type].total);
            }
        });
        var bar = document.getElementById('mk-global-bar');
        var label = document.getElementById('mk-global-label');
        if (bar) bar.style.width = (total > 0 ? Math.round(done / total * 100) : 0) + '%';
        if (label) label.textContent = done + ' / ' + total + ' {{ __('completati') }}';
    }

    // ── Toggle completato (calendario + card, sincronizzati) ─────────────
    document.querySelectorAll('.mk-check').forEach(function(check) {
        check.addEventListener('change', function() {
            var id = check.dataset.itemId;
            var completed = check.checked;
            post(URLS.toggle.replace('__ID__', id), { completed: completed })
                .then(function(res) {
                    document.querySelectorAll('.mk-check[data-item-id="' + id + '"]').forEach(function(c) { c.checked = res.completed; });
                    var card = document.getElementById('mk-card-' + id);
                    if (card) card.classList.toggle('done', res.completed);
                    document.querySelectorAll('.mk-badge[data-item-id="' + id + '"]').forEach(function(b) { b.classList.toggle('done', res.completed); });
                    var dateInput = document.querySelector('.mk-item-date[data-item-id="' + id + '"]');
                    if (dateInput && res.completed_date) dateInput.value = res.completed_date;
                    refreshCounters();
                })
                .catch(function() { check.checked = !completed; alert('Errore di salvataggio.'); });
        });
    });

    // ── Note + data card (autosave) ───────────────────────────────────────
    document.querySelectorAll('.mk-item-notes').forEach(function(area) {
        area.addEventListener('change', function() {
            post(URLS.update.replace('__ID__', area.dataset.itemId), { notes: area.value })
                .catch(function() { alert('Errore di salvataggio note.'); });
        });
    });
    document.querySelectorAll('.mk-item-date').forEach(function(input) {
        input.addEventListener('change', function() {
            post(URLS.update.replace('__ID__', input.dataset.itemId), { completed_date: input.value || null })
                .catch(function() { alert('Errore di salvataggio data.'); });
        });
    });

    // ── Drag & drop calendario ────────────────────────────────────────────
    var dragged = null;
    document.querySelectorAll('.mk-badge[draggable]').forEach(function(badge) {
        badge.addEventListener('dragstart', function(e) {
            dragged = badge;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', badge.dataset.itemId);
        });
        badge.addEventListener('dragend', function() { dragged = null; });
    });
    document.querySelectorAll('.mk-slot').forEach(function(zone) {
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', function() { zone.classList.remove('dragover'); });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (!dragged) return;
            var badge = dragged;
            post(URLS.move.replace('__ID__', badge.dataset.itemId), {
                week: parseInt(zone.dataset.week, 10),
                day_index: parseInt(zone.dataset.day, 10),
                slot: zone.dataset.slot
            }).then(function() {
                zone.appendChild(badge);
            }).catch(function() { alert('Errore nello spostamento.'); });
        });
    });
})();
</script>
@endpush
@endif
