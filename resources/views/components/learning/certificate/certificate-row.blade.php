@props([
    'certificate',
    'index',
])

@php
    $type = strtolower((string) ($certificate->certificate_type ?? 'content'));
    $typeStyles = [
        'playlist' => 'bg-green',
        'video' => 'bg-yellow text-white',
        'youtube' => 'bg-yellow text-white',
        'document' => 'bg-cyan',
        'pdf' => 'bg-cyan',
        'content' => 'bg-purple-lt text-purple',
        'global' => 'bg-orange-lt text-orange',
        'training_offline' => 'bg-teal-lt text-teal',
        'offline' => 'bg-teal-lt text-teal',
    ];
    $typeLabel = $certificate->type_label ?? \App\Models\Certificate::typeLabel($type);
    $setting = $certificate->certificateSetting;
    $playlistContents = $type === 'playlist'
        ? collect($setting?->trainingPlan?->trainingplandetail ?? [])->sortBy('content_order')->pluck('content.title')->filter()->values()
        : collect();
    $popover = '<div class="certificate-playlist-popover"><strong>Playlist</strong><hr>'
        . ($playlistContents->isEmpty()
            ? '<span class="text-muted">No content available.</span>'
            : '<ol>' . $playlistContents->map(fn ($title) => '<li>' . e($title) . '</li>')->implode('') . '</ol>')
        . '</div>';
    $shareUrl = $certificate->credential_url ?: ($certificate->verify_url ?: '');
    $documentUrl = route('certificate.viewer.document', $certificate->credential_id);
    $viewerUrl = route('certificate.viewer', $certificate->credential_id);
    $downloadUrl = route('certificate.download.pdf', $certificate->credential_id);
@endphp

<tr class="certificate-row" data-certificate-id="{{ $certificate->id }}">
    <td class="certificate-col-no text-center text-muted">{{ $index }}</td>
    <td class="certificate-col-main text-start">
        <a href="{{ route('certificate.show', $certificate->credential_id) }}"
           class="certificate-title certificate-title-link"
           title="View {{ $certificate->title }}">
            {{ $certificate->title }}
        </a>
    </td>
    <td class="certificate-col-type text-center">
        <span class="badge {{ $typeStyles[$type] ?? 'bg-secondary-lt text-secondary' }} learning-record-badge certificate-type-badge {{ $type === 'playlist' ? 'certificate-playlist-badge' : '' }}"
              @if($type === 'playlist') tabindex="0" role="button" data-bs-toggle="popover" data-trigger="hover focus" data-placement="auto" data-html="true" data-content="{{ $popover }}" @endif>
            {{ $typeLabel }}
        </span>
    </td>
    <td class="certificate-col-number text-start">
        <span class="certificate-number-text text-body fw-normal text-truncate d-block" title="{{ $certificate->certificate_number }}">
            {{ $certificate->certificate_number }}
        </span>
    </td>
    <td class="certificate-col-credential text-start">
        <span class="certificate-credential-text text-body fw-normal text-truncate d-block" title="{{ $certificate->credential_id }}">
            {{ $certificate->credential_id }}
        </span>
    </td>
    <td class="certificate-col-issued text-center" data-order="{{ optional($certificate->issued_at)->format('Y-m-d') }}">
        @if ($certificate->issued_at)
            <time class="certificate-issued-date" datetime="{{ $certificate->issued_at->format('Y-m-d') }}">
                {{ $certificate->issued_at->format('d M Y') }}
            </time>
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    <td class="certificate-col-action text-center">
        <div class="certificate-actions">
            <a class="btn certificate-action-button text-blue certificate-preview-trigger"
               href="{{ $viewerUrl }}"
               data-certificate-preview-url="{{ $documentUrl }}"
               data-certificate-viewer-url="{{ $viewerUrl }}"
               data-certificate-download-url="{{ $downloadUrl }}"
               data-certificate-preview-title="{{ $certificate->title }}"
               data-credential-id="{{ $certificate->credential_id }}"
               aria-haspopup="dialog"
               title="Preview Certificate"
               aria-label="Preview Certificate">
                <i class="ti ti-eye" aria-hidden="true"></i>
            </a>
            <a class="btn certificate-action-button text-green" href="{{ $downloadUrl }}" title="Download Certificate" aria-label="Download Certificate">
                <i class="ti ti-download" aria-hidden="true"></i>
            </a>
            <button type="button"
                    class="btn certificate-action-button text-azure btn-share-certificate"
                    data-url="{{ $shareUrl }}"
                    data-title="My OWL Learning Certificate"
                    data-text="I have completed this learning and earned an official certificate from OWL Learning Hub."
                    title="Share Certificate"
                    aria-label="Share Certificate">
                <i class="fas fa-share-alt" aria-hidden="true"></i>
            </button>
        </div>
    </td>
</tr>
