<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NON KP OWL — Employee Training Record: {{ $participant['name'] ?? 'Participant' }}</title>
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
        .profile-container {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
        }
        .profile-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .profile-table td {
            border: none;
            padding: 2px 6px;
            vertical-align: top;
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
    <!-- HEADER -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">PT Krakatau Posco &bull; OWL LMS</div>
                    <div class="report-title">NON KP OWL — Employee Training Record Report</div>
                </td>
                <td class="meta-text">
                    <div>Generated: <strong>{{ $generatedAt ?? date('d M Y H:i:s') }}</strong></div>
                    <div class="meta-badge">Total Records: {{ number_format($totalRecords) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- PARTICIPANT PROFILE & METRICS SUMMARY -->
    <div class="profile-container">
        <table class="profile-table">
            <tr>
                <td width="20%">
                    <span class="field-label">PARTICIPANT NAME</span><br>
                    <strong style="font-size: 9px; color: #003b6d;">{{ $participant['name'] ?? '-' }}</strong>
                </td>
                <td width="15%">
                    <span class="field-label">NIP / EMPLOYEE ID</span><br>
                    <strong>{{ $participant['employee_id'] ?: '-' }}</strong>
                </td>
                <td width="12%">
                    <span class="field-label">GRADE</span><br>
                    <span>{{ $participant['grade'] ?: '-' }}</span>
                </td>
                <td width="23%">
                    <span class="field-label">JOB POSITION</span><br>
                    <span>{{ $participant['job_position'] ?: '-' }}</span>
                </td>
                <td width="30%">
                    <span class="field-label">DEPARTMENT &bull; TEAM</span><br>
                    <span>{{ $participant['department'] ?: '-' }}{{ !empty($participant['team']) ? ' (' . $participant['team'] . ')' : '' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="field-label">COMPANY</span><br>
                    <span>{{ $participant['company'] ?: 'PT Krakatau Posco' }}</span>
                </td>
                <td>
                    <span class="field-label">TOTAL SESSIONS</span><br>
                    <strong style="color: #0f4c81;">{{ number_format($totalRecords) }}</strong>
                </td>
                <td>
                    <span class="field-label">FINISHED</span><br>
                    <strong style="color: #16a34a;">{{ number_format($records->where('progress', '>=', 100)->count()) }}</strong>
                </td>
                <td>
                    <span class="field-label">ONGOING / NOT STARTED</span><br>
                    <strong style="color: #f59e0b;">{{ number_format($records->where('progress', '<', 100)->count()) }}</strong>
                </td>
                <td>
                    <span class="field-label">TOTAL LEARNING HOURS</span><br>
                    <strong style="color: #0284c7;">{{ number_format($records->sum('learning_hours'), 1) }} Hours</strong>
                </td>
            </tr>
        </table>
    </div>

    <!-- DATA TABLE -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="3%" class="text-center">No</th>
                <th width="24%">Training Information</th>
                <th width="18%">Classification &amp; KPI</th>
                <th width="17%">Schedule &amp; Delivery</th>
                <th width="15%">Progress &amp; Attendance</th>
                <th width="12%">Service Bond &amp; Admin</th>
                <th width="11%">Kirkpatrick &amp; PIC</th>
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
            @endphp
            <tr>
                <td class="text-center">
                    <strong>{{ $index + 1 }}</strong><br>
                    <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                </td>
                <td>
                    <strong style="font-size: 8px; color: #003b6d;">{{ $row->course_name ?: '-' }}</strong><br>
                    @if(!empty($row->sub_course_name))
                        <span class="field-val" style="color: #475569;">{{ $row->sub_course_name }}</span><br>
                    @endif
                    <span class="field-label">Type:</span> <span class="field-val">{{ $row->type ?: 'In-House' }}</span> |
                    <span class="field-label">Category:</span> <span class="field-val">{{ $row->category ?: '-' }}</span><br>
                    <span class="field-label">Level:</span> <span class="field-val">{{ $row->level ?: '-' }}</span> |
                    <span class="field-label">Batch:</span> <span class="field-val">{{ $row->batch ?: '-' }}</span>
                </td>
                <td>
                    <span class="field-label">Main Class:</span> <span class="field-val">{{ $row->main_classification ?: '-' }}</span><br>
                    <span class="field-label">Sub Class:</span> <span class="field-val">{{ $row->sub_classification ?: '-' }}</span><br>
                    <span class="field-label">Corp KPI:</span> 
                    @if($row->counted_corporate_kpi)
                        <span class="badge badge-primary">Yes</span>
                    @elseif($row->counted_corporate_kpi === false || $row->counted_corporate_kpi === 0)
                        <span class="badge badge-secondary">No</span>
                    @else
                        <span class="field-val">-</span>
                    @endif
                    @if(!empty($row->sub_corporate_kpi))
                        <span class="field-val">({{ $row->sub_corporate_kpi }})</span>
                    @endif
                </td>
                <td>
                    <span class="field-label">Dates:</span> 
                    <span class="field-val">
                        {{ $row->start_date ? date('d/m/Y', strtotime($row->start_date)) : '-' }} &ndash; 
                        {{ $row->finish_date ? date('d/m/Y', strtotime($row->finish_date)) : '-' }}
                    </span><br>
                    <span class="field-label">Month:</span> <span class="field-val">{{ $month }}</span> |
                    <span class="field-label">Duration:</span> <span class="field-val">{{ $durationFormatted }}</span><br>
                    <span class="field-label">Delivery:</span> <span class="field-val">{{ $row->delivery_method ?: '-' }}</span>
                    @if(!empty($row->sub_delivery_method)) <span class="field-val">({{ $row->sub_delivery_method }})</span> @endif<br>
                    <span class="field-label">Provider:</span> <span class="field-val">{{ $row->provider_name ?: ($row->provider ?: '-') }}</span><br>
                    <span class="field-label">Venue:</span> <span class="field-val">{{ $row->venue ?: '-' }}</span> |
                    <span class="field-label">Instructor:</span> <span class="field-val">{{ $row->instructor ?: '-' }}</span>
                </td>
                <td>
                    <span class="field-label">Hours:</span> <span class="field-val"><strong>{{ number_format((float) ($row->learning_hours ?? 0), 1) }}h</strong></span> 
                    @if($row->course_learning_hours) <span class="field-label">(Course: {{ number_format((float) $row->course_learning_hours, 1) }}h)</span> @endif<br>
                    <span class="field-label">Progress:</span> <span class="field-val"><strong>{{ round($progress) }}%</strong></span><br>
                    <span class="field-label">Score:</span> <span class="field-val">{{ $row->assessment_score !== null ? number_format((float) $row->assessment_score, 1) : '-' }}</span> |
                    <span class="field-label">Cert:</span> <span class="field-val">{{ $row->certificate ?: '-' }}</span><br>
                    <span class="field-label">Attendance:</span> 
                    @if($row->attendance === 1 || $row->attendance === true)
                        <span class="badge badge-success">Present</span>
                    @elseif($row->attendance === 0 || $row->attendance === false)
                        <span class="badge badge-warning">Absent</span>
                    @else
                        <span class="field-val">-</span>
                    @endif
                    @if(!empty($row->attendance_reason))
                        <br><span class="field-label">Reason:</span> <span class="field-val">{{ $row->attendance_reason }}</span>
                    @endif
                </td>
                <td>
                    <span class="field-label">Bond Status:</span> <span class="field-val">{{ $row->bond_status ?: 'No' }}</span><br>
                    @if(!empty($row->bond_end_date))
                        <span class="field-label">End Date:</span> <span class="field-val">{{ date('d/m/Y', strtotime($row->bond_end_date)) }}</span><br>
                    @endif
                    @if(!empty($row->bond_amount))
                        <span class="field-label">Amount:</span> <span class="field-val">Rp {{ number_format((float) $row->bond_amount, 0, ',', '.') }}</span>
                    @endif
                </td>
                <td>
                    <span class="field-label">L1 Program:</span> <span class="field-val">{{ $row->l1_program ?: '-' }}</span><br>
                    <span class="field-label">L2 Post Test:</span> 
                    <span class="field-val">{{ $row->l2_numerical !== null ? number_format((float) $row->l2_numerical, 1) : '-' }}</span>
                    @if(!empty($row->l2_verbal)) <span class="field-val">({{ $row->l2_verbal }})</span> @endif<br>
                    <span class="field-label">L3 Project:</span> 
                    <span class="field-val">{{ $row->l3_numerical !== null ? number_format((float) $row->l3_numerical, 1) : '-' }}</span>
                    @if(!empty($row->l3_verbal)) <span class="field-val">({{ $row->l3_verbal }})</span> @endif<br>
                    <span class="field-label">PIC:</span> <span class="field-val">{{ $row->pic ?: '-' }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                    No training records found matching the specified criteria for this participant.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td width="50%">OWL LMS &bull; Manage Record &bull; Non KP OWL &bull; Participant: {{ $participant['name'] }}</td>
            <td width="50%" class="text-right">CONFIDENTIAL &bull; Internal HR Training Record</td>
        </tr>
    </table>
</body>
</html>
