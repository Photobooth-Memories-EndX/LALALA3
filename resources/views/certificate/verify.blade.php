<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Certificate</title>
    <style>
        body { margin: 0; background: #f3f6f9; color: #1f2937; font-family: Arial, sans-serif; }
        .card { max-width: 720px; margin: 8vh auto; padding: 48px; text-align: center; background: #fff; border-radius: 12px; box-shadow: 0 10px 35px rgba(0,0,0,.08); }
        .verified { display: inline-block; padding: 8px 14px; color: #087443; background: #dff7ea; border-radius: 20px; font-weight: bold; }
        .expired { display: inline-block; padding: 8px 14px; color: #b91c1c; background: #fee2e2; border-radius: 20px; font-weight: bold; }
        h1 { margin-bottom: 8px; } h3 { color: #365b7d; } .credential { margin-top: 28px; line-height: 1.8; }
    </style>
</head>
<body>
<main class="card">
    @if($certificate->isExpired())
        <span class="expired">EXPIRED CERTIFICATE</span>
    @elseif($certificate->isLifetime())
        <span class="verified">VERIFIED - LIFETIME CERTIFICATE</span>
    @else
        <span class="verified">VERIFIED - VALID</span>
    @endif

    <h1>{{ $certificate->participant_name }}</h1>
    <h3>{{ $certificate->title }}</h3>
    <p>Issued by {{ $certificate->certificateSetting->issuer_name ?? 'OWL Training Management' }} on {{ optional($certificate->issued_at)->format('F j, Y') }}</p>

    @if($certificate->expired_at)
        <p style="font-size: 0.9em; color: {{ $certificate->isExpired() ? '#b91c1c' : '#4b5563' }};">
            <strong>Expiry Date:</strong> {{ $certificate->expired_at->format('F j, Y') }}
        </p>
    @else
        <p style="font-size: 0.9em; color: #087443;">
            <strong>Validity:</strong> Lifetime (No Expiration)
        </p>
    @endif

    <div class="credential">
        <strong>{{ $certificate->certificate_number }}</strong><br>
        {{ $certificate->credential_id }}
    </div>
</main>
</body>
</html>
