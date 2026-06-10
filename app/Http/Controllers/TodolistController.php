<?php

namespace App\Http\Controllers;

use App\Models\CentralTodolistHole;
use App\Models\TodolistSummaryDay;
use App\Models\TodolistTask;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TodolistController extends Controller
{
    /* ══════════════════════════════════════════════════════════════════════════
     * HELPERS
     * ═════════════════════════════════════════════════════════════════════════*/

    /** Struttura settimane/giorni da DB (sostituisce config) */
    private function loadWeeks(): array
    {
        $grouped = [];
        foreach (TodolistSummaryDay::orderBy('id')->get() as $sd) {
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

    /** Tutti i giorni dal corrente (incluso) in avanti, ordinati per id */
    private function getAllDaysFrom(string $weekId, int $dayIndex): array
    {
        $id = TodolistSummaryDay::where('week_id', $weekId)->where('day_index', $dayIndex)->value('id');
        if (!$id) return [];
        return TodolistSummaryDay::where('id', '>=', $id)->orderBy('id')
            ->get(['week_id', 'day_index'])
            ->map(fn($d) => ['week_id' => $d->week_id, 'day_index' => $d->day_index])
            ->toArray();
    }

    /** Tutti i giorni DOPO il corrente, ordinati per id */
    private function getOrderedDaysAfter(string $weekId, int $dayIndex): array
    {
        $id = TodolistSummaryDay::where('week_id', $weekId)->where('day_index', $dayIndex)->value('id');
        if (!$id) return [];
        return TodolistSummaryDay::where('id', '>', $id)->orderBy('id')
            ->get(['week_id', 'day_index'])
            ->map(fn($d) => ['week_id' => $d->week_id, 'day_index' => $d->day_index])
            ->toArray();
    }

    /** Slot 0-5 occupati da buchi su un giorno */
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

    /** Mette le task in overflow dell'ultimo giorno disponibile */
    private function putInOverflow(Collection $tasks, string $weekId, int $dayIndex): void
    {
        $maxSort = (int)(TodolistTask::where('week_id', $weekId)
            ->where('day_index', $dayIndex)
            ->where('block_index', 99)
            ->max('sort_order') ?? -1);

        foreach ($tasks as $task) {
            if ($task->original_week_id === null) {
                $task->original_week_id     = $task->week_id;
                $task->original_day_index   = $task->day_index;
                $task->original_block_index = $task->block_index;
            }
            $task->week_id     = $weekId;
            $task->day_index   = $dayIndex;
            $task->block_index = 99;
            $task->sort_order  = ++$maxSort;
            $task->save();
        }
    }

    /**
     * Cascata waterfall: le task $displaced fluiscono nel primo slot libero
     * disponibile da ($startWeekId, $startDayIndex, $startSlot) in avanti.
     * Per ogni giorno: le displaced vanno PRIMA delle task esistenti sbloccate.
     */
    private function runCascade(Collection $displaced, string $startWeekId, int $startDayIndex, int $startSlot): void
    {
        // Se startSlot >= 6 passiamo al giorno successivo
        if ($startSlot >= 6) {
            $next = $this->getOrderedDaysAfter($startWeekId, $startDayIndex);
            if (empty($next)) {
                $this->putInOverflow($displaced, $startWeekId, $startDayIndex);
                return;
            }
            $startWeekId  = $next[0]['week_id'];
            $startDayIndex = $next[0]['day_index'];
            $startSlot    = 0;
        }

        $allDays = $this->getAllDaysFrom($startWeekId, $startDayIndex);
        $pending = $displaced->sortBy('block_index')->values();
        $isFirst = true;

        foreach ($allDays as $d) {
            if ($pending->isEmpty()) break;

            $wid = $d['week_id'];
            $di  = $d['day_index'];
            $dk  = "{$wid}_{$di}";

            $lockedSlots = TodolistTask::where('week_id', $wid)
                ->where('day_index', $di)->where('locked', true)
                ->whereBetween('block_index', [0, 5])->pluck('block_index')->toArray();

            $holeSlots = $this->getHoleSlotsForDay($dk);

            $existingUnlocked = TodolistTask::where('week_id', $wid)
                ->where('day_index', $di)->where('locked', false)
                ->whereBetween('block_index', [0, 5])->orderBy('block_index')->get();

            $range     = $isFirst ? range($startSlot, 5) : range(0, 5);
            $freeSlots = array_values(array_diff($range, $lockedSlots, $holeSlots));

            $pool       = $pending->concat($existingUnlocked)->values();
            $toPlace    = $pool->take(count($freeSlots));
            $newPending = $pool->slice(count($freeSlots))->values();

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
            $isFirst = false;
        }

        if ($pending->isNotEmpty()) {
            $lastDay = !empty($allDays) ? end($allDays) : ['week_id' => $startWeekId, 'day_index' => $startDayIndex];
            $this->putInOverflow($pending, $lastDay['week_id'], $lastDay['day_index']);
        }
    }

    /* ══════════════════════════════════════════════════════════════════════════
     * ROUTES
     * ═════════════════════════════════════════════════════════════════════════*/

    public function index()
    {
        $weeks = $this->loadWeeks();

        $allTasks = TodolistTask::orderBy('block_index')->orderBy('sort_order')->get();
        $tasks    = $allTasks
            ->groupBy(fn($t) => $t->week_id . '_' . $t->day_index)
            ->map(fn($g) => $g->groupBy('block_index'));

        $holes      = CentralTodolistHole::all()->groupBy('day_key');
        $totalTasks = $allTasks->count();
        $doneTasks  = $allTasks->where('is_done', true)->count();

        // Lista giorni ordinata per JS (preview multi-giorno nel modal buco)
        $orderedDays = TodolistSummaryDay::orderBy('id')
            ->get(['week_id', 'day_index', 'day_name'])
            ->map(fn($d) => ['key' => $d->week_id . '_' . $d->day_index, 'name' => $d->day_name])
            ->toArray();

        return view('todolist.index', compact('weeks', 'tasks', 'holes', 'totalTasks', 'doneTasks', 'orderedDays'));
    }

    public function toggle(Request $request)
    {
        $id = $request->input('task_id');
        if (!$id || !is_numeric($id) || (int)$id <= 0)
            return response()->json(['error' => 'ID non valido'], 422);

        $task = TodolistTask::findOrFail((int)$id);
        $task->is_done = !$task->is_done;
        $task->save();
        return response()->json(['done' => $task->is_done]);
    }

    public function toggleLock(int $id)
    {
        $task = TodolistTask::findOrFail($id);
        $task->locked = !$task->locked;
        $task->save();
        return response()->json(['locked' => $task->locked]);
    }

    /** Elimina una task non bloccata */
    public function deleteTask(int $id)
    {
        $task = TodolistTask::findOrFail($id);
        if ($task->locked) {
            return response()->json(['error' => 'La task è bloccata — sbloccala prima di eliminarla.'], 422);
        }
        $task->delete();
        return response()->json(['ok' => true]);
    }

    public function storeHole(Request $request)
    {
        $validated = $request->validate([
            'day_key'          => ['required', 'regex:/^w\d+_\d+$/', 'max:20'],
            'label'            => ['required', 'string', 'max:200'],
            'time_label'       => ['nullable', 'string', 'max:40'],
            'slot_start'       => ['required', 'integer', 'min:-1', 'max:5'],
            'total_slot_count' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $slotStart      = $validated['slot_start'];
        $totalSlotCount = $validated['total_slot_count'];
        $label          = $validated['label'];
        $timeLabel      = $validated['time_label'] ?? null;

        // Buco solo visivo (nessuna cascata)
        if ($slotStart === -1) {
            CentralTodolistHole::create([
                'day_key'      => $validated['day_key'],
                'label'        => $label,
                'time_label'   => $timeLabel,
                'insert_after' => -1,
                'slot_count'   => 1,
                'group_id'     => null,
            ]);
            return response()->json(['ok' => true]);
        }

        // Calcola giorni toccati dal buco
        [$weekId, $dayIndex] = explode('_', $validated['day_key']);
        $dayIndex = (int) $dayIndex;

        $allDays      = $this->getAllDaysFrom($weekId, $dayIndex);
        $remaining    = $totalSlotCount;
        $currentSlot  = $slotStart;
        $affectedDays = [];

        foreach ($allDays as $day) {
            if ($remaining <= 0) break;
            $slotsHere      = min($remaining, 6 - $currentSlot);
            $affectedDays[] = [
                'week_id'    => $day['week_id'],
                'day_index'  => $day['day_index'],
                'slot_start' => $currentSlot,
                'slot_count' => $slotsHere,
            ];
            $remaining   -= $slotsHere;
            $currentSlot  = 0;
        }

        // Crea record hole (uno per giorno toccato), collegati da group_id
        $groupId = null;
        foreach ($affectedDays as $i => $a) {
            $hole = CentralTodolistHole::create([
                'day_key'      => $a['week_id'] . '_' . $a['day_index'],
                'label'        => $label . ($i > 0 ? ' (cont.)' : ''),
                'time_label'   => $i === 0 ? $timeLabel : null,
                'insert_after' => $a['slot_start'],
                'slot_count'   => $a['slot_count'],
                'group_id'     => $groupId,
            ]);
            if ($groupId === null) {
                $groupId = $hole->id;
                $hole->group_id = $groupId;
                $hole->save();
            }
        }

        // Raccoglie tutte le task spostate dai giorni coperti (force-unlock se bloccate)
        $allDisplaced = collect();
        foreach ($affectedDays as $a) {
            $slotEnd = $a['slot_start'] + $a['slot_count'] - 1;
            $chunk   = TodolistTask::where('week_id', $a['week_id'])
                ->where('day_index', $a['day_index'])
                ->whereBetween('block_index', [$a['slot_start'], $slotEnd])
                ->orderBy('block_index')
                ->get();
            foreach ($chunk as $task) {
                if ($task->locked) $task->locked = false;
                if ($task->original_week_id === null) {
                    $task->original_week_id     = $task->week_id;
                    $task->original_day_index   = $task->day_index;
                    $task->original_block_index = $task->block_index;
                }
            }
            $allDisplaced = $allDisplaced->concat($chunk);
        }

        // Cascata dal primo slot libero dopo il buco
        if ($allDisplaced->isNotEmpty()) {
            $last      = end($affectedDays);
            $nextSlot  = $last['slot_start'] + $last['slot_count'];
            $this->runCascade($allDisplaced, $last['week_id'], $last['day_index'], $nextSlot);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyHole(int $id)
    {
        $hole    = CentralTodolistHole::findOrFail($id);
        $groupId = $hole->group_id;
        // Elimina tutti i record del gruppo (il group_id del primo è il suo stesso id)
        CentralTodolistHole::where('group_id', $groupId)->delete();
        if (!$hole->exists) {
            // già eliminato sopra
        } else {
            $hole->delete();
        }
        return response()->json(['ok' => true]);
    }

    public function reset()
    {
        TodolistTask::query()->update(['is_done' => false]);
        return redirect()->route('todolist.index')->with('success', 'Progressi azzerati.');
    }

    public function reseedTasks()
    {
        TodolistTask::truncate();
        CentralTodolistHole::truncate();
        TodolistSummaryDay::truncate();

        $monthAbbr = ['Giu' => 6, 'Lug' => 7, 'Ago' => 8, 'Set' => 9, 'Ott' => 10];
        $weeks     = config('todolist_data');

        // Summary days
        $summaryBuf = [];
        foreach ($weeks as $week) {
            preg_match('/^([A-Za-z]+)\s+(\d+)/u', $week['dates'], $m);
            $sm   = $monthAbbr[$m[1]] ?? 6;
            $sd   = (int)($m[2] ?? 1);
            $base = new DateTime("2026-{$sm}-{$sd}");
            foreach ($week['days'] as $di => $day) {
                $dt = clone $base;
                $dt->modify("+{$di} days");
                $summaryBuf[] = [
                    'week_id'       => $week['id'],
                    'week_label'    => $week['label'],
                    'week_color'    => $week['color'] ?? '#6366f1',
                    'week_month'    => $week['month'] ?? $sm,
                    'week_dates'    => $week['dates'],
                    'week_subtitle' => $week['subtitle'] ?? null,
                    'week_focus'    => $week['focus'] ?? null,
                    'week_goals'    => json_encode($week['goals'] ?? []),
                    'day_index'     => $di,
                    'day_name'      => $day['name'],
                    'day_theme'     => $day['theme'] ?? null,
                    'day_hours'     => $day['hours'] ?? null,
                    'calendar_date' => $dt->format('Y-m-d'),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
        }
        TodolistSummaryDay::insert($summaryBuf);

        // Tasks (max 6/giorno, cascata overflow)
        $taskBuf  = [];
        $overflow = [];
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
                foreach (array_slice($all, 0, 6) as $si => $task) {
                    $taskBuf[] = [
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
                if (count($taskBuf) >= 100) {
                    TodolistTask::insert($taskBuf);
                    $taskBuf = [];
                }
            }
        }
        if (!empty($taskBuf)) TodolistTask::insert($taskBuf);

        return redirect()->route('todolist.index')->with('success', 'Piano ripristinato.');
    }
}
