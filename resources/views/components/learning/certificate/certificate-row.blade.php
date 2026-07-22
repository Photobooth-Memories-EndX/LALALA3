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
    $issuer = $setting->issuer_name ?? 'OWL Training Management';
    $verificationUrl = $certificate->credential_url ?: route('certificate.verify', $certificate->credential_id);
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
    <td>
        <button type="button" class="btn btn-sm btn-icon btn-ghost-secondary certificate-expand-btn" aria-expanded="false" aria-controls="certificateDetail{{ $certificate->id }}" title="Expand detail">
            <i class="ti ti-chevron-right"></i>
        </button>
        <template class="certificate-detail-template">
            <div class="certificate-detail-grid" id="certificateDetail{{ $certificate->id }}">
                @foreach ([
                    'Certificate Number' => $certificate->certificate_number,
                    'Participant' => $certificate->participant_name,
                    'Certificate Type' => ucfirst($type),
                    'Target Name' => $certificate->title,
                    'Credential ID' => $certificate->credential_id,
                    'Issued Date' => optional($certificate->issued_at)->format('d M Y') ?? '-',
                    'Verification URL' => $verificationUrl,
                    'Score' => $certificate->score !== null ? $certificate->score : '-',
                    'Issuer' => $issuer,
                ] as $label => $value)
                    <div class="certificate-detail-item {{ $label === 'Verification URL' ? 'certificate-detail-wide' : '' }}">
                        <span>{{ $label }}</span>
                        @if ($label === 'Verification URL')
                            <a href="{{ $verificationUrl }}" target="_blank" rel="noopener">{{ $value }}</a>
                        @else
                            <strong>{{ $value }}</strong>
                        @endif
                    </div>
                @endforeach
            </div>
        </template>
    </td>
    <td class="text-muted">{{ $index }}</td>
    <td>
        <a href="{{ route('certificate.show', $certificate->credential_id) }}" class="certificate-number fw-bold">{{ $certificate->certificate_number }}</a>
        <div class="certificate-title text-truncate" title="{{ $certificate->title }}">{{ $certificate->title }}</div>
        <div class="small text-muted text-uppercase">{{ $certificate->participant_name }}</div>
    </td>
    <td>
        <span class="badge {{ $typeStyles[$type] ?? 'bg-secondary-lt text-secondary' }} certificate-type-badge {{ $type === 'playlist' ? 'certificate-playlist-badge' : '' }}"
              @if($type === 'playlist') tabindex="0" role="button" data-toggle="popover" data-trigger="hover focus" data-placement="auto" data-html="true" data-content="{{ $popover }}" @endif>
            {{ ucfirst($type) }}
        </span>
    </td>
    <td><div class="certificate-target" title="{{ $certificate->title }}">{{ $certificate->title }}</div></td>
    <td>
        <button type="button" class="btn btn-link p-0 certificate-copy-btn" data-copy="{{ $certificate->credential_id }}" title="Copy Credential ID">
            <span>{{ $certificate->credential_id }}</span><i class="ti ti-copy ms-1"></i>
        </button>
    </td>
    <td data-order="{{ optional($certificate->issued_at)->format('Y-m-d') }}">{{ optional($certificate->issued_at)->format('d M Y') ?? '-' }}</td>
    <td><span class="badge bg-green-lt text-green certificate-status-badge"><i class="ti ti-circle-check me-1"></i>Generated</span></td>
    <td class="text-end">
        <div class="btn-list flex-nowrap justify-content-end certificate-actions">
            <button type="button" class="btn btn-sm btn-outline-primary certificate-detail-btn" title="Detail"><i class="ti ti-eye"></i><span>Detail</span></button>
            <a href="{{ route('certificate.download.pdf', $certificate->credential_id) }}" class="btn btn-sm btn-outline-success" title="Download PDF"><i class="ti ti-download"></i></a>
            <a href="{{ route('certificate.verify', $certificate->credential_id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Verify"><i class="ti ti-shield-check"></i></a>
            <div class="dropdown">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-label="More actions"></button>
                <div class="dropdown-menu dropdown-menu-right">
                    <button type="button" class="dropdown-item certificate-copy-btn" data-copy="{{ $certificate->credential_id }}"><i class="ti ti-copy me-2"></i>Copy Credential ID</button>
                    <a class="dropdown-item" href="{{ $certificate->linked_in_url }}" target="_blank" rel="noopener"><i class="ti ti-brand-linkedin me-2"></i>Share LinkedIn</a>
                </div>
            </div>
        </div>
    </td>
</tr>
