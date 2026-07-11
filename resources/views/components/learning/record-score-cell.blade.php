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

    <span class="badge {{ $passed ? 'bg-green-lt text-green' : 'bg-red-lt text-red' }} learning-record-badge">{{ $row['score'] }}</span>

    <div class="small {{ $passed ? 'text-muted' : 'text-danger' }} mt-1">
        {{ $passed ? 'Min ' . $row['min_score'] : 'Butuh min ' . $row['min_score'] }}
    </div>
@endif
