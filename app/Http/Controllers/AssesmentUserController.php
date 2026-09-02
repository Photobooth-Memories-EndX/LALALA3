<?php

    namespace App\Http\Controllers;

    use App\Models\Assesment;
    use Illuminate\Http\Request;
    use App\Models\AssesmentQuestion;
    use App\Models\AssesmentQuestionBank;
    use App\Models\AssesmentResult;
    use App\Services\AssessmentAccessService;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Str;

    class AssesmentUserController extends Controller
    {
    public function index()
        {
            $assesment = Assesment::with([
                    'trainingplan',
                    'scores' => function ($query) {
                        $query->where('user_id', Auth::id());
                    },
                ])
                ->latest()
                ->paginate(15);

            $result = AssesmentResult::with('assesment')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();

            return view('user.assesment.index', compact('assesment', 'result'));
        }

        public function view($slug, AssessmentAccessService $assessmentAccess)
        {
            $assesment = Assesment::findBySlug($slug);
            $today = date("Y-m-d H:i:s");

            if ($assesment === null) {
                abort(404);
            }

            $reason = $assessmentAccess->denialReason($assesment, auth()->user());
            if ($reason !== null) {
                if ($reason === AssessmentAccessService::REASON_EXPIRED) {
                    $activeDraft = AssesmentResult::where('user_id', auth()->id())
                        ->where('assesment_id', $assesment->id)
                        ->where('batch', $assesment->batch)
                        ->where('final', 0)
                        ->latest('id')
                        ->first();

                    if ($activeDraft) {
                        $this->finalizeExpiredDraft($activeDraft, $assesment);
                        return to_route('assesment.user.result', $slug)
                            ->with('assesmentResultId', $activeDraft->id)
                            ->with('show_result_modal', true)
                            ->with('is_expired_autosubmit', true);
                    }
                }
                return $this->assessmentAccessDeniedResponse($assesment, $reason, $assessmentAccess);
            }

            $isBypassUser = in_array((int) (auth()->user()->role ?? auth()->user()->role_id), [2, 3], true);

            // Jika bukan Role 2 (Admin) atau Role 3 (Super Admin)
            if (!$isBypassUser)
            {
                // cek apakah user sebelumnya sudah mengikuti ujian di batch berjalan dan sudah disubmit (final = 1)
                $queryAssessmentResult = AssesmentResult::where('user_id', auth()->user()->id)
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $assesment->batch)
                    ->whereFinal(1);
                $assessmentResultCount = $queryAssessmentResult->count();
                $assesment_result      = $queryAssessmentResult->orderBy('score', 'desc')->first();

                if ($assesment_result) {
                    $maxAttempts = (string) ($assesment->type ?? '') === 'global' ? 1 : (1 + (int) ($assesment->total_remedials ?? 0));
                    if ($assessmentResultCount >= $maxAttempts || $assesment_result->score >= $assesment->passing_grade) {
                        return view('user.assesment.done', compact('assesment', 'assesment_result'));
                    }
                }
            }

            // Cek jawaban draft (autosave) / record attempt start
            $assesment_result_draft = AssesmentResult::where('user_id', auth()->user()->id)
                ->where('assesment_id', $assesment->id)
                ->where('batch', $assesment->batch)
                ->whereFinal(0)
                ->latest('id')
                ->first();

            $countDownDate = null;
            $expiresAtIso = null;

            $hasAccessPeriod = $assesment->hasAccessPeriod() && !empty($assesment->todate);
            $hasTimeLimit    = $assesment->hasTimeLimit();

            if (!$assesment_result_draft) {
                $completedCount = AssesmentResult::where('user_id', auth()->id())
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $assesment->batch)
                    ->where('final', 1)
                    ->count();
                $currentAttempt = $completedCount + 1;

                $draftLockKey = 'assessment-draft-lock:' . auth()->id() . ':' . $assesment->id . ':' . $assesment->batch . ':' . $currentAttempt;
                $assesment_result_draft = Cache::lock($draftLockKey, 10)->block(5, function () use ($assesment) {
                    return AssesmentResult::firstOrCreate(
                        [
                            'user_id'      => auth()->id(),
                            'assesment_id' => $assesment->id,
                            'batch'        => $assesment->batch,
                            'final'        => 0,
                        ],
                        [
                            'user_answer'  => [],
                            'score'        => 0,
                            'pass'         => 0,
                        ]
                    );
                });
            }

            $effectiveDeadline = null;

            if ($hasAccessPeriod && $hasTimeLimit && $assesment_result_draft) {
                $periodDeadline    = \Illuminate\Support\Carbon::parse($assesment->todate);
                $timeLimitDeadline = $assesment_result_draft->created_at->copy()->addMinutes((int) $assesment->time_limit_minutes);
                if ($isBypassUser && now()->format('Y-m-d H:i:s') > $periodDeadline->format('Y-m-d H:i:s')) {
                    $effectiveDeadline = $timeLimitDeadline;
                } else {
                    $effectiveDeadline = $periodDeadline->min($timeLimitDeadline);
                }
            } elseif ($hasAccessPeriod) {
                $periodDeadline = \Illuminate\Support\Carbon::parse($assesment->todate);
                if (!$isBypassUser || now()->format('Y-m-d H:i:s') <= $periodDeadline->format('Y-m-d H:i:s')) {
                    $effectiveDeadline = $periodDeadline;
                }
            } elseif ($hasTimeLimit && $assesment_result_draft) {
                $effectiveDeadline = $assesment_result_draft->created_at->copy()->addMinutes((int) $assesment->time_limit_minutes);
            }

            if ($effectiveDeadline !== null) {
                $countDownDate = $effectiveDeadline->format('Y-m-d H:i:s');
                $expiresAtIso  = $effectiveDeadline->toIso8601String();

                if ($assesment_result_draft && now()->format('Y-m-d H:i:s') > $effectiveDeadline->format('Y-m-d H:i:s')) {
                    $this->finalizeExpiredDraft($assesment_result_draft, $assesment);
                    return to_route('assesment.user.result', $slug)
                        ->with('assesmentResultId', $assesment_result_draft->id)
                        ->with('show_result_modal', true)
                        ->with('is_expired_autosubmit', true);
                }
            }

            $submissionSessionKey = "assessment_submission_token.{$assesment->id}.{$assesment->batch}";
            $assessmentSubmissionToken = session($submissionSessionKey);

            if (!$assessmentSubmissionToken) {
                $assessmentSubmissionToken = (string) Str::uuid();
                session([$submissionSessionKey => $assessmentSubmissionToken]);
            }

            $assesment_question = $this->ensureQuestionSnapshot($assesment_result_draft, $assesment);

            $currentAttempt = null;
            $maxAttempts = null;

            if (($assesment->type ?? '') !== 'global') {
                $completedCount = AssesmentResult::where('user_id', auth()->id())
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $assesment->batch)
                    ->where('final', 1)
                    ->count();

                $currentAttempt = $completedCount + 1;
                $maxAttempts = 1 + (int) ($assesment->total_remedials ?? 0);
            }

            return view('user.assesment.view', compact(
                'assesment',
                'assesment_question',
                'assesment_result_draft',
                'assessmentSubmissionToken',
                'countDownDate',
                'expiresAtIso',
                'currentAttempt',
                'maxAttempts'
            ));
        }

        public function store(Request $request, $slug, AssessmentAccessService $assessmentAccess)
        {
            $request->validate([
                'submission_token' => ['required', 'uuid'],
            ]);

            $assesment = Assesment::findBySlug($slug);

            if ($assesment === null) {
                abort(404);
            }

            $reason = $assessmentAccess->denialReasonForSubmitting($assesment, auth()->user());
            if ($reason !== null) {
                return $this->assessmentAccessDeniedResponse($assesment, $reason, $assessmentAccess);
            }

            $activeDraft = AssesmentResult::where('user_id', auth()->id())
                ->where('assesment_id', $assesment->id)
                ->where('batch', $assesment->batch)
                ->where('final', 0)
                ->latest('id')
                ->first();

            if (!$activeDraft) {
                $alreadySubmitted = AssesmentResult::where('user_id', auth()->id())
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $assesment->batch)
                    ->where('final', 1)
                    ->latest('id')
                    ->first();

                if ($alreadySubmitted) {
                    $submissionSessionKey = "assessment_submission_token.{$assesment->id}.{$assesment->batch}";
                    if (session($submissionSessionKey) === $request->submission_token) {
                        session()->forget($submissionSessionKey);
                    }
                    return to_route('assesment.user.result', $slug)->with('assesmentResultId', $alreadySubmitted->id);
                }
            }

            $draftId = $activeDraft ? $activeDraft->id : 0;
            $lockKey = 'assessment-store-lock:' . auth()->id() . ':' . $assesment->id . ':' . $assesment->batch . ':' . $draftId;
            $completedKey = 'assessment-completed:' . auth()->id() . ':' . $assesment->id . ':' . $assesment->batch . ':' . $draftId;

            $assesment_result = Cache::lock($lockKey, 10)->block(5, function () use ($request, $assesment, $completedKey, $activeDraft) {
                $completedResultId = Cache::get($completedKey);
                if ($completedResultId) {
                    return AssesmentResult::findOrFail($completedResultId);
                }

                $result = $activeDraft ?: AssesmentResult::firstOrCreate(
                    [
                        'user_id'      => auth()->id(),
                        'assesment_id' => $assesment->id,
                        'batch'        => $assesment->batch,
                        'final'        => 0,
                    ],
                    [
                        'user_answer'  => [],
                        'score'        => 0,
                        'pass'         => 0,
                    ]
                );

                // Safety check: ensure question_snapshot exists on the draft without modifying existing snapshots
                $this->ensureQuestionSnapshot($result, $assesment);

                $existingUserAnswers = is_array($result->user_answer) ? $result->user_answer : [];
                $submittedAnswers = collect($request->except(['_token', '_method', 'submission_token', 'is_expired']))
                    ->filter(function ($val) {
                        return $val !== null && $val !== '';
                    })
                    ->toArray();
                $mergedUserAnswers = array_replace($existingUserAnswers, $submittedAnswers);

                $scoreData = $this->calculateAttemptScore($result, $assesment, $mergedUserAnswers);

                $result->user_answer = $mergedUserAnswers;
                $result->score = $scoreData['score'];
                $result->batch = $assesment->batch;
                $result->pass = $scoreData['pass'];
                $result->final = 1;
                $result->save();

                Cache::put($completedKey, $result->id, now()->addDay());

                return $result;
            });

            $isExpiredAutoSubmit = $request->boolean('is_expired');
            $isBypassUser = in_array((int) (auth()->user()->role ?? auth()->user()->role_id), [2, 3], true);
            if (!$isExpiredAutoSubmit && $activeDraft) {
                $hasAccessPeriod = $assesment->hasAccessPeriod() && !empty($assesment->todate);
                $hasTimeLimit    = $assesment->hasTimeLimit();

                $effectiveDeadline = null;
                if ($hasAccessPeriod && $hasTimeLimit) {
                    $periodDeadline    = \Illuminate\Support\Carbon::parse($assesment->todate);
                    $timeLimitDeadline = $activeDraft->created_at->copy()->addMinutes((int) $assesment->time_limit_minutes);
                    if ($isBypassUser && now()->format('Y-m-d H:i:s') > $periodDeadline->format('Y-m-d H:i:s')) {
                        $effectiveDeadline = $timeLimitDeadline;
                    } else {
                        $effectiveDeadline = $periodDeadline->min($timeLimitDeadline);
                    }
                } elseif ($hasAccessPeriod) {
                    $periodDeadline = \Illuminate\Support\Carbon::parse($assesment->todate);
                    if (!$isBypassUser || now()->format('Y-m-d H:i:s') <= $periodDeadline->format('Y-m-d H:i:s')) {
                        $effectiveDeadline = $periodDeadline;
                    }
                } elseif ($hasTimeLimit) {
                    $effectiveDeadline = $activeDraft->created_at->copy()->addMinutes((int) $assesment->time_limit_minutes);
                }

                if ($effectiveDeadline !== null && now()->format('Y-m-d H:i:s') > $effectiveDeadline->format('Y-m-d H:i:s')) {
                    $isExpiredAutoSubmit = true;
                }
            }

            $submissionSessionKey = "assessment_submission_token.{$assesment->id}.{$assesment->batch}";
            if (session($submissionSessionKey) === $request->submission_token) {
                session()->forget($submissionSessionKey);
            }

            $redirect = to_route('assesment.user.result', $slug)->with('assesmentResultId', $assesment_result->id);
            if ($isExpiredAutoSubmit) {
                $redirect->with('show_result_modal', true)->with('is_expired_autosubmit', true);
            }

            return $redirect;
        }

        public function result($slug, AssessmentAccessService $assessmentAccess)
        {
            $assesment = Assesment::findBySlug($slug);
            if ($assesment === null) {
                abort(404);
            }
            $reason = $assessmentAccess->denialReasonForViewingResult($assesment, auth()->user());
            if ($reason !== null) {
                return $this->assessmentAccessDeniedResponse($assesment, $reason, $assessmentAccess);
            }

            $assesmentResultId = session('assesmentResultId');
            $assesment_result = null;

            if ($assesmentResultId) {
                $assesment_result = AssesmentResult::where('id', $assesmentResultId)
                    ->where('user_id', auth()->id())
                    ->first();
            }

            if (!$assesment_result) {
                $assesment_result = AssesmentResult::where('user_id', auth()->id())
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $assesment->batch)
                    ->where('final', 1)
                    ->latest('id')
                    ->first();
            }

            if (!$assesment_result) {
                $assesment_result = AssesmentResult::where('user_id', auth()->id())
                    ->where('assesment_id', $assesment->id)
                    ->where('final', 1)
                    ->latest('id')
                    ->first();
            }

            $currentAttempt = null;
            $maxAttempts = null;

            if (($assesment->type ?? '') !== 'global' && $assesment_result) {
                $batch = $assesment_result->batch ?? $assesment->batch;
                $currentAttempt = AssesmentResult::where('user_id', auth()->id())
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $batch)
                    ->where('final', 1)
                    ->where('id', '<=', $assesment_result->id)
                    ->count();

                if ($currentAttempt === 0) {
                    $currentAttempt = 1;
                }

                $maxAttempts = 1 + (int) ($assesment->total_remedials ?? 0);
            }

            return view('user.assesment.result', compact('assesment', 'assesment_result', 'currentAttempt', 'maxAttempts'));
        }

        public function autosave(Request $request, $slug, AssessmentAccessService $assessmentAccess)
        {
            $request->validate([
                'submission_token' => ['required', 'uuid'],
            ]);

            $assesment = Assesment::findBySlug($slug);

            if ($assesment === null) {
                abort(404);
            }

            $reason = $assessmentAccess->denialReason($assesment, auth()->user());
            if ($reason !== null) {
                if (request()->wantsJson() || request()->ajax()) {
                    return response()->json([
                        'status' => 403,
                        'message' => $assessmentAccess->message($reason),
                        'reason' => $reason,
                    ], 403);
                }
                return $this->assessmentAccessDeniedResponse($assesment, $reason, $assessmentAccess);
            }

            $activeDraft = AssesmentResult::where('user_id', auth()->id())
                ->where('assesment_id', $assesment->id)
                ->where('batch', $assesment->batch)
                ->where('final', 0)
                ->latest('id')
                ->first();

            if (!$activeDraft) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Assessment sudah disubmit atau draft tidak ditemukan',
                ]);
            }

            $lockKey = 'assessment-autosave-lock:' . auth()->id() . ':' . $assesment->id . ':' . $assesment->batch . ':' . $activeDraft->id;
            $completedKey = 'assessment-completed:' . auth()->id() . ':' . $assesment->id . ':' . $assesment->batch . ':' . $activeDraft->id;

            $saved = Cache::lock($lockKey, 10)->block(5, function () use ($request, $assesment, $completedKey, $activeDraft) {
                if (Cache::has($completedKey)) {
                    return false;
                }

                $result = $activeDraft;

                // Safety check: ensure question_snapshot exists on the draft without modifying existing snapshots
                $this->ensureQuestionSnapshot($result, $assesment);

                $existingUserAnswers = is_array($result->user_answer) ? $result->user_answer : [];
                $submittedAnswers = collect($request->except(['_token', '_method', 'submission_token', 'is_expired']))
                    ->filter(function ($val) {
                        return $val !== null && $val !== '';
                    })
                    ->toArray();
                $mergedUserAnswers = array_replace($existingUserAnswers, $submittedAnswers);

                $scoreData = $this->calculateAttemptScore($result, $assesment, $mergedUserAnswers);

                $result->user_answer = $mergedUserAnswers;
                $result->score = $scoreData['score'];
                $result->batch = $assesment->batch;
                $result->pass = $scoreData['pass'];
                $result->final = 0;
                $result->save();

                return true;
            });

            return response()->json([
                'status'=> 200,
                'message' => $saved ? 'Autosave berhasil' : 'Assessment sudah disubmit',
            ]);
        }

        private function assessmentAccessDeniedResponse(
            Assesment $assesment,
            string $reason,
            AssessmentAccessService $assessmentAccess
        ) {
            \Log::warning('[ASSESSMENT ACCESS] denied', [
                'assessment_id' => $assesment->id,
                'user_id'       => auth()->id(),
                'reason'        => $reason,
            ]);

            if ($reason === AssessmentAccessService::REASON_ASSIGNED) {
                return response()->view('errors.assessment-forbidden', [
                    'assesment' => $assesment,
                    'user'      => auth()->user(),
                ], 403);
            }

            return response()->view('errors.assessment-unavailable', [
                'assesment' => $assesment,
                'reason'    => $reason,
                'message'   => $assessmentAccess->message($reason),
                'fromdate'  => $assesment->fromdate,
                'todate'    => $assesment->todate,
            ], 403);
        }

        /**
         * Ensures that a question snapshot exists on the given draft AssesmentResult.
         * 
         * - If question_snapshot EXISTS: Returns existing questions without randomization or overwrite.
         * - If question_snapshot is EMPTY: Generates snapshot 1x under lock, saves it, and returns the questions.
         */
        private function ensureQuestionSnapshot(AssesmentResult $resultDraft, Assesment $assesment): Collection
        {
            if (!empty($resultDraft->question_snapshot)) {
                $snapshot = $resultDraft->question_snapshot;
                $questionIds = array_column($snapshot, 'question_id');

                $questionsMap = AssesmentQuestion::whereIn('id', $questionIds)->get()->keyBy('id');
                $assesment_question = new Collection();

                foreach ($questionIds as $qid) {
                    if (isset($questionsMap[$qid])) {
                        $assesment_question->push($questionsMap[$qid]);
                    }
                }

                return $assesment_question;
            }

            $snapshotLockKey = 'assessment-snapshot-lock:' . auth()->id() . ':' . $resultDraft->id;

            return Cache::lock($snapshotLockKey, 10)->block(5, function () use ($assesment, $resultDraft) {
                if ($resultDraft->exists) {
                    $resultDraft->refresh();
                }

                if (!empty($resultDraft->question_snapshot)) {
                    $snapshot = $resultDraft->question_snapshot;
                    $questionIds = array_column($snapshot, 'question_id');
                    $questionsMap = AssesmentQuestion::whereIn('id', $questionIds)->get()->keyBy('id');
                    $cols = new Collection();
                    foreach ($questionIds as $qid) {
                        if (isset($questionsMap[$qid])) {
                            $cols->push($questionsMap[$qid]);
                        }
                    }
                    return $cols;
                }

                $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

                if ($checkAssesmentQuestionBanks) {
                    $assessmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get();
                    $assesment_question = new Collection();

                    foreach ($assessmentQuestionBanks as $aqb) {
                        $assessmentQuestions = AssesmentQuestion::where('assesment_id', $assesment->id)
                            ->where('question_bank_id', $aqb->question_bank_id)
                            ->inRandomOrder()->limit($aqb->questions_amount)->get();

                        $assesment_question = $assesment_question->merge($assessmentQuestions);
                    }
                    $assesment_question = $assesment_question->shuffle();
                } else {
                    $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->inRandomOrder()->get();
                }

                $snapshotData = $assesment_question->values()->map(function ($q, $index) {
                    return [
                        'question_id' => $q->id,
                        'order'       => $index + 1,
                    ];
                })->toArray();

                if (!$resultDraft->exists) {
                    $resultDraft->user_answer = $resultDraft->user_answer ?? [];
                    $resultDraft->score = $resultDraft->score ?? 0;
                    $resultDraft->pass = $resultDraft->pass ?? 0;
                    $resultDraft->final = $resultDraft->final ?? 0;
                }

                $resultDraft->question_snapshot = $snapshotData;
                $resultDraft->save();

                return $assesment_question;
            });
        }

        /**
         * Calculates the score and pass status for an assessment attempt strictly
         * based on the questions present in the attempt's question_snapshot.
         */
        private function calculateAttemptScore(AssesmentResult $result, Assesment $assesment, array $userAnswers): array
        {
            $this->ensureQuestionSnapshot($result, $assesment);

            $snapshot = is_array($result->question_snapshot) ? $result->question_snapshot : [];
            $snapshotQuestionIds = array_column($snapshot, 'question_id');

            $correctAnswers = !empty($snapshotQuestionIds)
                ? AssesmentQuestion::whereIn('id', $snapshotQuestionIds)->pluck('answer', 'id')->toArray()
                : [];
            $totalQuestions = count($snapshotQuestionIds);

            $correctCount = 0;
            foreach ($snapshotQuestionIds as $qId) {
                $expected = $correctAnswers[$qId] ?? null;
                $actual = $userAnswers[$qId] ?? null;

                if ($expected !== null && $actual !== null && (string) $actual === (string) $expected) {
                    $correctCount++;
                }
            }

            $formulaScorePerQuestion = $totalQuestions > 0 ? (100 / $totalQuestions) : 0;
            $score = $correctCount * $formulaScorePerQuestion;
            $score = $score >= 100 ? 100 : round($score, 2);
            $pass = ($score < (float) ($assesment->passing_grade ?? 0)) ? 0 : 1;

            return [
                'score'           => $score,
                'pass'            => $pass,
                'correct_count'   => $correctCount,
                'total_questions' => $totalQuestions,
            ];
        }

        /**
         * Finalizes an active draft that has expired.
         */
        private function finalizeExpiredDraft(AssesmentResult $result, Assesment $assesment): void
        {
            if ((int) $result->final === 1) {
                return;
            }

            $existingUserAnswers = is_array($result->user_answer) ? $result->user_answer : [];
            $scoreData = $this->calculateAttemptScore($result, $assesment, $existingUserAnswers);

            $result->score = $scoreData['score'];
            $result->batch = $assesment->batch;
            $result->pass = $scoreData['pass'];
            $result->final = 1;
            $result->save();

            $completedKey = 'assessment-completed:' . auth()->id() . ':' . $assesment->id . ':' . $assesment->batch . ':' . $result->id;
            Cache::put($completedKey, $result->id, now()->addDay());
        }
    }
