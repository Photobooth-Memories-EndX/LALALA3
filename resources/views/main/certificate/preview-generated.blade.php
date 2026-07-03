<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Certificate</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #eef2f6;
            font-family: Arial, sans-serif;
            color: #696441;
        }

        .preview-wrapper {
            width: 848px;
            margin: 0 auto 30px auto;
            background: #ffffff;
            box-shadow: 0 10px 35px rgba(0,0,0,.15);
        }

        .preview-label {
            width: 848px;
            margin: 0 auto 12px auto;
            font-size: 14px;
            color: #6b7280;
        }

        .certificate-page {
            position: relative;
            width: 848px;
            height: 1200px;
            overflow: hidden;
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
        }

        .front-page {
            background-image: url('{{ asset("uploads/template/Front.png") }}');
        }

        .back-page {
            background-image: url('{{ asset("uploads/template/Bhind.png") }}');
        }

        .certificate-number {
            position: absolute;
            top: 58px;
            left: 58px;
            font-size: 19px;
            font-weight: bold;
            color: #696441;
        }

        .main-content {
            position: absolute;
            top: 330px;
            left: 58px;
            right: 128px;
            text-align: left;
        }

        .presented-text,
        .completion-text {
            font-size: 18px;
            margin-bottom: 28px;
            color: #696441;
        }

        .participant-name {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 34px;
            color: #696441;
            text-transform: uppercase;
            line-height: 1.12;
        }

        .training-title {
            font-size: 31px;
            font-weight: bold;
            line-height: 1.25;
            color: #696441;
            margin-bottom: 36px;
        }

        .details-table {
            font-size: 18px;
            line-height: 1.8;
            color: #696441;
        }

        .details-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .details-label {
            display: table-cell;
            width: 190px;
        }

        .details-separator {
            display: table-cell;
            width: 25px;
        }

        .details-value {
            display: table-cell;
        }

        .meta-info {
            margin-top: 54px;
            font-size: 17px;
            line-height: 1.8;
            color: #696441;
        }

        .credential-box {
            position: absolute;
            right: 58px;
            bottom: 54px;
            width: 360px;
            font-size: 12px;
            line-height: 1.45;
            color: #696441;
            text-align: right;
        }

        .qr-code {
            width: 86px;
            height: 86px;
            margin-left: auto;
            margin-bottom: 10px;
            display: block;
        }

        .credential-label {
            font-size: 14px;
        }

        .credential-id {
            font-size: 15px;
            font-weight: bold;
        }

        .credential-url {
            font-size: 10px;
            word-break: break-all;
        }

        .attachment-content {
            position: absolute;
            top: 115px;
            left: 70px;
            right: 100px;
            color: #696441;
        }

        .attachment-title {
            text-align: left;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .attachment-subtitle {
            text-align: left;
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.45;
        }

        .attachment-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            column-gap: 18px;
            row-gap: 4px;
            margin-bottom: 14px;
            font-size: 10px;
            line-height: 1.35;
            color: #696441;
        }

        .attachment-meta > div {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .content-list {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            color: #696441;
            line-height: 1.3;
        }

        .content-list th,
        .content-list td {
            border: 1px solid #c9c4a3;
            padding: 5px 8px;
            vertical-align: top;
            line-height: 1.3;
        }

        .content-list th {
            background: #f7f6ef;
            font-weight: bold;
        }

        .content-list .no-col {
            width: 8%;
        }

        .content-list .status-col {
            width: 18%;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $previewCredentialId = 'PREVIEW-000001';
    $certificateNumber = 'OWL-CERT-' . now()->format('Y') . '-000001';
    $verifyUrl = route('certificate.verify', $previewCredentialId);

    $periodTarget = $setting->certificate_type === 'content'
        ? $setting->content
        : ($setting->certificate_type === 'playlist' ? $setting->trainingPlan : null);

    $periodDate = ($periodTarget && $periodTarget->has_deadline && $periodTarget->from_date && $periodTarget->to_date)
        ? $periodTarget->from_date->format('d M Y') . ' - ' . $periodTarget->to_date->format('d M Y')
        : null;

    $qrCode = base64_encode(
        QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->color(105, 100, 65)
            ->generate($verifyUrl)
    );

    $playlistContents = $setting->certificate_type === 'playlist'
        ? $setting->playlistContents()->values()
        : collect();

    $contentChunks = $playlistContents->isNotEmpty()
        ? $playlistContents->chunk(30)
        : collect();
@endphp

<div class="preview-label">
    Preview Certificate - Front Page
</div>

<div class="preview-wrapper">
    <div class="certificate-page front-page">

        <div class="certificate-number">
            No. : {{ $certificateNumber }}
        </div>

        <div class="main-content">

            <div class="presented-text">
                This certificate is proudly presented to
            </div>

            <div class="participant-name">
                NAMA PESERTA
            </div>

            <div class="completion-text">
                for successfully completing
            </div>

            <div class="training-title">
                {{ $setting->sourceTitle() }}
            </div>

            <div class="details-table">

                <div class="details-row">
                    <div class="details-label">Certificate Type</div>
                    <div class="details-separator">:</div>
                    <div class="details-value">{{ ucfirst($setting->certificate_type) }}</div>
                </div>

                <div class="details-row">
                    <div class="details-label">Target Name</div>
                    <div class="details-separator">:</div>
                    <div class="details-value">{{ $setting->sourceTitle() }}</div>
                </div>

                @if($setting->assessment)
                    <div class="details-row">
                        <div class="details-label">Assessment</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $setting->assessment->title }}</div>
                    </div>
                @endif

                @if($setting->certificate_type !== 'global' && $periodDate)
                    <div class="details-row">
                        <div class="details-label">Period Date</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $periodDate }}</div>
                    </div>
                @endif

                @if($setting->is_assessment_required || $setting->certificate_type === 'global')
                    <div class="details-row">
                        <div class="details-label">Minimum Score</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $setting->minimum_score }}</div>
                    </div>

                    <div class="details-row">
                        <div class="details-label">Assessment Score</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">100</div>
                    </div>
                @endif

            </div>

            <div class="meta-info">
                <div>Issued Date : {{ now()->format('F d, Y') }}</div>
            </div>

        </div>

        <div class="credential-box">
            <img
                class="qr-code"
                src="data:image/svg+xml;base64,{{ $qrCode }}"
                alt="QR Code">

            <div class="credential-label">
                Credential ID:
            </div>

            <div class="credential-id">
                {{ $previewCredentialId }}
            </div>

            <div class="credential-url">
                {{ $verifyUrl }}
            </div>
        </div>

    </div>
