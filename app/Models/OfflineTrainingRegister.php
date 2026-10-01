<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineTrainingRegister extends Model
{
    use HasFactory;

    protected $table = 'offline_training_registers';

    protected $fillable = [
        'offline_training_id',
        'user_id',
        'status',
        'registered_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
        'completed_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'approved_at'   => 'datetime',
        'rejected_at'   => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(OfflineTraining::class, 'offline_training_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'Rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope for registrations that count towards seat consumption.
     */
    public function scopeConsumingSeats($query)
    {
        return $query->whereIn('status', ['Pending', 'Approved', 'Completed']);
    }
}
