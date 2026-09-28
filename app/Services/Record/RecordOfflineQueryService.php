<?php

namespace App\Services\Record;

use App\Http\Controllers\Manage\Record\RecordOfflineController;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecordOfflineQueryService
{
    /**
     * Build shared query for Non KP OWL offline records with all Quick and More Filters applied.
     *
     * @param  array  $filters
     * @param  string|null  $lockedClassification  If provided, classification scope is strictly locked to this value/slug.
     * @return Builder
     */
    public static function buildQuery(array $filters = [], ?string $lockedClassification = null): Builder
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');

        if (!$hasTables) {
            // Return empty dummy query if tables do not exist
            return DB::table('offline_training_users as otu')->whereRaw('1 = 0');
        }

        $query = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->leftJoin('users as u', 'u.id', '=', 'otu.user_id')
            ->whereNull('otu.deleted_at')
            ->whereNull('ot.deleted_at')
            ->select([
                'otu.id',
                'otu.offline_training_id',
                'otu.user_id',
                'otu.employee_id',
                'otu.name as participant_name',
                'otu.grade',
                'otu.job_position',
                'otu.team',
                'otu.department',
                'otu.company',
                'otu.learning_hours',
                'otu.progress',
                'otu.assessment_score',
                'otu.certificate',
                'otu.attendance',
                'otu.attendance_reason',
                'otu.bond_status',
                'otu.bond_end_date',
                'otu.bond_amount',
                'otu.l1_program',
                'otu.l2_numerical',
                'otu.l2_verbal',
                'otu.l3_numerical',
                'otu.l3_verbal',
                'otu.pic',
                'otu.created_at',
                'ot.course_name',
                'ot.sub_course_name',
                'ot.type',
                'ot.category',
                'ot.level',
                'ot.batch',
                'ot.main_classification',
                'ot.sub_classification',
                'ot.counted_corporate_kpi',
                'ot.sub_corporate_kpi',
                'ot.delivery_method',
                'ot.sub_delivery_method',
                'ot.provider',
                'ot.sub_provider',
                'ot.provider_name',
                'ot.venue',
                'ot.instructor',
                'ot.duration_seconds',
                'ot.learning_hours as course_learning_hours',
                'ot.start_date',
                'ot.finish_date',
                'u.name as user_name',
            ]);

        // 1. Classification Scope (Priority to locked route parameter)
        $classificationTarget = $lockedClassification ?? ($filters['main_classification'] ?? ($filters['classification'] ?? null));
        if (filled($classificationTarget) && $classificationTarget !== 'all') {
            $resolved = RecordOfflineController::resolveClassification($classificationTarget);
            if ($resolved['is_unclassified']) {
                $query->where(function ($q) {
                    $q->whereNull('ot.main_classification')
                      ->orWhere('ot.main_classification', '');
                });
            } else {
                $query->where('ot.main_classification', $resolved['name']);
            }
        }

        // 2. Filter: Employee Search (ID, participant name, or user account name)
        $employeeParam = trim((string) ($filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employee_q'] ?? ($filters['search_employee'] ?? '')))));
        if ($employeeParam !== '' && $employeeParam !== 'all') {
            $query->where(function ($q) use ($employeeParam) {
                $q->where('otu.employee_id', 'like', "%{$employeeParam}%")
                  ->orWhere('otu.name', 'like', "%{$employeeParam}%")
                  ->orWhere('u.name', 'like', "%{$employeeParam}%");
            });
        }

        // 3. Filter: Learning Title / Course Name
        $titleParam = trim((string) ($filters['learning_title'] ?? ($filters['course_name'] ?? ($filters['title_q'] ?? ($filters['title'] ?? ($filters['training_title'] ?? ''))))));
        if ($titleParam !== '') {
            $query->where(function ($q) use ($titleParam) {
                $q->where('ot.course_name', 'like', "%{$titleParam}%")
                  ->orWhere('ot.sub_course_name', 'like', "%{$titleParam}%");
            });
        }

        // 4. Filter: Type
        $typeParam = trim((string) ($filters['type'] ?? ($filters['training_type'] ?? 'all')));
        if ($typeParam !== '' && $typeParam !== 'all') {
            $query->where('ot.type', $typeParam);
        }

        // 5. Filter: Status (Finished, Ongoing, Not Started)
        $statusParam = strtolower(trim((string) ($filters['status'] ?? 'all')));
        if ($statusParam !== '' && $statusParam !== 'all') {
            if ($statusParam === 'finished' || $statusParam === '1' || $statusParam === 'finish') {
                $query->where('otu.progress', '>=', 100);
            } elseif ($statusParam === 'ongoing' || $statusParam === '0') {
                $query->where('otu.progress', '>', 0)
                      ->where('otu.progress', '<', 100);
            } elseif ($statusParam === 'not_started' || $statusParam === 'not-started') {
                $query->where(function ($q) {
                    $q->where('otu.progress', '<=', 0)
                      ->orWhereNull('otu.progress');
                });
            }
        }

        // 6. Filter: Year (from finish_date or start_date fallback)
        $yearParam = trim((string) ($filters['year'] ?? 'all'));
        if ($yearParam !== '' && $yearParam !== 'all') {
            $yearInt = (int) $yearParam;
            $query->where(function ($q) use ($yearInt) {
                $q->whereYear('ot.finish_date', $yearInt)
                  ->orWhere(function ($q2) use ($yearInt) {
                      $q2->whereNull('ot.finish_date')->whereYear('ot.start_date', $yearInt);
                  });
            });
        }

        // 7. More Filter: Date Range (Training Date on start_date / finish_date)
        $fromDateRaw = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDateRaw = $filters['to_date'] ?? ($filters['date_to'] ?? null);

        $fromDate = self::parseDate($fromDateRaw);
        $toDate = self::parseDate($toDateRaw);

        if ($fromDate && $toDate) {
            $query->where(function ($q) use ($fromDate, $toDate) {
                $q->where(function ($sub) use ($fromDate, $toDate) {
                    $sub->whereNotNull('ot.start_date')
                        ->whereBetween('ot.start_date', [$fromDate, $toDate]);
                })->orWhere(function ($sub) use ($fromDate, $toDate) {
                    $sub->whereNull('ot.start_date')
                        ->whereNotNull('ot.finish_date')
                        ->whereBetween('ot.finish_date', [$fromDate, $toDate]);
                });
            });
        } elseif ($fromDate) {
            $query->where(function ($q) use ($fromDate) {
                $q->where(function ($sub) use ($fromDate) {
                    $sub->whereNotNull('ot.start_date')
                        ->where('ot.start_date', '>=', $fromDate);
                })->orWhere(function ($sub) use ($fromDate) {
                    $sub->whereNull('ot.start_date')
                        ->whereNotNull('ot.finish_date')
                        ->where('ot.finish_date', '>=', $fromDate);
                });
            });
        } elseif ($toDate) {
            $query->where(function ($q) use ($toDate) {
                $q->where(function ($sub) use ($toDate) {
                    $sub->whereNotNull('ot.start_date')
                        ->where('ot.start_date', '<=', $toDate);
                })->orWhere(function ($sub) use ($toDate) {
                    $sub->whereNull('ot.start_date')
                        ->whereNotNull('ot.finish_date')
                        ->where('ot.finish_date', '<=', $toDate);
                });
            });
        }

        // 8. More Filter: Progress Range
        $progressRange = $filters['progress_range'] ?? null;
        if (filled($progressRange) && $progressRange !== 'all') {
            $val = str_replace([' ', '%'], '', (string) $progressRange);
            switch ($val) {
                case '0':
                    $query->where(function ($q) {
                        $q->where('otu.progress', '<=', 0)
                          ->orWhereNull('otu.progress');
                    });
                    break;
                case '1-25':
                    $query->where('otu.progress', '>', 0)
                          ->where('otu.progress', '<=', 25);
                    break;
                case '26-50':
                    $query->where('otu.progress', '>', 25)
                          ->where('otu.progress', '<=', 50);
                    break;
                case '51-75':
                    $query->where('otu.progress', '>', 50)
                          ->where('otu.progress', '<=', 75);
                    break;
                case '76-99':
                    $query->where('otu.progress', '>', 75)
                          ->where('otu.progress', '<', 100);
                    break;
                case '100':
                    $query->where('otu.progress', '>=', 100);
                    break;
            }
        }

        // 9. More Filter: Duration Range (seconds on ot.duration_seconds)
        $durationParam = strtolower(trim((string) ($filters['duration'] ?? ($filters['duration_range'] ?? 'all'))));
        if (filled($durationParam) && $durationParam !== 'all') {
            switch ($durationParam) {
                case 'lt10':
                case 'under_10':
                case '< 10 minutes':
                case '<10min':
                case '<10':
                    $query->where('ot.duration_seconds', '<', 600);
                    break;
                case '10-30':
                case '10_30':
                case '10 - 30 minutes':
                case '10-30min':
                    $query->where('ot.duration_seconds', '>=', 600)
                          ->where('ot.duration_seconds', '<=', 1800);
                    break;
                case '30-60':
                case '30_60':
                case '30 - 60 minutes':
                case '30-60min':
                    $query->where('ot.duration_seconds', '>', 1800)
                          ->where('ot.duration_seconds', '<=', 3600);
                    break;
                case 'gt60':
                case 'above_60':
                case '> 1 hour':
                case '>1hour':
                case '>60':
                case '>60min':
                    $query->where('ot.duration_seconds', '>', 3600);
                    break;
            }
        }

        // 10. More Filter: Attendance (Present / Absent)
        $attendanceParam = strtolower(trim((string) ($filters['attendance'] ?? 'all')));
        if (filled($attendanceParam) && $attendanceParam !== 'all') {
            if ($attendanceParam === 'present' || $attendanceParam === '1' || $attendanceParam === 'yes') {
                $query->where('otu.attendance', true);
            } elseif ($attendanceParam === 'absent' || $attendanceParam === '0' || $attendanceParam === 'no') {
                $query->where('otu.attendance', false);
            }
        }

        // 11. More Filter: Assessment Score (numeric range on otu.assessment_score)
        $scoreMin = $filters['score_min'] ?? ($filters['assessment_score_min'] ?? null);
        $scoreMax = $filters['score_max'] ?? ($filters['assessment_score_max'] ?? null);

        if (filled($scoreMin) && is_numeric($scoreMin)) {
            $query->where('otu.assessment_score', '>=', (float) $scoreMin);
        }
        if (filled($scoreMax) && is_numeric($scoreMax)) {
            $query->where('otu.assessment_score', '<=', (float) $scoreMax);
        }

        // 12. More Filter: Learning Hours (numeric range on otu.learning_hours)
        $hoursMin = $filters['learning_hours_min'] ?? ($filters['hours_min'] ?? null);
        $hoursMax = $filters['learning_hours_max'] ?? ($filters['hours_max'] ?? null);

        if (filled($hoursMin) && is_numeric($hoursMin)) {
            $query->where('otu.learning_hours', '>=', (float) $hoursMin);
        }
        if (filled($hoursMax) && is_numeric($hoursMax)) {
            $query->where('otu.learning_hours', '<=', (float) $hoursMax);
        }

        return $query;
    }

    /**
     * Build query strictly locked to the route classification.
     */
    public static function buildClassificationQuery(string $classification, array $filters = []): Builder
    {
        return self::buildQuery($filters, $classification);
    }

    /**
     * Parse date string formatted as dd/mm/yyyy or yyyy-mm-dd into standard Y-m-d.
     */
    public static function parseDate(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $cleaned = trim((string) $value);

        try {
            return Carbon::parse(str_replace('/', '-', $cleaned))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
