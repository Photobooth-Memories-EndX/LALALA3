@props(['row'])

@php
    $attendance = $row['attendance'] ?? $row['attendance_status'] ?? null;
    if ($attendance === null || $attendance === '') {
        $rawProgress = $row['progress'] ?? null;
        $isValidProgress = !is_null($rawProgress) && is_numeric($rawProgress);
        $attendance = ($isValidProgress && (int) $rawProgress >= 100) ? 'Yes' : 'No';
    }
    $attLower = strtolower(trim((string) $attendance));
    $isYes = ($attendance === true || $attendance === 1 || in_array($attLower, ['1', 'true', 'present', 'yes'], true));
@endphp

<div class="d-flex align-items-center justify-content-center text-center">
    @if ($isYes)
        <span class="badge bg-success-lt text-success fw-bold px-2 py-1">Yes</span>
    @else
        <span class="badge bg-secondary-lt text-secondary fw-bold px-2 py-1">No</span>
    @endif
</div>
