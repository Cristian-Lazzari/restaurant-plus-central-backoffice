<?php

namespace Database\Seeders;

use App\Models\CentralTodolistHole;
use App\Models\TodolistTask;
use Illuminate\Database\Seeder;

class TodolistTasksSeeder extends Seeder
{
    public function run(): void
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

        if (!empty($buffer)) {
            TodolistTask::insert($buffer);
        }
    }
}
