<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSyncLog extends Model
{
    use HasFactory;

    protected $table = 'employee_sync_logs';

    protected $fillable = [
        'started_at',
        'finished_at',
        'status',
        'is_dry_run',
        'total_api',
        'total_owl',
        'created_count',
        'updated_count',
        'unchanged_count',
        'skipped_count',
        'conflict_count',
        'failed_count',
        'details',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'is_dry_run' => 'boolean',
        'total_api' => 'integer',
        'total_owl' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'unchanged_count' => 'integer',
        'skipped_count' => 'integer',
        'conflict_count' => 'integer',
        'failed_count' => 'integer',
        'details' => 'array',
    ];
}
