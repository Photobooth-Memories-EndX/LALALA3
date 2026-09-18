<?php

namespace App\Support;

class LearningFilterHelper
{
    private static array $monthMap = [
        'january'   => 1,
        'february'  => 2,
        'march'     => 3,
        'april'     => 4,
        'may'       => 5,
        'june'      => 6,
        'july'      => 7,
        'august'    => 8,
        'september' => 9,
        'october'   => 10,
        'november'  => 11,
        'december'  => 12,
        'jan'       => 1,
        'feb'       => 2,
        'mar'       => 3,
        'apr'       => 4,
        'jun'       => 6,
        'jul'       => 7,
        'aug'       => 8,
        'sep'       => 9,
        'oct'       => 10,
        'nov'       => 11,
        'dec'       => 12,
    ];

    /**
     * Resolve string/numeric month representation to integer month (1-12).
     * Returns null if 'all', empty, or invalid.
     */
    public static function resolveMonth(?string $month): ?int
    {
        if ($month === null || $month === '' || strtolower(trim($month)) === 'all') {
            return null;
        }

        $trimmed = strtolower(trim($month));

        if (is_numeric($trimmed)) {
            $intVal = (int) $trimmed;
            if ($intVal >= 1 && $intVal <= 12) {
                return $intVal;
            }
        }

        return self::$monthMap[$trimmed] ?? null;
    }

    /**
     * Normalize learning status for Assessment.
     */
    public static function normalizeAssessmentStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return 'all';
        }

        $s = strtolower(trim($status));

        return match ($s) {
            'pending', 'not_started' => 'not_started',
            'passed'                 => 'passed',
            'failed'                 => 'failed',
            default                  => $s,
        };
    }

    /**
     * Normalize type for Assessment.
     */
    public static function normalizeAssessmentType(?string $type): string
    {
        if ($type === null || $type === '') {
            return 'all';
        }

        $t = strtolower(trim($type));

        return match ($t) {
            'playlist', 'trainingplan' => 'trainingplan',
            default                    => $t,
        };
    }
}
