<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $contextTitle ?? 'KP OWL — Online Learning Record' }}</title>
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
            font-size: 7.5px;
            line-height: 1.35;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-container {
            width: 100%;
            border-bottom: 2px solid #003b6d;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
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
        }
        .meta-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #0f172a;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 3px;
            margin-top: 2px;
        }
        .filter-badges-container {
            margin-top: 6px;
            margin-bottom: 6px;
        }
        .filter-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 7px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 3px;
            margin-right: 4px;
            margin-bottom: 3px;
        }
        .limit-notice {
            background-color: #fef3c7;
            border-left: 3px solid #d97706;
            color: #92400e;
            padding: 4px 8px;
            font-size: 7.5px;
            margin-bottom: 8px;
            border-radius: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7px;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table thead th {
            background-color: #003b6d;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 5px 4px;
            border: 1px solid #002b50;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table tbody td {
            border: 1px solid #cbd5e1;
            padding: 4px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody tr {
            page-break-inside: avoid;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 6.5px;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .badge-primary { background: #dbeafe; color: #1d4ed8; }
        .badge-info { background: #e0f2fe; color: #0284c7; }
        .footer-table {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            font-size: 7.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">PT Krakatau Posco &bull; OWL LMS</div>
                    <div class="report-title">{{ $contextTitle ?? 'KP OWL — Online Learning Record' }}</div>
                </td>
                <td class="meta-text">
                    <div>Generated: <strong>{{ $generatedAt ?? date('d M Y H:i:s') }}</strong></div>
                    <div class="meta-badge">Total Records: {{ number_format($totalRecords) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $activeFilters = [];
        if (!empty($classificationName)) {
            $activeFilters[] = 'Classification: ' . $classificationName;
        } elseif (!empty($filters['classification_slug'])) {
            $activeFilters[] = 'Classification: ' . $filters['classification_slug'];
        }
        if (!empty($filters['content_slug'])) {
            $activeFilters[] = 'Content: ' . $filters['content_slug'];
        }
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            $activeFilters[] = 'Year: ' . $filters['year'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $activeFilters[] = 'Status: ' . ucfirst($filters['status']);
        }
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $activeFilters[] = 'Type: ' . ucfirst($filters['type']);
        }
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $activeFilters[] = 'Category: ' . ($filters['category'] === 'all_content' ? 'All Content' : ucfirst($filters['category']));
        }
        if (!empty($filters['employee_id']) && $filters['employee_id'] !== 'all') {
            $activeFilters[] = 'Employee: ' . $filters['employee_id'];
        }
        if (!empty($filters['learning_title'])) {
            $activeFilters[] = 'Title: ' . $filters['learning_title'];
        }
        if (!empty($filters['program_type']) && $filters['program_type'] !== 'all') {
            $activeFilters[] = 'Program: ' . ucfirst($filters['program_type']);
        }
        if (!empty($filters['progress_range']) && $filters['progress_range'] !== 'all') {
            $activeFilters[] = 'Progress: ' . $filters['progress_range'] . '%';
        }
        if (!empty($filters['duration']) && $filters['duration'] !== 'all') {
            $activeFilters[] = 'Duration: ' . $filters['duration'];
        }
        if (!empty($filters['from_date'])) {
            $activeFilters[] = 'From: ' . $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $activeFilters[] = 'To: ' . $filters['to_date'];
        }
    @endphp

    @if(!empty($activeFilters))
        <div class="filter-badges-container">
            <span style="font-size: 7.5px; font-weight: bold; color: #475569;">Active Filters:</span>
            @foreach($activeFilters as $f)
                <span class="filter-badge">{{ $f }}</span>
            @endforeach
        </div>
    @endif

    @if(isset($limit) && $totalRecords > $limit)
        <div class="limit-notice">
            <strong>Note:</strong> Displaying top {{ number_format($records->count()) }} records out of {{ number_format($totalRecords) }} records for PDF readability and performance. For the full dataset, please use Excel (.xlsx) or CSV (.csv) export.
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;" class="text-center">#</th>
                <th style="width: 7%;" class="text-center">Status</th>
                <th style="width: 7%;">ID/NIP</th>
                <th style="width: 12%;">Employee Name</th>
                <th style="width: 12%;">Dept / Team</th>
                <th style="width: 23%;">Learning Title</th>
                <th style="width: 6%;" class="text-center">Type</th>
                <th style="width: 11%;">Classification</th>
                <th style="width: 7%;">Category</th>
                <th style="width: 6%;" class="text-center">Duration</th>
                <th style="width: 6%;" class="text-center">Progress</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $row)
            @php
                $progress = (float) ($row->progress ?? 0);
                $isFinished = (bool) ($row->finish ?? false) || $progress >= 100;
                $statusText = $isFinished ? 'Finished' : ($progress > 0 ? 'Ongoing' : 'Not Started');
                $statusClass = $isFinished ? 'badge-success' : ($progress > 0 ? 'badge-warning' : 'badge-secondary');

                $title = $row->content_title ?? '-';
                if (!empty($row->playlist_title)) {
                    $title .= ' (Playlist: ' . $row->playlist_title . ')';
                }

                $rawType = (string) ($row->content_type ?? '');
                $type = in_array(strtolower($rawType), ['video', 'youtube', 'mooc'], true) ? 'Video' : 'Document';

                $deptTeam = '-';
                if (!empty($row->user_department) || !empty($row->user_team)) {
                    $parts = array_filter([$row->user_department ?? '', $row->user_team ?? '']);
                    $deptTeam = implode(' / ', $parts);
                }
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
                <td>{{ $row->user_id_emp ?? ($row->id_emp ?? '-') }}</td>
                <td><strong>{{ $row->user_name ?? ($row->employee_name ?? '-') }}</strong></td>
                <td>{{ $deptTeam }}</td>
                <td>{{ $title }}</td>
                <td class="text-center">
                    <span class="badge {{ $type === 'Video' ? 'badge-info' : 'badge-primary' }}">{{ $type }}</span>
                </td>
                <td>{{ $row->classification_name ?? '-' }}</td>
                <td>{{ $row->category_source ?? 'All Content' }}</td>
                <td class="text-center">{{ \App\Support\DurationFormatter::format($row->content_duration ?? null) }}</td>
                <td class="text-center">
                    <strong style="color: {{ $isFinished ? '#15803d' : '#b45309' }}">{{ round($progress) }}%</strong>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 15px; color: #64748b;">
                    No online learning records found matching the active filters.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td style="border: none; text-align: left;">
                PT Krakatau Posco &bull; OWL LMS &bull; {{ $contextTitle ?? 'KP OWL Online Learning Record' }}
            </td>
            <td style="border: none; text-align: right;">
                Printed on {{ $generatedAt ?? date('d M Y H:i:s') }}
            </td>
        </tr>
    </table>
</body>
</html>
