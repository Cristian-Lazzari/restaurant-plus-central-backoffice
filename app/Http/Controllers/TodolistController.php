<?php

namespace App\Http\Controllers;

use App\Models\CentralTodolistHole;
use App\Models\TodolistSummaryDay;
use App\Models\TodolistTask;
use DateTime;
use Illuminate\Http\Request;

class TodolistController extends Controller
{
    /* ── Struttura settimane da DB ──────────────────────────────────────────── */
    private function loadWeeks(): array
    {
        $summaryDays = TodolistSummaryDay::orderBy('id')->get();
        $grouped = [];

        foreach ($summaryDays as $sd) {
            if (!isset($grouped[$sd->week_id])) {
                $grouped[$sd->week_id] = [
                    'id'       => $sd->week_id,
                    'label'    => $sd->week_label,
                    'color'    => $sd->week_color,
                    'month'    => $sd->week_month,
                    'dates'    => $sd->week_dates,
                    'subtitle' => $sd->week_subtitle,
                    'focus'    => $sd->week_focus,
                    'goals'    => $sd->week_goals ?? [],
                    'days'     => [],
                ];
            }
            $grouped[$sd->week_id]['days'][] = [
                'name'          => $sd->day_name,
                'theme'         => $sd->day_theme,
                'hours'         => $sd->day_hours,
                'calendar_date' => $sd->calendar_date?->format('Y-m-d'),
                'blocks'        => [],
            ];
        }

        return array_values($grouped);
    }

    /* ── Ordine giorni da DB (per cascata) ──────────────────────────────────── */
    private function getOrderedDaysAfter(string $weekId, int $dayIndex): array
    {
        $current = TodolistSummaryDay::where('week_id', $weekId)
            ->where('day_index', $dayIndex)
            ->value('id');

        if (!$current) return [];

        return TodolistSummaryDay::where('id', '>', $current)
            ->orderBy('id')
            ->get(['week_id', 'day_index'])
            ->map(fn($d) => ['week_id' => $d->week_id, 'day_index' => $d->day_index])
            ->toArray();
    }

    /* ── Slot occupati dai buchi di un giorno ───────────────────────────────── */
    private function getHoleSlotsForDay(string $dayKey): array
    {
        $slots = [];
        foreach (CentralTodolistHole::where('day_key', $dayKey)->where('insert_after', '>=', 0)->get() as $hole) {
            for ($s = $hole->insert_after; $s <= min($hole->insert_after + $hole->slot_count - 1, 5); $s++) {
                $slots[] = $s;
            }
        }
        return array_unique($slots);
    }

