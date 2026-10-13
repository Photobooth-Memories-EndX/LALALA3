<?php

namespace App\Services\Schedule;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TrainingScheduleImageService
{
    /**
     * Krakatau Posco / OWL brand palette.
     */
    protected const COLOR_DEEP_NAVY = [0, 43, 73];    // #002B49
    protected const COLOR_PTKP_BLUE  = [0, 91, 172];   // #005BAC
    protected const COLOR_RED_ACCENT = [227, 27, 35];  // #E31B23
    protected const COLOR_BG_LIGHT   = [244, 247, 250];// #F4F7FA
    protected const COLOR_WHITE      = [255, 255, 255];
    protected const COLOR_CARD_BORDER= [226, 232, 240];// #E2E8F0
    protected const COLOR_TEXT_DARK  = [15, 23, 42];   // #0F172A
    protected const COLOR_TEXT_MUTED = [100, 116, 139];// #64748B
    protected const COLOR_GREEN      = [22, 101, 52];  // #166534
    protected const COLOR_GREEN_BG   = [240, 253, 244];// #F0FDF4
    protected const COLOR_AMBER      = [180, 83, 9];   // #B45309
    protected const COLOR_AMBER_BG   = [254, 243, 199];// #FEF3C7
    protected const COLOR_BLUE_BG    = [238, 242, 255];// #EEF2FF

