@extends('layouts.app')

@section('content')

@php
    /* ── Mappa data → giorno piano ── */
    $monthAbbr  = ['Giu' => 6, 'Lug' => 7, 'Ago' => 8];
    $calData    = [];   // 'Y-m-d' => ['week', 'wi', 'di', 'day', 'total', 'done', 'pct']

    foreach ($weeks as $wi => $week) {
        preg_match('/^([A-Za-z]+)\s+(\d+)/u', $week['dates'], $m);
        $sm = $monthAbbr[$m[1]] ?? 6;
        $sd = (int)$m[2];
        $base = new DateTime("2026-{$sm}-{$sd}");

        foreach ($week['days'] as $di => $day) {
            $dt  = clone $base;
            $dt->modify("+{$di} days");
            $key = $dt->format('Y-m-d');

            $tot = 0; $dn = 0;
            foreach ($day['blocks'] as $bi => $block) {
                foreach ($block['tasks'] as $ti => $task) {
                    $tot++;
                    if (isset($completedKeys["{$week['id']}_{$di}_{$bi}_{$ti}"])) $dn++;
                }
            }
            $calData[$key] = [
                'week'  => $week,
                'wi'    => $wi,
                'di'    => $di,
                'day'   => $day,
                'total' => $tot,
                'done'  => $dn,
                'pct'   => $tot ? round($dn / $tot * 100) : 0,
            ];
        }
    }
@endphp

<style>
/* ─── WRAPPER PRINCIPALE ─────────────────────────────────────────────────────── */
.tl-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 120px);
    margin: -28px -28px -52px;
    overflow: hidden;
}

/* ─── TOGGLE LISTA / CALENDARIO ─────────────────────────────────────────────── */
.view-toggle-bar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 8px 16px;
    background: var(--surface);
    border-bottom: 1px solid var(--border-soft);
}
.vt-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: var(--radius-sm);
    border: 1px solid var(--border-soft);
    background: transparent; color: var(--muted);
    font-size: 12.5px; font-weight: 600;
    cursor: pointer; font-family: inherit;
    transition: all .15s;
}
.vt-btn:hover { background: var(--surface-2); color: var(--ink); }
.vt-btn.active {
    background: var(--brand-soft);
    border-color: var(--brand-muted);
    color: #066a52;
}
.vt-icon { font-size: 14px; }

/* ─── LISTA ─────────────────────────────────────────────────────────────────── */
#view-list {
    flex: 1;
    display: flex;
    overflow: hidden;
}

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

.month-group { margin-bottom: 18px; }
.month-label {
    font-size: 10px; font-weight: 760; text-transform: uppercase;
    letter-spacing: .1em; color: var(--muted); padding: 4px 8px; margin-bottom: 4px;
}
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

.week-header { margin-bottom: 20px; }
.week-title { font-size: 18px; font-weight: 780; color: var(--ink); }
.week-subtitle { font-size: 13px; color: var(--muted); margin-top: 3px; }
.week-focus {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--brand-soft); border: 1px solid var(--brand-muted);
    border-radius: var(--radius-sm); padding: 5px 12px;
    font-size: 12.5px; color: #066a52; font-weight: 600; margin-top: 10px;
}
.week-goals { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
.goal-chip {
    background: var(--green-soft); border: 1px solid var(--green-border);
    border-radius: var(--radius-sm); padding: 5px 12px;
    font-size: 12px; color: var(--green); font-weight: 600;
}
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

.time-block { margin-bottom: 12px; }
.time-label {
    font-size: 11px; font-weight: 760; text-transform: uppercase; letter-spacing: .07em;
    color: var(--muted); margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
    padding-top: 12px; border-top: 1px solid var(--border-soft);
}
.time-label::after { content: ''; flex: 1; height: 1px; background: var(--border-soft); }

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

.btn-reset {
    font-size: 11px; padding: 4px 10px; border-radius: var(--radius-sm);
    background: none; border: 1px solid var(--red-border); color: var(--red);
    cursor: pointer; font-family: inherit; font-weight: 600; margin-top: 4px;
}
.btn-reset:hover { background: var(--red-soft); }

/* ─── CALENDARIO ─────────────────────────────────────────────────────────────── */
#view-cal {
    flex: 1;
    overflow-y: auto;
    padding: 28px;
    background: var(--bg);
}
#view-cal::-webkit-scrollbar { width: 6px; }
#view-cal::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

