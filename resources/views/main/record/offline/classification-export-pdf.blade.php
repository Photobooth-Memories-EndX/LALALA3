<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offline Training Record — Classification: {{ $classificationName }}</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 8mm 8mm 12mm 8mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 7px;
            line-height: 1.25;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-container {
            width: 100%;
            border-bottom: 2px solid #003b6d;
            padding-bottom: 5px;
            margin-bottom: 6px;
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
            font-size: 11px;
            font-weight: bold;
            color: #003b6d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .meta-text {
            font-size: 7.5px;
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

        /* FILTER INFORMATION BAR */
        .filter-info-box {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 4px 8px;
            margin-bottom: 6px;
            font-size: 7px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 12px;
            color: #475569;
        }
        .filter-item strong {
            color: #0f172a;
        }
        .filter-chip {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: 600;
            font-size: 6.5px;
        }

        /* DATA TABLE */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5px;
        }
        .data-table thead {
            display: table-header-group;
        }
        .data-table thead th {
            background-color: #003b6d;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            padding: 4px 2px;
            border: 1px solid #002b50;
            vertical-align: middle;
            text-align: center;
            font-size: 6.2px;
            white-space: nowrap;
        }
        .data-table tbody td {
            border: 1px solid #cbd5e1;
            padding: 3px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody tr {
            page-break-inside: avoid;
        }

        /* ALIGNMENTS & UTILITIES */
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .nowrap { white-space: nowrap; }

        /* BADGES */
        .badge {
            display: inline-block;
            padding: 1px 4px;
            font-size: 6px;
            font-weight: bold;
            border-radius: 2px;
            text-align: center;
            white-space: nowrap;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger  { background-color: #fee2e2; color: #b91c1c; }
        .badge-secondary { background-color: #f1f5f9; color: #475569; }
        .badge-primary { background-color: #e0f2fe; color: #0369a1; }

        .footer-note {
            margin-top: 6px;
            font-size: 6.5px;
            color: #94a3b8;
            text-align: left;
        }
    </style>
</head>
<body>

    <!-- REPORT HEADER -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="brand-title">PT Krakatau Posco &bull; OWL Learning Hub &bull; Offline Training Record</div>
                    <div class="report-title">Classification: {{ $classificationName }}</div>
                </td>
                <td style="width: 35%;" class="meta-text">
                    <div>Generated: <strong>{{ $generatedAt }}</strong></div>
                    <div class="meta-badge">Total Filtered Records: {{ number_format($totalRecords) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ACTIVE FILTER SUMMARY -->
    @php
        $activeFilterItems = [];
        if (!empty($filters['employee_id']) || !empty($filters['employee'])) {
            $activeFilterItems[] = 'Employee/NIP: "' . ($filters['employee_id'] ?? $filters['employee']) . '"';
        }
        if (!empty($filters['course_name']) || !empty($filters['learning_title']) || !empty($filters['title'])) {
            $activeFilterItems[] = 'Learning Title: "' . ($filters['course_name'] ?? ($filters['learning_title'] ?? $filters['title'])) . '"';
        }
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $activeFilterItems[] = 'Type: ' . $filters['type'];
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $activeFilterItems[] = 'Status: ' . ucfirst(str_replace('_', ' ', $filters['status']));
        }
        if (!empty($filters['year']) && $filters['year'] !== 'all') {
            $activeFilterItems[] = 'Year: ' . $filters['year'];
        }
        $fromDate = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDate = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        if ($fromDate || $toDate) {
            $activeFilterItems[] = 'Date: ' . ($fromDate ?: 'Start') . ' to ' . ($toDate ?: 'End');
        }
        if (!empty($filters['progress_range']) && $filters['progress_range'] !== 'all') {
            $activeFilterItems[] = 'Progress: ' . $filters['progress_range'] . '%';
        }
        if (!empty($filters['duration']) && $filters['duration'] !== 'all') {
            $activeFilterItems[] = 'Duration: ' . $filters['duration'];
        }
        if (!empty($filters['attendance']) && $filters['attendance'] !== 'all') {
            $activeFilterItems[] = 'Attendance: ' . ucfirst($filters['attendance']);
        }
        if (isset($filters['score_min']) && $filters['score_min'] !== '') {
            $activeFilterItems[] = 'Min Score: ' . $filters['score_min'];
        }
        if (isset($filters['score_max']) && $filters['score_max'] !== '') {
            $activeFilterItems[] = 'Max Score: ' . $filters['score_max'];
        }
        if (isset($filters['hours_min']) && $filters['hours_min'] !== '') {
            $activeFilterItems[] = 'Min Hours: ' . $filters['hours_min'];
        }
        if (isset($filters['hours_max']) && $filters['hours_max'] !== '') {
            $activeFilterItems[] = 'Max Hours: ' . $filters['hours_max'];
        }
    @endphp
    <div class="filter-info-box">
        <span class="filter-item"><strong>Classification:</strong> <span class="filter-chip">{{ $classificationName }}</span></span>
        <span class="filter-item"><strong>Active Filters:</strong> 
            @if(count($activeFilterItems) > 0)
                {{ implode(' | ', $activeFilterItems) }}
            @else
                <span style="color: #64748b;">All records under this classification (no additional filters applied)</span>
            @endif
        </span>
    </div>

    <!-- COMPLETE CANONICAL 46-COLUMN TABLE (A3 LANDSCAPE) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 1.5%;">NO</th>
                <th style="width: 2.2%;">STATUS</th>
                <th style="width: 2.3%;">NIP</th>
                <th style="width: 3.5%;">NAME</th>
                <th style="width: 1.5%;">GRADE</th>
                <th style="width: 3.2%;">JOB POSITION</th>
                <th style="width: 2.5%;">TEAM</th>
                <th style="width: 2.7%;">DEPARTMENT</th>
                <th style="width: 2.2%;">COMPANY</th>
                <th style="width: 3.8%;">COURSE NAME</th>
                <th style="width: 2.8%;">SUB COURSE</th>
                <th style="width: 1.8%;">TYPE</th>
                <th style="width: 2.2%;">CATEGORY</th>
                <th style="width: 1.6%;">LEVEL</th>
                <th style="width: 1.6%;">BATCH</th>
                <th style="width: 2.8%;">MAIN CLASSIFICATION</th>
                <th style="width: 2.8%;">SUB CLASSIFICATION</th>
                <th style="width: 1.8%;">CORP KPI</th>
                <th style="width: 2.0%;">SUB CORP KPI</th>
                <th style="width: 2.0%;">DELIVERY</th>
                <th style="width: 2.0%;">SUB DELIVERY</th>
                <th style="width: 1.8%;">PROVIDER</th>
                <th style="width: 2.0%;">SUB PROVIDER</th>
                <th style="width: 2.5%;">PROVIDER NAME</th>
                <th style="width: 2.2%;">VENUE</th>
                <th style="width: 2.2%;">INSTRUCTOR</th>
                <th style="width: 1.8%;">DURATION</th>
                <th style="width: 1.6%;">TR. HRS</th>
                <th style="width: 2.0%;">START DATE</th>
                <th style="width: 2.0%;">FINISH DATE</th>
                <th style="width: 1.8%;">MONTH</th>
                <th style="width: 1.6%;">PART. HRS</th>
                <th style="width: 1.8%;">PROGRESS</th>
                <th style="width: 1.6%;">SCORE</th>
                <th style="width: 1.6%;">CERT</th>
                <th style="width: 2.0%;">ATTENDANCE</th>
                <th style="width: 2.2%;">REASON</th>
                <th style="width: 1.6%;">BOND</th>
                <th style="width: 2.0%;">BOND END</th>
                <th style="width: 2.0%;">BOND AMOUNT</th>
                <th style="width: 1.8%;">L1</th>
                <th style="width: 1.6%;">L2 NUM</th>
                <th style="width: 1.8%;">L2 VERB</th>
                <th style="width: 1.6%;">L3 NUM</th>
                <th style="width: 1.8%;">L3 VERB</th>
                <th style="width: 2.0%;">PIC</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $index => $row)
                @php
                    $progress = (float) ($row->progress ?? 0);
                    $statusText = $progress >= 100 ? 'Finished' : ($progress > 0 ? 'Ongoing' : 'Not Started');
                    $statusClass = $progress >= 100 ? 'badge-success' : ($progress > 0 ? 'badge-warning' : 'badge-secondary');

                    // Duration formatted as HH:MM:SS
                    $durationFormatted = '-';
                    if (!empty($row->duration_seconds)) {
                        $secs = (int) $row->duration_seconds;
                        $hours = floor($secs / 3600);
                        $mins = floor(($secs % 3600) / 60);
                        $seconds = $secs % 60;
                        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                    }

                    // Month derivation
                    $month = '-';
                    $dateForMonth = $row->start_date ?: $row->finish_date;
                    if (!empty($dateForMonth)) {
                        try {
                            $month = \Carbon\Carbon::parse($dateForMonth)->format('F');
                        } catch (\Throwable $e) {
                            $month = '-';
                        }
                    }

                    // Dates
                    $startDateStr = $row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '-';
                    $finishDateStr = $row->finish_date ? date('Y-m-d', strtotime($row->finish_date)) : '-';
                    $bondEndDateStr = $row->bond_end_date ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';

                    // Corporate KPI
                    $kpiStr = '-';
                    if ($row->counted_corporate_kpi !== null) {
                        if ((bool) $row->counted_corporate_kpi) {
                            $year = !empty($row->corporate_kpi_year)
                                ? $row->corporate_kpi_year
                                : (!empty($row->sub_corporate_kpi) && preg_match('/^\d{4}$/', (string) $row->sub_corporate_kpi) ? $row->sub_corporate_kpi : null);
                            $kpiStr = $year ? "Yes ({$year})" : "Yes";
                        } else {
                            $kpiStr = 'No';
                        }
                    }

                    // Attendance
                    $attendanceClass = 'badge-secondary';
                    $attendanceText = '-';
                    if ($row->attendance !== null) {
                        if ((bool) $row->attendance) {
                            $attendanceClass = 'badge-success';
                            $attendanceText = 'Present';
                        } else {
                            $attendanceClass = 'badge-danger';
                            $attendanceText = 'Absent';
                        }
                    }

                    $participantName = $row->participant_name ?: ($row->user_name ?: '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center"><span class="badge {{ $statusClass }}">{{ $statusText }}</span></td>
                    <td class="text-center nowrap">{{ $row->employee_id ?: '-' }}</td>
                    <td class="text-start"><strong>{{ $participantName }}</strong></td>
                    <td class="text-center">{{ $row->grade ?: '-' }}</td>
                    <td class="text-start">{{ $row->job_position ?: '-' }}</td>
                    <td class="text-start">{{ $row->team ?: '-' }}</td>
                    <td class="text-start">{{ $row->department ?: '-' }}</td>
                    <td class="text-start">{{ $row->company ?: '-' }}</td>
                    <td class="text-start"><strong>{{ $row->course_name ?: '-' }}</strong></td>
                    <td class="text-start">{{ $row->sub_course_name ?: '-' }}</td>
                    <td class="text-center">{{ $row->type ?: '-' }}</td>
                    <td class="text-start">{{ $row->category ?: '-' }}</td>
                    <td class="text-center">{{ $row->level ?: '-' }}</td>
                    <td class="text-center">{{ $row->batch ?: '-' }}</td>
                    <td class="text-start">{{ $row->main_classification ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_classification ?: '-' }}</td>
                    <td class="text-center">{{ $kpiStr }}</td>
                    <td class="text-start">{{ $row->sub_corporate_kpi ?: '-' }}</td>
                    <td class="text-center">{{ $row->delivery_method ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_delivery_method ?: '-' }}</td>
                    <td class="text-center">{{ $row->provider ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_provider ?: '-' }}</td>
                    <td class="text-start">{{ $row->provider_name ?: '-' }}</td>
                    <td class="text-start">{{ $row->venue ?: '-' }}</td>
                    <td class="text-start">{{ $row->instructor ?: '-' }}</td>
                    <td class="text-center">{{ $durationFormatted }}</td>
                    <td class="text-center">{{ $row->course_learning_hours !== null && $row->course_learning_hours !== '' ? number_format((float)$row->course_learning_hours, 1) . 'h' : '-' }}</td>
                    <td class="text-center nowrap">{{ $startDateStr }}</td>
                    <td class="text-center nowrap">{{ $finishDateStr }}</td>
                    <td class="text-center">{{ $month }}</td>
                    <td class="text-center">{{ $row->learning_hours !== null && $row->learning_hours !== '' ? number_format((float)$row->learning_hours, 1) . 'h' : '-' }}</td>
                    <td class="text-center"><span class="badge {{ $statusClass }}">{{ round($progress) }}%</span></td>
                    <td class="text-center">{{ $row->assessment_score !== null ? number_format((float)$row->assessment_score, 1) : '-' }}</td>
                    <td class="text-center">{{ !empty($row->certificate) ? 'Yes' : '-' }}</td>
                    <td class="text-center"><span class="badge {{ $attendanceClass }}">{{ $attendanceText }}</span></td>
                    <td class="text-start">{{ $row->attendance_reason ?: '-' }}</td>
                    <td class="text-center">{{ $row->bond_status ?: 'No' }}</td>
                    <td class="text-center nowrap">{{ $bondEndDateStr }}</td>
                    <td class="text-end">{{ $row->bond_amount !== null && $row->bond_amount !== '' ? number_format((float)$row->bond_amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-center">{{ $row->l1_program ?: '-' }}</td>
                    <td class="text-center">{{ $row->l2_numerical !== null ? number_format((float)$row->l2_numerical, 1) : '-' }}</td>
                    <td class="text-start">{{ $row->l2_verbal ?: '-' }}</td>
                    <td class="text-center">{{ $row->l3_numerical !== null ? number_format((float)$row->l3_numerical, 1) : '-' }}</td>
                    <td class="text-start">{{ $row->l3_verbal ?: '-' }}</td>
                    <td class="text-start">{{ $row->pic ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="46" class="text-center" style="padding: 25px; color: #64748b; font-size: 8.5px;">
                        No offline training records found matching the active filter criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        PT Krakatau Posco &bull; OWL LMS &bull; Strictly Confidential &bull; Offline Training Record: {{ $classificationName }} &bull; Total records: {{ $records->count() }}
    </div>

    <!-- DOMPDF PAGE NUMBERING SCRIPT -->
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont("Helvetica", "normal");
            $size = 6.5;
            $pageText = "Page " . $PAGE_NUM . " of " . $PAGE_COUNT;
            $width = $fontMetrics->getTextWidth($pageText, $font, $size);
            $x = $pdf->get_width() - $width - 24;
            $y = $pdf->get_height() - 18;
            $pdf->text($x, $y, $pageText, $font, $size, [0.4, 0.45, 0.5]);
        }
    </script>

</body>
</html>
