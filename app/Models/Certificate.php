<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'certificate_setting_id',
        'certificate_type',
        'source_id',
        'content_user_id',
        'offline_training_user_id',
        'assessment_result_id',
        'certificate_number',
        'credential_id',
        'credential_url',
        'title',
        'participant_name',
        'institution',
        'issued_date',
        'expired_date',
        'score',
        'issued_at',
        'file_path',
    ];

    protected $casts = [
        'issued_at'    => 'date',
        'issued_date'  => 'date',
        'expired_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function certificateSetting()
    {
        return $this->belongsTo(CertificateSetting::class);
    }

    public function assessmentResult()
    {
        return $this->belongsTo(AssesmentResult::class);
    }

    public function offlineTrainingUser()
    {
        return $this->belongsTo(OfflineTrainingUser::class, 'offline_training_user_id');
    }

    public function offlineTraining()
    {
        return $this->belongsTo(OfflineTraining::class, 'source_id');
    }

    public function getDisplayInstitutionAttribute(): string
    {
        return $this->institution ?: ($this->certificateSetting?->institution ?: 'PT KRAKATAU POSCO');
    }

    public function getDisplayIssuedDateAttribute()
    {
        return $this->issued_date ?: ($this->certificateSetting?->issued_date ?: $this->issued_at);
    }

    public function getDisplayExpiredDateAttribute()
    {
        return $this->expired_date ?: $this->certificateSetting?->expired_date;
    }

    public function getCertificateAvailableAttribute(): string
    {
        return ($this->certificateSetting?->is_active ?? true) ? 'YES' : 'NO';
    }

    public function getLinkedInUrlAttribute(): string
    {
        return 'https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME'
            . '&name=' . urlencode($this->title)
            . '&organizationName=' . urlencode($this->certificateSetting->issuer_name ?? 'OWL Training Management')
            . '&issueYear=' . optional($this->issued_at)->format('Y')
            . '&issueMonth=' . optional($this->issued_at)->format('m')
            . '&certUrl=' . urlencode(route('certificate.verify', $this->credential_id))
            . '&certId=' . urlencode($this->credential_id);
    }

    /**
     * Map internal/database certificate type to user-facing display label.
     */
    public static function typeLabel(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'content'                     => 'Content',
            'playlist', 'trainingplan'    => 'Playlist',
            'global'                      => 'Global',
            'training_offline', 'offline' => 'Training Offline',
            default                       => ucwords(str_replace('_', ' ', (string) $type)),
        };
    }

    /**
     * Get user-facing certificate type label accessor.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::typeLabel($this->certificate_type);
    }
}
