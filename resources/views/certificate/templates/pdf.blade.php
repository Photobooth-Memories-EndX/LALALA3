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
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
        page-break-after: auto;
    }

    .page-break {
        page-break-after: always;
    }

        .front-page {
            background-image: url('{{ public_path("uploads/template/front.png") }}');
        }

        .back-page {
            background-image: url('{{ public_path("uploads/template/bhind.png") }}');
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
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.55;
        }

        .content-list {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
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

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
@php
    use SimpleSoftwareIO\QrCode\Facades\QrCode;

    $setting = $certificate->certificateSetting;
    $verifyUrl = $certificate->credential_url ?: route('certificate.verify', $certificate->credential_id);

    $isOffline = in_array($certificate->certificate_type, ['training_offline', 'offline'], true);
    $offlineTraining = $isOffline ? ($certificate->offlineTraining ?: ($setting ? $setting->offlineTraining : null)) : null;

    if ($isOffline && $offlineTraining) {
        $startStr = $offlineTraining->start_date ? \Illuminate\Support\Carbon::parse($offlineTraining->start_date)->format('d M Y') : null;
        $finishStr = $offlineTraining->finish_date ? \Illuminate\Support\Carbon::parse($offlineTraining->finish_date)->format('d M Y') : $startStr;
        $periodDate = ($startStr && $finishStr && $startStr !== $finishStr)
            ? "{$startStr} - {$finishStr}"
            : ($finishStr ?: $startStr);
    } else {
        $periodTarget = $certificate->certificate_type === 'content'
            ? $setting?->content
            : ($certificate->certificate_type === 'playlist' ? $setting?->trainingPlan : null);

        $periodDate = ($periodTarget && $periodTarget->has_deadline && $periodTarget->from_date && $periodTarget->to_date)
            ? $periodTarget->from_date->format('d M Y') . ' - ' . $periodTarget->to_date->format('d M Y')
            : null;
    }

    $qrCode = base64_encode(
        QrCode::format('svg')
            ->size(180)
            ->margin(1)
            ->color(105, 100, 65)
            ->generate($verifyUrl)
    );

    $playlistContents = $certificate->certificate_type === 'playlist'
        ? $setting?->playlistContents()->values() ?? collect()
        : collect();

    $contentChunks = $playlistContents->isNotEmpty()
        ? $playlistContents->chunk(30)
        : collect([collect()]);
@endphp

<div  class="certificate-page front-page {{ $certificate->certificate_type === 'playlist' ? 'page-break' : '' }}">

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
                <div class="details-value">{{ str_replace('_', ' ', ucwords($certificate->certificate_type, '_')) }}</div>
            </div>

            <div class="details-row">
                <div class="details-label">Target Name</div>
                <div class="details-separator">:</div>
                <div class="details-value">{{ $certificate->title }}</div>
            </div>

            @if($setting && $setting->assessment)
                <div class="details-row">
                    <div class="details-label">Assessment</div>
                    <div class="details-separator">:</div>
                    <div class="details-value">{{ $setting->assessment->title }}</div>
                </div>
            @endif

            @if($certificate->certificate_type !== 'global' && $periodDate)
                <div class="details-row">
                    <div class="details-label">Period Date</div>
                    <div class="details-separator">:</div>
                    <div class="details-value">{{ $periodDate }}</div>
                </div>
            @endif

            @if($setting && ($setting->is_assessment_required || $certificate->certificate_type === 'global'))
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

            <div class="credential-url">Issued Date : {{ optional($certificate->issued_at)->format('F d, Y') }}</div>

            <div class="credential-url">
                {{ $verifyUrl }}
            </div>
    </div>

</div>

@if($certificate->certificate_type === 'playlist')
    @foreach($contentChunks as $chunkIndex => $contents)
        <div  class="certificate-page back-page {{ !$loop->last ? 'page-break' : '' }}">

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
                            <th width="8%">No</th>
                            <th>Content Title</th>
                            <th width="18%">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($contents as $content)
                            <tr>
                                <td class="text-center">
                                    {{ ($chunkIndex * 15) + $loop->iteration }}
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

                <div class="credential-url">Issued Date : {{ optional($certificate->issued_at)->format('F d, Y') }}</div>

                <div class="credential-url">
                    {{ $verifyUrl }}
                </div>


            </div>


        </div>
    @endforeach
@endif

</body>
</html>
