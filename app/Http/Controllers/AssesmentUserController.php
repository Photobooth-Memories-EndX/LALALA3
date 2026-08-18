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
                return $this->assessmentAccessDeniedResponse($assesment, $reason, $assessmentAccess);
            }

            //jika bukan ADMIN
            if (!auth()->user()->isAdmin())
            {
                //cek apakah user sebelumnya sudah mengikuti ujian di batch berjalan, juga apakah sudah pernah mengisi jawaban dan jawaban tsb sudah disubmit
                // $assesment_result = AssesmentResult::where('user_id', auth()->user()->id)
                //     ->where('assesment_id', $assesment->id)
                //     ->where('batch', $assesment->batch)
                //     ->whereFinal(1)->first();
                $queryAssessmentResult = AssesmentResult::where('user_id', auth()->user()->id)
                    ->where('assesment_id', $assesment->id)
                    ->where('batch', $assesment->batch)
                    ->whereFinal(1);
                $assessmentResultCount = $queryAssessmentResult->count();
                $assesment_result      = $queryAssessmentResult->orderBy('score', 'desc')->first();

                if ($assesment_result) {
                    if($assessmentResultCount >= 1 + $assesment->total_remedials || $assesment_result->score >= $assesment->passing_grade  ){
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

            if ($assesment->hasTimeLimit()) {
                if (!$assesment_result_draft && !auth()->user()->isAdmin()) {
                    $assesment_result_draft = AssesmentResult::create([
                        'user_id'      => auth()->user()->id,
                        'assesment_id' => $assesment->id,
                        'batch'        => $assesment->batch,
                        'final'        => 0,
                        'user_answer'  => null,
                        'score'        => 0,
                        'pass'         => 0,
                    ]);
                }

                if ($assesment_result_draft) {
                    $startedAt = $assesment_result_draft->created_at;
                    $expiresAt = $startedAt->copy()->addMinutes((int) $assesment->time_limit_minutes);
                    $countDownDate = $expiresAt->format('Y-m-d H:i:s');
                    $expiresAtIso  = $expiresAt->toIso8601String();
                } else {
                    $expiresAt = now()->addMinutes((int) $assesment->time_limit_minutes);
                    $countDownDate = $expiresAt->format('Y-m-d H:i:s');
                    $expiresAtIso  = $expiresAt->toIso8601String();
                }
            } elseif ($assesment->hasAccessPeriod() && $assesment->todate) {
                $countDownDate = $assesment->todate;
                $expiresAtIso  = \Carbon\Carbon::parse($assesment->todate)->toIso8601String();
            }

            $submissionSessionKey = "assessment_submission_token.{$assesment->id}.{$assesment->batch}";
            $assessmentSubmissionToken = session($submissionSessionKey);

            if (!$assessmentSubmissionToken) {
                $assessmentSubmissionToken = (string) Str::uuid();
                session([$submissionSessionKey => $assessmentSubmissionToken]);
            }

            $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

            if ($checkAssesmentQuestionBanks) {
                $assessmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get();
                $sumOfquestionsAmount    = $assessmentQuestionBanks->sum('questions_amount');

                $assesment_question = new Collection();

                foreach ($assessmentQuestionBanks as $aqb) {
                    $assessmentQuestions = AssesmentQuestion::where('assesment_id', $assesment->id)
                        ->where('question_bank_id', $aqb->question_bank_id)
                        ->where('is_used', true)
                        ->inRandomOrder()->limit($aqb->questions_amount)->get();

                    $assesment_question = $assesment_question->merge($assessmentQuestions)->shuffle();
                }
            } else {
                $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->inRandomOrder()->get();
            }

            return view('user.assesment.view', compact(
                'assesment',
                'assesment_question',
                'assesment_result_draft',
                'assessmentSubmissionToken',
                'countDownDate',
                'expiresAtIso'
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

            $reason = $assessmentAccess->denialReason($assesment, auth()->user());
            if ($reason !== null) {
                return $this->assessmentAccessDeniedResponse($assesment, $reason, $assessmentAccess);
            }

            // counting total questions from total of questions amount of assessment qyuestion bank.
            $assessmentQuestionBank      = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get()->sum('questions_amount');
            $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

            $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->pluck('answer', 'id')->toArray();
            if ($checkAssesmentQuestionBanks) {
                $assesment_question_count = $assessmentQuestionBank;
            } else {
                $assesment_question_count = count($assesment_question);
            }
            $user_answers = $request->collect()->except(['_token', '_method', 'submission_token'])->sortKeys()->toArray();

            // Old Process of Score Calculation
            // $wrong_answer       = array_diff_assoc($assesment_question, $user_answers);
            // $wrong_answer_count = count($wrong_answer);
            // $right_answer_count = $assesment_question_count - $wrong_answer_count;
            // $score              = ((int)$right_answer_count / (int)$assesment_question_count) * 100;

            // New Process of Score Calculation 
            /**
             * First Step is set up the value / score per question.
             * Score formula per question and 100 is the perfect score.
             */ 
            $formulaScorePerQuestion = 100 / $assesment_question_count;

            /**
             * Second Step is counting right/correct answer.
             */
            $wrong_answer        = array_diff_assoc($assesment_question, $user_answers);
            $wrong_answer_count  = count($wrong_answer);
            $right_answer_count  = count($assesment_question) - $wrong_answer_count;
            $amountOfRightAnswer = $assesment_question_count - ($assesment_question_count - $right_answer_count);

            /**
             * Last Step is counting the score.
             */
            $score = $amountOfRightAnswer * $formulaScorePerQuestion;

            $lockKey = 'assessment-submission-lock:' . auth()->id() . ':' . $request->submission_token;
            $completedKey = 'assessment-submission-completed:' . auth()->id() . ':' . $request->submission_token;

            $assesment_result = Cache::lock($lockKey, 10)->block(5, function () use ($request, $assesment, $score, $completedKey) {
                $completedResultId = Cache::get($completedKey);
                if ($completedResultId) {
                    return AssesmentResult::findOrFail($completedResultId);
                }

                $result = AssesmentResult::firstOrNew([
                    'user_id' => auth()->id(),
                    'assesment_id' => $assesment->id,
                    'batch' => $assesment->batch,
                    'final' => 0,
                ]);
                $result->user_answer = $request->except(['_token', '_method', 'submission_token']);
                $result->score = $score >= 100 ? 100 : $score;
                $result->batch = $assesment->batch;
                $result->pass = ($score < $assesment->passing_grade) ? 0 : 1;
                $result->final = 1;
                $result->save();

                Cache::put($completedKey, $result->id, now()->addDay());

                return $result;
            });

            $submissionSessionKey = "assessment_submission_token.{$assesment->id}.{$assesment->batch}";
            if (session($submissionSessionKey) === $request->submission_token) {
                session()->forget($submissionSessionKey);
            }

            return to_route('assesment.user.result', $slug)->with('assesmentResultId', $assesment_result->id);
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
                $assesment_result = AssesmentResult::where('assesment_id', $assesment->id)
                    ->where('user_id', auth()->id())
                    ->where('final', 1)
                    ->latest('id')
                    ->firstOrFail();
            }

            return view('user.assesment.result', compact('assesment', 'assesment_result'));
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

            // counting total questions from total of questions amount of assessment qyuestion bank.
            $assessmentQuestionBank      = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get()->sum('questions_amount');
            $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

            $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->pluck('answer', 'id')->toArray();
            if ($checkAssesmentQuestionBanks) {
                $assesment_question_count = $assessmentQuestionBank;
            } else {
                $assesment_question_count = count($assesment_question);
            }
            $user_answers = $request->collect()->except(['_token', '_method', 'submission_token'])->sortKeys()->toArray();

            // Old Process of Score Calculation
            // $wrong_answer       = array_diff_assoc($assesment_question, $user_answers);
            // $wrong_answer_count = count($wrong_answer);
            // $right_answer_count = $assesment_question_count - $wrong_answer_count;
            // $score              = ((int)$right_answer_count / (int)$assesment_question_count) * 100;

            // New Process of Score Calculation 
            /**
             * First Step is set up the value / score per question.
             * Score formula per question and 100 is the perfect score.
             */ 
            $formulaScorePerQuestion = 100 / $assesment_question_count;

            /**
             * Second Step is counting right/correct answer.
             */
            $wrong_answer        = array_diff_assoc($assesment_question, $user_answers);
            $wrong_answer_count  = count($wrong_answer);
            $right_answer_count  = count($assesment_question) - $wrong_answer_count;
            $amountOfRightAnswer = $assesment_question_count - ($assesment_question_count - $right_answer_count);

            /**
             * Last Step is counting the score.
             */
            $score = $amountOfRightAnswer * $formulaScorePerQuestion;

            $lockKey = 'assessment-submission-lock:' . auth()->id() . ':' . $request->submission_token;
            $completedKey = 'assessment-submission-completed:' . auth()->id() . ':' . $request->submission_token;

            $saved = Cache::lock($lockKey, 10)->block(5, function () use ($request, $assesment, $score, $completedKey) {
                if (Cache::has($completedKey)) {
                    return false;
                }

                $result = AssesmentResult::firstOrNew([
                    'user_id' => auth()->id(),
                    'assesment_id' => $assesment->id,
                    'batch' => $assesment->batch,
                    'final' => 0,
                ]);
                $result->user_answer = $request->except(['_token', '_method', 'submission_token']);
                $result->score = $score >= 100 ? 100 : $score;
                $result->batch = $assesment->batch;
                $result->pass = ($score < $assesment->passing_grade) ? 0 : 1;
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
    }
