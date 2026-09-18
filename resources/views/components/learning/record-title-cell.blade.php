@props(['row'])

@php
    $subtitleLower = strtolower((string) ($row['subtitle'] ?? ''));
    $learningProgram = str_contains($subtitleLower, 'mandatory')
        ? 'mandatory'
        : (str_contains($subtitleLower, 'optional') ? 'optional' : '');

    $canOpen = (bool) ($row['can_open'] ?? true);
    $accessStatus = (string) ($row['access_status'] ?? 'ACTIVE');
    $modalMessage = (string) ($row['modal_message'] ?? '');
    $title = (string) ($row['title'] ?? '-');
@endphp

<div class="learning-record-course-cell">
    <div class="learning-record-title mb-1">
        @if ($canOpen)
            @if ($row['slug'])
                <a href="{{ route('content.user.view', $row['slug']) }}" class="learning-record-link fw-semibold">{{ $title }}</a>
            @elseif (!empty($row['playlist_url']))
                <a href="{{ $row['playlist_url'] }}" class="learning-record-link fw-semibold">{{ $title }}</a>
            @else
                <span class="learning-record-title-text fw-semibold">{{ $title }}</span>
            @endif
        @else
            <a href="javascript:void(0);"
               class="learning-record-link learning-record-blocked-trigger fw-semibold text-muted text-decoration-none"
               data-status="{{ $accessStatus }}"
               data-message="{{ $modalMessage }}"
               data-title="{{ $title }}"
               role="button">
                {{ $title }}
            </a>
        @endif
    </div>

    {{-- Program: Optional / Mandatory (Plain text, no badge/pill/border/background) --}}
    @if ($learningProgram === 'mandatory')
        <div class="learning-record-program text-muted small mt-1">Mandatory</div>
    @elseif ($learningProgram === 'optional')
        <div class="learning-record-program text-muted small mt-1">Optional</div>
    @endif
</div>
