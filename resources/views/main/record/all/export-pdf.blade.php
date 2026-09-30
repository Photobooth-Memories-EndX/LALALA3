<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>All KP OWL - {{ $classificationName }} - Report</title>
    <style>
        @page {
            size: a2 landscape;
            margin: 15mm 12mm 15mm 12mm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 6.5pt;
            color: #1e293b;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .report-header {
            margin-bottom: 12px;
            border-bottom: 2px solid #003b6d;
            padding-bottom: 8px;
        }
        .report-title {
            font-size: 15pt;
            font-weight: bold;
            color: #003b6d;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 9.5pt;
            font-weight: 600;
            color: #475569;
            margin: 0 0 5px 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 6px;
            font-size: 7pt;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .filter-badge {
            display: inline-block;
            background: #e2e8f0;
            color: #1e293b;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
            margin-right: 4px;
            font-size: 6.5pt;
        }
        .note-box {
            background: #f1f5f9;
            border-left: 3px solid #003b6d;
            padding: 4px 8px;
            font-size: 6.5pt;
            color: #475569;
            margin-top: 4px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.data-table thead {
            display: table-header-group;
        }
        table.data-table tr {
            page-break-inside: avoid;
        }
        table.data-table th {
            background-color: #003b6d;
            color: #ffffff;
            font-size: 6pt;
            font-weight: bold;
            text-align: left;
            padding: 4px 3px;
            border: 0.5px solid #002244;
            word-wrap: break-word;
            vertical-align: middle;
        }
        table.data-table td {
            font-size: 6pt;
            padding: 3.5px 3px;
            border: 0.5px solid #cbd5e1;
            word-wrap: break-word;
            vertical-align: top;
        }
        table.data-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-online {
            color: #0c5b9e;
            font-weight: bold;
        }
        .badge-offline {
            color: #2b8a3e;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="report-header">
        <div class="report-title">All KP OWL — Combined Learning Records</div>
        <div class="report-subtitle">Classification: {{ $classificationName }}</div>
        
        <table class="meta-table">
            <tr>
                <td style="width: 50%;"><strong>Generated:</strong> {{ $generatedAt }} WIB</td>
                <td style="width: 50%;"><strong>Total Filtered Records:</strong> {{ number_format($totalRecords) }} rows</td>
            </tr>
            <tr>
                <td colspan="2">
                    <strong>Active Filters:</strong>
                    @if(!empty($activeFilterBadges))
                        @foreach($activeFilterBadges as $badge)
                            <span class="filter-badge">{{ $badge }}</span>
                        @endforeach
                    @else
                        <span class="filter-badge">All Records (No Filters)</span>
                    @endif
                </td>
            </tr>
        </table>
        
        <div class="note-box">
            <strong>Source Guide:</strong> 
            <strong>KP OWL</strong> = Online Learning Record | 
            <strong>Non KP OWL</strong> = Offline Training Record. 
            Non-applicable fields for a particular source are displayed as &quot;-&quot;.
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach($headers as $h)
                    @php
                        $isCenter = in_array($h, ['No', 'Source', 'Status', 'Training Hours', 'Participant Hours', 'Progress', 'Assessment Score', 'Certificate', 'Attendance', 'L2 Numerical', 'L3 Numerical']);
                    @endphp
                    <th class="{{ $isCenter ? 'text-center' : '' }}">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $r)
                @php
                    $durationFormatted = '-';
                    if (!empty($r->duration_seconds)) {
                        $secs = (int) $r->duration_seconds;
                        $hours = floor($secs / 3600);
                        $mins = floor(($secs % 3600) / 60);
                        $seconds = $secs % 60;
                        $durationFormatted = sprintf('%02d:%02d:%02d', $hours, $mins, $seconds);
                    }

                    $attendanceStr = '-';
                    if ($r->attendance !== null) {
                        $attendanceStr = (bool) $r->attendance ? 'Present' : 'Absent';
                    }

                    $kpiStr = '-';
                    if ($r->counted_corporate_kpi !== null) {
                        $kpiStr = (bool) $r->counted_corporate_kpi ? 'Yes' : 'No';
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center"><span class="{{ $r->source === 'offline' ? 'badge-offline' : 'badge-online' }}">{{ $r->source === 'offline' ? 'Non KP OWL' : 'KP OWL' }}</span></td>
                    <td>{{ $r->employee_id ?: '-' }}</td>
                    <td><strong>{{ $r->employee_name ?: '-' }}</strong></td>
                    <td class="text-center">{{ $r->status ? ucfirst($r->status) : '-' }}</td>
                    <td>{{ $r->grade ?: '-' }}</td>
                    <td>{{ $r->job_position ?: '-' }}</td>
                    <td>{{ $r->team ?: '-' }}</td>
                    <td>{{ $r->department ?: '-' }}</td>
                    <td>{{ $r->company ?: '-' }}</td>
                    <td>{{ $r->learning_title ?: '-' }}</td>
                    <td>{{ $r->sub_course_name ?: '-' }}</td>
                    <td>{{ $r->type ?: '-' }}</td>
                    <td>{{ $r->category ?: '-' }}</td>
                    <td>{{ $r->level ?: '-' }}</td>
                    <td>{{ $r->batch ?: '-' }}</td>
                    <td>{{ $r->classification ?: '-' }}</td>
                    <td>{{ $r->sub_classification ?: '-' }}</td>
                    <td class="text-center">{{ $kpiStr }}</td>
                    <td>{{ $r->sub_corporate_kpi ?: '-' }}</td>
                    <td>{{ $r->delivery_method ?: '-' }}</td>
                    <td>{{ $r->sub_delivery_method ?: '-' }}</td>
                    <td>{{ $r->provider ?: '-' }}</td>
                    <td>{{ $r->sub_provider ?: '-' }}</td>
                    <td>{{ $r->provider_name ?: '-' }}</td>
                    <td>{{ $r->venue ?: '-' }}</td>
                    <td>{{ $r->instructor ?: '-' }}</td>
                    <td class="text-center">{{ $durationFormatted }}</td>
                    <td class="text-center">{{ $r->training_hours !== null ? number_format((float) $r->training_hours, 2) : '-' }}</td>
                    <td class="text-center">{{ $r->start_date ? date('Y-m-d', strtotime($r->start_date)) : '-' }}</td>
                    <td class="text-center">{{ $r->finish_date ? date('Y-m-d', strtotime($r->finish_date)) : '-' }}</td>
                    <td class="text-center">{{ $r->learning_hours !== null ? number_format((float) $r->learning_hours, 2) : '0.00' }}</td>
                    <td class="text-center">{{ $r->progress !== null ? $r->progress . '%' : '-' }}</td>
                    <td class="text-center">{{ $r->assessment_score !== null ? number_format((float) $r->assessment_score, 2) : '-' }}</td>
                    <td class="text-center">{{ $r->certificate ?: '-' }}</td>
                    <td class="text-center">{{ $attendanceStr }}</td>
                    <td>{{ $r->attendance_reason ?: '-' }}</td>
                    <td class="text-center">{{ $r->bond_status ?: '-' }}</td>
                    <td class="text-center">{{ $r->bond_end_date ? date('Y-m-d', strtotime($r->bond_end_date)) : '-' }}</td>
                    <td class="text-center">{{ $r->bond_amount !== null ? 'IDR ' . number_format((float) $r->bond_amount, 0, ',', '.') : '-' }}</td>
                    <td>{{ $r->l1_program ?: '-' }}</td>
                    <td class="text-center">{{ $r->l2_numerical !== null ? number_format((float) $r->l2_numerical, 2) : '-' }}</td>
                    <td>{{ $r->l2_verbal ?: '-' }}</td>
                    <td class="text-center">{{ $r->l3_numerical !== null ? number_format((float) $r->l3_numerical, 2) : '-' }}</td>
                    <td>{{ $r->l3_verbal ?: '-' }}</td>
                    <td>{{ $r->pic ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="text-center" style="padding: 20px;">No records found matching the active filter criteria.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
