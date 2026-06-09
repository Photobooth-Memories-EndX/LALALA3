<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'certificate_setting_id',
        'content_user_id',
        'assessment_result_id',
        'certificate_number',
        'credential_id',
        'credential_url',
        'title',
        'participant_name',
        'score',
        'issued_at',
        'file_path',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function certificateSetting()
    {
        return $this->belongsTo(CertificateSetting::class);
    }
}