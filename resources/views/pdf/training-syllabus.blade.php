<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Training Syllabus - {{ $training->title ?? $training->course_name }}</title>
    <style>
        @page {
            margin: 28px 32px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #003b6d;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-title {
            font-size: 18pt;
            font-weight: bold;
            color: #003b6d;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .sub-title {
            font-size: 9pt;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            font-size: 9pt;
            font-weight: bold;
            padding: 4px 10px;
            border-radius: 4px;
            text-align: right;
            display: inline-block;
        }
        .training-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .training-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .meta-table {
            width: 100%;
            font-size: 9.5pt;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 120px;
            color: #64748b;
            font-weight: bold;
        }
        .meta-value {
            color: #1e293b;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #003b6d;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 16px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-content {
            font-size: 10pt;
            color: #334155;
            margin-bottom: 12px;
            text-align: justify;
        }
        .topic-item {
            margin-bottom: 10px;
            padding: 8px 12px;
            background-color: #ffffff;
            border-left: 3px solid #003b6d;
            border-top: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }
        .topic-header {
            font-weight: bold;
            color: #0f172a;
            font-size: 10.5pt;
            margin-bottom: 3px;
        }
        .topic-desc {
            font-size: 9.5pt;
            color: #475569;
            margin: 0;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            text-align: center;
        }
        ul {
            margin: 4px 0 6px 18px;
            padding: 0;
        }
        li {
            font-size: 10pt;
            color: #334155;
            margin-bottom: 3px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                <div class="logo-title">PT KRAKATAU POSCO</div>
                <div class="sub-title">Online Working &amp; Learning (KP-OWL) &bull; Training Management</div>
            </td>
            <td style="text-align: right;">
                <div class="doc-badge">OFFICIAL SYLLABUS</div>
                <div class="sub-title" style="margin-top: 4px;">Generated on {{ \Carbon\Carbon::now()->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="training-card">
        <div class="training-title">{{ $training->title ?? $training->course_name }}</div>
        <table class="meta-table">
            <tr>
                <td class="meta-label">Training Code:</td>
                <td class="meta-value">{{ $training->training_code ?? ('PTKP-TR-' . $training->id) }}</td>
                <td class="meta-label">Category:</td>
                <td class="meta-value">{{ $training->category ?? 'General' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Instructor:</td>
                <td class="meta-value">{{ $training->instructor ?? '-' }}</td>
                <td class="meta-label">Location / Room:</td>
                <td class="meta-value">{{ $training->location ?? ($training->venue ?? '-') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Schedule Date:</td>
                <td class="meta-value">
                    @if($training->start_date)
                        {{ \Carbon\Carbon::parse($training->start_date)->format('d M Y') }}
                        @if($training->end_date && $training->start_date != $training->end_date)
                            - {{ \Carbon\Carbon::parse($training->end_date)->format('d M Y') }}
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td class="meta-label">Session Time:</td>
                <td class="meta-value">
                    {{ substr($training->start_time ?? '08:00', 0, 5) }} - {{ substr($training->end_time ?? '16:00', 0, 5) }} WIB
                </td>
            </tr>
            @if(!empty($training->quota))
            <tr>
                <td class="meta-label">Capacity / Quota:</td>
                <td class="meta-value">{{ $training->quota }} Seats</td>
                <td class="meta-label">Status:</td>
                <td class="meta-value">{{ $training->status ?? 'Open Registration' }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if (!empty($training->description))
        <div class="section-title">1. Course Overview &amp; Description</div>
        <div class="section-content">
            {!! nl2br(e($training->description)) !!}
        </div>
    @endif

    @if (!empty($training->learning_objectives))
        <div class="section-title">2. Learning Objectives</div>
        <div class="section-content">
            @php
                $objLines = preg_split('/\r?\n/', $training->learning_objectives);
            @endphp
            @if(count($objLines) > 1)
                <ul>
                    @foreach($objLines as $line)
                        @php
                            $trimmed = trim(preg_replace('/^[•\-\*]\s*/', '', $line));
                        @endphp
                        @if(!empty($trimmed))
                            <li>{{ $trimmed }}</li>
                        @endif
                    @endforeach
                </ul>
            @else
                <p style="margin: 0;">{{ $training->learning_objectives }}</p>
            @endif
        </div>
    @endif

    <div class="section-title">3. Syllabus Modules &amp; Topics</div>
    <div class="section-content">
        @php
            $syllabusList = [];
            if (is_array($training->syllabus)) {
                $syllabusList = $training->syllabus;
            } elseif (is_string($training->syllabus)) {
                $decoded = json_decode($training->syllabus, true);
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }
                $syllabusList = is_array($decoded) ? $decoded : [];
            }
        @endphp

        @forelse($syllabusList as $idx => $topic)
            @php
                $topicTitle = $topic['topic'] ?? ($topic['title'] ?? ('Module ' . ($idx + 1)));
                $topicDesc = $topic['description'] ?? ($topic['outline'] ?? '');
            @endphp
            <div class="topic-item">
                <div class="topic-header">{{ $idx + 1 }}. {{ $topicTitle }}</div>
                @if(!empty($topicDesc))
                    @if(is_array($topicDesc))
                        <ul>
                            @foreach($topicDesc as $pt)
                                <li>{{ $pt }}</li>
                            @endforeach
                        </ul>
                    @elseif(str_contains($topicDesc, "\n"))
                        <ul>
                            @foreach(preg_split('/\r?\n/', $topicDesc) as $pt)
                                @if(trim($pt))
                                    <li>{{ trim(preg_replace('/^[•\-\*]\s*/', '', $pt)) }}</li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <div class="topic-desc">{{ $topicDesc }}</div>
                    @endif
                @else
                    <div class="topic-desc">Standard module coverage and competencies.</div>
                @endif
            </div>
        @empty
            <p style="font-style: italic; color: #94a3b8;">No detailed syllabus outline registered for this training program.</p>
        @endforelse
    </div>

    <div class="footer">
        PT Krakatau Posco &bull; Learning &amp; Development Department &bull; KP-OWL Training Management
    </div>

</body>
</html>
