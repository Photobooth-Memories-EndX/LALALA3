@extends('layouts.sidebar-master')

@section('content')
@php
    $isDebugCertificatePreview = (bool) ($isDebugCertificatePreview ?? false);
    $setting = $isDebugCertificatePreview
        ? $certificateSetting
        : $certificate->certificateSetting;
    $displayTitle = $isDebugCertificatePreview
        ? $setting->sourceTitle()
        : $certificate->title;
    $displayParticipantName = $isDebugCertificatePreview
        ? $debugParticipant->name
        : $certificate->participant_name;
    $debugPlaylist = $isDebugCertificatePreview
        ? $setting->trainingPlan
        : null;
    $hasCustomTemplate = $setting
        && !(bool) $setting->use_default_template
        && (
            !empty($setting->certificate_template)
            || !empty($setting->custom_background)
            || !empty($setting->background_image)
        );
@endphp

<div class="page-body">
    <div class="container-xl">

    @if($isDebugCertificatePreview)
        <div class="alert alert-warning border-warning shadow-sm debug-preview-alert" role="alert">
            <div class="d-flex align-items-start">
                <div class="me-3 flex-shrink-0">
                    <span class="avatar bg-warning-lt text-warning">
                        <i class="ti ti-alert-triangle fs-2" aria-hidden="true"></i>
                    </span>
                </div>
                <div>
                    <h2 class="alert-title mb-1">DEBUG CERTIFICATE PREVIEW</h2>
                    <p class="mb-2">This page is rendered using Debug Session.</p>
                    <ul class="mb-2 ps-3">
                        <li>No Certificate Record has been created.</li>
                        <li>No Credential ID exists.</li>
                        <li>No Certificate Number exists.</li>
                        <li>Download, Verify, Share, and LinkedIn are disabled.</li>
                    </ul>
                    <div class="fw-semibold">
                        Available only to users with
                        <code>playlist_debug_tools</code> permission.
                    </div>
                </div>
            </div>
        </div>

        <section class="card border-warning shadow-sm mb-3" aria-labelledby="debugPreviewModeTitle">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="text-muted text-uppercase small fw-bold">Developer Tools</div>
                    <h3 class="card-title mb-0" id="debugPreviewModeTitle">Preview Mode</h3>
                </div>
                <span class="badge bg-warning-lt text-warning">
                    <i class="ti ti-bug me-1" aria-hidden="true"></i>
                    DEBUG SESSION ACTIVE
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="text-muted small">Playlist</div>
                        <div class="fw-semibold">{{ $debugPlaylist?->title }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted small">Certificate Setting</div>
                        <div class="fw-semibold">
                            #{{ $setting->getKey() }} · {{ $setting->issuer_name }}
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted small">Current User</div>
                        <div class="fw-semibold">{{ $debugParticipant->name }}</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="text-muted small">Debug Session</div>
                        <span class="badge bg-success-lt text-success">ACTIVE</span>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap gap-2">
                <a href="{{ $playlistReturnUrl }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-2" aria-hidden="true"></i>
                    Back to Playlist
                </a>
                <form method="POST"
                      action="{{ route('playlist.debug.certificate.preview.exit', $debugPlaylist) }}"
                      class="m-0">
                    @csrf
                    @method('DELETE')
                    @if($previewContentSlug)
                        <input type="hidden" name="content" value="{{ $previewContentSlug }}">
                    @endif
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="ti ti-logout me-2" aria-hidden="true"></i>
                        Exit Debug Preview
                    </button>
                </form>
            </div>
        </section>
    @elseif(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card border-0 shadow-sm {{ $isDebugCertificatePreview ? 'certificate-preview-card' : '' }}">

                @if($isDebugCertificatePreview)
                    <div class="debug-certificate-watermark" aria-hidden="true">
                        DEBUG PREVIEW
                    </div>
                @endif

                <div class="card-body text-center p-5 position-relative certificate-card-content">

                    <div class="mb-3">

                        <span class="badge {{ $isDebugCertificatePreview ? 'bg-warning-lt text-warning' : 'bg-green-lt text-green' }}">
                            <i class="ti {{ $isDebugCertificatePreview ? 'ti-eye' : 'ti-circle-check' }} mr-1"></i>
                            {{ $isDebugCertificatePreview ? 'Debug Preview Only' : 'Certificate Generated' }}
                        </span>

                    </div>

                    <div class="mb-4">

                        <span class="avatar avatar-xl bg-green-lt text-green">
                            <i class="ti ti-certificate"></i>
                        </span>

                    </div>

                    <h2 class="mb-2">
                        {{ $displayTitle }}
                    </h2>

                    <div class="text-muted mb-4">
                        {{ $isDebugCertificatePreview ? 'Preview rendered for' : 'Successfully completed by' }}
                    </div>

                    <h3 class="mb-4">
                        {{ $displayParticipantName }}
                    </h3>

                    <div class="row text-left mb-4">

                        <div class="col-md-6 mb-3">

                            <div class="small text-muted">
                                Certificate Number
                            </div>

                            <div class="font-weight-bold">
                                {{ $isDebugCertificatePreview ? 'Not created (debug preview)' : $certificate->certificate_number }}
                            </div>

                        </div>

                        <div class="col-md-6 mb-3">

                            <div class="small text-muted">
                                Credential ID
                            </div>

                            <div class="font-weight-bold">
                                {{ $isDebugCertificatePreview ? 'Not created (debug preview)' : $certificate->credential_id }}
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="small text-muted">
                                Issued Date
                            </div>

                            <div class="font-weight-bold">
                                {{ $isDebugCertificatePreview
                                    ? 'Not issued'
                                    : optional($certificate->issued_at)->format('d M Y') }}
                            </div>

                        </div>

                        <div class="col-md-6">

                            <div class="small text-muted">
                                Issuer
                            </div>

                            <div class="font-weight-bold">
                                {{ optional($setting)->issuer_name }}
                            </div>

                        </div>

                    </div>

                    <hr>

                    <div class="text-center mt-4">

                        @if($isDebugCertificatePreview)
                            <span class="debug-disabled-action mx-1 mb-2"
                                  tabindex="0"
                                  role="button"
                                  data-debug-disabled-action
                                  data-tooltip="Disabled in Debug Preview.">
                                <button type="button" class="btn btn-primary" disabled aria-disabled="true">
                                    <i class="ti ti-file-type-pdf mr-1"></i>
                                    Download PDF
                                </button>
                            </span>
                            <span class="debug-disabled-action mx-1 mb-2"
                                  tabindex="0"
                                  role="button"
                                  data-debug-disabled-action
                                  data-tooltip="Disabled in Debug Preview.">
                                <button type="button" class="btn btn-success" disabled aria-disabled="true">
                                    <i class="ti ti-file-type-html mr-1"></i>
                                    Download HTML
                                </button>
                            </span>
                            <span class="debug-disabled-action mx-1 mb-2"
                                  tabindex="0"
                                  role="button"
                                  data-debug-disabled-action
                                  data-tooltip="Disabled in Debug Preview.">
                                <button type="button" class="btn btn-warning" disabled aria-disabled="true">
                                    <i class="ti ti-shield-check mr-1"></i>
                                    Verify
                                </button>
                            </span>
                            <span class="debug-disabled-action mx-1 mb-2"
                                  tabindex="0"
                                  role="button"
                                  data-debug-disabled-action
                                  data-tooltip="Disabled in Debug Preview.">
                                <button type="button" class="btn btn-secondary" disabled aria-disabled="true">
                                    <i class="ti ti-link-off mr-1"></i>
                                    Credential URL
                                </button>
                            </span>
                            <span class="debug-disabled-action mx-1 mb-2"
                                  tabindex="0"
                                  role="button"
                                  data-debug-disabled-action
                                  data-tooltip="Disabled in Debug Preview.">
                                <button type="button" class="btn btn-info" disabled aria-disabled="true">
                                    <i class="ti ti-share mr-1"></i>
                                    Share
                                </button>
                            </span>
                            <span class="debug-disabled-action mx-1 mb-2"
                                  tabindex="0"
                                  role="button"
                                  data-debug-disabled-action
                                  data-tooltip="Disabled in Debug Preview.">
                                <button type="button" class="btn btn-info" disabled aria-disabled="true">
                                    <i class="ti ti-brand-linkedin mr-1"></i>
                                    LinkedIn
                                </button>
                            </span>
                            @if($hasCustomTemplate)
                                <span class="debug-disabled-action mx-1 mb-2"
                                      tabindex="0"
                                      role="button"
                                      data-debug-disabled-action
                                      data-tooltip="Disabled in Debug Preview.">
                                    <button type="button" class="btn btn-outline-primary" disabled aria-disabled="true">
                                        <i class="ti ti-certificate mr-1"></i>
                                        Custom Certificate
                                    </button>
                                </span>
                            @endif
                            <div class="small text-muted mt-2">
                                Production actions are disabled because this preview has no certificate record or credential.
                            </div>
                        @else
                            {{-- PDF --}}
                            <a href="{{ route('certificate.download.pdf', $certificate->credential_id) }}"
                               class="btn btn-primary mx-1 mb-2">

                                <i class="ti ti-file-type-pdf mr-1"></i>
                                Download PDF

                            </a>

                            {{-- HTML --}}
                            <a href="{{ route('certificate.download', $certificate->credential_id) }}"
                               class="btn btn-success mx-1 mb-2">

                                <i class="ti ti-file-type-html mr-1"></i>
                                Download HTML

                            </a>

                            {{-- Verify --}}
                            <a href="{{ route('certificate.verify', $certificate->credential_id) }}"
                               target="_blank"
                               class="btn btn-warning mx-1 mb-2">

                                <i class="ti ti-shield-check mr-1"></i>
                                Verify

                            </a>

                            {{-- Custom Certificate --}}
                            @if($hasCustomTemplate)
                                <a href="{{ route('certificate.download.custom', $certificate->credential_id) }}"
                                   class="btn btn-outline-primary mx-1 mb-2">
                                    <i class="ti ti-certificate mr-1"></i>
                                    Download Custom Certificate
                                </a>
                            @endif

                            {{-- LinkedIn --}}
                            @if(!empty($certificate->linkedin_url))

                                <a href="{{ $certificate->linkedin_url }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="btn btn-info mx-1 mb-2">

                                    <i class="ti ti-brand-linkedin mr-1"></i>
                                    Add To LinkedIn

                                </a>

                            @endif
                        @endif

                    </div>

                </div>

                @if($isDebugCertificatePreview)
                    <div class="card-footer text-center bg-warning-lt">
                        <div class="fw-bold text-warning">Developer Debug Preview</div>
                        <div class="small text-muted">
                            This page does not create any Certificate Record.
                            Production Generate Flow remains unchanged.
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </div>

</div>

</div>
@endsection

@section('style')
    <style>
        .debug-preview-alert {
            border-left-width: 5px;
        }

        .certificate-preview-card {
            position: relative;
            overflow: hidden;
        }

        .certificate-card-content {
            z-index: 1;
        }

        .debug-certificate-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            z-index: 0;
            width: 140%;
            color: #f59f00;
            font-size: clamp(3rem, 9vw, 7rem);
            font-weight: 900;
            letter-spacing: .12em;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            opacity: .075;
            pointer-events: none;
            transform: translate(-50%, -50%) rotate(-24deg);
            user-select: none;
        }

        .debug-disabled-action {
            position: relative;
            display: inline-block;
            cursor: not-allowed;
        }

        .debug-disabled-action > .btn {
            pointer-events: none;
        }

        .debug-disabled-action::after {
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            z-index: 20;
            min-width: 190px;
            padding: 6px 9px;
            border-radius: 6px;
            background: #1f2937;
            color: #fff;
            content: attr(data-tooltip);
            font-size: 12px;
            line-height: 1.3;
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, 4px);
            transition: opacity .15s ease, transform .15s ease;
        }

        .debug-disabled-action:hover::after,
        .debug-disabled-action:focus::after,
        .debug-disabled-action.is-tooltip-visible::after {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    </style>
@endsection

@section('script')
    @if($isDebugCertificatePreview)
        <script>
            document.addEventListener('click', function (event) {
                const action = event.target.closest('[data-debug-disabled-action]');

                if (!action) {
                    return;
                }

                event.preventDefault();
                action.classList.add('is-tooltip-visible');

                window.setTimeout(function () {
                    action.classList.remove('is-tooltip-visible');
                }, 1800);
            });
        </script>
    @endif
@endsection
