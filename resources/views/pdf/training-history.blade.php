<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Training History - {{ $user->name ?? 'User' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 12mm 14mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
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
            margin-bottom: 12px;
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
            font-size: 11pt;
            font-weight: bold;
            color: #003b6d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .report-subtitle {
            font-size: 8pt;
            color: #64748b;
            margin-top: 1px;
        }
        .meta-text {
            font-size: 8pt;
            color: #64748b;
            text-align: right;
        }
        .meta-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 4px;
            font-size: 8pt;
        }
        .user-card {
            width: 100%;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .user-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .user-label {
            color: #64748b;
            font-weight: bold;
            width: 90px;
        }
        .user-value {
            color: #0f172a;
            font-weight: 500;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 6px;
        }
        .data-table thead th {
            background-color: #003b6d;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 7px;
            border: 1px solid #002b50;
            vertical-align: middle;
            font-size: 7.5pt;
        }
        .data-table tbody td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            vertical-align: middle;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tbody tr {
            page-break-inside: avoid;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 7pt;
            text-align: center;
        }
        .badge-approved { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-pending { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-completed { background-color: #e0f2fe; color: #0284c7; border: 1px solid #7dd3fc; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .badge-secondary { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .cat-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: 600;
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #64748b; }
        .fw-bold { font-weight: bold; }
        .small { font-size: 7.5pt; }

        .footer-note {
            margin-top: 14px;
            font-size: 7pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    {{-- Header Section --}}
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-title">PT Krakatau Posco &bull; OWL LMS</div>
                    <div class="report-title">Employee Training History</div>
                    <div class="report-subtitle">Personal registration and completion record for offline & schedule trainings</div>
                </td>
                <td class="meta-text">
                    <div>Generated: <strong>{{ $generatedAt }}</strong></div>
                    <div class="meta-badge">Total: {{ count($myHistory) }} Training{{ count($myHistory) === 1 ? '' : 's' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- User Profile & Filter Info --}}
    <div class="user-card">
        <table class="user-table">
            <tr>
                <td class="user-label">Employee:</td>
                <td class="user-value" style="width: 25%;">{{ $user->name ?? '-' }}</td>
                <td class="user-label">Department:</td>
                <td class="user-value" style="width: 25%;">{{ $user->department ?? '-' }}</td>
                <td class="user-label">Status Filter:</td>
                <td class="user-value">{{ !empty($filters['status']) && $filters['status'] !== 'all' ? ucfirst($filters['status']) : 'All' }}</td>
            </tr>
            <tr>
                <td class="user-label">Employee ID:</td>
                <td class="user-value">{{ $user->id_emp ?? ($user->id_user ?? '-') }}</td>
                <td class="user-label">Division:</td>
                <td class="user-value">{{ $user->division ?? '-' }}</td>
                <td class="user-label">Category Filter:</td>
                <td class="user-value">{{ !empty($filters['category']) && $filters['category'] !== 'all' ? $filters['category'] : 'All' }}</td>
            </tr>
            @if(!empty($filters['date']))
            <tr>
                <td class="user-label">Email:</td>
                <td class="user-value">{{ $user->email ?? '-' }}</td>
                <td class="user-label">Date Filter:</td>
                <td class="user-value" colspan="3">{{ $filters['date'] }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Data Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">NO</th>
                <th width="26%">TRAINING NAME</th>
                <th width="14%" class="text-center">CATEGORY</th>
                <th width="16%" class="text-center">SCHEDULE DATE</th>
                <th width="12%" class="text-center">TIME</th>
                <th width="16%">LOCATION &amp; INSTRUCTOR</th>
                <th width="12%" class="text-center">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($myHistory as $index => $item)
                @php
                    $t = $item->training;
                    $title = $t->title ?? ($t->course_name ?? 'Training Schedule');
                    $category = $t->category ?? 'General';
                    $location = $t->location ?? '-';
                    $instructor = $t->instructor ?? '-';
                    $status = $item->status ?? 'Pending';

                    $badgeClass = match($status) {
                        'Approved'  => 'badge-approved',
                        'Pending'   => 'badge-pending',
                        'Completed' => 'badge-completed',
                        'Rejected'  => 'badge-rejected',
                        default     => 'badge-secondary',
                    };

                    $startDate = $t->start_date ?? $item->registered_at;
                    $endDate = $t->end_date ?? null;
                    $startTime = substr($t->start_time ?? '08:00', 0, 5);
                    $endTime = substr($t->end_time ?? '16:00', 0, 5);

                    if ($endDate && $startDate != $endDate) {
                        $sParsed = \Carbon\Carbon::parse($startDate);
                        $eParsed = \Carbon\Carbon::parse($endDate);
                        if ($sParsed->format('M Y') === $eParsed->format('M Y')) {
                            $dateStr = $sParsed->format('d') . ' - ' . $eParsed->format('d M Y');
                        } else {
                            $dateStr = $sParsed->format('d M') . ' - ' . $eParsed->format('d M Y');
                        }
                    } elseif ($startDate) {
                        $dateStr = \Carbon\Carbon::parse($startDate)->format('d M Y');
                    } else {
                        $dateStr = '-';
                    }
                @endphp
                <tr>
                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a; font-size: 8.5pt;">{{ $title }}</strong>
                        @if(!empty($t->description))
                            <div class="text-muted small" style="margin-top: 2px;">{{ \Illuminate\Support\Str::limit($t->description, 75) }}</div>
                        @endif
                        <div class="small text-muted" style="margin-top: 2px;">
                            Registered: {{ $item->registered_at ? \Carbon\Carbon::parse($item->registered_at)->format('d M Y') : '-' }}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="cat-badge">{{ $category }}</span>
                    </td>
                    <td class="text-center fw-bold" style="color: #003b6d;">
                        {{ $dateStr }}
                    </td>
                    <td class="text-center text-muted small">
                        {{ $startTime }} - {{ $endTime }} WIB
                    </td>
                    <td>
                        <div style="font-weight: 500;">{{ $location }}</div>
                        <div class="text-muted small">Instructor: {{ $instructor }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $badgeClass }}">{{ $status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 24px; color: #64748b;">
                        <em>No training history records found matching the requested criteria.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer Section --}}
    <table style="width: 100%; margin-top: 14px; border-top: 1px solid #e2e8f0; padding-top: 6px;">
        <tr>
            <td style="font-size: 7pt; color: #94a3b8;">
                This document is automatically generated by OWL Learning Management System (PT Krakatau Posco). No signature is required.
            </td>
            <td class="text-right" style="font-size: 7pt; color: #94a3b8;">
                Printed on {{ $generatedAt }}
            </td>
        </tr>
    </table>
</body>
</html>
