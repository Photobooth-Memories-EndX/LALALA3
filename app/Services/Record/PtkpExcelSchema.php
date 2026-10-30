<?php

namespace App\Services\Record;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PtkpExcelSchema
{
    public const TEMPLATE_FILENAME = 'PTKP_OWL_Import_Template.xlsx';
    public const DATA_SHEET_TITLE = 'PTKP OWL Import';
    public const INSTRUCTIONS_SHEET_TITLE = 'Instructions';

    /**
     * Canonical 48-column specification for PTKP Import.
     */
    public static function columns(): array
    {
        return PtkpColumnDefinitions::all();
    }

    /**
     * Exact flat list of 48 canonical header strings.
     */
    public static function headers(): array
    {
        $headers = [];
        foreach (self::columns() as $col) {
            $headers[] = $col['header'];
        }
        return $headers;
    }

    public static function totalColumns(): int
    {
        return count(self::columns()); // 48
    }

    /**
     * Parent grouping specification for canonical 48 columns (A to AV).
     */
    public static function groups(): array
    {
        return [
            'status' => [
                'title'     => 'STATUS',
                'start_col' => 'A',
                'end_col'   => 'B',
                'fill'      => '4B5563',
                'color'     => 'FFFFFF',
                'columns'   => [1, 2],
            ],
            'participant_profile' => [
                'title'     => 'PARTICIPANT PROFILE',
                'start_col' => 'C',
                'end_col'   => 'I',
                'fill'      => '002B49',
                'color'     => 'FFFFFF',
                'columns'   => [3, 4, 5, 6, 7, 8, 9],
            ],
            'training_information' => [
                'title'     => 'TRAINING INFORMATION',
                'start_col' => 'J',
                'end_col'   => 'O',
                'fill'      => '0C5B9E',
                'color'     => 'FFFFFF',
                'columns'   => [10, 11, 12, 13, 14, 15],
            ],
            'classification' => [
                'title'     => 'CLASSIFICATION',
                'start_col' => 'P',
                'end_col'   => 'Q',
                'fill'      => '1B4D3E',
                'color'     => 'FFFFFF',
                'columns'   => [16, 17],
            ],
            'method_delivery' => [
                'title'     => 'METHOD & DELIVERY',
                'start_col' => 'R',
                'end_col'   => 'S',
                'fill'      => '3B82F6',
                'color'     => 'FFFFFF',
                'columns'   => [18, 19],
            ],
            'organizer_logistics' => [
                'title'     => 'ORGANIZER & LOGISTICS',
                'start_col' => 'T',
                'end_col'   => 'X',
                'fill'      => '475569',
                'color'     => 'FFFFFF',
                'columns'   => [20, 21, 22, 23, 24],
            ],
            'progress_schedule' => [
                'title'     => 'PROGRESS & SCHEDULE',
                'start_col' => 'Y',
                'end_col'   => 'AE',
                'fill'      => '0284C7',
                'color'     => 'FFFFFF',
                'columns'   => [25, 26, 27, 28, 29, 30, 31],
            ],
            'compliance' => [
                'title'     => 'COMPLIANCE',
                'start_col' => 'AF',
                'end_col'   => 'AG',
                'fill'      => 'D97706',
                'color'     => 'FFFFFF',
                'columns'   => [32, 33],
            ],
            'service_bond' => [
                'title'     => 'SERVICE BOND',
                'start_col' => 'AH',
                'end_col'   => 'AL',
                'fill'      => '7C3AED',
                'color'     => 'FFFFFF',
                'columns'   => [34, 35, 36, 37, 38],
            ],
            'evaluation' => [
                'title'     => 'EVALUATION (L1-L3)',
                'start_col' => 'AM',
                'end_col'   => 'AQ',
                'fill'      => 'BE185D',
                'color'     => 'FFFFFF',
                'columns'   => [39, 40, 41, 42, 43],
            ],
            'certificate' => [
                'title'     => 'CERTIFICATE',
                'start_col' => 'AR',
                'end_col'   => 'AU',
                'fill'      => '0D9488',
                'color'     => 'FFFFFF',
                'columns'   => [44, 45, 46, 47],
            ],
            'administration' => [
                'title'     => 'ADMINISTRATION',
                'start_col' => 'AV',
                'end_col'   => 'AV',
                'fill'      => '059669',
                'color'     => 'FFFFFF',
                'columns'   => [48],
            ],
        ];
    }

