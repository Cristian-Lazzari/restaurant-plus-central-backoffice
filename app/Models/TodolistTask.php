<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodolistTask extends Model
{
    protected $fillable = [
        'week_id',
        'day_index',
        'block_index',
        'sort_order',
        'text',
        'tag',
        'is_done',
        'original_week_id',
        'original_day_index',
        'original_block_index',
    ];

    protected $casts = [
        'is_done'               => 'boolean',
        'day_index'             => 'integer',
        'block_index'           => 'integer',
        'sort_order'            => 'integer',
        'original_day_index'    => 'integer',
        'original_block_index'  => 'integer',
    ];
}
