@extends('layouts.app')

@section('content')

<style>
/* ─── Todolist layout ─── */
.tl-wrap { display: flex; height: calc(100vh - 120px); gap: 0; margin: -28px -28px -52px; overflow: hidden; }

.tl-sidebar {
    width: 220px;
    flex-shrink: 0;
    border-right: 1px solid var(--border-soft);
    overflow-y: auto;
    padding: 16px 10px;
    background: var(--surface);
}
.tl-sidebar::-webkit-scrollbar { width: 4px; }
.tl-sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

.tl-main { flex: 1; overflow-y: auto; padding: 24px 28px; background: var(--bg); }
.tl-main::-webkit-scrollbar { width: 6px; }
.tl-main::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

/* Mese label */
.month-group { margin-bottom: 18px; }
.month-label {
    font-size: 10px; font-weight: 760; text-transform: uppercase;
    letter-spacing: .1em; color: var(--muted); padding: 4px 8px; margin-bottom: 4px;
}

/* Week button */
.week-btn {
    display: flex; align-items: center; gap: 8px; width: 100%;
    padding: 8px 10px; border: none; background: transparent;
    color: var(--muted); font-size: 13px; font-weight: 600;
    cursor: pointer; border-radius: var(--radius-sm); text-align: left;
    transition: background .15s, color .15s; margin-bottom: 2px; font-family: inherit;
}
.week-btn:hover { background: var(--surface-2); color: var(--ink); }
.week-btn.active { background: var(--brand-soft); color: #066a52; border-left: 3px solid var(--brand); }
.week-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.week-progress { font-size: 11px; color: var(--muted); margin-left: auto; }

/* Header settimana */
.week-header { margin-bottom: 20px; }
.week-title { font-size: 18px; font-weight: 780; color: var(--ink); }
.week-subtitle { font-size: 13px; color: var(--muted); margin-top: 3px; }
.week-focus {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--brand-soft); border: 1px solid var(--brand-muted);
    border-radius: var(--radius-sm); padding: 5px 12px;
    font-size: 12.5px; color: #066a52; font-weight: 600; margin-top: 10px;
}

