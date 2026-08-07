<?php

namespace App\Http\Controllers;

use App\Exports\AssessmentExport;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Services\Learning\CompletedPlaylistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * My Learning Assessment tab.
 *
 * Owns the assessment data and AJAX-rendered dashboard partial while the
 * LearningController remains the dashboard orchestrator.
 */
class LearningAssessmentController extends Controller
{
    public function __construct(private readonly CompletedPlaylistService $completedPlaylistService)
    {
    }

    /**
     * Render the Assessment tab fragment requested by the learning dashboard.
     */
    public function partial(Request $request)
    {
        $user   = Auth::user();
        $userId = Auth::id();
        $completedPlaylistIds = $this->completedPlaylistService
            ->forUser((int) $userId)
            ->pluck('id');

        $assessmentQuery = $this->getUserAssessmentQuery($user, $userId, $completedPlaylistIds);
        $filterOptions   = $this->getFilterOptions($assessmentQuery);
        $filteredQuery   = $this->applyFilters($assessmentQuery, $request, (int) $userId);
        $assessments     = $filteredQuery->get();

        return view('user.learning.dashboard-partials.assessment', [
            'assessments' => $assessments,
            'assessmentStats' => $this->summarizeAssessments($assessments, (int) $userId),
            'filterOptions' => $filterOptions,
            'result'      => $this->getUserAssessmentResults($userId),
        ]);
    }

