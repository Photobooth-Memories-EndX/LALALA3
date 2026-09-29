<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NON KP OWL — Offline Learning Record</title>
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
            font-size: 16px;
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
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        .data-table thead th {
            background-color: #003b6d;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 5px 6px;
            border: 1px solid #002b50;
            vertical-align: middle;
        }
        .data-table tbody td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody tr {
            page-break-inside: avoid;
        }
        .field-label {
            color: #64748b;
            font-size: 7px;
        }
        .field-val {
            font-weight: 500;
            color: #0f172a;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 7px;
        }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        .badge-primary { background: #dbeafe; color: #1d4ed8; }
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
                    <div class="report-title">NON KP OWL — Offline Learning Record</div>
                </td>
                <td class="meta-text">
                    <div>Generated: <strong>{{ $generatedAt ?? $generatedDate ?? date('d M Y H:i:s') }}</strong></div>
                    <div class="meta-badge">Total Records: {{ number_format($totalRecords) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="15%">Participant &amp; Organization</th>
                <th width="20%">Training Information</th>
                <th width="15%">Schedule &amp; Delivery</th>
                <th width="15%">Progress &amp; Attendance</th>
                <th width="15%">Service Bond &amp; Admin</th>
                <th width="17%">Kirkpatrick L1 - L3 &amp; PIC</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $row)
            @php
                $progress = (float) ($row->progress ?? 0);
                $statusText = $progress >= 100 ? 'Finished' : ($progress > 0 ? 'Ongoing (' . round($progress) . '%)' : 'Not Started');
                $statusClass = $progress >= 100 ? 'badge-success' : ($progress > 0 ? 'badge-warning' : 'badge-secondary');

                $durationFormatted = '-';
                if (!empty($row->duration_seconds)) {
                    $secs = (int) $row->duration_seconds;
                    $durationFormatted = sprintf('%02d:%02d:%02d', floor($secs / 3600), floor(($secs % 3600) / 60), $secs % 60);
                }

                $month = '-';
                $dateForMonth = $row->start_date ?: $row->finish_date;
                if (!empty($dateForMonth)) {
                    try { $month = date('F', strtotime($dateForMonth)); } catch (\Throwable $e) { $month = '-'; }
                }

                $participantName = $row->participant_name ?: ($row->user_name ?: '-');
            @endphp
            <tr>
                <td class="text-center">
                    <strong>{{ $index + 1 }}</strong><br>
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
                <td>
                    <strong style="font-size: 8px;">{{ $participantName }}</strong><br>
                    <span class="field-label">NIP:</span> <span class="field-val">{{ $row->employee_id ?: '-' }}</span><br>
                    <span class="field-label">Grade:</span> <span class="field-val">{{ $row->grade ?: '-' }}</span><br>
                    <span class="field-label">Position:</span> <span class="field-val">{{ $row->job_position ?: '-' }}</span><br>
                    <span class="field-label">Team:</span> <span class="field-val">{{ $row->team ?: '-' }}</span><br>
                    <span class="field-label">Dept:</span> <span class="field-val">{{ $row->department ?: '-' }}</span><br>
                    <span class="field-label">Company:</span> <span class="field-val">{{ $row->company ?: '-' }}</span>
                </td>
                <td>
                    <strong style="font-size: 8px;">{{ $row->course_name }}</strong><br>
                    @if(!empty($row->sub_course_name))
                        <span class="field-label">Sub:</span> <span class="field-val">{{ $row->sub_course_name }}</span><br>
                    @endif
                    <span class="field-label">Type/Batch:</span> <span class="field-val">{{ $row->type ?: 'In-House' }} / {{ $row->batch ?: '-' }}</span><br>
                    <span class="field-label">Category:</span> <span class="field-val">{{ $row->category ?: '-' }}</span> ({{ $row->level ?: '-' }})<br>
                    <span class="field-label">Classification:</span> <span class="field-val">{{ $row->main_classification ?: '-' }}</span><br>
                    @if(!empty($row->sub_classification))
                        <span class="field-label">Sub Class:</span> <span class="field-val">{{ $row->sub_classification }}</span><br>
                    @endif
                    <span class="field-label">Corporate KPI:</span> <span class="field-val">{{ $row->counted_corporate_kpi ? 'Yes' : ($row->counted_corporate_kpi === false ? 'No' : '-') }}</span>
                    @if(!empty($row->sub_corporate_kpi)) ({{ $row->sub_corporate_kpi }}) @endif
                </td>
                <td>
                    <span class="field-label">Start Date:</span> <span class="field-val">{{ $row->start_date ? date('d M Y', strtotime($row->start_date)) : '-' }}</span><br>
                    <span class="field-label">Finish Date:</span> <span class="field-val">{{ $row->finish_date ? date('d M Y', strtotime($row->finish_date)) : '-' }}</span><br>
                    <span class="field-label">Month:</span> <span class="field-val">{{ $month }}</span><br>
                    <span class="field-label">Duration:</span> <span class="field-val">{{ $durationFormatted }}</span><br>
                    <span class="field-label">Tr. Hours:</span> <span class="field-val">{{ $row->course_learning_hours ? number_format((float) $row->course_learning_hours, 1) . 'h' : '-' }}</span><br>
                    <span class="field-label">Delivery:</span> <span class="field-val">{{ $row->delivery_method ?: '-' }}</span><br>
                    @if(!empty($row->sub_delivery_method))
                        <span class="field-label">Sub Delivery:</span> <span class="field-val">{{ $row->sub_delivery_method }}</span><br>
                    @endif
                    <span class="field-label">Provider:</span> <span class="field-val">{{ $row->provider_name ?: ($row->provider ?: '-') }}</span><br>
                    <span class="field-label">Venue / Inst:</span> <span class="field-val">{{ $row->venue ?: '-' }} / {{ $row->instructor ?: '-' }}</span>
                </td>
                <td>
                    <span class="field-label">Part. Hours:</span> <span class="field-val">{{ $row->learning_hours ? number_format((float) $row->learning_hours, 1) . 'h' : '-' }}</span><br>
                    <span class="field-label">Progress:</span> <span class="field-val">{{ round($progress) }}%</span><br>
                    <span class="field-label">Assessment Score:</span> <span class="field-val">{{ $row->assessment_score !== null ? number_format((float) $row->assessment_score, 1) : '-' }}</span><br>
                    <span class="field-label">Certificate:</span> <span class="field-val">{{ $row->certificate ?: '-' }}</span><br>
                    <span class="field-label">Attendance:</span> 
                    @if($row->attendance === null)
                        <span class="field-val">-</span>
                    @elseif($row->attendance)
                        <span class="badge badge-success">Present</span>
                    @else
                        <span class="badge badge-warning">Absent</span>
                    @endif
                    <br>
                    @if(!empty($row->attendance_reason))
                        <span class="field-label">Reason:</span> <span class="field-val">{{ $row->attendance_reason }}</span>
                    @endif
                </td>
                <td>
                    <span class="field-label">Bond Status:</span> <span class="field-val">{{ $row->bond_status ?: 'No' }}</span><br>
                    <span class="field-label">Bond End Date:</span> <span class="field-val">{{ $row->bond_end_date ? date('d M Y', strtotime($row->bond_end_date)) : '-' }}</span><br>
                    <span class="field-label">Bond Amount:</span> <span class="field-val">{{ $row->bond_amount !== null ? 'Rp ' . number_format((float) $row->bond_amount, 0, ',', '.') : '-' }}</span>
                </td>
                <td>
                    <span class="field-label">L1 Program:</span> <span class="field-val">{{ $row->l1_program ?: '-' }}</span><br>
                    <span class="field-label">L2 Numerical:</span> <span class="field-val">{{ $row->l2_numerical !== null ? number_format((float) $row->l2_numerical, 1) : '-' }}</span><br>
                    <span class="field-label">L2 Verbal:</span> <span class="field-val">{{ $row->l2_verbal ?: '-' }}</span><br>
                    <span class="field-label">L3 Numerical:</span> <span class="field-val">{{ $row->l3_numerical !== null ? number_format((float) $row->l3_numerical, 1) : '-' }}</span><br>
                    <span class="field-label">L3 Verbal:</span> <span class="field-val">{{ $row->l3_verbal ?: '-' }}</span><br>
                    <span class="field-label">PIC:</span> <span class="field-val">{{ $row->pic ?: '-' }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">No offline training records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