/* Obiettivi */
.week-goals { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.goal-chip {
    background: var(--green-soft); border: 1px solid var(--green-border);
    border-radius: var(--radius-sm); padding: 5px 12px;
    font-size: 12px; color: var(--green); font-weight: 600;
}

/* Progress bar */
.overall-progress {
    background: var(--surface); border: 1px solid var(--border-soft);
    border-radius: var(--radius); padding: 12px 16px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: var(--shadow-sm);
}
.op-label { font-size: 12.5px; color: var(--muted); white-space: nowrap; }
.op-bar { flex: 1; height: 8px; background: var(--bg); border-radius: 4px; overflow: hidden; border: 1px solid var(--border-soft); }
.op-fill { height: 100%; background: linear-gradient(90deg, var(--brand), #34d399); border-radius: 4px; transition: width .5s; }
.op-pct { font-size: 12.5px; color: var(--brand); font-weight: 700; white-space: nowrap; }

/* Day card */
.day-card {
    background: var(--surface); border: 1px solid var(--border-soft);
    border-radius: var(--radius); margin-bottom: 10px; overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.day-header {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px;
    cursor: pointer; transition: background .15s; user-select: none;
}
.day-header:hover { background: var(--surface-2); }
.day-name { font-size: 13px; font-weight: 780; color: var(--ink); width: 80px; flex-shrink: 0; }
.day-theme { font-size: 12.5px; color: var(--muted); flex: 1; }
.day-hours {
    font-size: 11px; color: var(--muted); background: var(--surface-2);
    border: 1px solid var(--border-soft); border-radius: 5px; padding: 2px 8px;
}
.day-check { font-size: 13px; margin-left: 8px; color: var(--muted); }
.day-body { padding: 0 16px 14px; display: none; }
.day-body.open { display: block; }

/* Time block */
.time-block { margin-bottom: 12px; }
.time-label {
    font-size: 11px; font-weight: 760; text-transform: uppercase; letter-spacing: .07em;
    color: var(--muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
    padding-top: 12px; border-top: 1px solid var(--border-soft);
}
.time-label::after { content: ''; flex: 1; height: 1px; background: var(--border-soft); }

/* Task item */
.task-item {
    display: flex; align-items: flex-start; gap: 10px; padding: 7px 0;
    border-bottom: 1px solid var(--border-soft); cursor: pointer; transition: background .1s;
    border-radius: 3px;
}
.task-item:last-child { border-bottom: none; }
.task-item:hover { background: var(--surface-2); padding-left: 6px; }

.task-cb {
    width: 18px; height: 18px; border: 2px solid var(--border); border-radius: 4px;
    flex-shrink: 0; display: flex; align-items: center; justify-content: center;
    margin-top: 1px; transition: all .2s; background: #fff;
}
.task-cb.done { background: var(--brand); border-color: var(--brand); }
.task-cb.done::after { content: '✓'; color: #fff; font-size: 10px; font-weight: 800; }

.task-text { font-size: 13px; color: var(--ink-2); line-height: 1.5; flex: 1; }
.task-text.done { text-decoration: line-through; color: var(--muted); }

.task-tag {
    font-size: 10.5px; font-weight: 700; padding: 2px 7px;
    border-radius: 999px; flex-shrink: 0; margin-top: 2px; white-space: nowrap;
}
.tag-sales   { background: #fdf2ff; color: #7e22ce; border: 1px solid #e9d5ff; }
.tag-content { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.tag-ops     { background: var(--green-soft); color: var(--green); border: 1px solid var(--green-border); }
.tag-ads     { background: var(--amber-soft); color: var(--amber); border: 1px solid var(--amber-border); }
.tag-smm     { background: var(--red-soft); color: var(--red); border: 1px solid var(--red-border); }

/* Reset button */
.btn-reset {
    font-size: 11px; padding: 4px 10px; border-radius: var(--radius-sm);
    background: none; border: 1px solid var(--red-border); color: var(--red);
    cursor: pointer; font-family: inherit; font-weight: 600; margin-top: 4px;
}
.btn-reset:hover { background: var(--red-soft); }

@media (max-width: 768px) {
    .tl-wrap { flex-direction: column; height: auto; }
    .tl-sidebar { width: 100%; max-height: 200px; border-right: none; border-bottom: 1px solid var(--border-soft); display: flex; overflow-x: auto; padding: 10px; gap: 6px; }
    .month-group { display: flex; gap: 4px; flex-shrink: 0; flex-direction: row; align-items: center; }
    .month-label { display: none; }
    .tl-main { padding: 16px; }
}
</style>

<div class="tl-wrap">

    {{-- ─── SIDEBAR SETTIMANE ─── --}}
    <div class="tl-sidebar" id="tl-sidebar">
        @php
            $months = [6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto'];
        @endphp
        @foreach ($months as $m => $mLabel)
            <div class="month-group">
                <div class="month-label">{{ $mLabel }}</div>
                @foreach ($weeks as $wi => $week)
                    @if ($week['month'] !== $m) @continue @endif
                    @php
                        $wTotal = 0; $wDone = 0;
                        foreach ($week['days'] as $di => $day) {
                            foreach ($day['blocks'] as $bi => $block) {
                                foreach ($block['tasks'] as $ti => $task) {
                                    $wTotal++;
                                    if (isset($completedKeys["{$week['id']}_{$di}_{$bi}_{$ti}"])) $wDone++;
                                }
                            }
                        }
                        $pct = $wTotal ? round($wDone / $wTotal * 100) : 0;
                    @endphp
                    <button
                        class="week-btn {{ $wi === 0 ? 'active' : '' }}"
                        data-week="{{ $week['id'] }}"
                        onclick="switchWeek('{{ $week['id'] }}')"
                    >
                        <div class="week-dot" style="background:{{ $week['color'] }}"></div>
                        <div>
                            <div>{{ $week['label'] }}</div>
                            <div style="font-size:11px;color:var(--muted)">{{ $week['dates'] }}</div>
                        </div>
                        <div class="week-progress" id="pct-{{ $week['id'] }}">{{ $pct }}%</div>
                    </button>
                @endforeach
            </div>
        @endforeach

        <div style="margin-top:auto;padding-top:12px;border-top:1px solid var(--border-soft)">
            <form method="POST" action="{{ route('todolist.reset') }}"
                  onsubmit="return confirm('Sei sicuro di voler azzerare tutti i progressi?')">
                @csrf
                <button type="submit" class="btn-reset">↺ Azzera progressi</button>
            </form>
        </div>
    </div>

    {{-- ─── MAIN CONTENT ─── --}}
    <div class="tl-main" id="tl-main">
        @foreach ($weeks as $wi => $week)
            <div
                class="week-panel"
                id="panel-{{ $week['id'] }}"
                style="{{ $wi !== 0 ? 'display:none' : '' }}"
            >
                <div class="week-header">
                    <div class="week-title">{{ $week['label'] }} — {{ $week['dates'] }}</div>
                    <div class="week-subtitle">{{ $week['subtitle'] }}</div>
                    <div class="week-focus">{{ $week['focus'] }}</div>
                </div>

                <div class="week-goals">
                    @foreach ($week['goals'] as $goal)
                        <div class="goal-chip">🎯 {{ $goal }}</div>
                    @endforeach
                </div>

                @php
                    $wTotal = 0; $wDone = 0;
                    foreach ($week['days'] as $di => $day) {
                        foreach ($day['blocks'] as $bi => $block) {
                            foreach ($block['tasks'] as $ti => $task) {
                                $wTotal++;
                                if (isset($completedKeys["{$week['id']}_{$di}_{$bi}_{$ti}"])) $wDone++;
                            }
                        }
                    }
                    $wpct = $wTotal ? round($wDone / $wTotal * 100) : 0;
                @endphp

                <div class="overall-progress">
                    <span class="op-label">Progressi settimana:</span>
                    <div class="op-bar">
                        <div class="op-fill" id="bar-{{ $week['id'] }}" style="width:{{ $wpct }}%"></div>
                    </div>
                    <span class="op-pct" id="prog-{{ $week['id'] }}">{{ $wDone }}/{{ $wTotal }} ({{ $wpct }}%)</span>
                </div>

                @foreach ($week['days'] as $di => $day)
                    @php
                        $dayTotal = 0; $dayDone = 0;
                        foreach ($day['blocks'] as $bi => $block) {
                            foreach ($block['tasks'] as $ti => $task) {
                                $dayTotal++;
                                if (isset($completedKeys["{$week['id']}_{$di}_{$bi}_{$ti}"])) $dayDone++;
                            }
                        }
                        $allDone = ($dayTotal > 0 && $dayTotal === $dayDone);
                        $dayPanelId = "day-{$week['id']}-{$di}";
                    @endphp
                    <div class="day-card" id="card-{{ $dayPanelId }}">
                        <div class="day-header" onclick="toggleDay('{{ $dayPanelId }}')">
                            <div class="day-name">{{ $day['name'] }}</div>
                            <div class="day-theme">{{ $day['theme'] }}</div>
                            <div class="day-hours">{{ $day['hours'] }}</div>
                            <div class="day-check" id="check-{{ $dayPanelId }}">
                                @if ($allDone) ✅ @else ▼ @endif
                            </div>
                        </div>
                        <div class="day-body" id="body-{{ $dayPanelId }}">
                            @foreach ($day['blocks'] as $bi => $block)
                                <div class="time-block">
                                    <div class="time-label">{{ $block['time'] }} — {{ $block['label'] }}</div>
                                    @foreach ($block['tasks'] as $ti => $task)
                                        @php
                                            $tKey = "{$week['id']}_{$di}_{$bi}_{$ti}";
                                            $done = isset($completedKeys[$tKey]);
                                        @endphp
                                        <div
                                            class="task-item"
                                            id="task-{{ $tKey }}"
                                            onclick="toggleTask('{{ $tKey }}', this)"
                                        >
                                            <div class="task-cb {{ $done ? 'done' : '' }}" id="cb-{{ $tKey }}"></div>
                                            <div class="task-text {{ $done ? 'done' : '' }}" id="txt-{{ $tKey }}">{{ $task['text'] }}</div>
                                            <span class="task-tag tag-{{ $task['tag'] }}">
                                                {{ ['sales'=>'Sales','content'=>'Contenuto','ops'=>'Operativo','ads'=>'Ads','smm'=>'SMM'][$task['tag']] ?? $task['tag'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
const TOGGLE_URL = "{{ route('todolist.toggle') }}";
const CSRF = "{{ csrf_token() }}";

// ─── Stato locale per aggiornare barre di progresso ───────────────────────────
const taskState = {};
@foreach ($weeks as $week)
    @foreach ($week['days'] as $di => $day)
        @foreach ($day['blocks'] as $bi => $block)
            @foreach ($block['tasks'] as $ti => $task)
                @php $tKey = "{$week['id']}_{$di}_{$bi}_{$ti}"; @endphp
                taskState['{{ $tKey }}'] = {{ isset($completedKeys[$tKey]) ? 'true' : 'false' }};
            @endforeach
        @endforeach
    @endforeach
@endforeach

// Struttura per calcoli progress (week_id → elenco chiavi)
const weekKeys = {
@foreach ($weeks as $week)
    '{{ $week['id'] }}': [
    @foreach ($week['days'] as $di => $day)
        @foreach ($day['blocks'] as $bi => $block)
            @foreach ($block['tasks'] as $ti => $task)
                '{{ $week['id'] }}_{{ $di }}_{{ $bi }}_{{ $ti }}',
            @endforeach
        @endforeach
    @endforeach
    ],
@endforeach
};

function toggleTask(key, el) {
    const cb  = document.getElementById('cb-' + key);
    const txt = document.getElementById('txt-' + key);
    const wasDone = taskState[key];
    const nowDone = !wasDone;

    // Ottimistic UI
    taskState[key] = nowDone;
    cb.classList.toggle('done', nowDone);
    txt.classList.toggle('done', nowDone);
    updateProgress(key.split('_')[0]);

    fetch(TOGGLE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ task_key: key }),
    }).catch(() => {
        // Rollback on error
        taskState[key] = wasDone;
        cb.classList.toggle('done', wasDone);
        txt.classList.toggle('done', wasDone);
        updateProgress(key.split('_')[0]);
    });
}

function updateProgress(weekId) {
    const keys = weekKeys[weekId] || [];
    const total = keys.length;
    const done  = keys.filter(k => taskState[k]).length;
    const pct   = total ? Math.round(done / total * 100) : 0;

    const bar  = document.getElementById('bar-' + weekId);
    const prog = document.getElementById('prog-' + weekId);
    const pctEl = document.getElementById('pct-' + weekId);
    if (bar)  bar.style.width = pct + '%';
    if (prog) prog.textContent = done + '/' + total + ' (' + pct + '%)';
    if (pctEl) pctEl.textContent = pct + '%';
}

// ─── Visibilità pannelli ───────────────────────────────────────────────────────
let currentWeek = '{{ $weeks[0]['id'] ?? '' }}';

function switchWeek(id) {
    document.querySelectorAll('.week-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.week-btn').forEach(b => b.classList.remove('active'));
    const panel = document.getElementById('panel-' + id);
    if (panel) panel.style.display = '';
    const btn = document.querySelector('[data-week="' + id + '"]');
    if (btn) btn.classList.add('active');
    currentWeek = id;
    document.getElementById('tl-main').scrollTop = 0;
}

const openDays = {};
function toggleDay(dayId) {
    const body  = document.getElementById('body-' + dayId);
    const check = document.getElementById('check-' + dayId);
    if (!body) return;
    const isOpen = body.classList.contains('open');
    body.classList.toggle('open', !isOpen);
    openDays[dayId] = !isOpen;
    if (check && !check.textContent.includes('✅')) {
        check.textContent = isOpen ? '▼' : '▲';
    }
}
</script>
@endpush

@endsection
