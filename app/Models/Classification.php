<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
    use HasFactory;

    protected $table = 'classification';
    protected $guarded = ['id'];
    public function content()
    {
        // return $this->hasMany(Content::class);
    }
}
