<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TaskUnlock — tabel tunggal pencatat izin akses / unlock tugas user:
 * - standalone: dicatat saat user memasukkan Unique Code atau membuka shared link
 * - playlist: dicatat saat user membuka gembok tugas course setelah progress 100%
 */
class TaskUnlock extends Model
{
    protected $table = 'task_unlocks';

    protected $fillable = [
        'user_id',
        'type',
        'task_id',
        'training_plan_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class, 'training_plan_id');
    }
}
