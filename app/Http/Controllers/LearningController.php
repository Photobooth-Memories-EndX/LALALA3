<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\ContentUser;
use App\Models\CertificateSetting;
use App\Models\Certificate;
use App\Services\CertificateEligibilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LearningController extends Controller
{
    public function ongoing()
    {
        $content_user = ContentUser::with('content')
            ->where('user_id', auth()->id())
            ->where('finish', '0')
            ->latest()
            ->get();

        return view('user.learning.ongoing', compact('content_user'));
    }

    public function record(CertificateEligibilityService $eligibilityService)
    {
        $userId = auth()->id();

        $content_user = ContentUser::with([
                'content.classification',
                'assesment.scores',
            ])
            ->where('user_id', $userId)
            ->where('finish', '1')
            ->latest()
            ->get();

        $certificateSettings = CertificateSetting::with([
                'content',
                'trainingPlan.trainingplandetail.content',
                'assessment',
            ])
            ->where('is_active', 1)
            ->get();

        $certificates = Certificate::where('user_id', $userId)
            ->get()->keyBy('certificate_setting_id');

        $certificateOpportunities = $certificateSettings->map(function ($setting) use ($eligibilityService, $certificates) {
            return [
                'setting'     => $setting,
                'eligibility' => $eligibilityService->check($setting, auth()->user()),
                'certificate' => $certificates->get($setting->id),
            ];
        });

        return view('user.learning.finish', compact(
            'content_user',
            'certificateSettings',
            'certificateOpportunities'
        ));
    }

    public function assesment()
    {
        $user   = Auth::user();
        $userId = Auth::id();

        $assessments = Assesment::with([
                'assesments_assigned',
                'contents',
                'scores',
            ])
            ->whereHas('assesments_assigned', function ($query) use ($user) {
                $query->where('assigned_id', $user->id_emp)
                    ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                    ->orWhere('assigned_id', $user->id_department);
            })
            ->orWhereHas('contents', function ($query) {
                $query->whereHas('contentuser', function ($q) {
                    $q->where('progress', '>=', 100)->where('user_id', Auth::id());
                });
            })
            ->orWhereHas('scores', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->latest()
            ->get();

        $result = AssesmentResult::with('assesment')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();

        return view('user.learning.result', compact('result', 'assessments'));
    }

    /**
     * Lightweight dashboard – only loads summary counts and profile data.
     * Tab content is fetched on-demand via dashboardPartial().
     */
    public function dashboard()
    {
        $userId = Auth::id();
        $user   = Auth::user();

        $learningHours = $this->getUserLearningHours($userId);

        $totalLearning = ContentUser::where('user_id', $userId)->count();

        $completedLearning = ContentUser::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('progress', '>=', 100)->orWhere('finish', '1');
            })
            ->count();

        $ongoingLearning = ContentUser::where('user_id', $userId)
            ->where('finish', '0')
            ->count();

        $passedAssessment = AssesmentResult::where('user_id', $userId)
            ->where('final', 1)
            ->where('pass', 1)
            ->count();

        return view('user.learning.dashboard', compact(
            'user',
            'learningHours',
            'totalLearning',
            'completedLearning',
            'ongoingLearning',
            'passedAssessment'
        ));
    }

    /**
     * Returns HTML fragment for a single tab.
     * Called via AJAX from dashboard-script.blade.php.
     */
    public function dashboardPartial(string $tab, CertificateEligibilityService $eligibilityService)
    {
        $user   = Auth::user();
        $userId = Auth::id();

        switch ($tab) {
            case 'ongoing':
                $ongoing_content_user = $this->getOngoingLearningContents($userId);
                $ongoingLearning      = ContentUser::where('user_id', $userId)->where('finish', '0')->count();
                $averageProgress      = (int) round(ContentUser::where('user_id', $userId)->avg('progress') ?? 0);

                return view('user.learning.dashboard-partials.ongoing', compact(
                    'ongoing_content_user',
                    'ongoingLearning',
                    'averageProgress'
                ));

            case 'record':
                $content_user = ContentUser::with([
                        'content.classification',
                        'assesment' => fn ($q) => $q->with([
                            'scores' => fn ($q) => $q->where('user_id', $userId),
                        ]),
                    ])
                    ->where('user_id', $userId)
                    ->where('finish', '1')
                    ->latest()
                    ->get();

                $averageProgress = (int) round(
                    ContentUser::where('user_id', $userId)->where('finish', '1')->avg('progress') ?? 0
                );

                $certificateSettings = CertificateSetting::select([
                        'id', 'certificate_type', 'content_id', 'is_active',
                        'minimum_progress', 'minimum_score',
                    ])
                    ->where('is_active', 1)
                    ->get();

                return view('user.learning.dashboard-partials.record', compact(
                    'content_user',
                    'averageProgress',
                    'certificateSettings'
                ));

            case 'assessment':
                $assessments = $this->getUserAssessments($user, $userId);
                $result      = $this->getUserAssessmentResults($userId);

                return view('user.learning.dashboard-partials.assessment', compact(
                    'assessments',
                    'result'
                ));

            case 'claim':
                return view('user.learning.dashboard-partials.claim');

            case 'certificate':
                $userCertificates = Certificate::where('user_id', $userId)->latest()->get();

                return view('user.learning.dashboard-partials.certificate', compact('userCertificates'));

            case 'monitoring':
                return view('user.learning.dashboard-partials.monitoring');

            default:
                abort(404);
        }
    }

    // ─── Private helpers ────────────────────────────────────────────────────────

    private function getUserLearningHours($userId)
    {
        $hours = ContentUser::join('content', 'content_users.content_id', '=', 'content.id')
            ->where('content_users.user_id', $userId)
            ->whereNull('content.deleted_at')
            ->whereNotNull('content.duration')
            ->where('content.duration', '!=', '')
            ->sum(DB::raw("
                CASE
                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 2
                    THEN TIME_TO_SEC(content.duration) / 3600

                    WHEN LENGTH(content.duration) - LENGTH(REPLACE(content.duration, ':', '')) = 1
                    THEN TIME_TO_SEC(CONCAT('00:', content.duration)) / 3600

                    ELSE 0
                END
            "));

        return round($hours, 1);
    }

    private function getOngoingLearningContents($userId)
    {
        $search       = request('ongoing_search');
        $status       = request('ongoing_status', 'all');
        $year         = request('ongoing_year', 'all');
        $activitySort = request('ongoing_activity_sort', 'newest_activity');
        $progressSort = request('ongoing_progress_sort', 'all');

        $query = ContentUser::with([
                'content.classification',
                'assesment' => fn ($q) => $q->with([
                    'scores' => fn ($q) => $q->where('user_id', $userId),
                ]),
            ])
            ->where('user_id', $userId);

        if ($progressSort === 'completed') {
            $query->where('finish', '1');
        } else {
            $query->where('finish', '0');
        }

        $query->when($search, function ($q) use ($search) {
            $q->whereHas('content', function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhereHas('classification', function ($q) use ($search) {
                        $q->where('classification_name', 'like', '%' . $search . '%');
                    });
            });
        });

        $query->when($status !== 'all', fn ($q) => $q->where('status', $status));
        $query->when($year !== 'all', fn ($q) => $q->whereYear('created_at', $year));
        $query->when($progressSort === 'not_started', fn ($q) => $q->where('progress', 0));
        $query->when($progressSort === 'in_progress', fn ($q) => $q->whereBetween('progress', [1, 99]));
        $query->when($progressSort === 'almost_done', fn ($q) => $q->where('progress', '>=', 80)->where('progress', '<', 100));

        if ($progressSort === 'progress_highest') {
            $query->orderByDesc('progress');
        } elseif ($progressSort === 'progress_lowest') {
            $query->orderBy('progress');
        } elseif ($activitySort === 'oldest_activity') {
            $query->orderBy('updated_at');
        } else {
            $query->orderByDesc('updated_at');
        }

        return $query
            ->paginate(9, ['*'], 'ongoing_page')
            ->withQueryString()
            ->withPath(route('user.learning.dashboard'));
    }

    private function getUserAssessments($user, $userId)
    {
        return Assesment::with([
                'assesments_assigned',
                'contents',
                'scores'      => fn ($q) => $q->where('user_id', $userId),
                'trainingplan',
            ])
            ->where(function ($query) use ($user, $userId) {
                $query->whereHas('assesments_assigned', function ($q) use ($user) {
                    $q->where('assigned_id', $user->id_emp)
                        ->orWhere('assigned_id', $user->id_team ?? $user->id_tean)
                        ->orWhere('assigned_id', $user->id_department);
                })
                ->orWhereHas('contents', function ($q) use ($userId) {
                    $q->whereHas('contentuser', function ($q) use ($userId) {
                        $q->where('progress', '>=', 100)->where('user_id', $userId);
                    });
                })
                ->orWhereHas('scores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->latest()
            ->get();
    }

    private function getUserAssessmentResults($userId)
    {
        return AssesmentResult::with('assesment.trainingplan')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();
    }
}