    /* ── WATERFALL CASCADE ──────────────────────────────────────────────────── */
    private function cascadeFromHole(string $weekId, int $dayIndex, int $slotStart, int $slotCount): void
    {
        $slotEnd = min($slotStart + $slotCount - 1, 5);

        // 1. Task nei slot coperti dal buco (incluse le bloccate → force-unlock)
        $displaced = TodolistTask::where('week_id', $weekId)
            ->where('day_index', $dayIndex)
            ->whereBetween('block_index', [$slotStart, $slotEnd])
            ->orderBy('block_index')
            ->get();

        foreach ($displaced as $task) {
            if ($task->locked) {
                $task->locked = false; // il buco forza lo sblocco
            }
            if ($task->original_week_id === null) {
                $task->original_week_id     = $task->week_id;
                $task->original_day_index   = $task->day_index;
                $task->original_block_index = $task->block_index;
            }
        }

        if ($displaced->isEmpty()) return;

        // 2. Cascade a cascata: pending fluisce nel giorno successivo,
        //    bumpa le task esistenti che a loro volta fluiscono nel giorno dopo
        $futureDays = $this->getOrderedDaysAfter($weekId, $dayIndex);
        $pending    = $displaced->sortBy('block_index')->values();

        foreach ($futureDays as $d) {
            if ($pending->isEmpty()) break;

            $wid = $d['week_id'];
            $di  = $d['day_index'];
            $dk  = "{$wid}_{$di}";

            // Slot occupati da task bloccate (restano ferme)
            $lockedSlots = TodolistTask::where('week_id', $wid)
                ->where('day_index', $di)
                ->where('locked', true)
                ->whereBetween('block_index', [0, 5])
                ->pluck('block_index')
                ->toArray();

            // Slot occupati da buchi esistenti su questo giorno
            $holeSlots = $this->getHoleSlotsForDay($dk);

            // Task sbloccate esistenti su questo giorno → partecipano alla cascata
            $existingUnlocked = TodolistTask::where('week_id', $wid)
                ->where('day_index', $di)
                ->where('locked', false)
                ->whereBetween('block_index', [0, 5])
                ->orderBy('block_index')
                ->get();

            // Slot liberi su questo giorno
            $freeSlots = array_values(array_diff(range(0, 5), $lockedSlots, $holeSlots));

            // Pool: pending PRIMA (hanno priorità), poi le esistenti del giorno
            $pool       = $pending->concat($existingUnlocked)->values();
            $toPlace    = $pool->take(count($freeSlots));
            $newPending = $pool->slice(count($freeSlots))->values(); // overflow → giorno dopo

            foreach ($toPlace as $idx => $task) {
                if ($task->original_week_id === null) {
                    $task->original_week_id     = $task->week_id;
                    $task->original_day_index   = $task->day_index;
                    $task->original_block_index = $task->block_index;
                }
                $task->week_id     = $wid;
                $task->day_index   = $di;
                $task->block_index = $freeSlots[$idx];
                $task->sort_order  = 0;
                $task->save();
            }

            $pending = $newPending;
        }

        // Task rimaste senza posto → overflow dell'ultimo giorno disponibile
        if ($pending->isNotEmpty()) {
            $lastDay = !empty($futureDays)
                ? end($futureDays)
                : ['week_id' => $weekId, 'day_index' => $dayIndex];

            $maxSort = (int) (TodolistTask::where('week_id', $lastDay['week_id'])
                ->where('day_index', $lastDay['day_index'])
                ->where('block_index', 99)
                ->max('sort_order') ?? -1);

            foreach ($pending as $task) {
                if ($task->original_week_id === null) {
                    $task->original_week_id     = $task->week_id;
                    $task->original_day_index   = $task->day_index;
                    $task->original_block_index = $task->block_index;
                }
                $task->week_id     = $lastDay['week_id'];
                $task->day_index   = $lastDay['day_index'];
                $task->block_index = 99;
                $task->sort_order  = ++$maxSort;
                $task->save();
            }
        }
    }

    /* ── ROUTES ─────────────────────────────────────────────────────────────── */

    public function index()
    {
        $weeks = $this->loadWeeks();

        $allTasks = TodolistTask::orderBy('block_index')->orderBy('sort_order')->get();

        $tasks = $allTasks
            ->groupBy(fn($t) => $t->week_id . '_' . $t->day_index)
            ->map(fn($g) => $g->groupBy('block_index'));

        $holes = CentralTodolistHole::all()->groupBy('day_key');

        $totalTasks = $allTasks->count();
        $doneTasks  = $allTasks->where('is_done', true)->count();

        return view('todolist.index', compact('weeks', 'tasks', 'holes', 'totalTasks', 'doneTasks'));
    }

    public function toggle(Request $request)
    {
        $id = $request->input('task_id');
        if (!$id || !is_numeric($id) || (int) $id <= 0)
            return response()->json(['error' => 'ID non valido'], 422);

        $task          = TodolistTask::findOrFail((int) $id);
        $task->is_done = ! $task->is_done;
        $task->save();

        return response()->json(['done' => $task->is_done]);
    }

    public function toggleLock(int $id)
    {
        $task         = TodolistTask::findOrFail($id);
        $task->locked = ! $task->locked;
        $task->save();

        return response()->json(['locked' => $task->locked]);
    }

