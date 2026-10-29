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
     * Canonical 49-column specification for PTKP Import.
     */
    public static function columns(): array
    {
        return PtkpColumnDefinitions::all();
    }

    /**
     * Exact flat list of 49 canonical header strings.
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
        return count(self::columns()); // 49
    }

    /**
     * Parent grouping specification for canonical 49 columns (A to AW).
     */
    public static function groups(): array
    {
        return [
            'general' => [
                'title'     => 'GENERAL',
                'start_col' => 'A',
                'end_col'   => 'A',
                'fill'      => '4B5563',
                'color'     => 'FFFFFF',
                'columns'   => [1],
            ],
            'job_information' => [
                'title'     => 'JOB INFORMATION',
                'start_col' => 'B',
                'end_col'   => 'H',
                'fill'      => '002B49',
                'color'     => 'FFFFFF',
                'columns'   => [2, 3, 4, 5, 6, 7, 8],
            ],
            'training_information' => [
                'title'     => 'TRAINING INFORMATION',
                'start_col' => 'I',
                'end_col'   => 'N',
                'fill'      => '0C5B9E',
                'color'     => 'FFFFFF',
                'columns'   => [9, 10, 11, 12, 13, 14],
            ],
            'classification' => [
                'title'     => 'CLASSIFICATION',
                'start_col' => 'O',
                'end_col'   => 'P',
                'fill'      => '1B4D3E',
                'color'     => 'FFFFFF',
                'columns'   => [15, 16],
            ],
            'delivery' => [
                'title'     => 'DELIVERY',
                'start_col' => 'Q',
                'end_col'   => 'R',
                'fill'      => '3B82F6',
                'color'     => 'FFFFFF',
                'columns'   => [17, 18],
            ],
            'provider_instructor' => [
                'title'     => 'PROVIDER & INSTRUCTOR',
                'start_col' => 'S',
                'end_col'   => 'W',
                'fill'      => '475569',
                'color'     => 'FFFFFF',
                'columns'   => [19, 20, 21, 22, 23],
            ],
            'duration_hours' => [
                'title'     => 'DURATION & LEARNING HOURS',
                'start_col' => 'X',
                'end_col'   => 'Y',
                'fill'      => '0284C7',
                'color'     => 'FFFFFF',
                'columns'   => [24, 25],
            ],
            'progress' => [
                'title'     => 'PROGRESS',
                'start_col' => 'Z',
                'end_col'   => 'AA',
                'fill'      => '16A34A',
                'color'     => 'FFFFFF',
                'columns'   => [26, 27],
            ],
            'schedule_requirement' => [
                'title'     => 'SCHEDULE & REQUIREMENT',
                'start_col' => 'AB',
                'end_col'   => 'AG',
                'fill'      => 'D97706',
                'color'     => 'FFFFFF',
                'columns'   => [28, 29, 30, 31, 32, 33],
            ],
            'attendance_bond' => [
                'title'     => 'ATTENDANCE & BOND',
                'start_col' => 'AH',
                'end_col'   => 'AM',
                'fill'      => '7C3AED',
                'color'     => 'FFFFFF',
                'columns'   => [34, 35, 36, 37, 38, 39],
            ],
            'evaluation_completion' => [
                'title'     => 'EVALUATION & COMPLETION',
                'start_col' => 'AN',
                'end_col'   => 'AR',
                'fill'      => 'BE185D',
                'color'     => 'FFFFFF',
                'columns'   => [40, 41, 42, 43, 44],
            ],
            'closing_verification' => [
                'title'     => 'CLOSING & VERIFICATION',
                'start_col' => 'AS',
                'end_col'   => 'AS',
                'fill'      => '059669',
                'color'     => 'FFFFFF',
                'columns'   => [45],
            ],
            'certificate_details' => [
                'title'     => 'CERTIFICATE DETAILS',
                'start_col' => 'AT',
                'end_col'   => 'AW',
                'fill'      => '0D9488',
                'color'     => 'FFFFFF',
                'columns'   => [46, 47, 48, 49],
            ],
        ];
    }

    /**
     * Column widths matching the 49 columns.
     */
    public static function columnWidths(): array
    {
        return [
            'A' => 8,  'B' => 16, 'C' => 30, 'D' => 12, 'E' => 25, 'F' => 20, 'G' => 25, 'H' => 25,
            'I' => 35, 'J' => 25, 'K' => 15, 'L' => 18, 'M' => 12, 'N' => 12, 'O' => 25, 'P' => 25,
            'Q' => 18, 'R' => 20, 'S' => 18, 'T' => 18, 'U' => 25, 'V' => 20, 'W' => 22, 'X' => 16,
            'Y' => 16, 'Z' => 14, 'AA' => 16, 'AB' => 14, 'AC' => 14, 'AD' => 14, 'AE' => 14, 'AF' => 16,
            'AG' => 15, 'AH' => 14, 'AI' => 25, 'AJ' => 14, 'AK' => 16, 'AL' => 15, 'AM' => 18, 'AN' => 20,
            'AO' => 14, 'AP' => 14, 'AQ' => 14, 'AR' => 14, 'AS' => 22, 'AT' => 22, 'AU' => 24, 'AV' => 22,
            'AW' => 22,
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
        $r2Col2 = mb_strtolower($row2Cells[2] ?? '', 'UTF-8');

        $isLegacyTwoRow = (
            str_contains($r1Col2, 'job information') ||
            $r1Col1 === 'general' ||
            str_contains($row1Joined, 'training information')
        ) && (
            str_contains($r2Col2, 'employee') ||
            str_contains($r2Col2, 'id') ||
            str_contains($r2Col2, 'nip')
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
     * Header analysis specifically for canonical 49 PTKP columns.
     */
    public static function analyzeHeaders(array $foundHeaders, string $format = 'single_row'): array
    {
        $columns = self::columns();
        $expectedCount = count($columns); // 49
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
