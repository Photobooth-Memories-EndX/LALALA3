<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        body { margin: 0; font-family: Georgia, serif; color: #17324d; }
        .certificate { box-sizing: border-box; width: 1120px; min-height: 790px; margin: 20px auto; padding: 70px; text-align: center; border: 18px double #c7a34b;
            @if(!$certificate->certificateSetting->use_default_template && $certificate->certificateSetting->background_image)
            background: url('{{ asset($certificate->certificateSetting->background_image) }}') center/cover no-repeat;
            @elseif(!$certificate->certificateSetting->use_default_template && $certificate->certificateSetting->certificate_template)
            background: url('{{ asset($certificate->certificateSetting->certificate_template) }}') center/cover no-repeat;
            @endif
        }
        h1 { font-size: 54px; margin: 20px 0; } h2 { font-size: 38px; margin: 30px 0; }
        .title { font-size: 28px; font-weight: bold; } .meta { margin-top: 55px; font-family: Arial, sans-serif; }
        @media print { .certificate { margin: 0; } }
    </style>
</head>
<body>
<main class="certificate">
    @if($certificate->certificateSetting->issuer_logo)
        <img src="{{ asset($certificate->certificateSetting->issuer_logo) }}" alt="Issuer logo" style="max-height:80px;max-width:220px">
    @endif
    <div>OWL LMS</div>
    <h1>Certificate of Completion</h1>
    <p>This certificate is proudly presented to</p>
    <h2>{{ $certificate->participant_name }}</h2>
    <p>for successfully completing</p>
    <div class="title">{{ $certificate->title }}</div>
    @if(!is_null($certificate->score))<p>Assessment Score: {{ $certificate->score }}</p>@endif
    <div class="meta">
        <p>Issued by {{ $certificate->certificateSetting->issuer_name }}</p>
        <p>{{ optional($certificate->issued_at)->format('F j, Y') }}</p>
        <p>{{ $certificate->certificate_number }} | {{ $certificate->credential_id }}</p>
    </div>
</main>
</body>
</html>
