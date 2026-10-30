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

<div class="learning-record-course-cell d-flex flex-column align-items-center justify-content-center text-center w-100">
    <div class="learning-record-title text-center mb-1 w-100">
        <span class="fw-bold text-dark text-center fs-6">
            @if ($canOpen)
                @if (!empty($slug))
                    <a href="{{ route('content.user.view', $slug) }}" class="learning-record-link fw-bold text-dark text-center">{{ $title }}</a>
                @elseif (!empty($row['playlist_url'] ?? null))
                    <a href="{{ $row['playlist_url'] }}" class="learning-record-link fw-bold text-dark text-center">{{ $title }}</a>
                @else
                    <span class="learning-record-title-text fw-bold text-dark text-center">{{ $title }}</span>
                @endif
            @else
                <a href="javascript:void(0);"
                   class="learning-record-link learning-record-blocked-trigger fw-bold text-muted text-decoration-none text-center"
                   data-status="{{ $accessStatus }}"
                   data-message="{{ $modalMessage }}"
                   data-title="{{ $title }}"
                   role="button">
                    {{ $title }}
                </a>
            @endif
        </span>
    </div>

    {{-- Requirement sub-label under Course Title: Centered --}}
    <small class="text-muted text-center fs-7">
        Requirement: <span class="fw-semibold">{{ $isMandatory ? 'Mandatory' : 'Non-Mandatory' }}</span>
    </small>
</div>
