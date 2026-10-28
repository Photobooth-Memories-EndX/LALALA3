<?php

namespace App\Services\Record;

use App\Http\Controllers\Manage\Record\RecordOfflineController;
use App\Models\OfflineTraining;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecordOfflineQueryService
{
    protected static ?bool $hasBondDuration = null;
    protected static ?bool $hasBondAmountIdr = null;
    protected static ?bool $hasRequirement = null;

    protected static function bondDurationSelect(): mixed
    {
        if (self::$hasBondDuration === null) {
            self::$hasBondDuration = Schema::hasColumn('offline_training_users', 'bond_duration');
        }
        return self::$hasBondDuration ? 'otu.bond_duration' : DB::raw('NULL as bond_duration');
    }

    protected static function bondAmountSelect(): array
    {
        if (self::$hasBondAmountIdr === null) {
            self::$hasBondAmountIdr = Schema::hasColumn('offline_training_users', 'bond_amount_idr');
        }
        if (self::$hasBondAmountIdr) {
            return [
                'otu.bond_amount_idr',
                DB::raw('otu.bond_amount_idr as bond_amount'),
            ];
        }
        return [
            DB::raw('otu.bond_amount as bond_amount_idr'),
            'otu.bond_amount',
        ];
    }

    protected static function bondAmountColumn(): string
    {
        if (self::$hasBondAmountIdr === null) {
            self::$hasBondAmountIdr = Schema::hasColumn('offline_training_users', 'bond_amount_idr');
        }
        return self::$hasBondAmountIdr ? 'otu.bond_amount_idr' : 'otu.bond_amount';
    }

    protected static function requirementSelect(): mixed
    {
        if (self::$hasRequirement === null) {
            self::$hasRequirement = Schema::hasColumn('offline_trainings', 'requirement');
        }
        return self::$hasRequirement ? 'ot.requirement' : DB::raw("COALESCE(ot.status, 'Mandatory') as requirement");
    }

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
            ->whereNull('ot.deleted_at');

        OfflineTraining::applyRecordScope($query, 'ot');

        $query->select([
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
                self::bondDurationSelect(),
                'otu.bond_end_date',
                ...self::bondAmountSelect(),
                'otu.l1_program',
                'otu.l2_numerical',
                'otu.l2_verbal',
                'otu.l3_numerical',
                'otu.l3_verbal',
                'otu.pic',
                'otu.created_at',
                \Illuminate\Support\Facades\DB::raw("COALESCE(ot.title, ot.course_name, 'Training Record') as title"),
                'ot.course_name',
                'ot.sub_course_name',
                DB::raw("COALESCE(ot.type, '-') as type"),
                self::requirementSelect(),
                'ot.category',
                'ot.level',
                'ot.batch',
                'ot.main_classification',
                'ot.sub_classification',
                'ot.counted_corporate_kpi',
                'ot.corporate_kpi_year',
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
                'ot.end_date',
                'ot.finish_date',
                'ot.start_time',
                'ot.end_time',
                'ot.status',
                'u.name as user_name',
            ]);

        // 1. Classification Scope (Priority to locked route parameter)
        $classificationTarget = $lockedClassification ?? ($filters['main_classification'] ?? ($filters['classification'] ?? null));
        if (filled($classificationTarget) && $classificationTarget !== 'all') {
            $categorySlug = trim((string) $classificationTarget);
            $classificationSlug = str_replace('-', ' ', strtolower($categorySlug));
            $resolved = RecordOfflineController::resolveClassification($categorySlug);

            if ($resolved['is_unclassified']) {
                $query->where(function ($q) {
                    $q->whereNull('ot.main_classification')
                      ->orWhere('ot.main_classification', '');
                });
            } else {
                $resolvedName = $resolved['name'];
                $query->where(function ($q) use ($categorySlug, $classificationSlug, $resolvedName) {
                    $q->where('ot.main_classification', $resolvedName)
                      ->orWhere('ot.main_classification', $categorySlug)
                      ->orWhereRaw('LOWER(ot.main_classification) = ?', [strtolower($resolvedName)])
                      ->orWhereRaw('LOWER(ot.main_classification) = ?', [$classificationSlug])
                      ->orWhereRaw('LOWER(ot.main_classification) = ?', [strtolower($categorySlug)])
                      ->orWhereRaw("LOWER(REPLACE(REPLACE(ot.main_classification, ' ', '-'), '&', 'and')) = ?", [strtolower(\Illuminate\Support\Str::slug($categorySlug))])
                      ->orWhereRaw("LOWER(REPLACE(ot.main_classification, ' ', '-')) = ?", [strtolower(\Illuminate\Support\Str::slug($categorySlug))]);
                });
            }
        }

        // 2. Filter: Employee Search (ID, participant name, or user account name)
        $employeeParam = $filters['employee_id'] ?? ($filters['employee'] ?? ($filters['employees'] ?? ($filters['employee_q'] ?? ($filters['search_employee'] ?? ''))));
        if (is_array($employeeParam) && !empty($employeeParam)) {
            $query->where(function ($q) use ($employeeParam) {
                $q->whereIn('otu.employee_id', $employeeParam)
                  ->orWhereIn('otu.name', $employeeParam)
                  ->orWhereIn('u.name', $employeeParam);
            });
        } elseif (is_string($employeeParam) && trim($employeeParam) !== '' && trim($employeeParam) !== 'all') {
            $empStr = trim($employeeParam);
            $query->where(function ($q) use ($empStr) {
                $q->where('otu.employee_id', 'like', "%{$empStr}%")
                  ->orWhere('otu.name', 'like', "%{$empStr}%")
                  ->orWhere('u.name', 'like', "%{$empStr}%");
            });
        }

        // 3. Filter: Learning Title / Course Name
        $rawTitleParam = $filters['learning_title'] ?? ($filters['course_name'] ?? ($filters['title_q'] ?? ($filters['title'] ?? ($filters['training_title'] ?? ''))));
        if (is_array($rawTitleParam)) {
            $titleList = array_values(array_filter(array_map('trim', $rawTitleParam), fn($t) => $t !== '' && strtolower($t) !== 'all'));
            if (!empty($titleList)) {
                $query->where(function ($q) use ($titleList) {
                    $q->whereIn('ot.course_name', $titleList)
                      ->orWhereIn('ot.sub_course_name', $titleList);
                });
            }
        } else {
            $titleParam = trim((string) $rawTitleParam);
            if ($titleParam !== '' && strtolower($titleParam) !== 'all') {
                $query->where(function ($q) use ($titleParam) {
                    $q->where('ot.course_name', 'like', "%{$titleParam}%")
                      ->orWhere('ot.sub_course_name', 'like', "%{$titleParam}%");
                });
            }
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
        $yearParam = $filters['year'] ?? ($filters['years'] ?? 'all');
        if (is_array($yearParam) && !empty($yearParam)) {
            $yearInts = array_map('intval', array_filter($yearParam, 'is_numeric'));
            if (!empty($yearInts)) {
                $query->where(function ($q) use ($yearInts) {
                    $q->whereIn(DB::raw('YEAR(ot.finish_date)'), $yearInts)
                      ->orWhere(function ($q2) use ($yearInts) {
                          $q2->whereNull('ot.finish_date')->whereIn(DB::raw('YEAR(ot.start_date)'), $yearInts);
                      });
                });
            }
        } elseif (is_string($yearParam) && $yearParam !== '' && $yearParam !== 'all') {
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

        // 13. More Filter: Category
        $categoryParam = trim((string) ($filters['category'] ?? 'all'));
        if (filled($categoryParam) && $categoryParam !== 'all') {
            $query->where('ot.category', $categoryParam);
        }

        // 14. More Filter: Certificate
        $certParam = strtolower(trim((string) ($filters['certificate'] ?? 'all')));
        if (filled($certParam) && $certParam !== 'all') {
            if ($certParam === 'yes' || $certParam === 'available' || $certParam === '1') {
                $query->whereNotNull('otu.certificate')->where('otu.certificate', '!=', '')->where('otu.certificate', '!=', 'No');
            } elseif ($certParam === 'no' || $certParam === '0') {
                $query->where(function ($q) {
                    $q->whereNull('otu.certificate')
                      ->orWhere('otu.certificate', '')
                      ->orWhere('otu.certificate', 'No');
                });
            }
        }

        // 15. More Filter: Corporate KPI
        $kpiParam = strtolower(trim((string) ($filters['corporate_kpi'] ?? ($filters['counted_corporate_kpi'] ?? 'all'))));
        if (filled($kpiParam) && $kpiParam !== 'all') {
            if ($kpiParam === 'yes' || $kpiParam === '1') {
                $query->where('ot.counted_corporate_kpi', true);
            } elseif ($kpiParam === 'no' || $kpiParam === '0') {
                $query->where(function ($q) {
                    $q->where('ot.counted_corporate_kpi', false)
                      ->orWhereNull('ot.counted_corporate_kpi');
                });
            }
        }

        // 16. More Filter: Bond Status
        $bondParam = strtolower(trim((string) ($filters['bond_status'] ?? 'all')));
        if (filled($bondParam) && $bondParam !== 'all') {
            if ($bondParam === 'yes' || $bondParam === '1') {
                $query->whereNotNull('otu.bond_status')->where('otu.bond_status', '!=', 'No')->where('otu.bond_status', '!=', '');
            } elseif ($bondParam === 'no' || $bondParam === '0') {
                $query->where(function ($q) {
                    $q->whereNull('otu.bond_status')
                      ->orWhere('otu.bond_status', 'No')
                      ->orWhere('otu.bond_status', '');
                });
            }
        }

        // 17. More Filter: Provider
        $providerParam = trim((string) ($filters['provider'] ?? 'all'));
        if (filled($providerParam) && $providerParam !== 'all') {
            $query->where('ot.provider', $providerParam);
        }

        // 18. More Filter: Delivery Method
        $deliveryParam = trim((string) ($filters['delivery_method'] ?? 'all'));
        if (filled($deliveryParam) && $deliveryParam !== 'all') {
            $query->where('ot.delivery_method', $deliveryParam);
        }

        // 19. More Filter: Sub Classification
        $subClassParam = trim((string) ($filters['sub_classification'] ?? 'all'));
        if (filled($subClassParam) && $subClassParam !== 'all') {
            $query->where('ot.sub_classification', $subClassParam);
        }

        // 20. More Filter: Batch
        $batchParam = trim((string) ($filters['batch'] ?? 'all'));
        if (filled($batchParam) && $batchParam !== 'all') {
            $query->where('ot.batch', $batchParam);
        }

        // 21. More Filter: Department
        $deptParam = $filters['department'] ?? ($filters['departments'] ?? 'all');
        if (is_array($deptParam) && !empty($deptParam)) {
            $query->whereIn('otu.department', $deptParam);
        } elseif (is_string($deptParam) && filled($deptParam) && $deptParam !== 'all') {
            $query->where('otu.department', trim($deptParam));
        }

        // 22. More Filter: Company
        $companyParam = $filters['company'] ?? ($filters['companies'] ?? 'all');
        if (is_array($companyParam) && !empty($companyParam)) {
            $query->whereIn('otu.company', $companyParam);
        } elseif (is_string($companyParam) && filled($companyParam) && $companyParam !== 'all') {
            $query->where('otu.company', trim($companyParam));
        }

        // 23. More Filter: Grade
        $gradeParam = trim((string) ($filters['grade'] ?? 'all'));
        if (filled($gradeParam) && $gradeParam !== 'all') {
            $query->where('otu.grade', $gradeParam);
        }

        // 24. More Filter: Job Position
        $jobPosParam = trim((string) ($filters['job_position'] ?? ''));
        if (filled($jobPosParam) && $jobPosParam !== 'all') {
            $query->where('otu.job_position', 'like', "%{$jobPosParam}%");
        }

        // 25. More Filter: Division
        $divisionParam = $filters['division'] ?? ($filters['divisions'] ?? 'all');
        if (is_array($divisionParam) && !empty($divisionParam)) {
            $query->whereIn('u.division', $divisionParam);
        } elseif (is_string($divisionParam) && filled($divisionParam) && $divisionParam !== 'all') {
            $query->where('u.division', trim($divisionParam));
        }

        // 26. More Filter: Team
        $teamParam = $filters['team'] ?? ($filters['teams'] ?? '');
        if (is_array($teamParam) && !empty($teamParam)) {
            $query->where(function ($q) use ($teamParam) {
                $q->whereIn('otu.team', $teamParam)
                  ->orWhereIn('u.team', $teamParam);
            });
        } elseif (is_string($teamParam) && filled($teamParam) && $teamParam !== 'all') {
            $teamStr = trim($teamParam);
            $query->where(function ($q) use ($teamStr) {
                $q->where('otu.team', 'like', "%{$teamStr}%")
                  ->orWhere('u.team', 'like', "%{$teamStr}%");
            });
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
     * Build global query across all classifications (unlocked and excluding classification filters).
     */
    public static function buildGlobalQuery(array $filters = []): Builder
    {
        unset($filters['classification'], $filters['main_classification']);

        return self::buildQuery($filters, null);
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

    /**
     * Resolve a participant identity from user_id, employee_id/NIP, or matching User record.
     *
     * @param string $identifier
     * @return array|null
     */
    public static function resolveParticipant(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');
        if (!$hasTables) {
            return null;
        }

        $user = null;
        $userId = null;
        $employeeId = null;

        // Check if identifier matches a User ID
        if (is_numeric($identifier) && (int) $identifier > 0) {
            $user = User::whereNull('deleted_at')->find((int) $identifier);
            if ($user) {
                $userId = (int) $user->id;
                $employeeId = $user->id_emp ?: null;
            }
        }

        // If no user by ID, check if identifier matches User id_emp (NIP)
        if (!$user) {
            $user = User::whereNull('deleted_at')->where('id_emp', $identifier)->first();
            if ($user) {
                $userId = (int) $user->id;
                $employeeId = $user->id_emp;
            }
        }

        // Query active offline training records matching this participant
        $recordQuery = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->whereNull('otu.deleted_at')
            ->whereNull('ot.deleted_at');
        OfflineTraining::applyRecordScope($recordQuery, 'ot');

        $recordQuery->where(function ($q) use ($userId, $employeeId, $identifier) {
            if ($userId) {
                $q->where('otu.user_id', $userId);
                if ($employeeId) {
                    $q->orWhere('otu.employee_id', $employeeId);
                }
            } else {
                if (is_numeric($identifier)) {
                    $q->where('otu.user_id', (int) $identifier)
                      ->orWhere('otu.employee_id', $identifier);
                } else {
                    $q->where('otu.employee_id', $identifier);
                }
            }
        });

        $latestRecord = (clone $recordQuery)
            ->orderByDesc('otu.id')
            ->first();

        // If no active training records found for this participant, return null
        if (!$latestRecord) {
            return null;
        }

        // Backfill user if not resolved earlier
        if (!$user && !empty($latestRecord->user_id)) {
            $user = User::whereNull('deleted_at')->find($latestRecord->user_id);
        }
        if (!$user && !empty($latestRecord->employee_id)) {
            $user = User::whereNull('deleted_at')->where('id_emp', $latestRecord->employee_id)->first();
        }

        $finalUserId = $latestRecord->user_id ?: ($user?->id);
        $finalEmployeeId = $latestRecord->employee_id ?: ($user?->id_emp);
        $finalName = $latestRecord->name ?: ($user?->name ?: 'Participant');

        return [
            'user'           => $user,
            'user_id'        => $finalUserId,
            'employee_id'    => $finalEmployeeId,
            'name'           => $finalName,
            'grade'          => $latestRecord->grade ?? '',
            'job_position'   => $latestRecord->job_position ?? '',
            'team'           => $latestRecord->team ?? ($user?->team ?? ''),
            'department'     => $latestRecord->department ?? ($user?->department ?? ''),
            'company'        => $latestRecord->company ?? 'PT Krakatau Posco',
            'raw_identifier' => $identifier,
        ];
    }

    /**
     * Build query for a participant's training history.
     *
     * @param array $participantInfo
     * @param array $filters
     * @return Builder
     */
    public static function buildParticipantQuery(array $participantInfo, array $filters = []): Builder
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');
        if (!$hasTables) {
            return DB::table('offline_training_users as otu')->whereRaw('1 = 0');
        }

        $userId = $participantInfo['user_id'] ?? null;
        $employeeId = $participantInfo['employee_id'] ?? null;

        $query = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->leftJoin('users as u', 'u.id', '=', 'otu.user_id')
            ->whereNull('otu.deleted_at')
            ->whereNull('ot.deleted_at');

        OfflineTraining::applyRecordScope($query, 'ot');

        // Strictly scope to this participant
        $query->where(function ($q) use ($userId, $employeeId) {
            if ($userId && $employeeId) {
                $q->where('otu.user_id', $userId)
                  ->orWhere('otu.employee_id', $employeeId);
            } elseif ($userId) {
                $q->where('otu.user_id', $userId);
            } elseif ($employeeId) {
                $q->where('otu.employee_id', $employeeId);
            } else {
                $q->whereRaw('1 = 0');
            }
        });

        $query->select([
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
            self::bondDurationSelect(),
            'otu.bond_end_date',
            ...self::bondAmountSelect(),
            'otu.l1_program',
            'otu.l2_numerical',
            'otu.l2_verbal',
            'otu.l3_numerical',
            'otu.l3_verbal',
            'otu.pic',
            'otu.created_at',
            'ot.id as training_id',
            'ot.course_name',
            'ot.sub_course_name',
            DB::raw("COALESCE(ot.type, '-') as type"),
            self::requirementSelect(),
            'ot.category',
            'ot.level',
            'ot.batch',
            'ot.main_classification',
            'ot.sub_classification',
            'ot.counted_corporate_kpi',
            'ot.sub_corporate_kpi',
            'ot.corporate_kpi_year',
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
            'ot.end_date',
            'ot.finish_date',
            'ot.start_time',
            'ot.end_time',
            'u.name as user_name',
        ]);

        // Filter: Course Name / Title (Safe string extraction)
        $courseParam = '';
        if (isset($filters['course_name']) && is_string($filters['course_name'])) {
            $courseParam = trim($filters['course_name']);
        } elseif (isset($filters['title']) && is_string($filters['title'])) {
            $courseParam = trim($filters['title']);
        } elseif (isset($filters['learning_title']) && is_string($filters['learning_title'])) {
            $courseParam = trim($filters['learning_title']);
        } elseif (isset($filters['q']) && is_string($filters['q'])) {
            $courseParam = trim($filters['q']);
        }
        if ($courseParam !== '') {
            $query->where(function ($q) use ($courseParam) {
                $q->where('ot.course_name', 'like', "%{$courseParam}%")
                  ->orWhere('ot.sub_course_name', 'like', "%{$courseParam}%");
            });
        }

        // Filter: Type
        $typeParam = trim((string) ($filters['type'] ?? ($filters['training_type'] ?? 'all')));
        if ($typeParam !== '' && $typeParam !== 'all') {
            $query->where('ot.type', $typeParam);
        }

        // Filter: Status
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

        // Filter: Year
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

        // More Filter: Date Range (from_date & to_date)
        $fromDateRaw = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDateRaw = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        $fromDate = self::parseDate(is_string($fromDateRaw) ? $fromDateRaw : null);
        $toDate = self::parseDate(is_string($toDateRaw) ? $toDateRaw : null);

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

        // More Filter: Progress Range
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

        // More Filter: Duration Range
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

        // More Filter: Attendance (Present / Absent)
        $attendanceParam = strtolower(trim((string) ($filters['attendance'] ?? 'all')));
        if (filled($attendanceParam) && $attendanceParam !== 'all') {
            if ($attendanceParam === 'present' || $attendanceParam === '1' || $attendanceParam === 'yes') {
                $query->where(function ($q) {
                    $q->where('otu.attendance', true)
                      ->orWhere('otu.attendance', 1);
                });
            } elseif ($attendanceParam === 'absent' || $attendanceParam === '0' || $attendanceParam === 'no') {
                $query->where(function ($q) {
                    $q->where('otu.attendance', false)
                      ->orWhere('otu.attendance', 0);
                });
            }
        }

        // More Filter: Assessment Score
        $scoreMin = $filters['score_min'] ?? ($filters['assessment_score_min'] ?? null);
        $scoreMax = $filters['score_max'] ?? ($filters['assessment_score_max'] ?? null);
        if (filled($scoreMin) && is_numeric($scoreMin)) {
            $query->where('otu.assessment_score', '>=', (float) $scoreMin);
        }
        if (filled($scoreMax) && is_numeric($scoreMax)) {
            $query->where('otu.assessment_score', '<=', (float) $scoreMax);
        }

        // More Filter: Learning Hours
        $hoursMin = $filters['learning_hours_min'] ?? ($filters['hours_min'] ?? null);
        $hoursMax = $filters['learning_hours_max'] ?? ($filters['hours_max'] ?? null);
        if (filled($hoursMin) && is_numeric($hoursMin)) {
            $query->where('otu.learning_hours', '>=', (float) $hoursMin);
        }
        if (filled($hoursMax) && is_numeric($hoursMax)) {
            $query->where('otu.learning_hours', '<=', (float) $hoursMax);
        }

        // More Filter: Main Classification (Strictly from More Filters explicitly, NEVER from back-button navigation context)
        $classParam = trim((string) ($filters['filter_classification'] ?? ($filters['main_classification'] ?? '')));
        if ($classParam !== '' && $classParam !== 'all') {
            $categorySlug = $classParam;
            $classificationSlug = str_replace('-', ' ', strtolower($categorySlug));
            $resolved = RecordOfflineController::resolveClassification($categorySlug);
            if ($resolved['is_unclassified']) {
                $query->where(function ($q) {
                    $q->whereNull('ot.main_classification')
                      ->orWhere('ot.main_classification', '');
                });
            } else {
                $resolvedName = $resolved['name'];
                $query->where(function ($q) use ($categorySlug, $classificationSlug, $resolvedName) {
                    $q->where('ot.main_classification', $resolvedName)
                      ->orWhere('ot.main_classification', $categorySlug)
                      ->orWhereRaw('LOWER(ot.main_classification) = ?', [strtolower($resolvedName)])
                      ->orWhereRaw('LOWER(ot.main_classification) = ?', [$classificationSlug])
                      ->orWhereRaw('LOWER(ot.main_classification) = ?', [strtolower($categorySlug)]);
                });
            }
        }

        // More Filter: Sub Classification
        $subClassParam = trim((string) ($filters['sub_classification'] ?? ''));
        if ($subClassParam !== '' && $subClassParam !== 'all') {
            $query->where('ot.sub_classification', 'like', "%{$subClassParam}%");
        }

        // More Filter: Category
        $categoryParam = trim((string) ($filters['category'] ?? 'all'));
        if (filled($categoryParam) && $categoryParam !== 'all') {
            $query->where('ot.category', $categoryParam);
        }

        // More Filter: Level
        $levelParam = trim((string) ($filters['level'] ?? 'all'));
        if (filled($levelParam) && $levelParam !== 'all') {
            $query->where('ot.level', $levelParam);
        }

        // More Filter: Batch
        $batchParam = trim((string) ($filters['batch'] ?? ''));
        if ($batchParam !== '' && $batchParam !== 'all') {
            $query->where('ot.batch', 'like', "%{$batchParam}%");
        }

        // More Filter: Certificate
        $certParam = strtolower(trim((string) ($filters['certificate'] ?? 'all')));
        if (filled($certParam) && $certParam !== 'all') {
            if ($certParam === 'yes' || $certParam === 'available' || $certParam === '1') {
                $query->whereNotNull('otu.certificate')->where('otu.certificate', '!=', '')->where('otu.certificate', '!=', 'No');
            } elseif ($certParam === 'no' || $certParam === '0') {
                $query->where(function ($q) {
                    $q->whereNull('otu.certificate')
                      ->orWhere('otu.certificate', '')
                      ->orWhere('otu.certificate', 'No');
                });
            }
        }

        // More Filter: Corporate KPI
        $kpiParam = strtolower(trim((string) ($filters['corporate_kpi'] ?? ($filters['counted_corporate_kpi'] ?? 'all'))));
        if (filled($kpiParam) && $kpiParam !== 'all') {
            if ($kpiParam === 'yes' || $kpiParam === '1') {
                $query->where('ot.counted_corporate_kpi', true);
            } elseif ($kpiParam === 'no' || $kpiParam === '0') {
                $query->where(function ($q) {
                    $q->where('ot.counted_corporate_kpi', false)
                      ->orWhereNull('ot.counted_corporate_kpi');
                });
            }
        }

        // More Filter: Sub Corporate KPI
        $subKpiParam = trim((string) ($filters['sub_corporate_kpi'] ?? ''));
        if ($subKpiParam !== '' && $subKpiParam !== 'all') {
            $query->where('ot.sub_corporate_kpi', 'like', "%{$subKpiParam}%");
        }

        // More Filter: Bond Status
        $bondParam = strtolower(trim((string) ($filters['bond_status'] ?? 'all')));
        if (filled($bondParam) && $bondParam !== 'all') {
            if ($bondParam === 'yes' || $bondParam === '1') {
                $query->whereNotNull('otu.bond_status')->where('otu.bond_status', '!=', 'No')->where('otu.bond_status', '!=', '');
            } elseif ($bondParam === 'no' || $bondParam === '0') {
                $query->where(function ($q) {
                    $q->whereNull('otu.bond_status')
                      ->orWhere('otu.bond_status', 'No')
                      ->orWhere('otu.bond_status', '');
                });
            }
        }

        // More Filter: Bond End Date Range
        $bondFromRaw = $filters['bond_end_from'] ?? ($filters['bond_end_date_from'] ?? null);
        $bondToRaw = $filters['bond_end_to'] ?? ($filters['bond_end_date_to'] ?? null);
        $bondFrom = self::parseDate(is_string($bondFromRaw) ? $bondFromRaw : null);
        $bondTo = self::parseDate(is_string($bondToRaw) ? $bondToRaw : null);
        if ($bondFrom && $bondTo) {
            $query->whereBetween('otu.bond_end_date', [$bondFrom, $bondTo]);
        } elseif ($bondFrom) {
            $query->where('otu.bond_end_date', '>=', $bondFrom);
        } elseif ($bondTo) {
            $query->where('otu.bond_end_date', '<=', $bondTo);
        }

        // More Filter: Bond Amount Range
        $bondAmtMin = $filters['bond_amount_min'] ?? null;
        $bondAmtMax = $filters['bond_amount_max'] ?? null;
        if (filled($bondAmtMin) && is_numeric($bondAmtMin)) {
            $query->where(self::bondAmountColumn(), '>=', (float) $bondAmtMin);
        }
        if (filled($bondAmtMax) && is_numeric($bondAmtMax)) {
            $query->where(self::bondAmountColumn(), '<=', (float) $bondAmtMax);
        }

        // More Filter: Provider
        $providerParam = trim((string) ($filters['provider'] ?? 'all'));
        if (filled($providerParam) && $providerParam !== 'all') {
            $query->where('ot.provider', $providerParam);
        }

        // More Filter: Sub Provider
        $subProvParam = trim((string) ($filters['sub_provider'] ?? ''));
        if ($subProvParam !== '' && $subProvParam !== 'all') {
            $query->where('ot.sub_provider', 'like', "%{$subProvParam}%");
        }

        // More Filter: Provider Name
        $provNameParam = trim((string) ($filters['provider_name'] ?? ''));
        if ($provNameParam !== '' && $provNameParam !== 'all') {
            $query->where('ot.provider_name', 'like', "%{$provNameParam}%");
        }

        // More Filter: Delivery Method
        $deliveryParam = trim((string) ($filters['delivery_method'] ?? 'all'));
        if (filled($deliveryParam) && $deliveryParam !== 'all') {
            $query->where('ot.delivery_method', $deliveryParam);
        }

        // More Filter: Sub Delivery Method
        $subDelivParam = trim((string) ($filters['sub_delivery_method'] ?? ''));
        if ($subDelivParam !== '' && $subDelivParam !== 'all') {
            $query->where('ot.sub_delivery_method', 'like', "%{$subDelivParam}%");
        }

        // More Filter: Venue
        $venueParam = trim((string) ($filters['venue'] ?? ''));
        if ($venueParam !== '' && $venueParam !== 'all') {
            $query->where('ot.venue', 'like', "%{$venueParam}%");
        }

        // More Filter: Instructor
        $instructorParam = trim((string) ($filters['instructor'] ?? ''));
        if ($instructorParam !== '' && $instructorParam !== 'all') {
            $query->where('ot.instructor', 'like', "%{$instructorParam}%");
        }

        // More Filter: Attendance Reason
        $attReasonParam = trim((string) ($filters['attendance_reason'] ?? ''));
        if ($attReasonParam !== '') {
            $query->where('otu.attendance_reason', 'like', "%{$attReasonParam}%");
        }

        // More Filter: L1 Program
        $l1Param = trim((string) ($filters['l1_program'] ?? ($filters['l1'] ?? '')));
        if ($l1Param !== '') {
            $query->where('otu.l1_program', 'like', "%{$l1Param}%");
        }

        // More Filter: L2 Numerical Range
        $l2NumMin = $filters['l2_numerical_min'] ?? ($filters['l2_min'] ?? null);
        $l2NumMax = $filters['l2_numerical_max'] ?? ($filters['l2_max'] ?? null);
        if (filled($l2NumMin) && is_numeric($l2NumMin)) {
            $query->where('otu.l2_numerical', '>=', (float) $l2NumMin);
        }
        if (filled($l2NumMax) && is_numeric($l2NumMax)) {
            $query->where('otu.l2_numerical', '<=', (float) $l2NumMax);
        }

        // More Filter: L2 Verbal
        $l2vParam = trim((string) ($filters['l2_verbal'] ?? ''));
        if ($l2vParam !== '') {
            $query->where('otu.l2_verbal', 'like', "%{$l2vParam}%");
        }

        // More Filter: L3 Numerical Range
        $l3NumMin = $filters['l3_numerical_min'] ?? ($filters['l3_min'] ?? null);
        $l3NumMax = $filters['l3_numerical_max'] ?? ($filters['l3_max'] ?? null);
        if (filled($l3NumMin) && is_numeric($l3NumMin)) {
            $query->where('otu.l3_numerical', '>=', (float) $l3NumMin);
        }
        if (filled($l3NumMax) && is_numeric($l3NumMax)) {
            $query->where('otu.l3_numerical', '<=', (float) $l3NumMax);
        }

        // More Filter: L3 Verbal
        $l3vParam = trim((string) ($filters['l3_verbal'] ?? ''));
        if ($l3vParam !== '') {
            $query->where('otu.l3_verbal', 'like', "%{$l3vParam}%");
        }

        // More Filter: PIC
        $picParam = trim((string) ($filters['pic'] ?? ''));
        if ($picParam !== '') {
            $query->where('otu.pic', 'like', "%{$picParam}%");
        }

        return $query;
    }

    /**
     * Get summary metrics strictly scoped to a participant.
     *
     * @param array $participantInfo
     * @return array
     */
    public static function getParticipantSummary(array $participantInfo): array
    {
        $baseQuery = self::buildParticipantQuery($participantInfo);

        $totalTraining = (int) (clone $baseQuery)->count();
        $finished = (int) (clone $baseQuery)->where('otu.progress', '>=', 100)->count();
        $ongoing = (int) (clone $baseQuery)->where(function ($q) {
            $q->where('otu.progress', '<', 100)
              ->orWhereNull('otu.progress');
        })->count();
        $learningHours = (float) (clone $baseQuery)->sum('otu.learning_hours');

        return [
            'total_training' => $totalTraining,
            'finished'       => $finished,
            'ongoing'        => $ongoing,
            'learning_hours' => round($learningHours, 2),
        ];
    }

    /**
     * Build query for participants enrolled in a specific offline training event.
     *
     * @param int $trainingId
     * @param array $filters
     * @return Builder
     */
    public static function buildCourseQuery(int $trainingId, array $filters = []): Builder
    {
        $hasTables = Schema::hasTable('offline_trainings') && Schema::hasTable('offline_training_users');
        if (!$hasTables) {
            return DB::table('offline_training_users as otu')->whereRaw('1 = 0');
        }

        $query = DB::table('offline_training_users as otu')
            ->join('offline_trainings as ot', 'ot.id', '=', 'otu.offline_training_id')
            ->leftJoin('users as u', 'u.id', '=', 'otu.user_id')
            ->where('otu.offline_training_id', $trainingId)
            ->whereNull('otu.deleted_at')
            ->whereNull('ot.deleted_at');

        OfflineTraining::applyRecordScope($query, 'ot');

        $query->select([
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
            self::bondDurationSelect(),
            'otu.bond_end_date',
            ...self::bondAmountSelect(),
            'otu.l1_program',
            'otu.l2_numerical',
            'otu.l2_verbal',
            'otu.l3_numerical',
            'otu.l3_verbal',
            'otu.pic',
            'otu.created_at',
            'ot.id as training_id',
            'ot.course_name',
            'ot.sub_course_name',
            DB::raw("COALESCE(ot.type, '-') as type"),
            self::requirementSelect(),
            'ot.category',
            'ot.level',
            'ot.batch',
            'ot.main_classification',
            'ot.sub_classification',
            'ot.counted_corporate_kpi',
            'ot.corporate_kpi_year',
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
            'ot.end_date',
            'ot.finish_date',
            'ot.start_time',
            'ot.end_time',
            'u.name as user_name',
        ]);

        // 1. Filter: Participant Search / NIP (Safe string extraction, avoids array to string conversion)
        $searchParam = '';
        if (isset($filters['participant']) && is_string($filters['participant'])) {
            $searchParam = trim($filters['participant']);
        } elseif (isset($filters['employee_id']) && is_string($filters['employee_id'])) {
            $searchParam = trim($filters['employee_id']);
        } elseif (isset($filters['search']) && is_string($filters['search'])) {
            $searchParam = trim($filters['search']);
        } elseif (isset($filters['search']['value']) && is_string($filters['search']['value'])) {
            $searchParam = trim($filters['search']['value']);
        }

        if ($searchParam !== '') {
            $query->where(function ($q) use ($searchParam) {
                $q->where('otu.employee_id', 'like', "%{$searchParam}%")
                  ->orWhere('otu.name', 'like', "%{$searchParam}%")
                  ->orWhere('u.name', 'like', "%{$searchParam}%");
            });
        }

        // 2. Filter: Type
        $typeParam = trim((string) ($filters['type'] ?? ($filters['training_type'] ?? 'all')));
        if ($typeParam !== '' && $typeParam !== 'all') {
            $query->where('ot.type', $typeParam);
        }

        // 3. Filter: Status
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

        // 4. Filter: Year
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

        // 5. More Filter: Grade
        $gradeParam = trim((string) ($filters['grade'] ?? 'all'));
        if ($gradeParam !== '' && $gradeParam !== 'all') {
            $query->where('otu.grade', $gradeParam);
        }

        // 6. More Filter: Job Position
        $jobPosParam = trim((string) ($filters['job_position'] ?? ''));
        if ($jobPosParam !== '' && $jobPosParam !== 'all') {
            $query->where('otu.job_position', 'like', "%{$jobPosParam}%");
        }

        // 7. More Filter: Team
        $teamParam = trim((string) ($filters['team'] ?? 'all'));
        if ($teamParam !== '' && $teamParam !== 'all') {
            $query->where('otu.team', $teamParam);
        }

        // 8. More Filter: Department
        $deptParam = trim((string) ($filters['department'] ?? 'all'));
        if ($deptParam !== '' && $deptParam !== 'all') {
            $query->where('otu.department', $deptParam);
        }

        // 9. More Filter: Company
        $companyParam = trim((string) ($filters['company'] ?? 'all'));
        if ($companyParam !== '' && $companyParam !== 'all') {
            $query->where('otu.company', $companyParam);
        }

        // 10. More Filter: Progress Range
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

        // 11. More Filter: Learning Hours Range
        $hoursMin = $filters['learning_hours_min'] ?? ($filters['hours_min'] ?? null);
        $hoursMax = $filters['learning_hours_max'] ?? ($filters['hours_max'] ?? null);
        if (filled($hoursMin) && is_numeric($hoursMin)) {
            $query->where('otu.learning_hours', '>=', (float) $hoursMin);
        }
        if (filled($hoursMax) && is_numeric($hoursMax)) {
            $query->where('otu.learning_hours', '<=', (float) $hoursMax);
        }

        // 12. More Filter: Assessment Score Range
        $scoreMin = $filters['assessment_score_min'] ?? ($filters['score_min'] ?? null);
        $scoreMax = $filters['assessment_score_max'] ?? ($filters['score_max'] ?? null);
        if (filled($scoreMin) && is_numeric($scoreMin)) {
            $query->where('otu.assessment_score', '>=', (float) $scoreMin);
        }
        if (filled($scoreMax) && is_numeric($scoreMax)) {
            $query->where('otu.assessment_score', '<=', (float) $scoreMax);
        }

        // 13. More Filter: Certificate
        $certParam = strtolower(trim((string) ($filters['certificate'] ?? 'all')));
        if (filled($certParam) && $certParam !== 'all') {
            if ($certParam === 'yes' || $certParam === 'available' || $certParam === '1') {
                $query->whereNotNull('otu.certificate')->where('otu.certificate', '!=', '')->where('otu.certificate', '!=', 'No');
            } elseif ($certParam === 'no' || $certParam === '0') {
                $query->where(function ($q) {
                    $q->whereNull('otu.certificate')
                      ->orWhere('otu.certificate', '')
                      ->orWhere('otu.certificate', 'No');
                });
            }
        }

        // 14. More Filter: Attendance
        $attendanceParam = strtolower(trim((string) ($filters['attendance'] ?? 'all')));
        if (filled($attendanceParam) && $attendanceParam !== 'all') {
            if ($attendanceParam === 'present' || $attendanceParam === '1' || $attendanceParam === 'yes') {
                $query->where(function ($q) {
                    $q->where('otu.attendance', true)
                      ->orWhere('otu.attendance', 1);
                });
            } elseif ($attendanceParam === 'absent' || $attendanceParam === '0' || $attendanceParam === 'no') {
                $query->where(function ($q) {
                    $q->where('otu.attendance', false)
                      ->orWhere('otu.attendance', 0);
                });
            }
        }

        // 15. More Filter: Attendance Reason
        $attReasonParam = trim((string) ($filters['attendance_reason'] ?? ''));
        if ($attReasonParam !== '') {
            $query->where('otu.attendance_reason', 'like', "%{$attReasonParam}%");
        }

        // 16. More Filter: Bond Status
        $bondParam = strtolower(trim((string) ($filters['bond_status'] ?? 'all')));
        if (filled($bondParam) && $bondParam !== 'all') {
            if ($bondParam === 'yes' || $bondParam === '1') {
                $query->whereNotNull('otu.bond_status')->where('otu.bond_status', '!=', 'No')->where('otu.bond_status', '!=', '');
            } elseif ($bondParam === 'no' || $bondParam === '0') {
                $query->where(function ($q) {
                    $q->whereNull('otu.bond_status')
                      ->orWhere('otu.bond_status', 'No')
                      ->orWhere('otu.bond_status', '');
                });
            }
        }

        // 17. More Filter: Bond End Date Range
        $bondFromRaw = $filters['bond_end_from'] ?? ($filters['bond_end_date_from'] ?? null);
        $bondToRaw = $filters['bond_end_to'] ?? ($filters['bond_end_date_to'] ?? null);
        $bondFrom = self::parseDate(is_string($bondFromRaw) ? $bondFromRaw : null);
        $bondTo = self::parseDate(is_string($bondToRaw) ? $bondToRaw : null);
        if ($bondFrom && $bondTo) {
            $query->whereBetween('otu.bond_end_date', [$bondFrom, $bondTo]);
        } elseif ($bondFrom) {
            $query->where('otu.bond_end_date', '>=', $bondFrom);
        } elseif ($bondTo) {
            $query->where('otu.bond_end_date', '<=', $bondTo);
        }

        // 18. More Filter: Bond Amount Range
        $bondAmtMin = $filters['bond_amount_min'] ?? null;
        $bondAmtMax = $filters['bond_amount_max'] ?? null;
        if (filled($bondAmtMin) && is_numeric($bondAmtMin)) {
            $query->where(self::bondAmountColumn(), '>=', (float) $bondAmtMin);
        }
        if (filled($bondAmtMax) && is_numeric($bondAmtMax)) {
            $query->where(self::bondAmountColumn(), '<=', (float) $bondAmtMax);
        }

        // 19. More Filter: L1 Program
        $l1Param = trim((string) ($filters['l1_program'] ?? ($filters['l1'] ?? '')));
        if ($l1Param !== '') {
            $query->where('otu.l1_program', 'like', "%{$l1Param}%");
        }

        // 20. More Filter: L2 Numerical Range
        $l2NumMin = $filters['l2_numerical_min'] ?? ($filters['l2_min'] ?? null);
        $l2NumMax = $filters['l2_numerical_max'] ?? ($filters['l2_max'] ?? null);
        if (filled($l2NumMin) && is_numeric($l2NumMin)) {
            $query->where('otu.l2_numerical', '>=', (float) $l2NumMin);
        }
        if (filled($l2NumMax) && is_numeric($l2NumMax)) {
            $query->where('otu.l2_numerical', '<=', (float) $l2NumMax);
        }

        // 21. More Filter: L2 Verbal
        $l2vParam = trim((string) ($filters['l2_verbal'] ?? ''));
        if ($l2vParam !== '') {
            $query->where('otu.l2_verbal', 'like', "%{$l2vParam}%");
        }

        // 22. More Filter: L3 Numerical Range
        $l3NumMin = $filters['l3_numerical_min'] ?? ($filters['l3_min'] ?? null);
        $l3NumMax = $filters['l3_numerical_max'] ?? ($filters['l3_max'] ?? null);
        if (filled($l3NumMin) && is_numeric($l3NumMin)) {
            $query->where('otu.l3_numerical', '>=', (float) $l3NumMin);
        }
        if (filled($l3NumMax) && is_numeric($l3NumMax)) {
            $query->where('otu.l3_numerical', '<=', (float) $l3NumMax);
        }

        // 23. More Filter: L3 Verbal
        $l3vParam = trim((string) ($filters['l3_verbal'] ?? ''));
        if ($l3vParam !== '') {
            $query->where('otu.l3_verbal', 'like', "%{$l3vParam}%");
        }

        // 24. More Filter: PIC
        $picParam = trim((string) ($filters['pic'] ?? ''));
        if ($picParam !== '') {
            $query->where('otu.pic', 'like', "%{$picParam}%");
        }

        // 25. Date Range (from_date & to_date)
        $fromDateRaw = $filters['from_date'] ?? ($filters['date_from'] ?? null);
        $toDateRaw = $filters['to_date'] ?? ($filters['date_to'] ?? null);
        $fromDate = self::parseDate(is_string($fromDateRaw) ? $fromDateRaw : null);
        $toDate = self::parseDate(is_string($toDateRaw) ? $toDateRaw : null);

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

        return $query;
    }

    /**
     * Get summary metrics strictly scoped to an offline training event.
     *
     * @param int $trainingId
     * @param OfflineTraining $training
     * @return array
     */
    public static function getCourseSummary(int $trainingId, OfflineTraining $training): array
    {
        $baseQuery = self::buildCourseQuery($trainingId);

        $totalParticipants = (int) (clone $baseQuery)->count();
        $finished = (int) (clone $baseQuery)->where('otu.progress', '>=', 100)->count();
        $ongoing = (int) (clone $baseQuery)->where(function ($q) {
            $q->where('otu.progress', '<', 100)
              ->orWhereNull('otu.progress');
        })->count();
        $avgProgress = (float) ((clone $baseQuery)->avg('otu.progress') ?? 0);
        $learningHours = (float) ($training->learning_hours ?? 0);

        return [
            'total_participants' => $totalParticipants,
            'finished'           => $finished,
            'ongoing'            => $ongoing,
            'avg_progress'       => round($avgProgress, 1),
            'learning_hours'     => round($learningHours, 2),
        ];
    }

    /**
     * Get detail of a specific offline training record by record_id and optional user_id.
     */
    public static function getRecordDetail(int|string $recordId, ?int $userId = null): ?object
    {
        $query = self::buildQuery()->where('otu.id', $recordId);

        if ($userId !== null) {
            $query->where(function ($q) use ($userId) {
                $q->where('otu.user_id', $userId);
                $userEmpId = \Illuminate\Support\Facades\Auth::user()?->id_emp;
                if (!empty($userEmpId)) {
                    $q->orWhere('otu.employee_id', $userEmpId);
                }
            });
        }

        $record = $query->first();
        if ($record) {
            $record->source = 'Non-KP OWL';
            $record->delivery_method = \App\Models\OfflineTraining::normalizeDeliveryMethod($record->delivery_method ?? 'Offline');
            $record->user_name = !empty($record->user_name) ? $record->user_name : (!empty($record->participant_name) ? $record->participant_name : (\Illuminate\Support\Facades\Auth::user()?->name ?? '-'));
            $record->employee_id = !empty($record->employee_id) ? $record->employee_id : (\Illuminate\Support\Facades\Auth::user()?->employee_id ?? \Illuminate\Support\Facades\Auth::user()?->nip ?? \Illuminate\Support\Facades\Auth::user()?->id_emp ?? '-');
            $record->team = !empty($record->team) ? $record->team : (\Illuminate\Support\Facades\Auth::user()?->team ?? '-');
        }

        return $record;
    }
}
