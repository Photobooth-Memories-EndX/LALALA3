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
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #1e293b;
            width: 100%;
        }

        /* Top Brand Accent */
        .brand-accent-bar {
            height: 6px;
            background-color: #E31B23; /* Krakatau Posco Red */
            width: 100%;
        }

        /* Header Container */
        .header-box {
            background-color: #002B49; /* Krakatau Posco Deep Navy */
            color: #ffffff;
            padding: 24px 30px;
            border-bottom: 4px solid #005BAC; /* PTKP Blue */
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
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #cbd5e1;
            margin-bottom: 4px;
        }
        .brand-title {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
        }
        .brand-desc {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* Header Badges on Right */
        .header-meta {
            text-align: right;
        }
        .total-schedules-badge {
            display: inline-block;
            background-color: #005BAC;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            padding: 6px 16px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .meta-timestamp {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 6px;
        }

        /* Empty State */
        .empty-container {
            text-align: center;
            padding: 60px 20px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin: 30px;
        }
        .empty-title {
            font-size: 18px;
            font-weight: bold;
            color: #002B49;
            margin-bottom: 8px;
        }
        .empty-text {
            font-size: 13px;
            color: #64748b;
        }

        /* Footer */
        .pdf-footer {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding: 16px 30px;
            background: #ffffff;
            width: 100%;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
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
                </td>
            </tr>
        </table>
    </div>

    <!-- 2-Column Grid Layout -->
    @if(count($trainings) > 0)
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; width: 100%; box-sizing: border-box; padding: 20px; background: #f8fafc;">
            @foreach($trainings as $training)
                <div class="card-box" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px 24px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden;">
                    <div>
                        <!-- Header Table: Plain Category Text (Left 45%) & Status/Close Date (Right 55%) -->
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed;">
                            <tr>
                                <!-- Left: Category Plain Bold Text -->
                                <td style="vertical-align: top; text-align: left; width: 45%; padding-top: 2px;">
                                    <span style="font-size: 13px; font-weight: 700; color: #005BAC; text-transform: capitalize;">
                                        {{ $training->category ?? 'General' }}
                                    </span>
                                </td>

                                <!-- Right: Status Text & Short Close Date (Strictly Right Aligned) -->
                                <td style="vertical-align: top; text-align: right; width: 55%; padding-right: 0;">
                                    @php
                                        $statusRaw = $training->status ?? 'Open Registration';
                                        $isOpen = in_array(strtolower($statusRaw), ['open', 'open registration']);
                                    @endphp
                                    <div style="font-size: 12px; font-weight: 700; color: {{ $isOpen ? '#15803d' : '#475569' }};">
                                        {{ $isOpen ? 'Open Registration' : 'Closed' }}
                                    </div>
                                    @if($training->registration_close_date)
                                        @php
                                            $closeTimeFormatted = '23:59';
                                            if (!empty($training->registration_close_time)) {
                                                $closeTimeFormatted = \Carbon\Carbon::parse($training->registration_close_time)->format('H:i');
                                            }
                                        @endphp
                                        <div style="color: #b91c1c; font-size: 11px; font-weight: 600; margin-top: 4px; line-height: 1.2;">
                                            Close: {{ \Carbon\Carbon::parse($training->registration_close_date)->format('d M Y') }} {{ $closeTimeFormatted }} WIB
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <!-- Title & Subtitle -->
                        <h3 style="font-size: 16px; font-weight: 800; color: #002B49; margin: 4px 0 2px 0; line-height: 1.35; word-break: break-word;">
                            {{ $training->title }}
                        </h3>
                        @if(!empty($training->subtitle) || !empty($training->sub_course_name))
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 10px;">
                                {{ $training->subtitle ?? $training->sub_course_name }}
                            </div>
                        @else
                            <div style="margin-bottom: 10px;"></div>
                        @endif

                        <!-- Full-Width Single Box Container for Date & Venue -->
                        <div style="width: 100%; border: 1px solid #64748b; border-radius: 4px; padding: 6px 12px; margin-bottom: 14px; box-sizing: border-box; background: #ffffff;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 12px; font-weight: 700; color: #0f172a;">
                                <tr>
                                    <td style="white-space: nowrap; width: 1%; padding-right: 12px; vertical-align: middle;">
                                        Date: {{ \Carbon\Carbon::parse($training->start_date)->format('d M Y') }}@if($training->end_date && $training->end_date != $training->start_date) - {{ \Carbon\Carbon::parse($training->end_date)->format('d M Y') }}@endif
                                    </td>
                                    <td style="white-space: nowrap; width: 1%; padding: 0 12px; color: #94a3b8; vertical-align: middle;">|</td>
                                    <td style="vertical-align: middle; color: #0f172a; word-break: break-word; line-height: 1.3;">
                                        Venue: {{ $training->location ?? $training->venue ?? $training->room_name ?? $training->room ?? '-' }}
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Details List -->
                        <table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 11.5px;">
                            <tr>
                                <td style="width: 85px; font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Target</td>
                                <td style="width: 15px; text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0; word-break: break-word;">{{ $training->target_employee ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Batch</td>
                                <td style="text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0;">{{ $training->batch ?? '1' }}</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Time</td>
                                <td style="text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0;">
                                    {{ \Carbon\Carbon::parse($training->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($training->end_time)->format('H:i') }} WIB
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Duration</td>
                                <td style="text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0;">
                                    @php
                                        $startTime = \Carbon\Carbon::parse($training->start_time);
                                        $endTime = \Carbon\Carbon::parse($training->end_time);
                                        $durationHours = $startTime->diffInHours($endTime);
                                    @endphp
                                    {{ $durationHours }} Hours
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Instructor</td>
                                <td style="text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0; word-break: break-word;">
                                    {{ $training->instructor_name ?? $training->instructor ?? '-' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Quota</td>
                                <td style="text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0;">{{ $training->quota ?? $training->participant_quota ?? '30' }} Seats</td>
                            </tr>
                            <tr>
                                <td style="font-weight: 600; color: #334155; padding: 2.5px 0; vertical-align: top;">Syllabus</td>
                                <td style="text-align: center; color: #334155; padding: 2.5px 0; vertical-align: top;">:</td>
                                <td style="color: #0f172a; padding: 2.5px 0; word-break: break-word;">
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
                </div>
            @endforeach
        </div>
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