<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NON KP OWL — Course Record: {{ $training->course_name }}</title>
    <style>
        @page {
            size: A3 landscape;
            margin: 10mm 10mm 12mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 7.5px;
            line-height: 1.3;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-container {
            width: 100%;
            border-bottom: 2px solid #003b6d;
            padding-bottom: 6px;
            margin-bottom: 8px;
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
        .course-info-container {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 8px;
        }
        .course-info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .course-info-table td {
            border: none;
            padding: 2px 6px;
            vertical-align: top;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
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
            text-align: center;
            white-space: nowrap;
        }
        .data-table tbody td {
            border: 1px solid #cbd5e1;
            padding: 4px;
            vertical-align: middle;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 6.5px;
            font-weight: bold;
            border-radius: 2px;
            text-align: center;
        }
        .badge-success { background-color: #dcfce7; color: #15803d; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        .badge-danger  { background-color: #fee2e2; color: #b91c1c; }
        .badge-secondary { background-color: #f1f5f9; color: #475569; }
        .badge-primary { background-color: #e0f2fe; color: #0369a1; }
        .footer-note {
            margin-top: 8px;
            font-size: 7px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- REPORT HEADER -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td style="width: 60%;">
                    <div class="brand-title">OWL LMS &bull; Manage Record &bull; Non KP OWL</div>
                    <div class="report-title">Course Training Record</div>
                    <div style="font-size: 8.5px; color: #475569; margin-top: 2px;">
                        <strong>Course:</strong> {{ $training->course_name ?: '-' }}
                        @if (!empty($training->sub_course_name))
                            &bull; <strong>Sub Course:</strong> {{ $training->sub_course_name }}
                        @endif
                        @if (!empty($training->batch))
                            &bull; <strong>Batch:</strong> {{ $training->batch }}
                        @endif
                    </div>
                </td>
                <td style="width: 40%;" class="meta-text">
                    <div>Generated: <strong>{{ $generatedAt }}</strong></div>
                    <div class="meta-badge">Total Filtered Records: {{ $totalRecords }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- COURSE INFO & METRICS CARD -->
    <div class="course-info-container">
        <table class="course-info-table">
            <tr>
                <td style="width: 25%;">
                    <span style="color: #64748b;">Course Name:</span> <strong>{{ $training->course_name ?: '-' }}</strong><br>
                    <span style="color: #64748b;">Sub Course:</span> <strong>{{ $training->sub_course_name ?: '-' }}</strong><br>
                    <span style="color: #64748b;">Type:</span> <strong>{{ $training->type ?: '-' }}</strong> &bull; <span style="color: #64748b;">Batch:</span> <strong>{{ $training->batch ?: '-' }}</strong>
                </td>
                <td style="width: 25%;">
                    <span style="color: #64748b;">Classification:</span> <strong>{{ $training->main_classification ?: '-' }}</strong><br>
                    <span style="color: #64748b;">Category / Level:</span> <strong>{{ $training->category ?: '-' }} @if(!empty($training->level))({{ $training->level }})@endif</strong><br>
                    <span style="color: #64748b;">Provider:</span> <strong>{{ $training->provider_name ?: ($training->provider ?: '-') }}</strong>
                </td>
                <td style="width: 25%;">
                    <span style="color: #64748b;">Delivery Method:</span> <strong>{{ $training->delivery_method ?: '-' }}</strong><br>
                    <span style="color: #64748b;">Schedule:</span> 
                    <strong>
                        {{ $training->start_date ? \Carbon\Carbon::parse($training->start_date)->format('d M Y') : '-' }}
                        @if ($training->finish_date && $training->finish_date != $training->start_date)
                            — {{ \Carbon\Carbon::parse($training->finish_date)->format('d M Y') }}
                        @endif
                    </strong><br>
                    <span style="color: #64748b;">Venue:</span> <strong>{{ $training->venue ?: '-' }}</strong>
                </td>
                <td style="width: 25%;">
                    <span style="color: #64748b;">Active Filter:</span>
                    <strong>
                        @php
                            $activeFilters = [];
                            if (!empty($filters['participant'])) {
                                $activeFilters[] = 'Search: ' . $filters['participant'];
                            }
                            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                                $activeFilters[] = 'Status: ' . ucfirst(str_replace('_', ' ', $filters['status']));
                            }
                        @endphp
                        {{ !empty($activeFilters) ? implode(', ', $activeFilters) : 'All Participants' }}
                    </strong><br>
                    <span style="color: #64748b;">Training Hours:</span> <strong>{{ $training->learning_hours ? number_format((float)$training->learning_hours, 1) . 'h' : '-' }}</strong><br>
                    <span style="color: #64748b;">Duration:</span> 
                    <strong>
                        @if (!empty($training->duration_seconds))
                            {{ sprintf('%02d:%02d:%02d', floor($training->duration_seconds/3600), floor(($training->duration_seconds%3600)/60), $training->duration_seconds%60) }}
                        @else
                            -
                        @endif
                    </strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- FULL RAW CANONICAL RECORD TABLE (44 COLUMNS) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 2%;">NO</th>
                <th style="width: 3%;">ID</th>
                <th style="width: 4%;">NAME</th>
                <th style="width: 2%;">GRADE</th>
                <th style="width: 3%;">JOB POSITION</th>
                <th style="width: 3%;">TEAM</th>
                <th style="width: 3%;">DEPARTMENT</th>
                <th style="width: 3%;">COMPANY</th>
                <th style="width: 4%;">COURSE NAME</th>
                <th style="width: 3%;">SUB COURSE</th>
                <th style="width: 2%;">TYPE</th>
                <th style="width: 2%;">CATEGORY</th>
                <th style="width: 2%;">LEVEL</th>
                <th style="width: 2%;">BATCH</th>
                <th style="width: 3%;">CLASSIFICATION</th>
                <th style="width: 3%;">SUB CLASSIFICATION</th>
                <th style="width: 2%;">CORP KPI</th>
                <th style="width: 2%;">SUB CORP KPI</th>
                <th style="width: 2%;">DELIVERY</th>
                <th style="width: 2%;">SUB DELIVERY</th>
                <th style="width: 2%;">PROVIDER</th>
                <th style="width: 2%;">SUB PROVIDER</th>
                <th style="width: 2%;">DURATION</th>
                <th style="width: 2%;">HRS</th>
                <th style="width: 2%;">PROGRESS</th>
                <th style="width: 2%;">START</th>
                <th style="width: 2%;">FINISH</th>
                <th style="width: 2%;">MONTH</th>
                <th style="width: 2%;">PROVIDER NAME</th>
                <th style="width: 2%;">VENUE</th>
                <th style="width: 2%;">INSTRUCTOR</th>
                <th style="width: 2%;">SCORE</th>
                <th style="width: 2%;">CERTIFICATE</th>
                <th style="width: 2%;">ATTENDANCE</th>
                <th style="width: 2%;">REASON</th>
                <th style="width: 2%;">BOND</th>
                <th style="width: 2%;">BOND END</th>
                <th style="width: 2%;">BOND AMOUNT</th>
                <th style="width: 2%;">L1</th>
                <th style="width: 2%;">L2 NUM</th>
                <th style="width: 2%;">L2 VERB</th>
                <th style="width: 2%;">L3 NUM</th>
                <th style="width: 2%;">L3 VERB</th>
                <th style="width: 2%;">PIC</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $index => $row)
                @php
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
                    $finishDateStr = $row->finish_date ? date('Y-m-d', strtotime($row->finish_date)) : ($row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '-');
                    $bondEndDateStr = $row->bond_end_date ? date('Y-m-d', strtotime($row->bond_end_date)) : '-';

                    // Badges
                    $progress = (float) ($row->progress ?? 0);
                    $progressBadgeClass = $progress >= 100 ? 'badge-success' : ($progress > 0 ? 'badge-warning' : 'badge-secondary');
                    
                    $attendanceText = '-';
                    $attendanceClass = 'badge-secondary';
                    if ($row->attendance === 1 || $row->attendance === true || $row->attendance === '1') {
                        $attendanceText = 'Yes';
                        $attendanceClass = 'badge-success';
                    } elseif ($row->attendance === 0 || $row->attendance === false || $row->attendance === '0') {
                        $attendanceText = 'No';
                        $attendanceClass = 'badge-danger';
                    }

                    $kpiText = $row->counted_corporate_kpi ? 'Yes' : 'No';
                    $kpiClass = $row->counted_corporate_kpi ? 'badge-primary' : 'badge-secondary';

                    $name = $row->participant_name ?: ($row->user_name ?: '-');
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row->employee_id ?: '-' }}</td>
                    <td class="text-start fw-bold">{{ $name }}</td>
                    <td class="text-center">{{ $row->grade ?: '-' }}</td>
                    <td class="text-start">{{ $row->job_position ?: '-' }}</td>
                    <td class="text-start">{{ $row->team ?: '-' }}</td>
                    <td class="text-start">{{ $row->department ?: '-' }}</td>
                    <td class="text-start">{{ $row->company ?: '-' }}</td>
                    <td class="text-start fw-bold">{{ $row->course_name ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_course_name ?: '-' }}</td>
                    <td class="text-center">{{ $row->type ?: '-' }}</td>
                    <td class="text-start">{{ $row->category ?: '-' }}</td>
                    <td class="text-center">{{ $row->level ?: '-' }}</td>
                    <td class="text-center">{{ $row->batch ?: '-' }}</td>
                    <td class="text-start">{{ $row->main_classification ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_classification ?: '-' }}</td>
                    <td class="text-center"><span class="badge {{ $kpiClass }}">{{ $kpiText }}</span></td>
                    <td class="text-start">{{ $row->sub_corporate_kpi ?: '-' }}</td>
                    <td class="text-center">{{ $row->delivery_method ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_delivery_method ?: '-' }}</td>
                    <td class="text-center">{{ $row->provider ?: '-' }}</td>
                    <td class="text-start">{{ $row->sub_provider ?: '-' }}</td>
                    <td class="text-center">{{ $durationFormatted }}</td>
                    <td class="text-center">{{ $row->course_learning_hours !== null && $row->course_learning_hours !== '' ? number_format((float)$row->course_learning_hours, 1) . 'h' : ($row->learning_hours !== null ? number_format((float)$row->learning_hours, 1) . 'h' : '-') }}</td>
                    <td class="text-center"><span class="badge {{ $progressBadgeClass }}">{{ round($progress) }}%</span></td>
                    <td class="text-center">{{ $startDateStr }}</td>
                    <td class="text-center">{{ $finishDateStr }}</td>
                    <td class="text-center">{{ $month }}</td>
                    <td class="text-start">{{ $row->provider_name ?: '-' }}</td>
                    <td class="text-start">{{ $row->venue ?: '-' }}</td>
                    <td class="text-start">{{ $row->instructor ?: '-' }}</td>
                    <td class="text-center">{{ $row->assessment_score !== null ? number_format((float)$row->assessment_score, 1) : '-' }}</td>
                    <td class="text-center">{{ !empty($row->certificate) ? 'Yes' : '-' }}</td>
                    <td class="text-center"><span class="badge {{ $attendanceClass }}">{{ $attendanceText }}</span></td>
                    <td class="text-start">{{ $row->attendance_reason ?: '-' }}</td>
                    <td class="text-center">{{ $row->bond_status ?: 'No' }}</td>
                    <td class="text-center">{{ $bondEndDateStr }}</td>
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
                    <td colspan="44" class="text-center" style="padding: 20px; color: #64748b;">
                        No participant training records found matching the criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Page generated by OWL LMS &bull; Strictly confidential &bull; Total records: {{ $records->count() }}
    </div>

</body>
</html>
