@props([
    'certificate',
    'index',
])

@php
    $type = strtolower((string) ($certificate->certificate_type ?? 'content'));
    $typeStyles = [
        'content' => 'bg-blue-lt text-blue',
        'playlist' => 'bg-purple-lt text-purple',
        'global' => 'bg-orange-lt text-orange',
    ];
    $setting = $certificate->certificateSetting;
    $playlistContents = $type === 'playlist'
        ? collect($setting?->trainingPlan?->trainingplandetail ?? [])->sortBy('content_order')->pluck('content.title')->filter()->values()
        : collect();
    $popover = '<div class="certificate-playlist-popover"><strong>Playlist</strong><hr>'
        . ($playlistContents->isEmpty()
            ? '<span class="text-muted">No content available.</span>'
            : '<ol>' . $playlistContents->map(fn ($title) => '<li>' . e($title) . '</li>')->implode('') . '</ol>')
        . '</div>';
@endphp

<tr class="certificate-row" data-certificate-id="{{ $certificate->id }}">
    <td class="certificate-col-no text-center text-muted">{{ $index }}</td>
    <td class="certificate-col-main">
        <a href="{{ route('certificate.show', $certificate->credential_id) }}" class="certificate-number fw-semibold">{{ $certificate->certificate_number }}</a>
        <div class="certificate-title" title="{{ $certificate->title }}">{{ $certificate->title }}</div>
        <div class="certificate-participant small text-muted text-uppercase"><i class="ti ti-user"></i><span>{{ $certificate->participant_name }}</span></div>
    </td>
    <td class="certificate-col-type text-center">
        <span class="badge {{ $typeStyles[$type] ?? 'bg-secondary-lt text-secondary' }} certificate-type-badge {{ $type === 'playlist' ? 'certificate-playlist-badge' : '' }}"
              @if($type === 'playlist') tabindex="0" role="button" data-toggle="popover" data-trigger="hover focus" data-placement="auto" data-html="true" data-content="{{ $popover }}" @endif>
            {{ ucfirst($type) }}
        </span>
    </td>
    <td class="certificate-col-credential">
        <button type="button" class="btn btn-link p-0 certificate-copy-btn" data-copy="{{ $certificate->credential_id }}" title="Copy Credential ID">
            <span>{{ $certificate->credential_id }}</span><i class="ti ti-copy ms-1"></i>
        </button>
    </td>
    <td class="certificate-col-issued text-center" data-order="{{ optional($certificate->issued_at)->format('Y-m-d') }}">
        @if ($certificate->issued_at)
            <time class="certificate-issued-date" datetime="{{ $certificate->issued_at->format('Y-m-d') }}">
                <span>{{ $certificate->issued_at->format('d M') }}</span>
                <strong>{{ $certificate->issued_at->format('Y') }}</strong>
            </time>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    <td class="certificate-col-status text-center"><span class="badge bg-green-lt text-green certificate-status-badge"><i class="ti ti-circle-check me-1"></i>Generated</span></td>
    <td class="certificate-col-action text-center">
        <div class="dropdown certificate-action-dropdown">
            <button type="button" class="btn btn-sm btn-outline-primary btn-action btn-learning-sm dropdown-toggle certificate-action-trigger" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                <i class="ti ti-dots-vertical"></i><span>Actions</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right certificate-action-menu">
                <a class="dropdown-item" href="{{ route('certificate.show', $certificate->credential_id) }}">
                    <i class="ti ti-eye"></i><span>View Detail</span>
                </a>
                <a class="dropdown-item learning-action-success" href="{{ route('certificate.download.pdf', $certificate->credential_id) }}">
                    <i class="ti ti-download"></i><span>Download Certificate</span>
                </a>
                <a class="dropdown-item" href="{{ route('certificate.verify', $certificate->credential_id) }}" target="_blank" rel="noopener">
                    <i class="ti ti-shield-check"></i><span>Open Verification</span>
                </a>
                <div class="dropdown-divider"></div>
                <button type="button" class="dropdown-item certificate-copy-btn" data-copy="{{ $certificate->credential_id }}">
                    <i class="ti ti-copy"></i><span>Copy Credential ID</span>
                </button>
            </div>
        </div>
    </td>
</tr>
