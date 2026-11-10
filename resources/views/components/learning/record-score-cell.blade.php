@props(['row'])

{{-- Kolom Score Test — satu source of truth: nilai & ambang berasal dari
     CertificateEligibilityResult (hasil final terbaik pada assessment aktif),
     angka yang sama dengan yang menentukan tombol Generate Certificate. --}}

@php
    $assessmentRequired = (bool) ($row['assessment_required'] ?? false);
    $scoreValue = $row['score'] ?? $row['assessment_score'] ?? null;
    $minScoreValue = (int) ($row['min_score'] ?? 0);
    $hasScore = $scoreValue !== null && $scoreValue !== '' && is_numeric($scoreValue);
@endphp

<div class="d-flex flex-column align-items-center justify-content-center text-center">
    @if (! $assessmentRequired && ! $hasScore)
        {{-- Assessment tidak aktif / tidak wajib dan belum ada nilai — tampilkan badge pill abu-abu (-) --}}
        <span class="badge learning-score-empty-pill">-</span>
    @elseif (! $hasScore)
        {{-- Assessment wajib tapi belum ada hasil final (Belum mengerjakan assessment) --}}
        <span class="badge learning-score-empty-pill">-</span>
        <div class="small text-danger mt-1">Has not completed the assessment</div>
    @else
        @php
            // Ambang efektif dari service — lulus di sini ⟺ gerbang assessment lolos.
            $passed = (float) $scoreValue >= $minScoreValue;
        @endphp

        @if ($passed)
            <span class="badge rounded-pill learning-record-badge learning-score-pill" style="background-color: #dcfce7; color: #15803d; font-weight: 600;">Passed</span>
            <div class="small fw-semibold mt-1" style="color: #15803d;">Score : {{ $scoreValue !== null && $scoreValue !== '' ? $scoreValue : '-' }}</div>
        @elseif (! $assessmentRequired)
            {{-- Assessment nonaktif / tidak wajib dan belum lulus — jangan blokir, tampilkan strip --}}
            <span class="badge learning-score-empty-pill">-</span>
        @else
            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
            <div class="small text-danger mt-1">Score : {{ $scoreValue !== null && $scoreValue !== '' ? $scoreValue : '-' }}</div>
            <div class="small text-danger">Passing Grade : {{ $minScoreValue }}</div>
        @endif
    @endif
</div>