    public function storeHole(Request $request)
    {
        $validated = $request->validate([
            'day_key'    => ['required', 'regex:/^w\d+_\d+$/', 'max:20'],
            'label'      => ['required', 'string', 'max:200'],
            'time_label' => ['nullable', 'string', 'max:40'],
            'slot_start' => ['required', 'integer', 'min:-1', 'max:5'],
            'slot_count' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $hole = CentralTodolistHole::create([
            'day_key'      => $validated['day_key'],
            'label'        => $validated['label'],
            'time_label'   => $validated['time_label'] ?? null,
            'insert_after' => $validated['slot_start'],
            'slot_count'   => $validated['slot_count'],
        ]);

        if ($validated['slot_start'] >= 0) {
            [$weekId, $dayIndex] = explode('_', $validated['day_key']);
            $this->cascadeFromHole($weekId, (int) $dayIndex, $validated['slot_start'], $validated['slot_count']);
        }

        return response()->json(['id' => $hole->id]);
    }

    public function destroyHole(int $id)
    {
        CentralTodolistHole::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    public function reset()
    {
        TodolistTask::query()->update(['is_done' => false]);
        return redirect()->route('todolist.index')->with('success', 'Tutti i progressi sono stati azzerati.');
    }

    public function reseedTasks()
    {
        // Pulizia completa
        TodolistTask::truncate();
        CentralTodolistHole::truncate();
        TodolistSummaryDay::truncate();

        $monthAbbr = ['Giu' => 6, 'Lug' => 7, 'Ago' => 8, 'Set' => 9, 'Ott' => 10];
        $weeks     = config('todolist_data');

        // 1. Popolamento summary_days
        $summaryBuffer = [];
        foreach ($weeks as $week) {
            preg_match('/^([A-Za-z]+)\s+(\d+)/u', $week['dates'], $m);
            $sm   = $monthAbbr[$m[1]] ?? 6;
            $sd   = (int) ($m[2] ?? 1);
            $base = new DateTime("2026-{$sm}-{$sd}");

            foreach ($week['days'] as $di => $day) {
                $dt = clone $base;
                $dt->modify("+{$di} days");
                $summaryBuffer[] = [
                    'week_id'      => $week['id'],
                    'week_label'   => $week['label'],
                    'week_color'   => $week['color'] ?? '#6366f1',
                    'week_month'   => $week['month'] ?? $sm,
                    'week_dates'   => $week['dates'],
                    'week_subtitle'=> $week['subtitle'] ?? null,
                    'week_focus'   => $week['focus'] ?? null,
                    'week_goals'   => json_encode($week['goals'] ?? []),
                    'day_index'    => $di,
                    'day_name'     => $day['name'],
                    'day_theme'    => $day['theme'] ?? null,
                    'day_hours'    => $day['hours'] ?? null,
                    'calendar_date'=> $dt->format('Y-m-d'),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
        }
        TodolistSummaryDay::insert($summaryBuffer);

        // 2. Popolamento tasks (max 6/giorno, cascata overflow)
        $taskBuffer = [];
        $overflow   = [];

        foreach ($weeks as $week) {
            foreach ($week['days'] as $di => $day) {
                $dayTasks = [];
                foreach ($day['blocks'] as $block) {
                    foreach ($block['tasks'] as $task) {
                        $dayTasks[] = ['text' => $task['text'], 'tag' => $task['tag'] ?? 'ops'];
                    }
                }

                $all      = array_merge($overflow, $dayTasks);
                $overflow = array_slice($all, 6);
                $toInsert = array_slice($all, 0, 6);

                foreach ($toInsert as $si => $task) {
                    $taskBuffer[] = [
                        'week_id'              => $week['id'],
                        'day_index'            => $di,
                        'block_index'          => $si,
                        'sort_order'           => 0,
                        'text'                 => $task['text'],
                        'tag'                  => $task['tag'],
                        'is_done'              => false,
                        'locked'               => false,
                        'original_week_id'     => null,
                        'original_day_index'   => null,
                        'original_block_index' => null,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                if (count($taskBuffer) >= 100) {
                    TodolistTask::insert($taskBuffer);
                    $taskBuffer = [];
                }
            }
        }
        if (!empty($taskBuffer)) TodolistTask::insert($taskBuffer);

        return redirect()->route('todolist.index')->with('success', 'Piano ripristinato: summary days + task (6/giorno).');
    }
}
