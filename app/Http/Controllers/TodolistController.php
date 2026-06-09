<?php

namespace App\Http\Controllers;

use App\Models\CentralTodolistHole;
use App\Models\TodolistTask;
use Illuminate\Http\Request;

class TodolistController extends Controller
{
    public function index()
    {
        $weeks = config('todolist_data');

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
            $this->cascadeFromHole($weekId, (int)$dayIndex, $validated['slot_start'], $validated['slot_count']);
        }

        return response()->json(['id' => $hole->id]);
    }

    private function cascadeFromHole(string $weekId, int $dayIndex, int $slotStart, int $slotCount): void
    {
        $slotEnd = $slotStart + $slotCount - 1;

        // 1. Task spostate (nei slot coperti dal buco, non bloccate)
        $displaced = TodolistTask::where('week_id', $weekId)
            ->where('day_index', $dayIndex)
            ->whereBetween('block_index', [$slotStart, $slotEnd])
            ->where('locked', false)
            ->orderBy('block_index')->orderBy('sort_order')
            ->get();

        // 2. Task rimanenti stesso giorno dopo il buco (non bloccate, non overflow)
        $sameDayRest = TodolistTask::where('week_id', $weekId)
            ->where('day_index', $dayIndex)
            ->where('block_index', '>', $slotEnd)
            ->where('block_index', '<', 99)
            ->where('locked', false)
            ->orderBy('block_index')->orderBy('sort_order')
            ->get();

        // 3. Tutte le task dei giorni successivi (non bloccate, non overflow)
        $futureDays  = $this->getOrderedDaysAfter($weekId, $dayIndex);
        $futureTasks = collect();
        foreach ($futureDays as $d) {
            $chunk = TodolistTask::where('week_id', $d['week_id'])
                ->where('day_index', $d['day_index'])
                ->where('block_index', '<', 99)
                ->where('locked', false)
                ->orderBy('block_index')->orderBy('sort_order')
                ->get();
            $futureTasks = $futureTasks->concat($chunk);
        }

        // Queue: spostate PRIMA, poi same-day rest, poi future
        $queue = $displaced->concat($sameDayRest)->concat($futureTasks)->values();

        if ($queue->isEmpty()) return;

        // 4. Ridistribuisci la queue nei giorni disponibili
        $days = array_merge(
            [['week_id' => $weekId, 'day_index' => $dayIndex]],
            $futureDays
        );

        $qi = 0;

        foreach ($days as $d) {
            if ($qi >= $queue->count()) break;

            $wid = $d['week_id'];
            $di  = $d['day_index'];
            $dk  = $wid . '_' . $di;

            // Slot occupati da task bloccate
            $lockedSlots = TodolistTask::where('week_id', $wid)
                ->where('day_index', $di)
                ->where('locked', true)
                ->whereBetween('block_index', [0, 5])
                ->pluck('block_index')
                ->toArray();

            // Slot occupati da buchi su questo giorno
            $holeSlots = $this->getHoleSlotsForDay($dk);

            // Slot liberi: 0-5 esclusi locked e buchi
            // Sul giorno corrente: solo slot DOPO il buco appena creato
            $range = ($wid === $weekId && $di === $dayIndex)
                ? range($slotEnd + 1, 5)
                : range(0, 5);

            $freeSlots = array_values(array_diff($range, $lockedSlots, $holeSlots));

            foreach ($freeSlots as $slot) {
                if ($qi >= $queue->count()) break;

                $task = $queue[$qi++];
                if ($task->original_week_id === null) {
                    $task->original_week_id     = $task->week_id;
                    $task->original_day_index   = $task->day_index;
                    $task->original_block_index = $task->block_index;
                }
                $task->week_id     = $wid;
                $task->day_index   = $di;
                $task->block_index = $slot;
                $task->sort_order  = 0;
                $task->save();
            }
        }

        // Eventuali task rimanenti → overflow dell'ultimo giorno
        if ($qi < $queue->count()) {
            $lastDay = end($days);
            $maxSort = (int)(TodolistTask::where('week_id', $lastDay['week_id'])
                ->where('day_index', $lastDay['day_index'])
                ->where('block_index', 99)
                ->max('sort_order') ?? -1);

            for ($i = $qi; $i < $queue->count(); $i++) {
                $task = $queue[$i];
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

    private function getHoleSlotsForDay(string $dayKey): array
    {
        $holes = CentralTodolistHole::where('day_key', $dayKey)
            ->where('insert_after', '>=', 0)
            ->get();

        $slots = [];
        foreach ($holes as $hole) {
            $end = $hole->insert_after + $hole->slot_count - 1;
            for ($s = $hole->insert_after; $s <= min($end, 5); $s++) {
                $slots[] = $s;
            }
        }
        return array_unique($slots);
    }

    private function getOrderedDaysAfter(string $weekId, int $dayIndex): array
    {
        $weeks  = config('todolist_data');
        $result = [];
        $found  = false;

        foreach ($weeks as $week) {
            foreach ($week['days'] as $di => $day) {
                if (!$found) {
                    if ($week['id'] === $weekId && $di === $dayIndex) $found = true;
                    continue;
                }
                $result[] = ['week_id' => $week['id'], 'day_index' => $di];
            }
        }
        return $result;
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
        TodolistTask::truncate();
        CentralTodolistHole::truncate();

        $weeks    = config('todolist_data');
        $buffer   = [];
        $overflow = []; // task che non entrano nel giorno → cascadano al giorno successivo

        foreach ($weeks as $week) {
            foreach ($week['days'] as $di => $day) {
                // Raccoglie tutte le task del giorno dal config
                $dayTasks = [];
                foreach ($day['blocks'] as $block) {
                    foreach ($block['tasks'] as $task) {
                        $dayTasks[] = ['text' => $task['text'], 'tag' => $task['tag'] ?? 'ops'];
                    }
                }

                // Prepend overflow dal giorno precedente
                $allTasks = array_merge($overflow, $dayTasks);
                $overflow = array_slice($allTasks, 6); // > 6 → giorno successivo
                $toInsert = array_slice($allTasks, 0, 6);

                // slot 0,1,2 = mattina; 3,4,5 = pomeriggio
                foreach ($toInsert as $si => $task) {
                    $buffer[] = [
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

                if (count($buffer) >= 100) {
                    TodolistTask::insert($buffer);
                    $buffer = [];
                }
            }
        }

        if (!empty($buffer)) TodolistTask::insert($buffer);

        return redirect()->route('todolist.index')->with('success', 'Task ripristinate (6/giorno, 3 mattina + 3 pomeriggio).');
    }
}
