<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Preview Certificate</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            padding: 40px;
        }

        .certificate {
            width: 1000px;
            min-height: 650px;
            margin: auto;
            background: white;
            border: 12px solid #1f2937;
            padding: 50px;
            text-align: center;
            position: relative;
        }

        .issuer {
            font-size: 18px;
            color: #374151;
            margin-bottom: 40px;
        }

        .title {
            font-size: 42px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 20px;
        }

        .subtitle {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .name {
            font-size: 34px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 20px;
        }

        .course {
            font-size: 26px;
            color: #111827;
            margin-bottom: 30px;
        }

        .meta {
            font-size: 16px;
            color: #374151;
            margin-top: 40px;
        }

        .footer {
            position: absolute;
            bottom: 40px;
            left: 50px;
            right: 50px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>

<body>

@php
    $certificateName = 'Certificate of Completion';

    if ($setting->certificate_type === 'content') {
        $targetName = $setting->content->title ?? 'Content Learning';
    } elseif ($setting->certificate_type === 'playlist') {
        $targetName = $setting->trainingPlan->title ?? 'Playlist Learning';
    } else {
        $targetName = $setting->assessment->title ?? 'Global Assessment';
    }
@endphp

<div class="certificate">

    <div class="issuer">
        {{ $setting->issuer_name }}
    </div>

    <div class="title">
        {{ $certificateName }}
    </div>

    <div class="subtitle">
        This certificate is proudly presented to
    </div>

    <div class="name">
        Nama Peserta
    </div>

    <div class="subtitle">
        for successfully completing
    </div>

    <div class="course">
        {{ $targetName }}
    </div>

    <div class="meta">
        Minimum Progress: {{ $setting->minimum_progress }}%
        @if($setting->is_assessment_required)
            | Minimum Score: {{ $setting->minimum_score }}
        @endif
    </div>

    <div class="footer">
        <div>
            Credential ID: PREVIEW-000001
        </div>

        <div>
            Issued Date: {{ now()->format('Y-m-d') }}
        </div>
    </div>

</div>

</body>
</html>