    /**
     * Column widths matching the 48 columns.
     */
    public static function columnWidths(): array
    {
        return [
            'A' => 12, 'B' => 8,  'C' => 16, 'D' => 30, 'E' => 12, 'F' => 25, 'G' => 20, 'H' => 25,
            'I' => 25, 'J' => 35, 'K' => 25, 'L' => 15, 'M' => 18, 'N' => 12, 'O' => 12, 'P' => 25,
            'Q' => 25, 'R' => 18, 'S' => 20, 'T' => 18, 'U' => 18, 'V' => 25, 'W' => 20, 'X' => 22,
            'Y' => 16, 'Z' => 14, 'AA' => 14, 'AB' => 14, 'AC' => 14, 'AD' => 14, 'AE' => 16, 'AF' => 15,
            'AG' => 14, 'AH' => 25, 'AI' => 14, 'AJ' => 16, 'AK' => 15, 'AL' => 18, 'AM' => 20, 'AN' => 14,
            'AO' => 14, 'AP' => 14, 'AQ' => 14, 'AR' => 22, 'AS' => 24, 'AT' => 22, 'AU' => 22, 'AV' => 24,
        ];
    }

    /**
     * Normalize header string for consistent, resilient comparison.
     */
    public static function normalizeHeaderString(?string $header): string
    {
        if ($header === null) {
            return '';
        }

        $h = trim($header);
        $h = mb_strtolower($h, 'UTF-8');
        $h = str_replace(['_', '-', '/', '\\'], ' ', $h);
        $h = preg_replace('/[^\p{L}\p{N}\s\']/u', ' ', $h);
        $h = preg_replace('/\s+/', ' ', $h);

        return trim($h);
    }

    /**
     * Detect single-row vs legacy two-row grouped format in PTKP workbook.
     */
    public static function detectTemplateFormat(Worksheet $worksheet): array
    {
        $highestColumn = $worksheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $row1Cells = [];
        $row2Cells = [];

        for ($col = 1; $col <= max($highestColumnIndex, count(self::columns())); $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $r1Val = $worksheet->getCell($colLetter . '1')->getValue();
            $r2Val = $worksheet->getCell($colLetter . '2')->getValue();
            $row1Cells[$col] = $r1Val !== null ? trim((string) $r1Val) : '';
            $row2Cells[$col] = $r2Val !== null ? trim((string) $r2Val) : '';
        }

        $row1Joined = mb_strtolower(implode(' ', $row1Cells), 'UTF-8');
        $r1Col1 = mb_strtolower($row1Cells[1] ?? '', 'UTF-8');
        $r1Col2 = mb_strtolower($row1Cells[2] ?? '', 'UTF-8');
        $r1Col3 = mb_strtolower($row1Cells[3] ?? '', 'UTF-8');
        $r2Col1 = mb_strtolower($row2Cells[1] ?? '', 'UTF-8');
        $r2Col2 = mb_strtolower($row2Cells[2] ?? '', 'UTF-8');
        $r2Col3 = mb_strtolower($row2Cells[3] ?? '', 'UTF-8');

        $row1HasCourseHeader = str_contains($row1Joined, 'course title') ||
                               str_contains($row1Joined, 'course name') ||
                               str_contains($row1Joined, 'nama course') ||
                               str_contains($row1Joined, 'judul');

        $row2Joined = mb_strtolower(implode(' ', $row2Cells), 'UTF-8');
        $row2HasCourseHeader = str_contains($row2Joined, 'course title') ||
                               str_contains($row2Joined, 'course name') ||
                               str_contains($row2Joined, 'nama course') ||
                               str_contains($row2Joined, 'judul');

        $isLegacyTwoRow = !$row1HasCourseHeader && (
            str_contains($r1Col3, 'participant profile') ||
            str_contains($r1Col3, 'job information') ||
            $r1Col1 === 'status' ||
            $r1Col1 === 'general' ||
            str_contains($row1Joined, 'participant profile') ||
            str_contains($row1Joined, 'job information') ||
            str_contains($row1Joined, 'training information') ||
            str_contains($row1Joined, 'evaluation')
        ) && (
            $row2HasCourseHeader ||
            str_contains($r2Col3, 'employee') ||
            in_array($r2Col3, ['id', 'nip'], true) ||
            in_array($r2Col2, ['no', 'no.', 'nomor'], true) ||
            str_contains($r2Col1, 'status')
        );

        if ($isLegacyTwoRow) {
            $foundHeaders = [];
            $lastNonEmpty = 0;
            for ($c = 1; $c <= count($row2Cells); $c++) {
                if ($row2Cells[$c] !== '') {
                    $lastNonEmpty = $c;
                }
            }
            $targetColCount = $lastNonEmpty;
            for ($c = 1; $c <= $targetColCount; $c++) {
                $foundHeaders[$c] = $row2Cells[$c] ?? '';
            }

            return [
                'format'         => 'legacy_two_row',
                'header_row'     => 2,
                'data_start_row' => 3,
                'group_row'      => 1,
                'detected_count' => $targetColCount,
                'found_headers'  => $foundHeaders,
            ];
        }

        $foundHeaders = [];
        $lastNonEmpty = 0;
        for ($c = 1; $c <= count($row1Cells); $c++) {
            if ($row1Cells[$c] !== '') {
                $lastNonEmpty = $c;
            }
        }
        $targetColCount = $lastNonEmpty;
        for ($c = 1; $c <= $targetColCount; $c++) {
            $foundHeaders[$c] = $row1Cells[$c] ?? '';
        }

        return [
            'format'         => 'single_row',
            'header_row'     => 1,
            'data_start_row' => 2,
            'group_row'      => null,
            'detected_count' => $targetColCount,
            'found_headers'  => $foundHeaders,
        ];
    }