    /**
     * Generate JPG announcement card image as binary string.
     *
     * @param mixed $trainings
     * @param array $filterSummary
     * @param string|null $viewContent
     * @return string
     */
    public function generateJpg($trainings, array $filterSummary = [], ?string $viewContent = null, array $options = []): string
    {
        $items = $trainings instanceof Collection ? $trainings : collect($trainings);
        $items = $items->filter(function ($item) {
            return strtolower($item->status ?? '') !== 'running';
        });
        $count = $items->count();

        $fontBold = $this->getFontPath(true);
        $fontRegular = $this->getFontPath(false);

        // Dimensions (1920px standard viewport width matching reference, deviceScaleFactor: 2)
        $canvasWidth = $options['width'] ?? 1920;
        $deviceScaleFactor = $options['deviceScaleFactor'] ?? 2;
        $headerHeight = 220;
        $footerHeight = 70;
        $cardGapX = 40;
        $cardGapY = 30;
        $startX = 70;
        $cardWidth = (int) floor(($canvasWidth - (2 * $startX) - $cardGapX) / 2);
        $cardHeight = 380;

        $rowsCount = $count > 0 ? (int) ceil($count / 2) : 1;
        $contentHeight = max(400, ($rowsCount * $cardHeight) + (($rowsCount - 1) * $cardGapY) + 40);
        $canvasHeight = $headerHeight + $contentHeight + $footerHeight;

        $img = imagecreatetruecolor($canvasWidth, $canvasHeight);

        // Palette allocations
        $bgLight   = imagecolorallocate($img, ...self::COLOR_BG_LIGHT);
        $deepNavy  = imagecolorallocate($img, ...self::COLOR_DEEP_NAVY);
        $ptkpBlue  = imagecolorallocate($img, ...self::COLOR_PTKP_BLUE);
        $redAccent = imagecolorallocate($img, ...self::COLOR_RED_ACCENT);
        $white     = imagecolorallocate($img, ...self::COLOR_WHITE);
        $cardBorder= imagecolorallocate($img, ...self::COLOR_CARD_BORDER);
        $textDark  = imagecolorallocate($img, ...self::COLOR_TEXT_DARK);
        $textMuted = imagecolorallocate($img, ...self::COLOR_TEXT_MUTED);
        $greenText = imagecolorallocate($img, ...self::COLOR_GREEN);
        $greenBg   = imagecolorallocate($img, ...self::COLOR_GREEN_BG);
        $amberText = imagecolorallocate($img, ...self::COLOR_AMBER);
        $amberBg   = imagecolorallocate($img, ...self::COLOR_AMBER_BG);
        $blueBg    = imagecolorallocate($img, ...self::COLOR_BLUE_BG);

        // Fill background
        imagefilledrectangle($img, 0, 0, $canvasWidth, $canvasHeight, $bgLight);

        // Header Background
        imagefilledrectangle($img, 0, 0, $canvasWidth, $headerHeight, $deepNavy);

        // Top Red Accent Line
        imagefilledrectangle($img, 0, 0, $canvasWidth, 8, $redAccent);

        // Bottom Blue Accent Line of Header
        imagefilledrectangle($img, 0, $headerHeight - 6, $canvasWidth, $headerHeight, $ptkpBlue);

        // Header Typography
        $brandText = "PT KRAKATAU POSCO  •  OWL LEARNING HUB";
        $titleText = "TRAINING SCHEDULE ANNOUNCEMENT";
        $subText   = "Official Offline Training Calendar  •  OWL Learning Hub";

        if ($fontBold) {
            imagettftext($img, 14, 0, $startX, 50, $white, $fontBold, $brandText);
            imagettftext($img, 28, 0, $startX, 105, $white, $fontBold, $titleText);
            imagettftext($img, 13, 0, $startX, 145, $cardBorder, $fontRegular, $subText);
        } else {
            imagestring($img, 4, $startX, 35, $brandText, $white);
            imagestring($img, 5, $startX, 75, $titleText, $white);
            imagestring($img, 4, $startX, 120, $subText, $cardBorder);
        }

        // Header Right: Badge & Meta
        $totalBadgeText = "Total Schedules: {$count} Trainings";
        $badgeX = $canvasWidth - 360;
        $badgeY = 50;
        $badgeW = 300;
        $badgeH = 46;

        imagefilledrectangle($img, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $ptkpBlue);
        imagerectangle($img, $badgeX, $badgeY, $badgeX + $badgeW, $badgeY + $badgeH, $white);

        if ($fontBold) {
            imagettftext($img, 14, 0, $badgeX + 22, $badgeY + 30, $white, $fontBold, $totalBadgeText);
        } else {
            imagestring($img, 4, $badgeX + 20, $badgeY + 15, $totalBadgeText, $white);
        }

        $metaText = "Generated: " . Carbon::now()->format('d M Y, H:i') . " WIB";
        if ($fontRegular) {
            imagettftext($img, 11, 0, $badgeX + 40, $badgeY + 80, $cardBorder, $fontRegular, $metaText);
        } else {
            imagestring($img, 3, $badgeX + 30, $badgeY + 70, $metaText, $cardBorder);
        }

        // Active Filter Summary in Header
        if (!empty($filterSummary)) {
            $filterStr = "Active Filters: " . implode("  |  ", $filterSummary);
            if ($fontRegular) {
                imagettftext($img, 11, 0, $startX, $headerHeight - 20, $white, $fontRegular, $filterStr);
            } else {
                imagestring($img, 3, $startX, $headerHeight - 25, $filterStr, $white);
            }
        }

        // Render Cards
        $curY = $headerHeight + 30;

        if ($count === 0) {
            // Empty State Card
            $emptyW = $canvasWidth - ($startX * 2);
            $emptyH = 260;
            imagefilledrectangle($img, $startX, $curY, $startX + $emptyW, $curY + $emptyH, $white);
            imagerectangle($img, $startX, $curY, $startX + $emptyW, $curY + $emptyH, $cardBorder);
            imagefilledrectangle($img, $startX, $curY, $startX + $emptyW, $curY + 6, $ptkpBlue);

            $emptyMsg1 = "No Training Schedules Found";
            $emptyMsg2 = "No PTKP training schedules match the selected filters criteria.";
            if ($fontBold) {
                imagettftext($img, 20, 0, $startX + 60, $curY + 110, $deepNavy, $fontBold, $emptyMsg1);
                imagettftext($img, 14, 0, $startX + 60, $curY + 160, $textMuted, $fontRegular, $emptyMsg2);
            } else {
                imagestring($img, 5, $startX + 60, $curY + 90, $emptyMsg1, $deepNavy);
                imagestring($img, 4, $startX + 60, $curY + 130, $emptyMsg2, $textMuted);
            }
        } else {
            foreach ($items as $idx => $item) {
                $colIdx = $idx % 2;
                $rowIdx = (int) floor($idx / 2);

                $cardX = $startX + ($colIdx * ($cardWidth + $cardGapX));
                $cardY = $curY + ($rowIdx * ($cardHeight + $cardGapY));

                // Draw Card Box
                imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardWidth, $cardY + $cardHeight, $white);
                imagerectangle($img, $cardX, $cardY, $cardX + $cardWidth, $cardY + $cardHeight, $cardBorder);

                // Top Accent Bar (PTKP Blue)
                imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardWidth, $cardY + 5, $ptkpBlue);

                // 1. Top Badges: Category & Status (with Close Reg date beneath badge)
                $cat = $item->category ?? 'General';
                $status = $item->status ?? 'Open Registration';

                // Category pill
                imagefilledrectangle($img, $cardX + 18, $cardY + 16, $cardX + 18 + 150, $cardY + 40, $blueBg);
                imagerectangle($img, $cardX + 18, $cardY + 16, $cardX + 18 + 150, $cardY + 40, $cardBorder);
                if ($fontBold) {
                    imagettftext($img, 9, 0, $cardX + 26, $cardY + 33, $ptkpBlue, $fontBold, mb_strimwidth($cat, 0, 18, '...'));
                } else {
                    imagestring($img, 2, $cardX + 24, $cardY + 22, mb_strimwidth($cat, 0, 16, '...'), $ptkpBlue);
                }

                // Status pill
                $isClosed = str_contains(strtolower($status), 'closed');
                $statusLabel = $isClosed ? 'Closed' : 'Open Registration';
                $statusBg = $isClosed ? $cardBorder : $greenBg;
                $statusColor = $isClosed ? $textMuted : $greenText;
                $statPillW = 145;
                $statX = $cardX + $cardWidth - $statPillW - 18;
                imagefilledrectangle($img, $statX, $cardY + 16, $statX + $statPillW, $cardY + 40, $statusBg);
                imagerectangle($img, $statX, $cardY + 16, $statX + $statPillW, $cardY + 40, $cardBorder);
                if ($fontBold) {
                    imagettftext($img, 9, 0, $statX + 12, $cardY + 33, $statusColor, $fontBold, $statusLabel);
                } else {
                    imagestring($img, 2, $statX + 10, $cardY + 22, $statusLabel, $statusColor);
                }

                // Close reg date beneath badge
                $regCloses = $item->registration_close_date ? Carbon::parse($item->registration_close_date)->format('d M Y') : 'Until Started';
                if ($item->registration_close_time) {
                    $rawTime = trim($item->registration_close_time);
                    $regCloses .= ' ' . (strlen($rawTime) === 5 ? $rawTime . ':00' : $rawTime);
                } else {
                    $regCloses .= ' 23:59:00';
                }
                $closeBadgeText = "Close: {$regCloses} WIB";
                if ($fontRegular) {
                    imagettftext($img, 8, 0, $statX + 8, $cardY + 54, $redAccent, $fontRegular, $closeBadgeText);
                } else {
                    imagestring($img, 1, $statX + 8, $cardY + 44, $closeBadgeText, $redAccent);
                }

                // 2. Training Title (Bold, PTKP Navy)
                $title = $item->title ?? ($item->course_name ?? 'Training Title');
                $lines = $this->wrapText($title, 55, 2);
                $titleY = $cardY + 76;
                foreach ($lines as $lIndex => $line) {
                    if ($fontBold) {
                        imagettftext($img, 13, 0, $cardX + 18, $titleY + ($lIndex * 20), $deepNavy, $fontBold, $line);
                    } else {
                        imagestring($img, 4, $cardX + 18, $titleY - 8 + ($lIndex * 18), $line, $deepNavy);
                    }
                }
                if (!empty($item->sub_course_name) && count($lines) <= 1) {
                    $subTitle = mb_strimwidth($item->sub_course_name, 0, 42, '...');
                    if ($fontRegular) {
                        imagettftext($img, 9, 0, $cardX + 18, $titleY + 22, $textMuted, $fontRegular, $subTitle);
                    } else {
                        imagestring($img, 2, $cardX + 18, $titleY + 12, $subTitle, $textMuted);
                    }
                }

                // 3. Single Full-Width Box Container for Date & Venue
                $startDate = $item->start_date ? Carbon::parse($item->start_date)->format('d M Y') : '-';
                $endDate = $item->end_date ? Carbon::parse($item->end_date)->format('d M Y') : $startDate;
                $schedulePeriod = ($startDate === $endDate || empty($item->end_date)) ? $startDate : "{$startDate} - {$endDate}";
                $venue = $item->location ?? ($item->venue ?? ($item->room_name ?? ($item->room ?? '-')));

                $boxX = $cardX + 18;
                $boxY = $cardY + 112;
                $boxW = $cardWidth - 36;
                $boxH = 26;
                $slateBorder = imagecolorallocate($img, 100, 116, 139);

                imagefilledrectangle($img, $boxX, $boxY, $boxX + $boxW, $boxY + $boxH, $white);
                imagerectangle($img, $boxX, $boxY, $boxX + $boxW, $boxY + $boxH, $slateBorder);

                $dateVenueText = "Date: {$schedulePeriod}   |   Venue: " . mb_strimwidth($venue, 0, 52, '...');
                if ($fontBold) {
                    imagettftext($img, 9, 0, $boxX + 12, $boxY + 17, $textDark, $fontBold, $dateVenueText);
                } else {
                    imagestring($img, 3, $boxX + 12, $boxY + 6, $dateVenueText, $textDark);
                }

                // 4. Target
                $target = $item->target_employee ?? '-';
                $targetLine = "Target: " . mb_strimwidth($target, 0, 44, '...');

                // 5. Batch
                $batchLine = "Batch: " . ($item->batch ?? '1');

                // 6. Time
                $timeLine = "Time: -";
                if (!empty($item->start_time) && !empty($item->end_time)) {
                    $timeLine = "Time: " . Carbon::parse($item->start_time)->format('H:i') . " - " . Carbon::parse($item->end_time)->format('H:i') . " WIB";
                } elseif (!empty($item->start_time)) {
                    $timeLine = "Time: " . Carbon::parse($item->start_time)->format('H:i') . " WIB";
                }

                // 7. Duration
                $durationHours = 0;
                if (!empty($item->start_time) && !empty($item->end_time)) {
                    $sT = Carbon::parse($item->start_time);
                    $eT = Carbon::parse($item->end_time);
                    $diff = $sT->diffInHours($eT);
                    $durationHours = $diff > 0 ? $diff : 1;
                }
                $durationLine = "Duration: " . ($durationHours > 0 ? "{$durationHours} Hours" : "-");

                // 8. Instructor
                $instructor = $item->instructor_name ?? ($item->instructor ?? '-');
                $instructorLine = "Instructor: " . mb_strimwidth($instructor, 0, 36, '...');

                // 9. Quota
                $quota = $item->quota ?? ($item->participant_quota ?? 30);
                $quotaLine = "Quota: {$quota} Seats";

                // 10. Syllabus Summary
                if (isset($item->syllabi) && is_iterable($item->syllabi) && count($item->syllabi) > 0) {
                    $syllabusSummary = collect($item->syllabi)->pluck('title')->implode(', ');
                } else {
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
                }
                $syllabusLine = "Syllabus: " . mb_strimwidth($item->syllabus_summary ?? ($syllabusSummary ?: '-'), 0, 44, '...');

                $details = [
                    $targetLine,
                    $batchLine,
                    $timeLine,
                    $durationLine,
                    $instructorLine,
                    $quotaLine,
                    $syllabusLine,
                ];

                $detY = $cardY + 158;
                foreach ($details as $dIdx => $textLine) {
                    $yPos = $detY + ($dIdx * 23);
                    if ($fontRegular) {
                        imagettftext($img, 9, 0, $cardX + 18, $yPos, $textDark, $fontRegular, $textLine);
                    } else {
                        imagestring($img, 2, $cardX + 18, $yPos - 10, $textLine, $textDark);
                    }
                }
            }
        }

        // Footer
        $footerY = $canvasHeight - $footerHeight;
        imagefilledrectangle($img, 0, $footerY, $canvasWidth, $canvasHeight, $deepNavy);
        imagefilledrectangle($img, 0, $footerY, $canvasWidth, $footerY + 3, $redAccent);

        $footerText = "PT KRAKATAU POSCO Learning & Development Center  •  Generated via OWL Learning Hub";
        if ($fontRegular) {
            imagettftext($img, 11, 0, $startX, $canvasHeight - 26, $white, $fontRegular, $footerText);
        } else {
            imagestring($img, 3, $startX, $canvasHeight - 40, $footerText, $white);
        }

        ob_start();
        imagejpeg($img, null, 92);
        $output = ob_get_clean();
        imagedestroy($img);

        return (string) $output;
    }

    /**
     * Resolve valid TTF font path on Windows / Linux systems.
     *
     * @param bool $bold
     * @return string|null
     */
    protected function getFontPath(bool $bold = false): ?string
    {
        $candidates = $bold
            ? [
                'C:/Windows/Fonts/arialbd.ttf',
                'C:/Windows/Fonts/segoeuib.ttf',
                'C:/Windows/Fonts/calibrib.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            ]
            : [
                'C:/Windows/Fonts/arial.ttf',
                'C:/Windows/Fonts/segoeui.ttf',
                'C:/Windows/Fonts/calibri.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Wrap text into lines.
     *
     * @param string $text
     * @param int $width
     * @param int $maxLines
     * @return array
     */
    protected function wrapText(string $text, int $width = 36, int $maxLines = 2): array
    {
        $wrapped = explode("\n", wordwrap($text, $width, "\n", true));
        if (count($wrapped) > $maxLines) {
            $wrapped = array_slice($wrapped, 0, $maxLines);
            $wrapped[$maxLines - 1] = rtrim($wrapped[$maxLines - 1]) . '...';
        }
        return $wrapped;
    }
}
