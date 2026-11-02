<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Certificate - {{ $certificate->certificate_number }}</title>
    <style>
        body { margin: 0; background: #f3f6f9; color: #1f2937; font-family: Arial, sans-serif; }
        .card { max-width: 720px; margin: 8vh auto; padding: 48px; text-align: center; background: #fff; border-radius: 12px; box-shadow: 0 10px 35px rgba(0,0,0,.08); }
        .verified { display: inline-block; padding: 8px 14px; color: #087443; background: #dff7ea; border-radius: 20px; font-weight: bold; }
        h1 { margin-bottom: 8px; } h3 { color: #365b7d; } .credential { margin-top: 28px; line-height: 1.8; }
        .cert-info { margin-top: 20px; font-size: 14px; color: #4b5563; }
        .cert-info table { margin: 0 auto; text-align: left; border-collapse: collapse; }
        .cert-info td { padding: 4px 12px; }
        .cert-info td.label { font-weight: 600; color: #6b7280; }
    </style>
</head>
<body>
<main class="card">
    <span class="verified">VERIFIED CERTIFICATE</span>

    <h1>{{ $certificate->participant_name }}</h1>
    <h3>{{ $certificate->title }}</h3>
    <p>Issued by {{ $certificate->certificateSetting->issuer_name ?? 'OWL Training Management' }} on {{ optional($certificate->display_issued_date)->format('F j, Y') ?: optional($certificate->issued_at)->format('F j, Y') }}</p>

    <div class="cert-info">
        <table>
            <tr>
                <td class="label">Certificate Available:</td>
                <td><strong>{{ $certificate->certificate_available }}</strong></td>
            </tr>
            <tr>
                <td class="label">Institution:</td>
                <td>{{ $certificate->display_institution }}</td>
            </tr>
            <tr>
                <td class="label">Issued Date:</td>
                <td>{{ $certificate->display_issued_date ? \Carbon\Carbon::parse($certificate->display_issued_date)->format('d-m-Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Expired Date:</td>
                <td>{{ $certificate->display_expired_date ? \Carbon\Carbon::parse($certificate->display_expired_date)->format('d-m-Y') : '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="credential">
        <strong>{{ $certificate->certificate_number }}</strong><br>
        {{ $certificate->credential_id }}
    </div>
</main>
</body>
</html>
