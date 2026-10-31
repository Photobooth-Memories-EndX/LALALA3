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
     * Canonical 46-column specification for PTKP Import.
     */
    public static function columns(): array
    {
        return PtkpColumnDefinitions::all();
    }

    /**
     * Exact flat list of 46 canonical header strings.
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
        return count(self::columns()); // 46
    }

    /**
     * Group definitions (empty array for simplified single-row header template).
     */
    public static function groups(): array
    {
        return [];
    }

    /**
     * Column widths matching the 46 canonical columns (A to AT).
     */
    public static function columnWidths(): array
    {
        return [
            'A'  => 16, // Employee ID
            'B'  => 30, // Employee Name
            'C'  => 12, // Grade
            'D'  => 25, // Job Position
            'E'  => 20, // Team
            'F'  => 25, // Department
            'G'  => 25, // Company
            'H'  => 35, // Course Title
            'I'  => 25, // Sub Course Title
            'J'  => 15, // Type
            'K'  => 18, // Category
            'L'  => 12, // Level
            'M'  => 12, // Batch
            'N'  => 25, // Main Classification
            'O'  => 25, // Sub Classification
            'P'  => 18, // Delivery Method
            'Q'  => 20, // Sub Delivery Method
            'R'  => 18, // Provider
            'S'  => 18, // Sub Provider
            'T'  => 25, // Provider Name
            'U'  => 20, // Venue
            'V'  => 22, // Instructor
            'W'  => 16, // Learning Hours
            'X'  => 14, // % Progress
            'Y'  => 14, // Start Date
            'Z'  => 14, // End Date
            'AA' => 14, // Start Time
            'AB' => 14, // End Time
            'AC' => 16, // Record Month
            'AD' => 15, // Requirement
            'AE' => 14, // Attendance
            'AF' => 25, // Attendance Reason
            'AG' => 14, // Bond Status
            'AH' => 16, // Bond Duration
            'AI' => 15, // Bond End Date
            'AJ' => 18, // Bond Amount
            'AK' => 20, // Score (L1) - Course
            'AL' => 14, // Score (L2)
            'AM' => 14, // L2 Status
            'AN' => 14, // Score (L3)
            'AO' => 14, // L3 Status
            'AP' => 22, // Certificate Availability
            'AQ' => 24, // Certificate Institution
            'AR' => 22, // Certificate Issued Date
            'AS' => 22, // Certificate Expired Date
            'AT' => 24, // Person in Charge (PIC)
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
        $row2Joined = mb_strtolower(implode(' ', $row2Cells), 'UTF-8');
        $row2HasCourseHeader = str_contains($row2Joined, 'course title') ||
                               str_contains($row2Joined, 'course name') ||
                               str_contains($row2Joined, 'nama course') ||
                               str_contains($row2Joined, 'judul');

        $row1HasCourseHeader = str_contains($row1Joined, 'course title') ||
                               str_contains($row1Joined, 'course name') ||
                               str_contains($row1Joined, 'nama course') ||
                               str_contains($row1Joined, 'judul');

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
            str_contains(mb_strtolower($row2Cells[3] ?? '', 'UTF-8'), 'employee') ||
            str_contains(mb_strtolower($row2Cells[1] ?? '', 'UTF-8'), 'employee')
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
     * Header analysis specifically for canonical 46 PTKP columns with resilient mapping.
     * Gracefully supports:
     * 1. Simplified canonical 46 columns (starts with Employee ID at col 1)
     * 2. 47-column format (starts with leading No column)
     * 3. 48-column format (starts with leading Status & No columns)
     */
    public static function analyzeHeaders(array $foundHeaders, string $format = 'single_row'): array
    {
        $columns = self::columns(); // 46 canonical columns
        $canonicalCount = count($columns); // 46
        $detectedCount = count($foundHeaders);

        // Precompute canonical map
        $canonicalMap = [];
        $aliasToKeyMap = [];
        foreach ($columns as $idx => $col) {
            $normH = self::normalizeHeaderString($col['header']);
            $canonicalMap[$idx] = [
                'col'         => $idx,
                'letter'      => Coordinate::stringFromColumnIndex($idx),
                'header'      => $col['header'],
                'norm_header' => $normH,
                'key'         => $col['key'],
                'required'    => $col['required'],
                'aliases'     => array_map([self::class, 'normalizeHeaderString'], $col['aliases'] ?? []),
            ];

            $aliasToKeyMap[$normH] = $col;
            foreach ($canonicalMap[$idx]['aliases'] as $alias) {
                if ($alias !== '') {
                    $aliasToKeyMap[$alias] = $col;
                }
            }
        }

        // Determine if there are leading auxiliary columns (Status / No)
        $h1 = isset($foundHeaders[1]) ? self::normalizeHeaderString($foundHeaders[1]) : '';
        $h2 = isset($foundHeaders[2]) ? self::normalizeHeaderString($foundHeaders[2]) : '';
        $h3 = isset($foundHeaders[3]) ? self::normalizeHeaderString($foundHeaders[3]) : '';

        $statusAliases = ['status', 'status validasi', 'system status', 'validation status', 'row status', 'stat'];
        $noAliases = ['no', 'no.', 'nomor', '#', 'row no', 'seq'];

        $offset = 0;
        $hasLeadingStatus = false;
        $hasLeadingNo = false;

        if (in_array($h1, $statusAliases, true)) {
            $hasLeadingStatus = true;
            if (in_array($h2, $noAliases, true)) {
                $hasLeadingNo = true;
                $offset = 2; // Leading Status (1) and No (2)
            } else {
                $offset = 1; // Leading Status (1)
            }
        } elseif (in_array($h1, $noAliases, true)) {
            $hasLeadingNo = true;
            $offset = 1; // Leading No (1)
        }

        $expectedCount = $canonicalCount + $offset;
        $maxCol = max($expectedCount, $detectedCount);

        $columnResults = [];
        $issues = [];
        $validCount = 0;
        $autoMatchedCount = 0;
        $warningCount = 0;
        $invalidCount = 0;
        $missingCount = 0;
        $duplicateCount = 0;
        $mappedKeys = [];

        for ($pos = 1; $pos <= $maxCol; $pos++) {
            $colLetter = Coordinate::stringFromColumnIndex($pos);
            $rawFound = isset($foundHeaders[$pos]) ? trim((string) $foundHeaders[$pos]) : '';
            $normFound = self::normalizeHeaderString($rawFound);

            // Handle leading auxiliary columns (Status / No)
            if ($offset > 0 && $pos <= $offset) {
                $isStatusCol = ($pos === 1 && $hasLeadingStatus);
                $isNoCol = ($pos === 1 && $hasLeadingNo && !$hasLeadingStatus) || ($pos === 2 && $hasLeadingNo);

                if ($isStatusCol) {
                    $validCount++;
                    $columnResults[$pos] = [
                        'col'       => $pos,
                        'letter'    => $colLetter,
                        'expected'  => 'Status',
                        'found'     => $rawFound,
                        'status'    => 'VALID',
                        'mapped_to' => 'Status',
                        'key'       => 'status',
                        'message'   => 'Legacy validation status column (optional on upload).',
                    ];
                    continue;
                }

                if ($isNoCol) {
                    $validCount++;
                    $columnResults[$pos] = [
                        'col'       => $pos,
                        'letter'    => $colLetter,
                        'expected'  => 'No',
                        'found'     => $rawFound,
                        'status'    => 'VALID',
                        'mapped_to' => 'No',
                        'key'       => 'row_no',
                        'message'   => 'Legacy sequence number column (preview only).',
                    ];
                    continue;
                }
            }

            // Target canonical index (1 to 46)
            $canonicalIdx = $pos - $offset;
            $canonical = $canonicalMap[$canonicalIdx] ?? null;

            if ($canonicalIdx > $canonicalCount) {
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
                        'message'   => "Unexpected extra column {$pos} ({$colLetter}: '{$rawFound}'). PTKP template specifies exactly {$canonicalCount} columns.",
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

            $targetKey = null;
            $targetHeader = null;
            $matchStatus = null;
            $matchMsg = null;

            // Direct positional match (Exact)
            if (mb_strtolower($rawFound, 'UTF-8') === mb_strtolower($canonical['header'], 'UTF-8')) {
                $targetKey = $canonical['key'];
                $targetHeader = $canonical['header'];
                $matchStatus = 'VALID';
                $matchMsg = 'Exact canonical match.';
            } elseif ($normFound === $canonical['norm_header'] || in_array($normFound, $canonical['aliases'], true)) {
                // Direct positional match (Normalized or Alias)
                $targetKey = $canonical['key'];
                $targetHeader = $canonical['header'];
                $matchStatus = 'AUTO_MATCHED';
                $matchMsg = "Matched via alias / normalization ('{$rawFound}' -> '{$canonical['header']}').";
            } elseif (isset($aliasToKeyMap[$normFound])) {
                // Repositioned column matched by header or alias
                $matchedOther = $aliasToKeyMap[$normFound];
                $targetKey = $matchedOther['key'];
                $targetHeader = $matchedOther['header'];
                $matchStatus = 'AUTO_MATCHED';
                $matchMsg = "Repositioned column matched by header ('{$rawFound}' -> '{$matchedOther['header']}').";
            }

            if ($targetKey !== null) {
                if (isset($mappedKeys[$targetKey])) {
                    $duplicateCount++;
                    $prevCol = $mappedKeys[$targetKey];
                    $issue = [
                        'col'       => $pos,
                        'letter'    => $colLetter,
                        'expected'  => $canonical['header'],
                        'found'     => $rawFound,
                        'status'    => 'DUPLICATE',
                        'mapped_to' => $targetHeader,
                        'key'       => $targetKey,
                        'message'   => "Duplicate column header: '{$rawFound}' at column {$colLetter} duplicates column {$prevCol} (field '{$targetKey}').",
                    ];
                    $columnResults[$pos] = $issue;
                    $issues[] = $issue;
                    continue;
                }

                if ($matchStatus === 'VALID') {
                    $validCount++;
                } else {
                    $autoMatchedCount++;
                }

                $columnResults[$pos] = [
                    'col'       => $pos,
                    'letter'    => $colLetter,
                    'expected'  => $canonical['header'],
                    'found'     => $rawFound,
                    'status'    => $matchStatus,
                    'mapped_to' => $targetHeader,
                    'key'       => $targetKey,
                    'message'   => $matchMsg,
                ];
                $mappedKeys[$targetKey] = $colLetter;
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

        // Check if any mandatory canonical columns were never mapped
        foreach ($columns as $idx => $col) {
            $key = $col['key'];
            if (!isset($mappedKeys[$key]) && $col['required']) {
                $missingCount++;
                $issues[] = [
                    'col'       => $idx + $offset,
                    'letter'    => Coordinate::stringFromColumnIndex($idx + $offset),
                    'expected'  => $col['header'],
                    'found'     => null,
                    'status'    => 'MISSING',
                    'mapped_to' => null,
                    'key'       => $key,
                    'message'   => "Mandatory column '{$col['header']}' is missing from the uploaded file.",
                ];
            }
        }

        // Build column positions mapping (key => column letter)
        $columnPositions = [];
        foreach ($columns as $idx => $col) {
            $key = $col['key'];
            $colLetter = $mappedKeys[$key] ?? Coordinate::stringFromColumnIndex($idx + $offset);
            $columnPositions[$key] = $colLetter;
        }

        $canProceed = ($invalidCount === 0 && $missingCount === 0 && $duplicateCount === 0);

        return [
            'can_proceed'        => $canProceed,
            'expected_count'     => $expectedCount,
            'detected_count'     => $detectedCount,
            'valid_count'        => $validCount,
            'auto_matched_count' => $autoMatchedCount,
            'warning_count'      => $warningCount,
            'invalid_count'      => $invalidCount,
            'missing_count'      => $missingCount,
            'duplicate_count'    => $duplicateCount,
            'columns'            => $columnResults,
            'issues'             => $issues,
            'column_positions'   => $columnPositions,
            'column_keys'        => array_keys($columnPositions),
        ];
    }
}
