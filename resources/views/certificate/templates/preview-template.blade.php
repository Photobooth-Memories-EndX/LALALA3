<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>

    <style>
        @page {
            size: 848px 1200px;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 848px;
            height: 1200px;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: #696441;
            background: #ffffff;
        }

        .certificate-page {
            position: relative;
            width: 848px;
            height: 1200px;
            overflow: hidden;
            page-break-after: always;
            background-size: 100% 100%;
            background-position: center center;
            background-repeat: no-repeat;
        }

        .front-page {
            background-image: url('{{ public_path("uploads/template/Front.png") }}');
        }

        .back-page {
            background-image: url('{{ public_path("uploads/template/Bhind.png") }}');
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

        .presented-text {
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

        .completion-text {
            font-size: 18px;
            margin-bottom: 28px;
            color: #696441;
        }

        .training-title {
            font-size: 31px;
            font-weight: bold;
            line-height: 1.25;
            color: #696441;
            margin-bottom: 36px;
        }

        .details-table {
            margin-top: 0;
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

        .qr-placeholder {
            width: 86px;
            height: 86px;
            border: 1px solid #696441;
            margin-left: auto;
            margin-bottom: 10px;
            font-size: 10px;
            text-align: center;
            line-height: 86px;
            color: #696441;
        }

        .credential-label {
            font-size: 14px;
            color: #696441;
        }

        .credential-id {
            font-size: 15px;
            font-weight: bold;
            color: #696441;
        }

        .credential-url {
            font-size: 10px;
            word-break: break-all;
            color: #696441;
        }

        .attachment-content {
            position: absolute;
            top: 230px;
            left: 70px;
            right: 100px;
            font-family: Arial, sans-serif;
            color: #696441;
        }

        .attachment-title {
            text-align: left;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #696441;
        }

        .attachment-subtitle {
            text-align: left;
            font-size: 15px;
            margin-bottom: 28px;
            line-height: 1.6;
            color: #696441;
        }

        .attachment-meta {
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.7;
            color: #696441;
        }

        .content-list {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            color: #696441;
        }

        .content-list th,
        .content-list td {
            border: 1px solid #c9c4a3;
            padding: 8px 10px;
            vertical-align: top;
        }

        .content-list th {
            background: #f7f6ef;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .back-credential-box {
            position: absolute;
            right: 58px;
            bottom: 54px;
            width: 360px;
            font-size: 12px;
            line-height: 1.45;
            color: #696441;
            text-align: right;
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
@endphp

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
            <div>Issuer : {{ $setting->issuer_name }}</div>
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

@if($certificate->certificate_type === 'playlist')
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

            <div class="attachment-meta">
                <div>Participant Name : {{ $certificate->participant_name }}</div>
                <div>Certificate Number : {{ $certificate->certificate_number }}</div>
                <div>Credential ID : {{ $certificate->credential_id }}</div>
                <div>Issued Date : {{ optional($certificate->issued_at)->format('F d, Y') }}</div>
            </div>

            <table class="content-list">
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th>Content Title</th>
                        <th width="18%">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($setting->playlistContents() as $content)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
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

        <div class="back-credential-box">
            <div>Credential ID:</div>
            <strong>{{ $certificate->credential_id }}</strong>
        </div>

    </div>
@endif

</body>
</html>
