<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Assesment;
use App\Models\Content;

class CertificateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'content_id',
        'minimum_progress',
        'minimum_score',
        'issuer_name',
        'issuer_logo',
        'is_active',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assesment::class, 'assessment_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

}
