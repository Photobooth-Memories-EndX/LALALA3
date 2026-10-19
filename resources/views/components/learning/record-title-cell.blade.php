@props(['row'])

@php
    $rawRequirement = strtolower(trim((string) ($row['requirement'] ?? $row['subtitle'] ?? '-')));
    $isNonMandatory = str_contains($rawRequirement, 'non-mandatory')
        || str_contains($rawRequirement, 'non mandatory')
        || str_contains($rawRequirement, 'optional');
    $isMandatory = !$isNonMandatory && str_contains($rawRequirement, 'mandatory');

    $canOpen = (bool) ($row['can_open'] ?? true);
    $accessStatus = (string) ($row['access_status'] ?? 'ACTIVE');
    $modalMessage = (string) ($row['modal_message'] ?? '');
    $title = (string) ($row['title'] ?? '-');
    $slug = $row['slug'] ?? null;
@endphp

<div class="learning-record-course-cell">
    <div class="learning-record-title mb-1">
        @if ($canOpen)
            @if (!empty($slug))
                <a href="{{ route('content.user.view', $slug) }}" class="learning-record-link fw-semibold">{{ $title }}</a>
            @elseif (!empty($row['playlist_url'] ?? null))
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

    {{-- Requirement sub-label under Course Title: Mandatory badge or Non-Mandatory text --}}
    <div class="learning-record-program text-muted small mt-1 d-flex align-items-center gap-1 flex-wrap">
        <span class="text-muted">Requirement:</span>
        @if ($isMandatory)
            <span class="badge bg-red-lt text-red fw-semibold" style="font-size: 0.7rem;">Mandatory</span>
        @else
            <span class="text-muted fw-medium">Non-Mandatory</span>
        @endif
    </div>
</div>
