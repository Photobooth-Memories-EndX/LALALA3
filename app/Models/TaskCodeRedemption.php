<?php

namespace App\Models;

/**
 * Backward-compatibility alias for TaskUnlock (type = standalone).
 */
class TaskCodeRedemption extends TaskUnlock
{
    protected static function booted()
    {
        static::addGlobalScope('standalone', function ($query) {
            $query->where('type', 'standalone');
        });

        static::creating(function ($model) {
            $model->type = 'standalone';
        });
    }
}
