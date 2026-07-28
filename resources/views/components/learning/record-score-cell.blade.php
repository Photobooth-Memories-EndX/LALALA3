@props(['row'])

{{-- Kolom Score Test — satu source of truth: nilai & ambang berasal dari
     CertificateEligibilityResult (hasil final terbaik pada assessment aktif),
     angka yang sama dengan yang menentukan tombol Generate Certificate. --}}

@if (! $row['assessment_required'])
    {{-- Tidak ada assessment aktif — score bukan syarat certificate. --}}
    <span class="badge bg-secondary-lt text-secondary learning-record-badge">-</span>
@elseif (is_null($row['score']))
    {{-- Assessment wajib tapi belum ada hasil final (Need Assessment). --}}
    <span class="badge bg-secondary-lt text-secondary learning-record-badge">-</span>
    <div class="small text-danger mt-1">Belum mengerjakan assessment</div>
@else
    @php
        // Ambang efektif dari service — lulus di sini ⟺ gerbang assessment lolos.
        $passed = $row['score'] >= $row['min_score'];
    @endphp

    @if ($passed)
        <span class="badge bg-green-lt text-green learning-record-badge">{{ $row['score'] }}</span>
    @else
        <span class="badge bg-red-lt text-red learning-record-badge">FAILED</span>
        <div class="small text-danger mt-1">Score : {{ $row['score'] }}</div>
        <div class="small text-danger">Passing Grade : {{ $row['min_score'] }}</div>
    @endif
@endif
