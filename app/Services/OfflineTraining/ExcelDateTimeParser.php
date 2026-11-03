<?php

namespace App\Services\OfflineTraining;

use Carbon\Carbon;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ExcelDateTimeParser
{
    /**
     * Standard empty / placeholder values that represent NULL.
     */
    public const EMPTY_PLACEHOLDERS = [
        '-',
        '--',
        '---',
        'n/a',
        'na',
        'none',
        'null',
        'nil',
        'tba',
        'tbd',
        'belum ada',
        'belum',
        'kosong',
        '#n/a',
        '#value!',
        '#ref!',
    ];

    /**
     * Parse and normalize date into 'YYYY-MM-DD' string, or return null if empty/invalid.
     */
    public static function parseDate($value): ?string
    {
        $res = self::parseDateResult($value);
        return $res['valid'] ? $res['value'] : null;
    }

    /**
     * Detailed date parse result indicating validity, empty status, and error message.
     *
     * @param mixed $value
     * @return array{
     *     valid: bool,
     *     value: string|null,
     *     is_empty: bool,
     *     error: string|null,
     *     raw: mixed
     * }
     */
    public static function parseDateResult($value): array
    {
        if ($value === null) {
            return [
                'valid'    => true,
                'value'    => null,
                'is_empty' => true,
                'error'    => null,
                'raw'      => null,
            ];
        }

        if ($value instanceof DateTimeInterface) {
            return [
                'valid'    => true,
                'value'    => $value->format('Y-m-d'),
                'is_empty' => false,
                'error'    => null,
                'raw'      => $value,
            ];
        }

        $str = is_scalar($value) ? trim((string) $value) : '';
        if ($str === '' || in_array(strtolower($str), self::EMPTY_PLACEHOLDERS, true)) {
            return [
                'valid'    => true,
                'value'    => null,
                'is_empty' => true,
                'error'    => null,
                'raw'      => $value,
            ];
        }

        // 1. ISO Format: YYYY-MM-DD or YYYY/MM/DD or YYYY.MM.DD
        if (preg_match('/^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/', $str, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];

            if (checkdate($month, $day, $year)) {
                return [
                    'valid'    => true,
                    'value'    => sprintf('%04d-%02d-%02d', $year, $month, $day),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Invalid calendar date '{$str}' (month {$month} or day {$day} does not exist in year {$year}).",
                'raw'      => $value,
            ];
        }

        // 2. Standard Localized Format: DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[-\/\.](\d{1,2})[-\/\.](\d{4})$/', $str, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];

            // Strict DAY-FIRST interpretation (e.g. 05/06/2026 is strictly 5 June 2026, never 6 May)
            if (checkdate($month, $day, $year)) {
                return [
                    'valid'    => true,
                    'value'    => sprintf('%04d-%02d-%02d', $year, $month, $day),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Invalid calendar date '{$str}' (day {$day} or month {$month} does not exist in year {$year}).",
                'raw'      => $value,
            ];
        }

        // 3. Two-digit Year Localized Format: DD/MM/YY or DD-MM-YY or DD.MM.YY (e.g. 10/08/26, 05/06/26)
        if (preg_match('/^(\d{1,2})[-\/\.](\d{1,2})[-\/\.](\d{2})$/', $str, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $yy = (int) $m[3];
            $year = $yy < 70 ? 2000 + $yy : 1900 + $yy;

            // Strict DAY-FIRST interpretation
            if (checkdate($month, $day, $year)) {
                return [
                    'valid'    => true,
                    'value'    => sprintf('%04d-%02d-%02d', $year, $month, $day),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Invalid calendar date '{$str}' (day {$day} or month {$month} does not exist in year {$year}).",
                'raw'      => $value,
            ];
        }

        // 3. Numeric Excel Serial Date (e.g. 46023, 46052)
        if (is_numeric($str)) {
            $num = (float) $str;
            if ($num >= 1.0 && $num <= 2958465.0) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject($num);
                    return [
                        'valid'    => true,
                        'value'    => $dt->format('Y-m-d'),
                        'is_empty' => false,
                        'error'    => null,
                        'raw'      => $value,
                    ];
                } catch (Throwable $e) {
                    return [
                        'valid'    => false,
                        'value'    => null,
                        'is_empty' => false,
                        'error'    => "Failed to convert Excel date serial '{$str}'.",
                        'raw'      => $value,
                    ];
                }
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Numeric value '{$str}' is out of acceptable date serial range (1 to 2958465).",
                'raw'      => $value,
            ];
        }

        // 4. Text-based Date fallback (e.g. '10 Aug 2026', 'August 10, 2026')
        if (preg_match('/[a-zA-Z]/', $str)) {
            try {
                $c = Carbon::parse($str);
                if ($c->year >= 1970 && $c->year <= 2100) {
                    return [
                        'valid'    => true,
                        'value'    => $c->format('Y-m-d'),
                        'is_empty' => false,
                        'error'    => null,
                        'raw'      => $value,
                    ];
                }
            } catch (Throwable $e) {
                // fall through to unrecognized
            }
        }

        return [
            'valid'    => false,
            'value'    => null,
            'is_empty' => false,
            'error'    => "Unrecognized date format '{$str}'. Expected YYYY-MM-DD or DD/MM/YYYY.",
            'raw'      => $value,
        ];
    }

    /**
     * Format a database YYYY-MM-DD date into DD/MM/YYYY for UI preview display.
     */
    public static function formatDisplayDate(?string $isoDate): ?string
    {
        if ($isoDate === null || $isoDate === '' || $isoDate === '-') {
            return null;
        }

        $s = trim($isoDate);
        if (preg_match('/^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})/', $s, $m)) {
            return sprintf('%02d/%02d/%04d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        if (preg_match('/^(\d{1,2})[-\/\.](\d{1,2})[-\/\.](\d{4})/', $s, $m)) {
            return sprintf('%02d/%02d/%04d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return $s;
    }

    /**
     * Parse and normalize time into 'HH:mm:ss' string, or return null if empty/invalid.
     */
    public static function parseTime($value): ?string
    {
        $res = self::parseTimeResult($value);
        return $res['valid'] ? $res['value'] : null;
    }

    /**
     * Detailed time parse result indicating validity, empty status, and error message.
     *
     * @param mixed $value
     * @return array{
     *     valid: bool,
     *     value: string|null,
     *     is_empty: bool,
     *     error: string|null,
     *     raw: mixed
     * }
     */
    public static function parseTimeResult($value): array
    {
        if ($value === null) {
            return [
                'valid'    => true,
                'value'    => null,
                'is_empty' => true,
                'error'    => null,
                'raw'      => null,
            ];
        }

        if ($value instanceof DateTimeInterface) {
            return [
                'valid'    => true,
                'value'    => $value->format('H:i:s'),
                'is_empty' => false,
                'error'    => null,
                'raw'      => $value,
            ];
        }

        $str = is_scalar($value) ? trim((string) $value) : '';
        if ($str === '' || in_array(strtolower($str), self::EMPTY_PLACEHOLDERS, true)) {
            return [
                'valid'    => true,
                'value'    => null,
                'is_empty' => true,
                'error'    => null,
                'raw'      => $value,
            ];
        }

        // 1. Standard 24-hour time format: HH:mm or HH:mm:ss (supports colon ':' or dot '.' delimiter, e.g. '08:00', '08.00', '17:00:00')
        if (preg_match('/^(\d{1,2})[:\.](\d{2})(?:[:\.](\d{2}))?$/', $str, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $s = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;

            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59 && $s >= 0 && $s <= 59) {
                return [
                    'valid'    => true,
                    'value'    => sprintf('%02d:%02d:%02d', $h, $min, $s),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Time '{$str}' is out of acceptable range. Hours must be 0-23 and minutes 0-59.",
                'raw'      => $value,
            ];
        }

        // 2. 12-hour AM/PM format: e.g. '8:00 AM', '05:30 PM'
        if (preg_match('/^(\d{1,2})[:\.](\d{2})(?:[:\.](\d{2}))?\s*(am|pm)$/i', $str, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $s = isset($m[3]) && $m[3] !== '' ? (int) $m[3] : 0;
            $ampm = strtolower($m[4] ?? $m[3]);

            if ($h >= 1 && $h <= 12 && $min >= 0 && $min <= 59 && $s >= 0 && $s <= 59) {
                if ($ampm === 'pm' && $h < 12) {
                    $h += 12;
                } elseif ($ampm === 'am' && $h === 12) {
                    $h = 0;
                }

                return [
                    'valid'    => true,
                    'value'    => sprintf('%02d:%02d:%02d', $h, $min, $s),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Invalid 12-hour time format in '{$str}'.",
                'raw'      => $value,
            ];
        }

        // 3. Numeric Excel Time / Day Fraction (e.g. 0.333333333 -> 08:00:00, 0.708333333 -> 17:00:00)
        if (is_numeric($str)) {
            $num = (float) $str;

            // Pure time fraction between 0.0 and 1.0
            if ($num >= 0.0 && $num < 1.0) {
                $seconds = (int) round($num * 86400) % 86400;
                return [
                    'valid'    => true,
                    'value'    => gmdate('H:i:s', $seconds),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            // Integer hour shorthand between 0 and 23 without dot (e.g. '8' -> 08:00:00, '17' -> 17:00:00)
            if (ctype_digit($str) && (int) $str >= 0 && (int) $str <= 23) {
                return [
                    'valid'    => true,
                    'value'    => sprintf('%02d:00:00', (int) $str),
                    'is_empty' => false,
                    'error'    => null,
                    'raw'      => $value,
                ];
            }

            // Datetime serial with fractional time part (e.g. 46023.333333333)
            if ($num >= 1.0) {
                $fraction = fmod($num, 1.0);
                if ($fraction > 0.000001) {
                    $seconds = (int) round($fraction * 86400) % 86400;
                    return [
                        'valid'    => true,
                        'value'    => gmdate('H:i:s', $seconds),
                        'is_empty' => false,
                        'error'    => null,
                        'raw'      => $value,
                    ];
                }

                return [
                    'valid'    => false,
                    'value'    => null,
                    'is_empty' => false,
                    'error'    => "Numeric value '{$str}' appears to be a Date serial without time information.",
                    'raw'      => $value,
                ];
            }

            return [
                'valid'    => false,
                'value'    => null,
                'is_empty' => false,
                'error'    => "Invalid numeric time value '{$str}'.",
                'raw'      => $value,
            ];
        }

        return [
            'valid'    => false,
            'value'    => null,
            'is_empty' => false,
            'error'    => "Unrecognized time format '{$str}'. Expected HH:mm or HH:mm:ss (00:00:00 - 23:59:59).",
            'raw'      => $value,
        ];
    }
}
