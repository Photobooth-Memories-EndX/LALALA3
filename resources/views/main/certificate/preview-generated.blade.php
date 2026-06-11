<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Certificate</title>

    <style>
        body {
            margin: 0;
            padding: 30px;
            background: #eef2f6;
            color: #17324d;
            font-family: Georgia, serif;
        }

        .certificate {
            box-sizing: border-box;
            width: 1200px;
            min-height: 848px;
            margin: auto;
            padding: 80px;
            text-align: center;
            background: #fff;
            border: 18px double #c7a34b;

            @if(!$setting->use_default_template && ($setting->custom_background ?: $setting->background_image))
                background: url('{{ asset($setting->custom_background ?: $setting->background_image) }}') center/cover no-repeat;
            @elseif(!$setting->use_default_template && $setting->certificate_template)
                background: url('{{ asset($setting->certificate_template) }}') center/cover no-repeat;
            @endif
        }

        h1 {
            margin: 20px 0;
            font-size: 60px;
            line-height: 1.2;
        }

        h2 {
            margin: 30px 0;
            font-size: 42px;
            line-height: 1.2;
        }

        p {
            margin: 12px 0;
            font-size: 18px;
        }

        .target {
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
        }

        .footer {
            margin-top: 50px;
            font-family: Arial, sans-serif;
            font-size: 16px;
            line-height: 1.8;
        }
    </style>
</head>

<body>
<main class="certificate">

    <div>{{ $setting->issuer_name ?? 'OWL Training Management' }}</div>

    <h1>Certificate of Completion</h1>

    <p>This certificate is proudly presented to</p>

    <h2>Nama Peserta</h2>

    <p>for successfully completing</p>

    <div class="target">
        {{ $setting->sourceTitle() }}
    </div>

    <div class="details">
        <div>Certificate Type: {{ ucfirst($setting->certificate_type) }}</div>
        <div>Target Name: {{ $setting->sourceTitle() }}</div>

        @if($setting->certificate_type !== 'global')
            <div>Minimum Progress: {{ $setting->minimum_progress }}%</div>
        @endif

        @if($setting->is_assessment_required)
            <div>Assessment: {{ optional($setting->assessment)->title ?: '-' }}</div>
            <div>Minimum Score: {{ $setting->minimum_score }}</div>
        @endif
    </div>

    @if($setting->certificate_type === 'playlist')
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

    <div class="footer">
        <div>Credential ID: PREVIEW-000001</div>
        <div>Issued Date: {{ now()->format('F j, Y') }}</div>
    </div>

</main>
</body>
</html>