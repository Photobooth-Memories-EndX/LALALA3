<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>

    <style>
        @page {
            size: 1200px 848px;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 1200px;
            height: 848px;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: Georgia, serif;
            color: #17324d;
            background: #ffffff;
        }

        .certificate {
            position: relative;
            width: 1200px;
            height: 848px;
            overflow: hidden;
            text-align: center;

            @if(
                !$certificate->certificateSetting->use_default_template &&
                ($certificate->certificateSetting->custom_background ?: $certificate->certificateSetting->background_image)
            )
                background-image: url('{{ public_path($certificate->certificateSetting->custom_background ?: $certificate->certificateSetting->background_image) }}');
                background-size: 100% 100%;
                background-position: center center;
                background-repeat: no-repeat;
            @elseif(
                !$certificate->certificateSetting->use_default_template &&
                $certificate->certificateSetting->certificate_template
            )
                background-image: url('{{ public_path($certificate->certificateSetting->certificate_template) }}');
                background-size: 100% 100%;
                background-position: center center;
                background-repeat: no-repeat;
            @else
                background: #ffffff;
                border: 18px double #c7a34b;
            @endif
        }

        .content-wrapper {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 82%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .issuer {
            font-size: 18px;
            margin-bottom: 18px;
        }

        h1 {
            font-size: 52px;
            line-height: 1.1;
            margin: 0 0 18px 0;
            color: #0f2d52;
        }

        h2 {
            font-size: 40px;
            line-height: 1.1;
            margin: 18px 0;
            color: #0f2d52;
        }

        p {
            font-size: 16px;
            margin: 8px 0;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            line-height: 1.3;
            margin: 16px auto;
            max-width: 850px;
        }

        .details {
            margin-top: 18px;
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.6;
        }

        .playlist {
            display: inline-block;
            text-align: left;
            margin-top: 12px;
            font-size: 12px;
            font-family: Arial, sans-serif;
        }

        .playlist ol {
            margin-top: 6px;
            padding-left: 18px;
        }

        .meta {
            margin-top: 24px;
            font-size: 12px;
            line-height: 1.6;
            font-family: Arial, sans-serif;
        }

        .certificate-number {
            font-weight: bold;
        }
    </style>
</head>

<body>
@php($setting = $certificate->certificateSetting)

<div class="certificate">

    <div class="content-wrapper">

        <div class="issuer">
            {{ $setting->issuer_name }}
        </div>

        <h1>Certificate of Completion</h1>

        <p>This certificate is proudly presented to</p>

        <h2>{{ $certificate->participant_name }}</h2>

        <p>for successfully completing</p>

        <div class="title">
            {{ $certificate->title }}
        </div>

        <div class="details">
            <div>Certificate Type : {{ ucfirst($certificate->certificate_type) }}</div>
            <div>Target Name : {{ $certificate->title }}</div>

            @if($certificate->certificate_type !== 'global')
                <div>Minimum Progress : {{ $setting->minimum_progress }}%</div>
            @endif

            @if($setting->is_assessment_required)
                <div>Assessment : {{ optional($setting->assessment)->title ?: '-' }}</div>
                <div>Minimum Score : {{ $setting->minimum_score }}</div>
                <div>Assessment Score : {{ $certificate->score ?? '-' }}</div>
            @endif
        </div>

        @if($certificate->certificate_type === 'playlist')
            <div class="playlist">
                <strong>Playlist Content</strong>

                <ol>
                    @forelse($setting->playlistContents() as $content)
                        <li>{{ $content->title }}</li>
                    @empty
                        <li>No content available</li>
                    @endforelse
                </ol>
            </div>
        @endif

        <div class="meta">
            <div>Issued by {{ $setting->issuer_name }}</div>
            <div>{{ optional($certificate->issued_at)->format('F d, Y') }}</div>
            <div class="certificate-number">{{ $certificate->certificate_number }}</div>
            <div>{{ $certificate->credential_id }}</div>
        </div>

    </div>

</div>

</body>
</html>