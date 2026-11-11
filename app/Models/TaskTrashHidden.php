<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTrashHidden extends Model
{
    protected $table = 'task_trash_hidden';

    protected $fillable = [
        'item_type',
        'course_type',
        'item_id',
    ];
}
