@props(['row'])

{{-- Kolom Certificate — urutan prioritas:
     Generate Certificate -> Need Assessment -> Need Score -> Need Progress.
     Tombol Download dihapus; download hanya tersedia di halaman Certificate,
     sertifikat yang sudah terbit ditandai badge "Issued". --}}

@php
    $progressReady = (int) $row['progress'] >= (int) $row['min_progress'];
    $scoreReady    = $row['min_score'] <= 0
        || (!is_null($row['score']) && (float) $row['score'] >= (float) $row['min_score']);
@endphp

@if ($row['cert_state'] === 'issued')
    <span class="badge bg-green-lt text-green w-100">
        <i class="ti ti-certificate me-1"></i>
        Issued
    </span>
@elseif ($progressReady && $scoreReady)
    @if ($row['cert_is_form'])
        <form method="POST" action="{{ $row['cert_action_url'] }}">
            @csrf
            <button class="btn btn-primary btn-sm w-100">
                <i class="ti ti-certificate me-1"></i>
                Generate Certificate
            </button>
        </form>
    @else
        <a href="{{ $row['cert_action_url'] }}"
           class="btn btn-primary btn-sm w-100">
            <i class="ti ti-certificate me-1"></i>
            Generate Certificate
        </a>
    @endif
@elseif ($row['min_score'] > 0 && is_null($row['score']))
    <span class="badge bg-red-lt text-red w-100">
        Need Assessment
    </span>
@elseif ($row['min_score'] > 0 && (float) $row['score'] < (float) $row['min_score'])
    <span class="badge bg-red-lt text-red w-100">
        Need Score {{ $row['min_score'] }}
    </span>
@else
    <span class="badge bg-red-lt text-red w-100">
        Need Progress {{ $row['min_progress'] }}%
    </span>
@endif
