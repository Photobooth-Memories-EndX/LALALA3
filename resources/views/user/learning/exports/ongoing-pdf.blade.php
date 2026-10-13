<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Learning Ongoing — {{ $mode }}</title>
    <style>
        @page { margin: 22px; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { margin: 0 0 4px; font-size: 16px; }
        .meta { margin-bottom: 12px; color: #64748b; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 5px 4px; border: 1px solid #cbd5e1; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #d9eaf7; font-weight: bold; }
        th:nth-child(1), td:nth-child(1) { width: 3%; text-align: center; }
        th:nth-child(2), td:nth-child(2) { width: 20%; }
        th:nth-child(3), td:nth-child(3) { width: 8%; }
        th:nth-child(4), td:nth-child(4) { width: 7%; }
        th:nth-child(5), td:nth-child(5) { width: 11%; }
        th:nth-child(6), td:nth-child(6) { width: 7%; }
        th:nth-child(7), td:nth-child(7) { width: 7%; }
        th:nth-child(8), td:nth-child(8) { width: 8%; }
        th:nth-child(9), td:nth-child(9), th:nth-child(10), td:nth-child(10) { width: 9%; }
        th:nth-child(11), td:nth-child(11) { width: 6%; }
        th:nth-child(12), td:nth-child(12) { width: 7%; }
        th:nth-child(13), td:nth-child(13) { width: 8%; }
        tr.total td { font-weight: bold; background: #f1f5f9; }
        tr.blank td { height: 5px; border: 0; padding: 0; }
    </style>
</head>
<body>
    <h1>My Learning Ongoing — {{ $mode }}</h1>
    <div class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>
    <table>
        <thead><tr>@foreach ($rows[0] ?? [] as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach (array_slice($rows, 1) as $row)
                @php
                    $isBlank = count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0;
                    $isTotal = ($row[0] ?? null) === 'TOTAL';
                @endphp
                <tr class="{{ $isBlank ? 'blank' : ($isTotal ? 'total' : '') }}">
                    @foreach ($row as $value)<td>{{ $value }}</td>@endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
