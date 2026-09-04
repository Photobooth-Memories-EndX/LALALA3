@props(['row'])

{{-- Kolom Score Test — satu source of truth: nilai & ambang berasal dari
     CertificateEligibilityResult (hasil final terbaik pada assessment aktif),
     angka yang sama dengan yang menentukan tombol Generate Certificate. --}}

<div class="d-flex flex-column align-items-center justify-content-center text-center">
    @if (! $row['assessment_required'] && is_null($row['score']))
        {{-- Assessment tidak aktif / tidak wajib dan belum ada nilai — tampilkan badge pill abu-abu (-) --}}
        <span class="badge learning-score-empty-pill">-</span>
    @elseif (is_null($row['score']))
        {{-- Assessment wajib tapi belum ada hasil final (Belum mengerjakan assessment) --}}
        <span class="badge learning-score-empty-pill">-</span>
        <div class="small text-danger mt-1">Has not completed the assessment</div>
    @else
        @php
            // Ambang efektif dari service — lulus di sini ⟺ gerbang assessment lolos.
            $passed = $row['score'] >= $row['min_score'];
        @endphp

        @if ($passed)
            <span class="badge bg-green-lt text-green learning-record-badge learning-score-pill">{{ $row['score'] }}</span>
        @elseif (! $row['assessment_required'])
            {{-- Assessment nonaktif / tidak wajib dan belum lulus — jangan blokir, tampilkan strip --}}
            <span class="badge learning-score-empty-pill">-</span>
        @else
            <span class="badge bg-red-lt text-red learning-record-badge learning-score-pill">FAILED</span>
            <div class="small text-danger mt-1">Score : {{ $row['score'] }}</div>
            <div class="small text-danger">Passing Grade : {{ $row['min_score'] }}</div>
        @endif
    @endif
</div>
