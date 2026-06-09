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

        if (! $id || ! is_numeric($id) || (int) $id <= 0) {
            return response()->json(['error' => 'ID non valido'], 422);
        }

        $task          = TodolistTask::findOrFail((int) $id);
        $task->is_done = ! $task->is_done;
        $task->save();

        return response()->json(['done' => $task->is_done]);
    }

    public function storeHole(Request $request)
    {
        $validated = $request->validate([
            'day_key'      => ['required', 'regex:/^w\d+_\d+$/', 'max:20'],
            'label'        => ['required', 'string', 'max:200'],
            'time_label'   => ['nullable', 'string', 'max:40'],
            'insert_after' => ['required', 'integer', 'min:-1'],
        ]);

        $hole = CentralTodolistHole::create($validated);

        if ($validated['insert_after'] >= 0) {
            [$weekId, $dayIndex] = explode('_', $validated['day_key']);
            $dayIndex = (int) $dayIndex;

            $tasksToMove = TodolistTask::where('week_id', $weekId)
                ->where('day_index', $dayIndex)
                ->where('block_index', '<=', (int) $validated['insert_after'])
                ->where('block_index', '!=', 99)
                ->orderBy('block_index')
                ->orderBy('sort_order')
                ->get();

            if ($tasksToMove->isNotEmpty()) {
                $next    = $this->findNextDay($weekId, $dayIndex);
                $maxSort = (int) (TodolistTask::where('week_id', $next['week_id'])
                    ->where('day_index', $next['day_index'])
                    ->where('block_index', 99)
                    ->max('sort_order') ?? -1);

                foreach ($tasksToMove as $task) {
                    if ($task->original_week_id === null) {
                        $task->original_week_id     = $task->week_id;
                        $task->original_day_index   = $task->day_index;
                        $task->original_block_index = $task->block_index;
                    }
                    $task->week_id     = $next['week_id'];
                    $task->day_index   = $next['day_index'];
                    $task->block_index = 99;
                    $task->sort_order  = ++$maxSort;
                    $task->save();
                }
            }
        }

        return response()->json(['id' => $hole->id]);
    }

    private function findNextDay(string $weekId, int $dayIndex): array
    {
        $weeks = config('todolist_data');

        foreach ($weeks as $wi => $week) {
            if ($week['id'] !== $weekId) continue;

            if ($dayIndex + 1 < count($week['days'])) {
                return ['week_id' => $weekId, 'day_index' => $dayIndex + 1];
            }
            if (isset($weeks[$wi + 1])) {
                return ['week_id' => $weeks[$wi + 1]['id'], 'day_index' => 0];
            }
            // ultima giornata dell'ultimo giorno: resta sulla stessa
            return ['week_id' => $weekId, 'day_index' => $dayIndex];
        }

        return ['week_id' => $weekId, 'day_index' => $dayIndex];
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

        $weeks  = config('todolist_data');
        $buffer = [];

        foreach ($weeks as $week) {
            foreach ($week['days'] as $di => $day) {
                foreach ($day['blocks'] as $bi => $block) {
                    foreach ($block['tasks'] as $ti => $task) {
                        $buffer[] = [
                            'week_id'              => $week['id'],
                            'day_index'            => $di,
                            'block_index'          => $bi,
                            'sort_order'           => $ti,
                            'text'                 => $task['text'],
                            'tag'                  => $task['tag'] ?? 'ops',
                            'is_done'              => false,
                            'original_week_id'     => null,
                            'original_day_index'   => null,
                            'original_block_index' => null,
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ];

                        if (count($buffer) >= 100) {
                            TodolistTask::insert($buffer);
                            $buffer = [];
                        }
                    }
                }
            }
        }

        if (! empty($buffer)) {
            TodolistTask::insert($buffer);
        }

        return redirect()->route('todolist.index')->with('success', 'Task ripristinate alle posizioni originali.');
    }
}
