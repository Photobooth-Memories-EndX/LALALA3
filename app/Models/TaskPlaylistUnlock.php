<?php

namespace App\Models;

/**
 * Backward-compatibility alias for TaskUnlock (type = playlist).
 */
class TaskPlaylistUnlock extends TaskUnlock
{
    protected static function booted()
    {
        static::addGlobalScope('playlist', function ($query) {
            $query->where('type', 'playlist');
        });

        static::creating(function ($model) {
            $model->type = 'playlist';
        });
    }
}
