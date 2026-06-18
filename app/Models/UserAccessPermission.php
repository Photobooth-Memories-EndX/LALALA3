<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccessPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module_key',
        'can_access',
    ];

    protected $casts = [
        'can_access' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}