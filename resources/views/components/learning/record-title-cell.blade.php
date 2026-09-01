@props(['row'])

@php
    $subtitleLower = strtolower((string) ($row['subtitle'] ?? ''));
    $learningProgram = str_contains($subtitleLower, 'mandatory')
        ? 'mandatory'
        : (str_contains($subtitleLower, 'optional') ? 'optional' : '');
@endphp

<div class="learning-record-course-cell position-relative">
    <div class="learning-record-title mb-1">
        @if ($row['slug'])
            <a href="{{ route('content.user.view', $row['slug']) }}"
               class="learning-record-link fw-semibold course-preview-trigger"
               data-preview-kind="content"
               data-course-title="{{ $row['title'] }}">
                {{ $row['title'] }}
            </a>
        @elseif (!empty($row['playlist_url']))
            <a href="{{ $row['playlist_url'] }}"
               class="learning-record-link fw-semibold course-preview-trigger"
               data-preview-kind="playlist"
               data-course-title="{{ $row['title'] }}">
                {{ $row['title'] }}
            </a>
        @else
            <span class="learning-record-title-text fw-semibold course-preview-trigger"
                  data-preview-kind="{{ $row['row_kind'] === 'playlist' ? 'playlist' : 'content' }}"
                  data-course-title="{{ $row['title'] }}">
                {{ $row['title'] }}
            </span>
        @endif
    </div>

    {{-- Program: Optional / Mandatory (Plain text, no badge/pill/border/background) --}}
    @if ($learningProgram === 'mandatory')
        <div class="learning-record-program text-muted small mt-1">Mandatory</div>
    @elseif ($learningProgram === 'optional')
        <div class="learning-record-program text-muted small mt-1">Optional</div>
    @endif

    {{-- Template Hover Preview --}}
    <div class="d-none course-preview-template">
        @if ($row['row_kind'] === 'playlist')
            <div class="course-preview-popover course-preview-playlist">
                <div class="course-preview-header">
                    <div class="course-preview-title">{{ $row['title'] }}</div>
                </div>
                <div class="course-preview-body">
                    @if (!empty($row['playlist_items']))
                        <ol class="course-preview-playlist-list mb-0">
                            @foreach ($row['playlist_items'] as $item)
                                <li class="course-preview-playlist-item">
                                    @if (!empty($item['url']))
                                        <a href="{{ $item['url'] }}" class="course-preview-playlist-link">
                                            {{ $item['title'] }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ $item['title'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="text-muted small">No content in this playlist.</div>
                    @endif
                </div>
            </div>
        @else
            <div class="course-preview-popover course-preview-content">
                <div class="course-preview-header">
                    <div class="course-preview-title">{{ $row['title'] }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