</div>

@if($setting->certificate_type === 'playlist')
    @foreach($contentChunks as $chunkIndex => $contents)
        <div class="preview-label">
            Preview Certificate - Attachment Page {{ $loop->iteration }}
        </div>

        <div class="preview-wrapper">
            <div class="certificate-page back-page">

                <div class="attachment-content">

                    <div class="attachment-title">
                        Attachment of Training Content
                    </div>

                    <div class="attachment-subtitle">
                        This attachment lists the learning contents completed for
                        <br>
                        <strong>{{ $setting->sourceTitle() }}</strong>
                    </div>


                    <table class="content-list">
                        <thead>
                            <tr>
                                <th class="no-col">No</th>
                                <th>Content Title</th>
                                <th class="status-col">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($contents as $content)
                                <tr>
                                    <td class="text-center">
                                        {{ ($chunkIndex * 30) + $loop->iteration }}
                                    </td>
                                    <td>{{ $content->title }}</td>
                                    <td class="text-center">Completed</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">
                                        No content available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                <div class="credential-box">
                    <img
                        class="qr-code"
                        src="data:image/svg+xml;base64,{{ $qrCode }}"
                        alt="QR Code">

                    <div class="credential-label">
                        Credential ID:
                    </div>

                    <div class="credential-id">
                        {{ $previewCredentialId }}
                    </div>

                    <div class="credential-url">
                        {{ $verifyUrl }}
                    </div>
                </div>

            </div>
        </div>
    @endforeach
@endif

</body>
</html>
