<?php

namespace App\Http\Controllers;

use App\Exports\AssessmentExport;
use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Services\Learning\CompletedPlaylistService;
use App\Support\LearningFilterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    private function getUserAssessmentQuery($user, $userId, $completedPlaylistIds = null)
    {
        if ($completedPlaylistIds === null) {
            $completedPlaylistIds = $this->completedPlaylistService
                ->forUser((int) $userId)
                ->pluck('id');
        }

        $completedPlaylistIdArray = $completedPlaylistIds instanceof \Illuminate\Support\Collection
            ? $completedPlaylistIds->all()
            : (array) $completedPlaylistIds;

        return Assesment::with([
                'assesments_assigned',
                'contents',
                'content',
                'scores'            => fn ($q) => $q->where('user_id', $userId)->latest('id'),
                'assessmentResults' => fn ($q) => $q->where('user_id', $userId)->latest('id'),
                'trainingplan',
            ])
            ->withCount('questions')
            ->where(function ($root) use ($userId, $completedPlaylistIdArray) {
                // SOURCE A — HISTORY: User has at least one record in assesment_results
                $root->whereHas('scores', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                // SOURCE B — READY TO START: Active assessment without result, prerequisite 100% complete
                ->orWhere(function ($ready) use ($userId, $completedPlaylistIdArray) {
                    $ready->where('status', 'OK')
                        ->whereDoesntHave('scores', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->where(function ($sourceB) use ($userId, $completedPlaylistIdArray) {
                            // 1. Direct Content Assessment: Content is 100% completed by user
                            $sourceB->where(function ($cq) use ($userId) {
                                $cq->where('type', 'content')
                                    ->where(function ($sub) {
                                        $sub->where('assasment_content_for', 'content')
                                            ->orWhereNull('assasment_content_for');
                                    })
                                    ->whereNotNull('content_id')
                                    ->whereHas('contents', function ($q) use ($userId) {
                                        $q->whereHas('contentuser', function ($cu) use ($userId) {
                                            $cu->where('user_id', $userId)
                                                ->where(fn ($w) => $w->where('progress', '>=', 100)->orWhere('finish', '1'));
                                        });
                                    });
                            })
                            // 2. Playlist Assessment: Total playlist contents 100% completed by user
                            ->orWhere(function ($pq) use ($completedPlaylistIdArray) {
                                $pq->where(function ($sub) {
                                    $sub->where('assasment_content_for', 'playlist')
                                        ->orWhere('type', 'trainingplan');
                                })
                                ->whereNotNull('trainingplan_id')
                                ->whereIn('trainingplan_id', !empty($completedPlaylistIdArray) ? $completedPlaylistIdArray : [-1]);
                            });
                        });
                });
            });
    }

    private function applyFilters($query, Request $request, $userId)
    {
        $rawType = $request->input('assessment_filter_type', $request->input('type', 'all'));
        $type = LearningFilterHelper::normalizeAssessmentType($rawType);

        $rawStatus = $request->input('assessment_filter_status', $request->input('status', 'all'));
        $status = LearningFilterHelper::normalizeAssessmentStatus($rawStatus);

        $score = $request->input('assessment_filter_score', $request->input('score', 'all'));
        $result = $request->input('assessment_filter_result', $request->input('result', 'all'));
        $date = $request->input('assessment_filter_date', $request->input('date', 'all'));
        $availability = $request->input('assessment_filter_availability', $request->input('availability', 'all'));
        $search = trim((string) $request->input('assessment_search', $request->input('search', $request->input('q', ''))));
        $now = now();

        $year = (string) $request->input('assessment_filter_year', $request->input('year', 'all'));
        $rawMonth = (string) $request->input('assessment_filter_month', $request->input('month', 'all'));
        $month = LearningFilterHelper::resolveMonth($rawMonth);

        $batch = (string) $request->input('assessment_filter_batch', $request->input('batch', 'all'));
        $grade = (string) $request->input('assessment_filter_grade', $request->input('grade', 'all'));

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
            ->when($type !== 'all', function ($q) use ($type) {
                if ($type === 'content_content') {
                    $q->where('type', 'content')
                        ->where(function ($sub) {
                            $sub->where('assasment_content_for', 'content')
                                ->orWhereNull('assasment_content_for');
                        });
                } elseif ($type === 'content_playlist') {
                    $q->where('type', 'content')
                        ->where('assasment_content_for', 'playlist');
                } elseif ($type === 'trainingplan' || $type === 'playlist') {
                    $q->where('type', 'trainingplan');
                } elseif ($type === 'global') {
                    $q->where('type', 'global');
                } elseif ($type === 'content') {
                    // Backward compatibility for legacy URL ?assessment_filter_type=content or ?type=content
                    $q->where('type', 'content');
                } else {
                    $q->where('type', $type);
                }
            })
            ->when($status === 'passed', fn ($q) => $q->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 1)))
            ->when($status === 'failed', fn ($q) => $q
                ->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 0))
                ->whereDoesntHave('scores', fn ($scoreQuery) => $finalScore($scoreQuery)->where('pass', 1)))
            ->when($status === 'not_started', fn ($q) => $q->whereDoesntHave('scores', $finalScore))
            ->when($year !== 'all' && $year !== '' && ctype_digit($year),
                fn ($q) => $q->whereYear('fromdate', (int) $year))
            ->when($month !== null,
                fn ($q) => $q->whereMonth('fromdate', $month))
            ->when($batch !== 'all' && $batch !== '',
                fn ($q) => $q->whereHas('scores', fn ($scoreQuery) => $finalScore($scoreQuery)
                    ->where('batch', $batch)))
            ->when($grade !== 'all' && $grade !== '',
                fn ($q) => $q->where('passing_grade', $grade))
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
            'years' => (clone $query)->reorder()
                ->whereNotNull('fromdate')
                ->select(DB::raw('YEAR(fromdate) as year'))
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->unique()
                ->values(),
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
            $userScores = collect($assessment->getRelation('scores') ?? $assessment->getRelation('assessmentResults') ?? [])
                ->filter(fn ($score) => (string) ($score->user_id ?? '') === (string) $userId);

            $finalResult = $userScores
                ->where('final', 1)
                ->sortByDesc('score')
                ->first();

            if (!$finalResult) {
                $finalResult = $userScores
                    ->filter(fn ($s) => $s->score !== null)
                    ->sortByDesc('score')
                    ->first();
            }

            if ($finalResult !== null) {
                $isPassed = (bool) ($finalResult->pass ?? ($finalResult->score !== null && $assessment->passing_grade !== null && (float) $finalResult->score >= (float) $assessment->passing_grade));
                if ($isPassed) {
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
