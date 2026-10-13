@props(['row'])

@php
    $attendance = $row['attendance'] ?? null;
    if ($attendance === null || $attendance === '') {
        $rawProgress = $row['progress'] ?? null;
        $isValidProgress = !is_null($rawProgress) && is_numeric($rawProgress);
        $attendance = ($isValidProgress && (int) $rawProgress >= 100) ? 'Present' : '-';
    }
    $isPresent = strtolower(trim((string) $attendance)) === 'present';
@endphp

<div class="d-flex align-items-center justify-content-center text-center">
    @if ($isPresent)
        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Present</span>
    @else
        <span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-1">-</span>
    @endif
</div>
