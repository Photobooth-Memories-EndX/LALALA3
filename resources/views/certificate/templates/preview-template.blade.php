<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate Preview</title>

    <style>
        body {
            margin: 0;
            padding: 20px;
            background: #f4f6f9;
            font-family: Georgia, serif;
            color: #17324d;
        }

        .certificate {
            position: relative;
            box-sizing: border-box;

            width: 1200px;
            min-height: 848px;

            margin: 0 auto;
            padding: 80px;

            text-align: center;

            border: 18px double #c7a34b;

            overflow: hidden;
        }

        .certificate-bg {
            position: absolute;
            inset: 0;

            background-position: center;
            background-size: 100% 100%;
            background-repeat: no-repeat;

            z-index: 1;
        }

        .content {
            position: relative;
            z-index: 2;
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
    </style>
</head>

<body>

<main class="certificate">

    @if(!$setting->use_default_template && ($setting->custom_background ?: $setting->background_image))
        <div
            class="certificate-bg"
            style="background-image:url('{{ asset($setting->custom_background ?: $setting->background_image) }}')">
        </div>
    @elseif(!$setting->use_default_template && $setting->certificate_template)
        <div
            class="certificate-bg"
            style="background-image:url('{{ asset($setting->certificate_template) }}')">
        </div>
    @endif

    <div class="content">

        <div style="font-size:20px;font-weight:600;">
            {{ $setting->issuer_name }}
        </div>

        <h1>Certificate of Completion</h1>

        <p>
            This certificate is proudly presented to
        </p>

        <h2>
            Nama Peserta
        </h2>

        <p>
            for successfully completing
        </p>

        <div class="title">
            {{ $setting->sourceTitle() }}
        </div>

        <div class="details">

            <div>
                Certificate Type:
                <strong>{{ ucfirst($setting->certificate_type) }}</strong>
            </div>

            @if($setting->certificate_type !== 'global')
                <div>
                    Minimum Progress:
                    <strong>{{ $setting->minimum_progress }}%</strong>
                </div>
            @endif

            @if($setting->is_assessment_required)
                <div>
                    Assessment:
                    <strong>{{ optional($setting->assessment)->title ?: '-' }}</strong>
                </div>

                <div>
                    Minimum Score:
                    <strong>{{ $setting->minimum_score }}</strong>
                </div>
            @endif

        </div>

        @if($setting->certificate_type === 'playlist')
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

            <div>
                Issued by {{ $setting->issuer_name }}
            </div>

            <div>
                {{ now()->format('F j, Y') }}
            </div>

            <div>
                PREVIEW-CERTIFICATE
            </div>

        </div>

    </div>

</main>

</body>
</html>