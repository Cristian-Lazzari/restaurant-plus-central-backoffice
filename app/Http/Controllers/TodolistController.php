<?php

namespace App\Http\Controllers;

use App\Models\CentralTodolistCompletion;
use Illuminate\Http\Request;

class TodolistController extends Controller
{
    public function index()
    {
        $weeks = config('todolist_data');

        // Carica tutti i task completati dal DB in un Set per lookup O(1)
        $completedKeys = CentralTodolistCompletion::pluck('task_key')->flip()->toArray();

        // Calcola statistiche globali
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

        return view('todolist.index', compact('weeks', 'completedKeys', 'totalTasks', 'doneTasks'));
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

    public function reset()
    {
        CentralTodolistCompletion::truncate();

        return redirect()->route('todolist.index')->with('success', 'Tutti i progressi sono stati azzerati.');
    }
}
