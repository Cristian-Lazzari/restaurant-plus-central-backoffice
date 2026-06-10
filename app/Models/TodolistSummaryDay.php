<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodolistSummaryDay extends Model
{
    protected $table    = 'todolist_summary_days';
    protected $fillable = [
        'week_id', 'week_label', 'week_color', 'week_month', 'week_dates',
        'week_subtitle', 'week_focus', 'week_goals',
        'day_index', 'day_name', 'day_theme', 'day_hours', 'calendar_date',
    ];
    protected $casts = [
        'week_goals'    => 'array',
        'week_month'    => 'integer',
        'day_index'     => 'integer',
        'calendar_date' => 'date',
    ];
}
