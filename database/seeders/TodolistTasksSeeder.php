<?php

namespace Database\Seeders;

use App\Models\TodolistTask;
use Illuminate\Database\Seeder;

class TodolistTasksSeeder extends Seeder
{
    public function run(): void
    {
        TodolistTask::truncate();

        $weeks  = config('todolist_data');
        $buffer = [];

        foreach ($weeks as $week) {
            foreach ($week['days'] as $di => $day) {
                foreach ($day['blocks'] as $bi => $block) {
                    foreach ($block['tasks'] as $ti => $task) {
                        $buffer[] = [
                            'week_id'     => $week['id'],
                            'day_index'   => $di,
                            'block_index' => $bi,
                            'sort_order'  => $ti,
                            'text'        => $task['text'],
                            'tag'         => $task['tag'] ?? 'ops',
                            'is_done'     => false,
                            'original_week_id'    => null,
                            'original_day_index'  => null,
                            'original_block_index'=> null,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];

                        if (count($buffer) >= 100) {
                            TodolistTask::insert($buffer);
                            $buffer = [];
                        }
                    }
                }
            }
        }

        if (!empty($buffer)) {
            TodolistTask::insert($buffer);
        }
    }
}
