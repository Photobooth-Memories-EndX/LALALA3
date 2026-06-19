<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleAccessPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'module_key',
        'can_access',
    ];

    protected $casts = [
        'can_access' => 'boolean',
    ];
}