    /**
     * Header analysis specifically for canonical 48 PTKP columns.
     */
    public static function analyzeHeaders(array $foundHeaders, string $format = 'single_row'): array
    {
        $columns = self::columns();
        $expectedCount = count($columns); // 48
        $detectedCount = count($foundHeaders);

        $canonicalMap = [];
        foreach ($columns as $idx => $col) {
            $canonicalMap[$idx] = [
                'col'         => $idx,
                'letter'      => Coordinate::stringFromColumnIndex($idx),
                'header'      => $col['header'],
                'norm_header' => self::normalizeHeaderString($col['header']),
                'key'         => $col['key'],
                'required'    => $col['required'],
                'aliases'     => array_map([self::class, 'normalizeHeaderString'], $col['aliases'] ?? []),
            ];
        }

        $columnResults = [];
        $issues = [];
        $validCount = 0;
        $autoMatchedCount = 0;
        $warningCount = 0;
        $invalidCount = 0;
        $missingCount = 0;

        $maxCol = max($expectedCount, $detectedCount);

        for ($pos = 1; $pos <= $maxCol; $pos++) {
            $colLetter = Coordinate::stringFromColumnIndex($pos);
            $rawFound = isset($foundHeaders[$pos]) ? trim((string) $foundHeaders[$pos]) : '';
            $normFound = self::normalizeHeaderString($rawFound);
            $canonical = $canonicalMap[$pos] ?? null;

            if ($pos > $expectedCount) {
                if ($rawFound !== '') {
                    $invalidCount++;
                    $issue = [
                        'col'       => $pos,
                        'letter'    => $colLetter,
                        'expected'  => null,
                        'found'     => $rawFound,
                        'status'    => 'INVALID',
                        'mapped_to' => null,
                        'key'       => null,
                        'message'   => "Unexpected extra column {$pos} ({$colLetter}: '{$rawFound}'). PTKP template specifies exactly {$expectedCount} columns.",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                }
                continue;
            }

            if ($rawFound === '') {
                $missingCount++;
                $issue = [
                    'col'       => $pos,
                    'letter'    => $colLetter,
                    'expected'  => $canonical['header'],
                    'found'     => '',
                    'status'    => 'MISSING',
                    'mapped_to' => null,
                    'key'       => $canonical['key'],
                    'message'   => "Missing header at column {$pos} ({$colLetter}: '{$canonical['header']}').",
                ];
                $columnResults[$pos] = $issue;
                $issues[] = $issue;
                continue;
            }

            if (mb_strtolower($rawFound, 'UTF-8') === mb_strtolower($canonical['header'], 'UTF-8')) {
                $validCount++;
                $columnResults[$pos] = [
                    'col'       => $pos,
                    'letter'    => $colLetter,
                    'expected'  => $canonical['header'],
                    'found'     => $rawFound,
                    'status'    => 'VALID',
                    'mapped_to' => $canonical['header'],
                    'key'       => $canonical['key'],
                    'message'   => 'Exact canonical match.',
                ];
                continue;
            }

            if ($normFound === $canonical['norm_header'] || in_array($normFound, $canonical['aliases'], true)) {
                $autoMatchedCount++;
                $columnResults[$pos] = [
                    'col'       => $pos,
                    'letter'    => $colLetter,
                    'expected'  => $canonical['header'],
                    'found'     => $rawFound,
                    'status'    => 'AUTO_MATCHED',
                    'mapped_to' => $canonical['header'],
                    'key'       => $canonical['key'],
                    'message'   => "Matched via alias / normalization ('{$rawFound}' -> '{$canonical['header']}').",
                ];
                continue;
            }

            $invalidCount++;
            $issue = [
                'col'       => $pos,
                'letter'    => $colLetter,
                'expected'  => $canonical['header'],
                'found'     => $rawFound,
                'status'    => 'INVALID',
                'mapped_to' => null,
                'key'       => $canonical['key'],
                'message'   => "Column {$pos} ({$colLetter}): expected '{$canonical['header']}', found '{$rawFound}'.",
            ];
            $columnResults[$pos] = $issue;
            $issues[] = $issue;
        }

        $canProceed = ($invalidCount === 0 && $missingCount === 0);

        return [
            'can_proceed'        => $canProceed,
            'expected_count'     => $expectedCount,
            'detected_count'     => $detectedCount,
            'valid_count'        => $validCount,
            'auto_matched_count' => $autoMatchedCount,
            'warning_count'      => $warningCount,
            'invalid_count'      => $invalidCount,
            'missing_count'      => $missingCount,
            'columns'            => $columnResults,
            'issues'             => $issues,
        ];
    }
}