.cal-month-section { margin-bottom: 48px; }
.cal-month-title {
    font-size: 17px; font-weight: 780; color: var(--ink);
    margin-bottom: 16px; display: flex; align-items: center; gap: 10px;
}
.cal-month-title span {
    font-size: 12px; font-weight: 600; color: var(--muted);
    background: var(--surface); border: 1px solid var(--border-soft);
    border-radius: 999px; padding: 2px 10px;
}

.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}
.cal-dow {
    font-size: 11px; font-weight: 760; text-transform: uppercase;
    letter-spacing: .08em; color: var(--muted); text-align: center;
    padding: 6px 0 10px;
}
.cal-dow.sun { color: #e07a7a; }

.cal-cell {
    min-height: 90px;
    border-radius: var(--radius-sm);
    padding: 8px 10px;
    border: 1px solid transparent;
    transition: border-color .15s, box-shadow .15s;
    position: relative;
    background: transparent;
}
.cal-cell.empty { background: transparent; border-color: transparent; }
.cal-cell.out-of-month {
    background: transparent;
    border-color: transparent;
    opacity: .25;
}
.cal-cell.is-rest {
    background: var(--surface);
    border-color: var(--border-soft);
}
.cal-cell.has-tasks {
    background: var(--surface);
    border-color: var(--border-soft);
    cursor: pointer;
    box-shadow: var(--shadow-sm);
}
.cal-cell.has-tasks:hover {
    border-color: var(--brand-muted);
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}
.cal-cell.is-today {
    border-color: var(--brand) !important;
    box-shadow: 0 0 0 2px var(--brand-soft);
}
.cal-cell.all-done {
    border-color: #34d399 !important;
    background: #f0fdf6;
}

.cal-cell-top {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 5px;
}
.cal-cell-num {
    font-size: 13px; font-weight: 780; color: var(--ink);
}
.cal-cell.is-rest .cal-cell-num { color: var(--muted); }
.cal-cell.is-today .cal-cell-num {
    background: var(--brand); color: #fff;
    border-radius: 50%; width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px;
}
.cal-cell-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.cal-cell-theme {
    font-size: 11.5px; color: var(--ink-2); line-height: 1.4;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; margin-bottom: 6px;
}
.cal-cell.is-rest .cal-cell-theme {
    font-size: 11px; color: var(--muted); font-style: italic;
}

.cal-mini-bar {
    height: 4px; background: var(--bg); border-radius: 2px;
    overflow: hidden; border: 1px solid var(--border-soft);
    margin-top: auto;
}
.cal-mini-fill {
    height: 100%; background: linear-gradient(90deg, var(--brand), #34d399);
    border-radius: 2px; transition: width .4s;
}
.cal-mini-pct {
    font-size: 10px; color: var(--muted); text-align: right;
    margin-top: 3px;
}
.cal-mini-pct.done-all { color: #059669; font-weight: 700; }

/* ─── MODALE GIORNO ─────────────────────────────────────────────────────────── */
.cal-modal-overlay {
    position: fixed; inset: 0; z-index: 1000;
    background: rgba(0,0,0,.45);
    display: none; align-items: flex-start; justify-content: flex-end;
    padding: 20px;
}
.cal-modal-overlay.open { display: flex; }

.cal-modal-panel {
    width: 480px; max-width: calc(100vw - 40px);
    max-height: calc(100vh - 40px);
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    display: flex; flex-direction: column;
    overflow: hidden;
    animation: slideIn .2s ease;
}
@keyframes slideIn {
    from { transform: translateX(30px); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}

.cal-modal-header {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 20px; border-bottom: 1px solid var(--border-soft);
    flex-shrink: 0;
}
.cal-modal-title-main { font-size: 15px; font-weight: 780; color: var(--ink); flex: 1; }
.cal-modal-title-sub  { font-size: 12px; color: var(--muted); margin-top: 2px; }
.cal-modal-close {
    width: 28px; height: 28px; border: none; background: var(--surface-2);
    border-radius: 50%; font-size: 16px; cursor: pointer; color: var(--muted);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    font-family: inherit;
}
.cal-modal-close:hover { background: var(--red-soft); color: var(--red); }

.cal-modal-progress {
    padding: 10px 20px; border-bottom: 1px solid var(--border-soft);
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
    background: var(--bg);
}
.cal-modal-body {
    overflow-y: auto; padding: 0 20px 16px; flex: 1;
}
.cal-modal-body::-webkit-scrollbar { width: 4px; }
.cal-modal-body::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

/* Switch week button nel modal */
.cal-modal-goto {
    display: flex; align-items: center; gap: 6px;
    font-size: 11.5px; color: var(--brand); background: var(--brand-soft);
    border: 1px solid var(--brand-muted); border-radius: var(--radius-sm);
    padding: 4px 10px; cursor: pointer; font-family: inherit; font-weight: 600;
    flex-shrink: 0;
    transition: background .15s;
}
.cal-modal-goto:hover { background: #d1fae5; }

/* ─── RESPONSIVE ─────────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
    .cal-grid { gap: 3px; }
    .cal-cell { min-height: 70px; padding: 5px 6px; }
    .cal-cell-theme { display: none; }
    .cal-mini-pct { display: none; }
}
@media (max-width: 768px) {
    .tl-container { height: auto; }
    #view-list { flex-direction: column; max-height: calc(100vh - 165px); }
    .tl-sidebar { width: 100%; max-height: 180px; border-right: none; border-bottom: 1px solid var(--border-soft); display: flex; overflow-x: auto; padding: 10px; gap: 6px; }
    .month-group { display: flex; gap: 4px; flex-shrink: 0; flex-direction: row; align-items: center; }
    .month-label { display: none; }
    .tl-main { padding: 16px; }
    #view-cal { padding: 16px; }
    .cal-modal-panel { width: 100%; max-width: none; border-radius: var(--radius) var(--radius) 0 0; position: fixed; bottom: 0; right: 0; left: 0; max-height: 80vh; }
    .cal-modal-overlay { align-items: flex-end; padding: 0; }
}
</style>

{{-- ─── TOGGLE ─────────────────────────────────────────────────────────────── --}}
<div class="tl-container">

    <div class="view-toggle-bar">
        <button class="vt-btn active" id="btn-view-list" onclick="setView('list')">
            <span class="vt-icon">☰</span> Lista
        </button>
        <button class="vt-btn" id="btn-view-cal" onclick="setView('cal')">
            <span class="vt-icon">⊞</span> Calendario
        </button>
    </div>

    {{-- ─── VISTA LISTA ─────────────────────────────────────────────────────── --}}
    <div id="view-list">
        <div class="tl-sidebar" id="tl-sidebar">
            @php $months = [6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto']; @endphp
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
                            $allDone    = ($dayTotal > 0 && $dayTotal === $dayDone);
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

    {{-- ─── VISTA CALENDARIO ───────────────────────────────────────────────── --}}
    <div id="view-cal" style="display:none">
        @php
            $calMonths = [
                6 => ['name' => 'Giugno',  'year' => 2026, 'days' => 30],
                7 => ['name' => 'Luglio',  'year' => 2026, 'days' => 31],
                8 => ['name' => 'Agosto',  'year' => 2026, 'days' => 31],
            ];
            $tagLabels = ['sales'=>'Sales','content'=>'Contenuto','ops'=>'Operativo','ads'=>'Ads','smm'=>'SMM'];
            $today     = date('Y-m-d');
        @endphp

        @foreach ($calMonths as $mNum => $mInfo)
            @php
                $firstDow = (int)(new DateTime("2026-{$mNum}-01"))->format('N'); // 1=Mon … 7=Sun
                $emptyCells = $firstDow - 1; // celle vuote prima del 1°
            @endphp
            <div class="cal-month-section">
                <div class="cal-month-title">
                    {{ $mInfo['name'] }} <span>{{ $mInfo['year'] }}</span>
                </div>

                <div class="cal-grid">
                    {{-- Intestazioni giorni --}}
                    @foreach (['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $dLabel)
                        <div class="cal-dow {{ $dLabel === 'Dom' ? 'sun' : '' }}">{{ $dLabel }}</div>
                    @endforeach

                    {{-- Celle vuote all'inizio --}}
                    @for ($e = 0; $e < $emptyCells; $e++)
                        <div class="cal-cell empty"></div>
                    @endfor

                    {{-- Giorni del mese --}}
                    @for ($d = 1; $d <= $mInfo['days']; $d++)
                        @php
                            $dateKey   = sprintf('2026-%02d-%02d', $mNum, $d);
                            $cellDow   = (int)(new DateTime($dateKey))->format('N'); // 1=Mon…7=Sun
                            $isSunday  = ($cellDow === 7);
                            $isToday   = ($dateKey === $today);
                            $hasTasks  = isset($calData[$dateKey]);
                            $cInfo     = $hasTasks ? $calData[$dateKey] : null;
                            $allDone   = $hasTasks && $cInfo['total'] > 0 && $cInfo['done'] === $cInfo['total'];

                            $classes = ['cal-cell'];
                            if ($isSunday)   $classes[] = 'is-rest';
                            elseif ($hasTasks) $classes[] = 'has-tasks';
                            if ($isToday)    $classes[] = 'is-today';
                            if ($allDone)    $classes[] = 'all-done';
                        @endphp

                        <div
                            class="{{ implode(' ', $classes) }}"
                            @if ($hasTasks)
                                id="cal-cell-{{ $dateKey }}"
                                onclick="openDayModal('{{ $dateKey }}')"
                                title="{{ $cInfo['day']['theme'] }}"
                            @endif
                        >
                            <div class="cal-cell-top">
                                <div class="cal-cell-num">{{ $d }}</div>
                                @if ($hasTasks)
                                    <div class="cal-cell-dot" style="background:{{ $cInfo['week']['color'] }}"></div>
                                @endif
                            </div>

                            @if ($isSunday)
                                <div class="cal-cell-theme">riposo</div>
                            @elseif ($hasTasks)
                                <div class="cal-cell-theme">{{ $cInfo['day']['theme'] }}</div>
                                <div class="cal-mini-bar">
                                    <div
                                        class="cal-mini-fill"
                                        id="cal-bar-{{ $dateKey }}"
                                        style="width:{{ $cInfo['pct'] }}%"
                                    ></div>
                                </div>
                                <div class="cal-mini-pct {{ $allDone ? 'done-all' : '' }}" id="cal-pct-{{ $dateKey }}">
                                    @if ($allDone)
                                        ✓ completato
                                    @elseif ($cInfo['total'] > 0)
                                        {{ $cInfo['done'] }}/{{ $cInfo['total'] }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endfor

                    {{-- Celle vuote alla fine (riempi l'ultima riga) --}}
                    @php
                        $lastDow    = (int)(new DateTime("2026-{$mNum}-{$mInfo['days']}"))->format('N');
                        $trailingCells = $lastDow < 7 ? 7 - $lastDow : 0;
                    @endphp
                    @for ($e = 0; $e < $trailingCells; $e++)
                        <div class="cal-cell empty"></div>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>

</div>{{-- /tl-container --}}

{{-- ─── MODALE DETTAGLIO GIORNO ──────────────────────────────────────────────── --}}
<div class="cal-modal-overlay" id="cal-modal-overlay" onclick="handleModalOverlayClick(event)">
    <div class="cal-modal-panel" id="cal-modal-panel">
        <div class="cal-modal-header">
            <div style="flex:1">
                <div class="cal-modal-title-main" id="cal-modal-title-main">—</div>
                <div class="cal-modal-title-sub"  id="cal-modal-title-sub"></div>
            </div>
            <button class="cal-modal-goto" id="cal-modal-goto-btn" onclick="gotoWeekFromModal()">
                → Vai alla settimana
            </button>
            <button class="cal-modal-close" onclick="closeDayModal()">×</button>
        </div>
        <div class="cal-modal-progress" id="cal-modal-progress-wrap">
            <span class="op-label" style="font-size:12px">Progressi:</span>
            <div class="op-bar" style="flex:1">
                <div class="op-fill" id="cal-modal-bar" style="width:0%"></div>
            </div>
            <span class="op-pct" id="cal-modal-pct">0/0</span>
        </div>
        <div class="cal-modal-body" id="cal-modal-body">
            {{-- Contenuto iniettato da JS --}}
        </div>
    </div>
</div>

{{-- Holder nascosto per i template del modal --}}
<div id="modal-tpl-holder" style="display:none">
@foreach ($weeks as $wi => $week)
    @foreach ($week['days'] as $di => $day)
        <div id="modal-tpl-{{ $week['id'] }}-{{ $di }}" style="display:none">
            @foreach ($day['blocks'] as $bi => $block)
                <div class="time-block" style="padding-top:14px">
                    <div class="time-label">{{ $block['time'] }} — {{ $block['label'] }}</div>
                    @foreach ($block['tasks'] as $ti => $task)
                        @php
                            $tKey = "{$week['id']}_{$di}_{$bi}_{$ti}";
                            $done = isset($completedKeys[$tKey]);
                        @endphp
                        <div
                            class="task-item"
                            id="mtask-{{ $tKey }}"
                            onclick="toggleTask('{{ $tKey }}', this, true)"
                        >
                            <div class="task-cb {{ $done ? 'done' : '' }}" id="mcb-{{ $tKey }}"></div>
                            <div class="task-text {{ $done ? 'done' : '' }}" id="mtxt-{{ $tKey }}">{{ $task['text'] }}</div>
                            <span class="task-tag tag-{{ $task['tag'] }}">
                                {{ $tagLabels[$task['tag']] ?? $task['tag'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
@endforeach
</div>{{-- /modal-tpl-holder --}}

@push('scripts')
<script>
const TOGGLE_URL = "{{ route('todolist.toggle') }}";
const CSRF       = "{{ csrf_token() }}";

/* ── Stato task ─────────────────────────────────────────────────────────────── */
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

/* Mappa data → info settimana+giorno per il calendario */
const calDayMap = {
@foreach ($calData as $dateKey => $info)
    '{{ $dateKey }}': {
        wid:    '{{ $info['week']['id'] }}',
        wi:     {{ $info['wi'] }},
        di:     {{ $info['di'] }},
        label:  '{{ $info['day']['name'] }}',
        theme:  @json($info['day']['theme']),
        hours:  '{{ $info['day']['hours'] }}',
        color:  '{{ $info['week']['color'] }}',
        wLabel: '{{ $info['week']['label'] }}',
        wDates: '{{ $info['week']['dates'] }}',
        keys:   [
            @foreach ($info['day']['blocks'] as $bi => $block)
                @foreach ($block['tasks'] as $ti => $task)
                    '{{ $info['week']['id'] }}_{{ $info['di'] }}_{{ $bi }}_{{ $ti }}',
                @endforeach
            @endforeach
        ],
    },
@endforeach
};

/* ── Toggle task (funziona sia in lista che in modal) ───────────────────────── */
function toggleTask(key, el, fromModal) {
    const cb  = document.getElementById('cb-' + key);
    const txt = document.getElementById('txt-' + key);
    const mcb  = document.getElementById('mcb-' + key);
    const mtxt = document.getElementById('mtxt-' + key);

    const wasDone = taskState[key];
    const nowDone = !wasDone;
    taskState[key] = nowDone;

    [cb, mcb].forEach(e  => e  && e.classList.toggle('done', nowDone));
    [txt, mtxt].forEach(e => e  && e.classList.toggle('done', nowDone));

    const weekId = key.split('_')[0];
    updateProgress(weekId);
    updateCalendarCell(weekId, key);
    if (fromModal) updateModalProgress();

    fetch(TOGGLE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ task_key: key }),
    }).catch(() => {
        taskState[key] = wasDone;
        [cb, mcb].forEach(e  => e  && e.classList.toggle('done', wasDone));
        [txt, mtxt].forEach(e => e  && e.classList.toggle('done', wasDone));
        updateProgress(weekId);
        updateCalendarCell(weekId, key);
        if (fromModal) updateModalProgress();
    });
}

/* ── Aggiorna barra progresso settimana (lista) ─────────────────────────────── */
function updateProgress(weekId) {
    const keys  = weekKeys[weekId] || [];
    const total = keys.length;
    const done  = keys.filter(k => taskState[k]).length;
    const pct   = total ? Math.round(done / total * 100) : 0;

    const bar   = document.getElementById('bar-' + weekId);
    const prog  = document.getElementById('prog-' + weekId);
    const pctEl = document.getElementById('pct-' + weekId);
    if (bar)   bar.style.width = pct + '%';
    if (prog)  prog.textContent = done + '/' + total + ' (' + pct + '%)';
    if (pctEl) pctEl.textContent = pct + '%';
}

/* ── Aggiorna cella calendario dopo toggle ──────────────────────────────────── */
function updateCalendarCell(weekId, changedKey) {
    Object.entries(calDayMap).forEach(([dateKey, info]) => {
        if (info.wid !== weekId) return;
        if (!info.keys.includes(changedKey)) return;

        const total = info.keys.length;
        const done  = info.keys.filter(k => taskState[k]).length;
        const pct   = total ? Math.round(done / total * 100) : 0;

        const bar   = document.getElementById('cal-bar-' + dateKey);
        const pctEl = document.getElementById('cal-pct-' + dateKey);
        const cell  = document.getElementById('cal-cell-' + dateKey);

        if (bar)   bar.style.width = pct + '%';
        if (pctEl) {
            if (done === total && total > 0) {
                pctEl.textContent = '✓ completato';
                pctEl.className = 'cal-mini-pct done-all';
            } else {
                pctEl.textContent = done + '/' + total;
                pctEl.className = 'cal-mini-pct';
            }
        }
        if (cell) {
            cell.classList.toggle('all-done', done === total && total > 0);
        }
    });
}

/* ── Navigazione vista lista ─────────────────────────────────────────────────── */
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

/* ── Cambio vista Lista / Calendario ─────────────────────────────────────────── */
function setView(view) {
    const isList = view === 'list';
    document.getElementById('view-list').style.display   = isList  ? '' : 'none';
    document.getElementById('view-cal').style.display    = !isList ? '' : 'none';
    document.getElementById('btn-view-list').classList.toggle('active', isList);
    document.getElementById('btn-view-cal').classList.toggle('active', !isList);
    localStorage.setItem('tl-view', view);
}

/* ── Modale giorno ───────────────────────────────────────────────────────────── */
let modalCurrentDate = null;
let modalActiveTpl   = null;   // elemento mosso nel modal body

function openDayModal(dateKey) {
    const info = calDayMap[dateKey];
    if (!info) return;

    // Rimetti eventuale template precedente nell'holder
    _returnModalTpl();

    modalCurrentDate = dateKey;

    document.getElementById('cal-modal-title-main').textContent =
        info.label + ' — ' + info.theme;
    document.getElementById('cal-modal-title-sub').textContent =
        info.wLabel + '  ·  ' + info.wDates + '  ·  ' + info.hours;

    // MOVE (non clone) — gli ID rimangono unici nel DOM
    const tpl  = document.getElementById('modal-tpl-' + info.wid + '-' + info.di);
    const body = document.getElementById('cal-modal-body');
    body.innerHTML = '';
    if (tpl) {
        tpl.style.display = '';
        body.appendChild(tpl);
        modalActiveTpl = tpl;
    }

    updateModalProgress();
    document.getElementById('cal-modal-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function _returnModalTpl() {
    if (!modalActiveTpl) return;
    modalActiveTpl.style.display = 'none';
    document.getElementById('modal-tpl-holder').appendChild(modalActiveTpl);
    modalActiveTpl = null;
}

function updateModalProgress() {
    if (!modalCurrentDate) return;
    const info  = calDayMap[modalCurrentDate];
    if (!info) return;
    const total = info.keys.length;
    const done  = info.keys.filter(k => taskState[k]).length;
    const pct   = total ? Math.round(done / total * 100) : 0;
    document.getElementById('cal-modal-bar').style.width = pct + '%';
    document.getElementById('cal-modal-pct').textContent = done + '/' + total + ' (' + pct + '%)';
}

function closeDayModal() {
    _returnModalTpl();
    document.getElementById('cal-modal-overlay').classList.remove('open');
    document.getElementById('cal-modal-body').innerHTML = '';
    document.body.style.overflow = '';
    modalCurrentDate = null;
}

function handleModalOverlayClick(e) {
    if (e.target === document.getElementById('cal-modal-overlay')) closeDayModal();
}

function gotoWeekFromModal() {
    if (!modalCurrentDate) return;
    const info = calDayMap[modalCurrentDate];
    if (!info) return;
    closeDayModal();
    setView('list');
    switchWeek(info.wid);
    // Apri il giorno
    setTimeout(() => {
        const dayId = 'day-' + info.wid + '-' + info.di;
        const body  = document.getElementById('body-' + dayId);
        if (body && !body.classList.contains('open')) toggleDay(dayId);
        const card  = document.getElementById('card-' + dayId);
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

/* Chiudi modale con ESC */
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDayModal(); });

/* ── Ripristino vista salvata ───────────────────────────────────────────────── */
(function() {
    const saved = localStorage.getItem('tl-view');
    if (saved === 'cal') setView('cal');
})();
</script>
@endpush

@endsection
