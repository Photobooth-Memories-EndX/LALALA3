<?php

namespace App\Services;

use App\Models\AssesmentResult;
use App\Models\ContentUser;

/**
 * Hasil evaluasi CertificateEligibilityService — kontrak resmi antar layer
 * (Service → Controller/Blade), menggantikan array associative.
 *
 * Controller tidak perlu tahu struktur internal service; penambahan informasi
 * baru cukup menambah properti di sini tanpa mengubah pemanggil lama, dan
 * typo nama field terdeteksi sebagai error, bukan null diam-diam.
 */
final class CertificateEligibilityResult
{
    public function __construct(
        public readonly bool $eligible,
        public readonly ?string $reason = null,
        // ContentUser milik user (baris content) — dipakai generate certificate
        // dan sorting tanggal pada certificateReady().
        public readonly ?ContentUser $learning = null,
        // Hasil final terbaik pada assessment aktif: hasil LULUS saat eligible,
        // atau hasil terbaik-walau-gagal agar kolom Score menampilkan nilai
        // yang sama dengan yang dievaluasi.
        public readonly ?AssesmentResult $assessmentResult = null,
        // true bila entity punya minimal satu assessment aktif (deteksi
        // otomatis) sehingga kelulusan assessment menjadi syarat certificate.
        public readonly bool $assessmentRequired = false,
        // Ambang lulus efektif yang dipakai evaluasi; null bila assessment
        // tidak diwajibkan.
        public readonly ?float $minimumScore = null,
    ) {
    }

    /**
     * Score untuk kolom UI — angka yang benar-benar dipakai evaluasi,
     * null bila user belum punya hasil final pada assessment aktif.
     */
    public function score(): ?int
    {
        return $this->assessmentResult === null || $this->assessmentResult->score === null
            ? null
            : (int) $this->assessmentResult->score;
    }
}
