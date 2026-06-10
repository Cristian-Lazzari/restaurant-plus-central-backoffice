<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralTodolistHole extends Model
{
    protected $table    = 'central_todolist_holes';
    protected $fillable = ['day_key', 'label', 'time_label', 'insert_after', 'slot_count', 'group_id'];
    protected $casts    = ['insert_after' => 'integer', 'slot_count' => 'integer', 'group_id' => 'integer'];
}
