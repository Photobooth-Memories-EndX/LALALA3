@php
    $trainings = collect($trainings ?? [])->filter(function ($item) {
        $st = strtolower($item->status ?? '');
        return $st !== 'running';
    });
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>PTKP Offline Training Schedule Announcement</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8px;
            line-height: 1.35;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Top Brand Accent */
        .brand-accent-bar {
            height: 4px;
            background-color: #E31B23; /* Krakatau Posco Red */
            width: 100%;
        }

        /* Header Container */
        .header-box {
            background-color: #002B49; /* Krakatau Posco Deep Navy */
            color: #ffffff;
            padding: 10px 14px;
            margin-bottom: 12px;
            border-bottom: 3px solid #005BAC; /* PTKP Blue */
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .brand-subtitle {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #cbd5e1;
            margin-bottom: 2px;
        }
        .brand-title {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
        }
        .brand-desc {
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* Header Badges on Right */
        .header-meta {
            text-align: right;
        }
        .total-schedules-badge {
            display: inline-block;
            background-color: #005BAC;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .meta-timestamp {
            font-size: 7.5px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .filter-tags {
            margin-top: 5px;
            font-size: 7.5px;
            color: #e2e8f0;
        }
        .filter-tag-pill {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 4px;
        }

        /* 3-Column Card Grid Table Layout */
        .card-grid-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 10px;
            table-layout: fixed;
            margin: 0;
            padding: 0;
            page-break-inside: avoid;
        }
        .card-cell {
            width: 33.333%;
            vertical-align: top;
            padding: 0;
        }
        .card-cell-empty {
            border: none;
            background: transparent;
        }

        /* Announcement Card Box */
        .schedule-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-top: 4px solid #005BAC;
            border-radius: 8px;
            padding: 10px 11px;
            min-height: 195px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        /* Card Header & Badges */
        .card-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .card-header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .category-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 10px;
            background-color: #EEF2FF;
            color: #005BAC;
            border: 1px solid #dbeafe;
            text-transform: uppercase;
        }
        .status-badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            padding: 2px 7px;
            border-radius: 10px;
            text-transform: capitalize;
        }
        .status-open {
            background-color: #F0FDF4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .status-running {
            background-color: #FEF3C7;
            color: #B45309;
            border: 1px solid #fde68a;
        }
        .status-closed {
            background-color: #F1F5F9;
            color: #64748B;
            border: 1px solid #cbd5e1;
        }

        /* Card Title & Batch */
        .card-title {
            font-size: 10.5px;
            font-weight: bold;
            color: #002B49;
            line-height: 1.25;
            margin-top: 3px;
            margin-bottom: 2px;
            min-height: 22px;
        }
        .card-code-batch,
        .card-batch {
            font-size: 7.5px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .text-xs { font-size: 7.5px !important; }
        .text-gray-500 { color: #64748b !important; }
        .mt-1 { margin-top: 4px !important; }
        .card-divider {
            border-top: 1px dashed #e2e8f0;
            margin: 4px 0 5px 0;
        }

        /* Card Information List */
        .info-row {
            margin-bottom: 3.5px;
            font-size: 7.5px;
            line-height: 1.25;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
            display: inline-block;
            width: 76px;
        }
        .info-val {
            color: #0f172a;
        }
        .highlight-date {
            font-weight: bold;
            color: #005BAC;
        }
        .highlight-deadline {
            color: #dc2626;
            font-weight: 600;
        }

        /* Empty State */
        .empty-container {
            text-align: center;
            padding: 40px 20px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 20px;
        }
        .empty-title {
            font-size: 14px;
            font-weight: bold;
            color: #002B49;
            margin-bottom: 6px;
        }
        .empty-text {
            font-size: 9px;
            color: #64748b;
        }

        /* Footer */
        .pdf-footer {
            margin-top: 14px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            width: 100%;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            color: #94a3b8;
        }
        .footer-table td {
            border: none;
            padding: 0;
        }
    </style>
</head>
<body>
    <!-- Top Red Brand Accent -->
    <div class="brand-accent-bar"></div>

    <!-- Header Section -->
    <div class="header-box">
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="brand-subtitle">PT KRAKATAU POSCO &bull; OWL LEARNING HUB</div>
                    <h1 class="brand-title">Training Schedule Announcement</h1>
                    <div class="brand-desc">Official Offline Training Calendar &bull; OWL Learning Hub</div>
                </td>
                <td class="header-meta" style="width: 35%;">
                    <div class="total-schedules-badge">
                        Total Schedules: {{ count($trainings) }} Trainings
                    </div>
                    <div class="meta-timestamp">
                        Generated: {{ \Carbon\Carbon::now()->format('d M Y, H:i') }} WIB
                    </div>
                    @if(!empty($activeFilters))
                        <div class="filter-tags">
                            Filters:
                            @foreach($activeFilters as $key => $val)
                                <span class="filter-tag-pill">{{ $key }}: {{ $val }}</span>
                            @endforeach
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- 3-Column Card Grid Layout -->
    @if(count($trainings) > 0)
        @foreach($trainings->chunk(3) as $row)
            <table class="card-grid-table">
                <tr>
                    @foreach($row as $item)
                        @php
                            $training = $item;
                            $startDate = $item->start_date ? \Carbon\Carbon::parse($item->start_date)->format('d M Y') : '-';
                            $endDate = $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d M Y') : $startDate;
                            $schedulePeriod = ($startDate === $endDate) ? $startDate : "{$startDate} - {$endDate}";
                            $startTime = $item->start_time ? substr($item->start_time, 0, 5) : '08:00';
                            $endTime = $item->end_time ? substr($item->end_time, 0, 5) : '17:00';

                            $regDeadline = $item->registration_close_date ? \Carbon\Carbon::parse($item->registration_close_date)->format('d M Y') : 'Until Started';
                            if ($item->registration_close_time) {
                                $regDeadline .= ' ' . substr($item->registration_close_time, 0, 5) . ' WIB';
                            }

                            $status = $item->status ?? 'Open Registration';
                            $statusClass = match(strtolower($status)) {
                                'running' => 'status-running',
                                'closed'  => 'status-closed',
                                default   => 'status-open',
                            };
                        @endphp
                        <td class="card-cell">
                            <div class="schedule-card">
                                <!-- 1. Category & Status with Close Reg date beneath badge -->
                                <table class="card-header-table">
                                    <tr>
                                        <td style="text-align: left; vertical-align: top;">
                                            <span class="category-badge">{{ $item->category ?? 'General' }}</span>
                                        </td>
                                        <td style="text-align: right; vertical-align: top;">
                                            <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
                                            <div style="font-size: 6.5px; color: #dc2626; margin-top: 2px; font-weight: 600;">Close: {{ $regDeadline }}</div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- 2. Training Title (and optional sub course name) -->
                                <div class="card-title">{{ $item->title ?? ($item->course_name ?? 'Training Schedule') }}</div>
                                @if(!empty($item->sub_course_name))
                                    <div style="font-size: 7.5px; color: #64748b; margin-top: -2px; margin-bottom: 2px;">{{ $item->sub_course_name }}</div>
                                @endif

                                <div class="card-divider"></div>

                                <!-- 3. Date - Venue -->
                                <div class="info-row">
                                    <span class="info-label">Date - Venue:</span>
                                    <span class="info-val highlight-date">{{ $schedulePeriod }} ({{ $startTime }} - {{ $endTime }} WIB) &bull; {{ $item->location ?? ($item->venue ?? 'PTKP Training Room') }}</span>
                                </div>

                                <!-- 4. Target -->
                                <div class="info-row">
                                    <span class="info-label">Target:</span>
                                    <span class="info-val">{{ $item->target_employee ?? 'All Employees' }}</span>
                                </div>

                                <!-- 5. Batch -->
                                <div class="info-row">
                                    <span class="info-label">Batch:</span>
                                    <span class="info-val">{{ $item->batch ?? '-' }}</span>
                                </div>

                                <!-- 6. Instructor & Quota -->
                                <div class="info-row">
                                    <span class="info-label">Instructor &amp; Quota:</span>
                                    <span class="info-val">{{ $item->instructor ?? '-' }} &bull; Quota: {{ $item->quota ?? 0 }} Seats</span>
                                </div>

                                <!-- 7. Syllabus Summary -->
                                @php
                                    $syllabusTopics = [];
                                    if (!empty($item->syllabus) && is_array($item->syllabus)) {
                                        foreach ($item->syllabus as $s) {
                                            $t = is_array($s) ? ($s['topic'] ?? $s['title'] ?? '') : (string) $s;
                                            if (trim($t) !== '') {
                                                $syllabusTopics[] = trim($t);
                                            }
                                        }
                                    }
                                    $syllabusSummary = !empty($syllabusTopics) ? implode(', ', array_slice($syllabusTopics, 0, 3)) : ($item->learning_objectives ?? '-');
                                @endphp
                                <div class="info-row" style="margin-top: 4px; padding-top: 4px; border-top: 1px dotted #e2e8f0;">
                                    <span class="info-label">Syllabus:</span>
                                    <span class="info-val" style="color: #475569;">{{ \Illuminate\Support\Str::limit($syllabusSummary, 65) }}</span>
                                </div>
                            </div>
                        </td>
                    @endforeach

                    {{-- Fill remaining slots in row if less than 3 --}}
                    @for($i = $row->count(); $i < 3; $i++)
                        <td class="card-cell card-cell-empty"></td>
                    @endfor
                </tr>
            </table>
        @endforeach
    @else
        <div class="empty-container">
            <div class="empty-title">No Training Schedules Found</div>
            <div class="empty-text">There are currently no training schedules matching your filter criteria.</div>
        </div>
    @endif

    <!-- Footer Section -->
    <div class="pdf-footer">
        <table class="footer-table">
            <tr>
                <td style="text-align: left;">
                    PT KRAKATAU POSCO &bull; People & Organization Development &bull; Learning Hub
                </td>
                <td style="text-align: right;">
                    Document generated from OWL Learning Hub
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
