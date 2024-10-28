<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssesmentQuestion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'option' => 'array'
    ];

    public function assessment()
    {
        return $this->belongsTo(Assesment::class, 'assesment_id', 'id');
    }
}
