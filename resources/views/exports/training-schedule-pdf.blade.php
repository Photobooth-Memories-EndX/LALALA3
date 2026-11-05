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

        /* Standardized Announcement Card Box */
        .card-box,
        .schedule-card {
            min-height: 380px;
            padding: 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #ffffff;
            box-sizing: border-box;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        /* Fixed Key-Value Details Alignment Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            line-height: 1.35;
            table-layout: fixed;
        }
        .details-table td {
            padding: 2.5px 0;
            vertical-align: top;
            border: none;
        }
        td.label,
        .details-table td.label {
            width: 90px;
            font-weight: 700;
            color: #334155;
        }
        td.colon,
        .details-table td.colon {
            width: 15px;
            font-weight: 700;
            color: #334155;
            text-align: center;
        }
        td.value,
        .details-table td.value {
            color: #0f172a;
        }

        /* Card Header & Badges */
        .card-header-row {
            width: 100%;
            margin-bottom: 6px;
            height: 30px;
            display: block;
        }
        .card-header-row:after {
            content: "";
            display: table;
            clear: both;
        }
        .badge-cat,
        .badge-category {
            float: left;
            display: inline-block;
        }
        .card-header-right {
            float: right;
            text-align: right;
        }
        .badge-status {
            display: inline-block;
        }
        .card-title-h3 {
            clear: both;
            height: 34px;
            line-height: 1.3;
            overflow: hidden;
        }
        .date-venue-box {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
            white-space: nowrap !important;
            clear: both;
        }
        .date-pill-cell {
            display: table-cell !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            font-size: 8.5px !important;
        }
        .venue-pill-cell {
            display: table-cell !important;
            vertical-align: middle !important;
            white-space: nowrap !important;
            text-align: right !important;
            padding-left: 4px !important;
            font-size: 8.5px !important;
            max-width: 48% !important;
            overflow: hidden !important;
        }
        .date-pill-cell svg,
        .venue-pill-cell svg {
            vertical-align: -1px;
            margin-right: 2px;
            width: 9px !important;
            height: 9px !important;
        }
        .pill-box {
            display: inline-block;
            vertical-align: middle;
        }
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

        .details-table td {
            vertical-align: top;
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
                            $statusRaw = $training->status ?? 'Open Registration';
                            $isOpen = in_array(strtolower($statusRaw), ['open', 'open registration']);

                            $startDate = (!empty($item->start_date) && strtolower($item->status ?? '') !== 'unscheduled') ? \Carbon\Carbon::parse($item->start_date)->format('d M Y') : 'TBA (To be announce)';
                            $endDate = $item->end_date ? \Carbon\Carbon::parse($item->end_date)->format('d M Y') : $startDate;
                            $schedulePeriod = ($startDate === $endDate || empty($item->end_date)) ? $startDate : "{$startDate} - {$endDate}";

                            $venue = $training->location ?? ($training->venue ?? ($training->room_name ?? ($training->room ?? '-')));

                            $sTime = \Carbon\Carbon::parse($training->start_time ?? '08:00');
                            $eTime = \Carbon\Carbon::parse($training->end_time ?? '17:00');
                            $diffHours = $sTime->diffInHours($eTime);
                            $durationText = ($diffHours > 0 ? $diffHours : 8) . ' Hours';

                            $quotaText = ($training->quota ?? ($training->participant_quota ?? 30)) . ' Seats';

                            // Syllabus topics list
                            $syllabusTopics = [];
                            if (!empty($training->syllabi) && count($training->syllabi) > 0) {
                                foreach ($training->syllabi as $s) {
                                    $syllabusTopics[] = is_object($s) ? ($s->title ?? $s->topic ?? '') : (string) $s;
                                }
                            } elseif (!empty($training->syllabus) && is_array($training->syllabus)) {
                                foreach ($training->syllabus as $s) {
                                    $tTopic = is_array($s) ? ($s['topic'] ?? $s['title'] ?? '') : (string) $s;
                                    if (trim($tTopic) !== '') {
                                        $syllabusTopics[] = trim($tTopic);
                                    }
                                }
                            } elseif (!empty($training->syllabus_summary)) {
                                $syllabusTopics = array_map('trim', explode(',', $training->syllabus_summary));
                            }
                            $syllabusTopics = array_values(array_filter($syllabusTopics));
                        @endphp
                        <td class="card-cell">
                            <div class="card-box" style="min-height: 380px; padding: 16px; border: 1px solid #cbd5e1; border-radius: 12px; background: #ffffff; box-sizing: border-box; margin-bottom: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                                <!-- Row 1: Header (Category Pill Badge Left, Status Badge & Red Close Date Right) -->
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px;">
                                    <tr>
                                        <td style="vertical-align: top; text-align: left;">
                                            <span style="display: inline-block; background: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 8.5px; text-transform: uppercase; white-space: nowrap; vertical-align: top;">
                                                {{ $training->category ?? 'GENERAL' }}
                                            </span>
                                        </td>
                                        <td style="vertical-align: top; text-align: right;">
                                            @if($isOpen)
                                                <span style="display: inline-block; background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 8.5px; white-space: nowrap;">
                                                    Open Registration
                                                </span>
                                            @else
                                                <span style="display: inline-block; background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 8.5px; white-space: nowrap;">
                                                    Closed
                                                </span>
                                            @endif
                                            @if($training->registration_close_date)
                                                @php
                                                    $closeDateStr = \Carbon\Carbon::parse($training->registration_close_date)->format('d M Y');
                                                    $rawCloseTime = $training->registration_close_time ? trim($training->registration_close_time) : '23:59';
                                                    $closeTimeStr = strlen($rawCloseTime) >= 5 ? substr($rawCloseTime, 0, 5) : $rawCloseTime;
                                                @endphp
                                                <div style="color: #dc2626; font-size: 8px; font-weight: 700; margin-top: 3px; white-space: nowrap;">
                                                    Close: {{ $closeDateStr }} {{ $closeTimeStr }} WIB
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                <!-- Row 2: Title in bold Navy font -->
                                <div style="font-size: 11.5px; font-weight: 800; color: #002B49; line-height: 1.3; min-height: 32px; margin: 5px 0 2px 0; word-break: break-word;">
                                    {{ $training->title }}
                                </div>
                                @if(!empty($training->subtitle) || !empty($training->sub_course_name))
                                    <div style="font-size: 8px; color: #64748b; margin-bottom: 4px; line-height: 1.2;">
                                        {{ $training->subtitle ?? $training->sub_course_name }}
                                    </div>
                                @endif

                                <!-- Row 3: Date & Venue Inline Row -->
                                <div style="font-size: 9px; color: #475569; font-weight: 600; line-height: 1.35; min-height: 20px; max-height: 24px; overflow: hidden; margin-bottom: 2px;">
                                    <span style="white-space: nowrap;">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>{{ $schedulePeriod }}
                                    </span>
                                    <span style="color: #94a3b8; margin: 0 4px;">|</span>
                                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 150px; display: inline-block; vertical-align: middle;">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -1px; margin-right: 2px;">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>{{ $training->location ?? $training->venue ?? $training->room_name ?? $training->room ?? '-' }}
                                    </span>
                                </div>

                                <!-- Row 4: Divider Line -->
                                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 0 0 12px 0;">

                                <!-- Row 5: Key-Value Details Alignment Table -->
                                <table class="details-table" style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 10.5px;">
                                    <tr>
                                        <td style="width: 85px; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Target</td>
                                        <td style="width: 15px; text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0; word-break: break-word;">{{ $training->target_employee ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Batch</td>
                                        <td style="text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0;">{{ $training->batch ?? '1' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Time</td>
                                        <td style="text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0;">
                                            @if(empty($training->start_time) || empty($training->end_time) || strtolower($training->status ?? '') === 'unscheduled')TBA @else {{ \Carbon\Carbon::parse($training->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($training->end_time)->format('H:i') }} WIB @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Duration</td>
                                        <td style="text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0;">
                                            @php
                                                $startTime = \Carbon\Carbon::parse($training->start_time);
                                                $endTime = \Carbon\Carbon::parse($training->end_time);
                                                $durationHours = $startTime->diffInHours($endTime);
                                            @endphp
                                            {{ $durationHours }} Hours
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Instructor</td>
                                        <td style="text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0; word-break: break-word;">
                                            {{ $training->instructor_name ?? $training->instructor ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Quota</td>
                                        <td style="text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0;">{{ $training->quota ?? $training->participant_quota ?? '30' }} Seats</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">Syllabus</td>
                                        <td style="text-align: center; font-weight: 700; color: #475569; padding: 3px 0; vertical-align: top;">:</td>
                                        <td style="color: #0f172a; padding: 3px 0; word-break: break-word;">
                                            @if($training->syllabi && $training->syllabi->count() > 0)
                                                <ul style="margin: 0; padding-left: 14px;">
                                                    @foreach($training->syllabi as $syllab)
                                                        <li>{{ $syllab->title }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                {{ $training->syllabus_summary ?? '-' }}
                                            @endif
                                        </td>
                                    </tr>
                                </table>
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
