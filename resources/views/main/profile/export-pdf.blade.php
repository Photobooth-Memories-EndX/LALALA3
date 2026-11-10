<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $profile->name }} — Learning History</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 10mm 12mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8px;
            line-height: 1.35;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-container {
            width: 100%;
            border-bottom: 2px solid #003b6d;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .brand-title {
            font-size: 13px;
            font-weight: bold;
            color: #003b6d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .meta-text {
            font-size: 8px;
            color: #64748b;
            text-align: right;
            line-height: 1.4;
        }
        .user-info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
        }
        .user-info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .user-info-table td {
            border: none;
            padding: 2px 4px;
            vertical-align: middle;
        }
        .info-label {
            color: #64748b;
            font-weight: bold;
            width: 85px;
        }
        .info-val {
            color: #1e293b;
            font-weight: 600;
        }
        .filter-badges-container {
            margin-bottom: 8px;
        }
        .filter-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            margin-right: 4px;
            margin-bottom: 3px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.5px;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table thead th {
            background-color: #003b6d;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 5px 6px;
            border: 0.5px solid #00284d;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 7.5px;
        }
        .data-table tbody tr {
            page-break-inside: avoid;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody td {
            padding: 4px 6px;
            border: 0.5px solid #cbd5e1;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .text-center {
            text-align: center !important;
        }
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            font-size: 7px;
            font-weight: bold;
            border-radius: 2px;
            text-align: center;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #15803d;
            border: 0.5px solid #86efac;
        }
        .badge-warning {
            background-color: #fef9c3;
            color: #a16207;
            border: 0.5px solid #fde047;
        }
        .badge-type {
            background-color: #ede9fe;
            color: #6d28d9;
            border: 0.5px solid #ddd6fe;
        }
        .progress-bar-container {
            width: 100%;
            background-color: #e2e8f0;
            border-radius: 2px;
            height: 6px;
            overflow: hidden;
            margin-top: 2px;
        }
        .progress-bar-fill {
            height: 6px;
            background-color: #0284c7;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 12px;
            font-size: 6.5px;
            color: #94a3b8;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 2px;
            display: flex;
            justify-content: space-between;
        }
        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>
<body>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="brand-title">PT KRAKATAU POSCO &bull; OWL LEARNING HUB</div>
                    <div class="report-title">USER LEARNING PROFILE &mdash; LEARNING HISTORY</div>
                </td>
                <td style="width: 35%;" class="meta-text">
                    <div><strong>Generated:</strong> {{ now()->format('d M Y, H:i') }} WIB</div>
                    <div><strong>Total Records:</strong> {{ number_format($records->count()) }} items</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- User Profile Context Box -->
    <div class="user-info-box">
        <table class="user-info-table">
            <tr>
                <td class="info-label">Employee:</td>
                <td class="info-val" style="width: 35%;">{{ $profile->name }}</td>
                <td class="info-label">NIP / ID:</td>
                <td class="info-val">{{ $profile->id_emp ?? '-' }}</td>
            </tr>
            <tr>
                <td class="info-label">Department:</td>
                <td class="info-val">{{ $profile->department ?? '-' }}</td>
                <td class="info-label">Division / Team:</td>
                <td class="info-val">{{ $profile->division ?? '-' }} / {{ $profile->team ?? '-' }}</td>
            </tr>
        </table>
    </div>

    @if (!empty($activeFilters))
        <div class="filter-badges-container">
            <span style="font-size: 7.5px; font-weight: bold; color: #475569; margin-right: 4px;">Active Filters:</span>
            @foreach ($activeFilters as $name => $val)
                <span class="filter-badge">{{ $name }}: {{ $val }}</span>
            @endforeach
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 3.5%;">NO</th>
                <th class="text-center" style="width: 7.5%;">SOURCE</th>
                <th class="text-center" style="width: 6.5%;">STATUS</th>
                <th style="width: 13%;">EMPLOYEE</th>
                <th style="width: 25%;">LEARNING TITLE</th>
                <th class="text-center" style="width: 6.5%;">TYPE</th>
                <th style="width: 14%;">CLASSIFICATION</th>
                <th class="text-center" style="width: 7.5%;">DURATION</th>
                <th class="text-center" style="width: 8%;">DATE</th>
                <th style="width: 8.5%;">PROGRESS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $index => $row)
                @php
                    $isFinished = ($row->status ?? '') === 'Finished' || (int) ($row->finish ?? 0) === 1 || (float) ($row->progress ?? 0) >= 100;
                    $progress = (int) round((float) ($row->progress ?? 0));
                    $title = $row->learning_title ?? $row->content?->title ?? '-';
                    $type = $row->type ?? $row->content?->type ?? '-';
                    $classification = $row->classification ?? $row->content?->classification?->classification_name ?? '-';
                    $empName = $row->employee_name ?? $row->user?->name ?? $profile->name ?? '-';
                    $source = ($row->source ?? '') === 'online' ? 'KP OWL' : (($row->source ?? '') === 'offline' ? 'Non KP OWL' : '-');

                    $durSec = (int) ($row->duration_seconds ?? 0);
                    $durationFormatted = $durSec > 0 ? \App\Support\DurationFormatter::fromSeconds($durSec) : \App\Support\DurationFormatter::format($row->content?->duration ?? null);

                    $startDate = !empty($row->start_date) && $row->start_date !== '-' ? date('d M Y', strtotime($row->start_date)) : null;
                    $endDate = !empty($row->end_date) && $row->end_date !== '-' ? date('d M Y', strtotime($row->end_date)) : (!empty($row->finish_date) ? date('d M Y', strtotime($row->finish_date)) : null);
                    if ($startDate && $endDate && $startDate !== $endDate) {
                        $dateFormatted = $startDate . ' – ' . $endDate;
                    } elseif ($startDate || $endDate) {
                        $dateFormatted = $startDate ?: $endDate;
                    } else {
                        $dateFormatted = !empty($row->created_at) ? date('d M Y', strtotime($row->created_at)) : '-';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">
                        <span class="badge {{ ($row->source ?? '') === 'online' ? 'badge-type' : 'badge-warning' }}">{{ $source }}</span>
                    </td>
                    <td class="text-center">
                        @if ($isFinished)
                            <span class="badge badge-success">Finished</span>
                        @else
                            <span class="badge badge-warning">Ongoing</span>
                        @endif
                    </td>
                    <td>{{ $empName }}</td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a;">{{ $title }}</div>
                        <div style="font-size: 6.5px; color: #64748b;">{{ $type }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-type">{{ $type }}</span>
                    </td>
                    <td>{{ $classification }}</td>
                    <td class="text-center">{{ $durationFormatted }}</td>
                    <td class="text-center">{{ $dateFormatted }}</td>
                    <td>
                        <div style="font-weight: 600; font-size: 7px;">{{ $progress }}%</div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: {{ $progress }}%;"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">
                        No learning records found matching the active filter criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Confidential &mdash; PT Krakatau Posco Internal Learning Records</span>
        <span>Page <span class="pagenum"></span></span>
    </div>

</body>
</html>
