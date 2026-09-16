@props(['row'])

@php
    $subtitleLower = strtolower((string) ($row['subtitle'] ?? ''));
    $learningProgram = str_contains($subtitleLower, 'mandatory')
        ? 'mandatory'
        : (str_contains($subtitleLower, 'optional') ? 'optional' : '');
@endphp

<div class="learning-record-course-cell">
    <div class="learning-record-title mb-1">
        @if ($row['slug'])
            <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link fw-semibold">{{ $row['title'] }}</a>
        @elseif (!empty($row['playlist_url']))
            <a href="{{ $row['playlist_url'] }}" class="learning-record-link fw-semibold">{{ $row['title'] }}</a>
        @else
            <span class="learning-record-title-text fw-semibold">{{ $row['title'] }}</span>
        @endif
    </div>

    {{-- Program: Optional / Mandatory (Plain text, no badge/pill/border/background) --}}
    @if ($learningProgram === 'mandatory')
        <div class="learning-record-program text-muted small mt-1">Mandatory</div>
    @elseif ($learningProgram === 'optional')
        <div class="learning-record-program text-muted small mt-1">Optional</div>
    @endif
</div>
