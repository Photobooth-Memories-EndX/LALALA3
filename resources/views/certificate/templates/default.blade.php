<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>

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

        .download-wrapper {
            width: 848px;
            margin: 0 auto 30px auto;
            background: #ffffff;
            box-shadow: 0 10px 35px rgba(0,0,0,.15);
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

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            .download-wrapper {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }

            .download-wrapper:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>
@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $setting = $certificate->certificateSetting;
    $verifyUrl = $certificate->credential_url ?: route('certificate.verify', $certificate->credential_id);

    $qrCode = base64_encode(
        QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->color(105, 100, 65)
            ->generate($verifyUrl)
    );

    $playlistContents = $certificate->certificate_type === 'playlist'
        ? $setting->playlistContents()->values()
        : collect();

    $contentChunks = $playlistContents->isNotEmpty()
        ? $playlistContents->chunk(30)
        : collect();
@endphp

<div class="download-wrapper">
    <div class="certificate-page front-page">

        <div class="certificate-number">
            No. : {{ $certificate->certificate_number }}
        </div>

        <div class="main-content">

            <div class="presented-text">
                This certificate is proudly presented to
            </div>

            <div class="participant-name">
                {{ $certificate->participant_name }}
            </div>

            <div class="completion-text">
                for successfully completing
            </div>

            <div class="training-title">
                {{ $certificate->title }}
            </div>

            <div class="details-table">

                <div class="details-row">
                    <div class="details-label">Certificate Type</div>
                    <div class="details-separator">:</div>
                    <div class="details-value">{{ ucfirst($certificate->certificate_type) }}</div>
                </div>

                <div class="details-row">
                    <div class="details-label">Target Name</div>
                    <div class="details-separator">:</div>
                    <div class="details-value">{{ $certificate->title }}</div>
                </div>

                @if($setting->assessment)
                    <div class="details-row">
                        <div class="details-label">Assessment</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $setting->assessment->title }}</div>
                    </div>
                @endif

                @if($certificate->certificate_type !== 'global')
                    <div class="details-row">
                        <div class="details-label">Minimum Progress</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $setting->minimum_progress }}%</div>
                    </div>
                @endif

                @if($setting->is_assessment_required || $certificate->certificate_type === 'global')
                    <div class="details-row">
                        <div class="details-label">Minimum Score</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $setting->minimum_score }}</div>
                    </div>
                @endif

                @if($certificate->score)
                    <div class="details-row">
                        <div class="details-label">Assessment Score</div>
                        <div class="details-separator">:</div>
                        <div class="details-value">{{ $certificate->score }}</div>
                    </div>
                @endif

            </div>

            <div class="meta-info">
                <div>Issued Date : {{ optional($certificate->issued_at)->format('F d, Y') }}</div>
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
                {{ $certificate->credential_id }}
            </div>

            <div class="credential-url">
                {{ $verifyUrl }}
            </div>
        </div>

    </div>
</div>

@if($certificate->certificate_type === 'playlist')
    @foreach($contentChunks as $chunkIndex => $contents)
        <div class="download-wrapper">
            <div class="certificate-page back-page">

                <div class="attachment-content">

                    <div class="attachment-title">
                        Attachment of Training Content
                    </div>

                    <div class="attachment-subtitle">
                        This attachment lists the learning contents completed for
                        <br>
                        <strong>{{ $certificate->title }}</strong>
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
                        {{ $certificate->credential_id }}
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
