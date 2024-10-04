<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssesmentAssigned extends Model
{
    use HasFactory;

    protected $table   = 'assesment_assigned';
    protected $guarded = ['id'];

    public function assesment()
    {
        return $this->belongsTo(Assesment::class, 'assesment_id', 'id');
    }
}
