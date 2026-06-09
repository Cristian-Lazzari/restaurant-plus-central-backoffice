<?php

namespace App\Http\Controllers;

use App\Models\CentralTodolistCompletion;
use App\Models\CentralTodolistHole;
use Illuminate\Http\Request;

class TodolistController extends Controller
{
    public function index()
    {
        $weeks = config('todolist_data');

        $completedKeys = CentralTodolistCompletion::pluck('task_key')->flip()->toArray();

        $totalTasks = 0;
        $doneTasks  = 0;
        foreach ($weeks as $week) {
            foreach ($week['days'] as $di => $day) {
                foreach ($day['blocks'] as $bi => $block) {
                    foreach ($block['tasks'] as $ti => $task) {
                        $totalTasks++;
                        $key = "{$week['id']}_{$di}_{$bi}_{$ti}";
                        if (isset($completedKeys[$key])) {
                            $doneTasks++;
                        }
                    }
                }
            }
        }

        $holes = CentralTodolistHole::all()->groupBy('day_key');

        return view('todolist.index', compact('weeks', 'completedKeys', 'totalTasks', 'doneTasks', 'holes'));
    }

    public function toggle(Request $request)
    {
        $key = $request->input('task_key');

        if (! $key || ! preg_match('/^w\d+_\d+_\d+_\d+$/', $key)) {
            return response()->json(['error' => 'Chiave non valida'], 422);
        }

        $existing = CentralTodolistCompletion::where('task_key', $key)->first();

        if ($existing) {
            $existing->delete();
            $done = false;
        } else {
            CentralTodolistCompletion::create(['task_key' => $key]);
            $done = true;
        }

        return response()->json(['done' => $done]);
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

        return response()->json(['id' => $hole->id]);
    }

    public function destroyHole(int $id)
    {
        CentralTodolistHole::findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    public function reset()
    {
        CentralTodolistCompletion::truncate();

        return redirect()->route('todolist.index')->with('success', 'Tutti i progressi sono stati azzerati.');
    }
}
