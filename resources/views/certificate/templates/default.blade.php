<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        /* body { margin: 0; font-family: Georgia, serif; color: #17324d; }
        .certificate { box-sizing: border-box; width: 1120px; min-height: 790px; margin: 20px auto; padding: 65px; text-align: center; border: 18px double #c7a34b;
            @if(!$certificate->certificateSetting->use_default_template && ($certificate->certificateSetting->custom_background ?: $certificate->certificateSetting->background_image))
            background: url('{{ asset($certificate->certificateSetting->custom_background ?: $certificate->certificateSetting->background_image) }}') center/cover no-repeat;
            @elseif(!$certificate->certificateSetting->use_default_template && $certificate->certificateSetting->certificate_template)
            background: url('{{ asset($certificate->certificateSetting->certificate_template) }}') center/cover no-repeat;
            @endif
        }
        h1 { font-size: 54px; margin: 20px 0; } h2 { font-size: 38px; margin: 28px 0; }
        .title { font-size: 28px; font-weight: bold; } .details, .meta { font-family: Arial, sans-serif; line-height: 1.7; }
        .details { margin: 28px 0; } .playlist { display: inline-block; text-align: left; }
        .meta { margin-top: 40px; } @media print { .certificate { margin: 0; } } */

    body {
        margin: 0;
        padding: 20px;
        background: #f4f6f9;
        font-family: Georgia, serif;
        color: #17324d;
    }

    .certificate {
        box-sizing: border-box;

        width: 1200px;
        min-height: 848px;

        margin: 0 auto;
        padding: 80px;

        text-align: center;

        border: 18px double #c7a34b;

        @if(!$certificate->certificateSetting->use_default_template && ($certificate->certificateSetting->custom_background ?: $certificate->certificateSetting->background_image))
            background: url('{{ asset($certificate->certificateSetting->custom_background ?: $certificate->certificateSetting->background_image) }}') center center no-repeat;
        @elseif(!$certificate->certificateSetting->use_default_template && $certificate->certificateSetting->certificate_template)
            background: url('{{ asset($certificate->certificateSetting->certificate_template) }}') center center no-repeat;
        @endif

        background-size: 100% 100%;
    }

    h1 {
        font-size: 60px;
        margin: 20px 0;
        line-height: 1.2;
    }

    h2 {
        font-size: 42px;
        margin: 30px 0;
        line-height: 1.2;
    }

    p {
        font-size: 18px;
        margin: 12px 0;
    }

    .title {
        font-size: 32px;
        font-weight: bold;
        margin-top: 20px;
    }

    .details {
        margin: 35px auto;
        font-family: Arial, sans-serif;
        font-size: 16px;
        line-height: 1.8;
    }

    .playlist {
        display: inline-block;
        text-align: left;
        font-family: Arial, sans-serif;
        font-size: 16px;
        margin-top: 15px;
    }

    .playlist ol {
        margin-top: 10px;
        padding-left: 25px;
    }

    .meta {
        margin-top: 50px;
        font-family: Arial, sans-serif;
        font-size: 16px;
        line-height: 1.8;
    }

    @media print {
        body {
            padding: 0;
            margin: 0;
        }

        .certificate {
            margin: 0;
        }
    }
    </style>
</head>
<body>
@php($setting = $certificate->certificateSetting)
<main class="certificate">
    <div>{{ $setting->issuer_name }}</div>
    <h1>Certificate of Completion</h1>
    <p>This certificate is proudly presented to</p>
    <h2>{{ $certificate->participant_name }}</h2>
    <p>for successfully completing</p>
    <div class="title">{{ $certificate->title }}</div>
    <div class="details">
        <div>Certificate Type: {{ ucfirst($certificate->certificate_type) }}</div>
        <div>Target Name: {{ $certificate->title }}</div>
        @if($certificate->certificate_type !== 'global')
            <div>Minimum Progress: {{ $setting->minimum_progress }}%</div>
        @endif
        @if($setting->is_assessment_required)
            <div>Assessment: {{ optional($setting->assessment)->title ?: '-' }}</div>
            <div>Minimum Score: {{ $setting->minimum_score }}</div>
            <div>Assessment Score: {{ $certificate->score ?? '-' }}</div>
        @endif
    </div>
    @if($certificate->certificate_type === 'playlist')
        <div class="playlist">
            <strong>Playlist Content</strong>
            <ol>
                @forelse($setting->playlistContents() as $content)
                    <li>{{ $content->title }}</li>
                @empty
                    <li>No content</li>
                @endforelse
            </ol>
        </div>
    @endif
    <div class="meta">
        <div>Issued by {{ $setting->issuer_name }}</div>
        <div>{{ optional($certificate->issued_at)->format('F j, Y') }}</div>
        <div>{{ $certificate->certificate_number }} | {{ $certificate->credential_id }}</div>
    </div>
</main>
</body>
</html>