    public function download(Request $request)
    {
        $user = Auth::user();
        $userId = (int) Auth::id();
        $completedPlaylistIds = $this->completedPlaylistService
            ->forUser($userId)
            ->pluck('id');
        $assessmentQuery = $this->getUserAssessmentQuery($user, $userId, $completedPlaylistIds);
        $assessments = $this->applyFilters($assessmentQuery, $request, $userId)
            ->get();

        return Excel::download(
            AssessmentExport::forMyLearning($assessments, $userId),
            'Assessment_List_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function getUserAssessmentQuery($user, $userId, $completedPlaylistIds)
    {
        return Assesment::with([
                'assesments_assigned',
                'contents',
                'scores'      => fn ($q) => $q->where('user_id', $userId),
                'trainingplan',
            ])
            ->where(function ($query) use ($user, $userId, $completedPlaylistIds) {
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
                ->orWhere(function ($q) use ($completedPlaylistIds) {
                    $q->where('type', 'trainingplan')
                        ->where('status', 'OK')
                        ->whereIn('trainingplan_id', $completedPlaylistIds);
                })
                ->orWhereHas('scores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            });
    }

    private function applyFilters($query, Request $request, $userId)
    {
        $type = $request->input('assessment_filter_type', 'all');
        $status = $request->input('assessment_filter_status', 'all');
        $score = $request->input('assessment_filter_score', 'all');
        $result = $request->input('assessment_filter_result', 'all');
        $date = $request->input('assessment_filter_date', 'all');
        $availability = $request->input('assessment_filter_availability', 'all');
        $search = trim((string) $request->input('assessment_search', ''));
        $now = now();

        $finalScore = fn ($q) => $q->where('user_id', $userId)->where('final', 1);

        return $query
            ->when($search !== '', function ($q) use ($search) {
                $keyword = '%' . $search . '%';
                $q->where(function ($searchQuery) use ($keyword) {
                    $searchQuery->where('title', 'like', $keyword)
                        ->orWhereHas('contents', fn ($content) => $content->where('title', 'like', $keyword))
                        ->orWhereHas('trainingplan', fn ($playlist) => $playlist->where('title', 'like', $keyword));
                });
            })
            ->when($type !== 'all', fn ($q) => $q->where('type', $type === 'playlist' ? 'trainingplan' : $type))
            ->when($status === 'passed', fn ($q) => $q->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 1)))
            ->when($status === 'failed', fn ($q) => $q
                ->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 0))
                ->whereDoesntHave('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 1)))
            ->when($status === 'not_started', fn ($q) => $q->whereDoesntHave('scores', $finalScore))
            ->when($request->filled('assessment_filter_year') && $request->input('assessment_filter_year') !== 'all',
                fn ($q) => $q->whereYear('fromdate', $request->input('assessment_filter_year')))
            ->when($request->filled('assessment_filter_month') && $request->input('assessment_filter_month') !== 'all',
                fn ($q) => $q->whereMonth('fromdate', $request->input('assessment_filter_month')))
            ->when($request->filled('assessment_filter_batch') && $request->input('assessment_filter_batch') !== 'all',
                fn ($q) => $q->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)
                    ->where('batch', $request->input('assessment_filter_batch'))))
            ->when($request->filled('assessment_filter_grade') && $request->input('assessment_filter_grade') !== 'all',
                fn ($q) => $q->where('passing_grade', $request->input('assessment_filter_grade')))
            ->when($score === 'none', fn ($q) => $q->whereDoesntHave('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->whereNotNull('score')))
            ->when($score === '0_59', fn ($q) => $q
                ->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->whereBetween('score', [0, 59.9999]))
                ->whereDoesntHave('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('score', '>=', 60)))
            ->when($score === '60_79', fn ($q) => $q
                ->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->whereBetween('score', [60, 79.9999]))
                ->whereDoesntHave('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('score', '>=', 80)))
            ->when($score === '80_100', fn ($q) => $q->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->whereBetween('score', [80, 100])))
            ->when($result === 'passed', fn ($q) => $q->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 1)))
            ->when($result === 'failed', fn ($q) => $q
                ->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 0))
                ->whereDoesntHave('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 1)))
            ->when($request->filled('assessment_filter_from_date'),
                fn ($q) => $q->whereDate('fromdate', '>=', $request->input('assessment_filter_from_date')))
            ->when($request->filled('assessment_filter_to_date'),
                fn ($q) => $q->whereDate('fromdate', '<=', $request->input('assessment_filter_to_date')))
            ->when($date === 'this_month', fn ($q) => $q->whereYear('fromdate', $now->year)->whereMonth('fromdate', $now->month))
            ->when($date === 'last_month', function ($q) use ($now) {
                $lastMonth = $now->copy()->subMonthNoOverflow();
                $q->whereYear('fromdate', $lastMonth->year)->whereMonth('fromdate', $lastMonth->month);
            })
            ->when($date === 'this_year', fn ($q) => $q->whereYear('fromdate', $now->year))
            ->when($availability === 'available', fn ($q) => $q->where('fromdate', '<=', $now)->where('todate', '>=', $now))
            ->when($availability === 'upcoming', fn ($q) => $q->where('fromdate', '>', $now))
            ->when($availability === 'expired', fn ($q) => $q->where('todate', '<', $now))
            ->when($date === 'newest', fn ($q) => $q->orderByDesc('fromdate'))
            ->when($date === 'oldest', fn ($q) => $q->orderBy('fromdate'))
            ->when($date === 'title_asc', fn ($q) => $q->orderBy('title', 'asc'))
            ->when($date === 'title_desc', fn ($q) => $q->orderBy('title', 'desc'))
            ->when(!in_array($date, ['newest', 'oldest', 'title_asc', 'title_desc'], true), fn ($q) => $q->latest());
    }

    private function getFilterOptions($query): array
    {
        return [
            'years' => (clone $query)->reorder()->whereNotNull('fromdate')->selectRaw('YEAR(fromdate) as year')->distinct()->orderByDesc('year')->pluck('year'),
            'batches' => collect([1, 2, 4]),
            'passingGrades' => (clone $query)->reorder()->whereNotNull('passing_grade')
                ->select('passing_grade')
                ->distinct()
                ->orderBy('passing_grade')
                ->pluck('passing_grade'),
        ];
    }

    private function getUserAssessmentResults($userId)
    {
        return AssesmentResult::with('assesment.trainingplan')
            ->where('user_id', $userId)
            ->whereFinal(1)
            ->latest()
            ->get();
    }

    private function summarizeAssessments($assessments, int $userId): array
    {
        $started = 0;
        $passed = 0;
        $failed = 0;

        foreach ($assessments as $assessment) {
            $finalResult = $assessment->getRelation('scores')
                ->where('user_id', $userId)
                ->where('final', 1)
                ->sortByDesc('score')
                ->first();

            if ($finalResult !== null) {
                if ($finalResult->pass) {
                    $passed++;
                } else {
                    $failed++;
                }

                continue;
            }

            $started++;
        }

        return [
            ['label' => 'Total Assessment', 'value' => $assessments->count()],
            ['label' => 'Started', 'value' => $started],
            ['label' => 'Passed', 'value' => $passed],
            ['label' => 'Failed', 'value' => $failed],
        ];
    }
}
