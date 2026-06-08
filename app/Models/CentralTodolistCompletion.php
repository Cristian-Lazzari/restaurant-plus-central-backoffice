<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentralTodolistCompletion extends Model
{
    protected $table = 'central_todolist_completions';

    protected $fillable = ['task_key'];
}
