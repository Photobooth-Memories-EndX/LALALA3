<?php

    namespace App\Http\Controllers;

    use App\Models\Assesment;
    use Illuminate\Http\Request;
    use App\Models\AssesmentQuestion;
    use App\Models\AssesmentQuestionBank;
    use App\Models\AssesmentResult;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Support\Facades\Auth;

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
                ->get();

            $result = AssesmentResult::with('assesment')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();

            return view('user.assesment.index', compact('assesment', 'result'));
        }

        public function view($slug)
        {
            $assesment = Assesment::findBySlug($slug);
            $today = date("Y-m-d H:i:s");

            \Log::info('[ASSESSMENT DEBUG] view() called', [
                'slug'            => $slug,
                'assessment_id'   => $assesment ? $assesment->id : null,
                'assessment_type' => $assesment ? $assesment->type : null,
                'assessment_status' => $assesment ? $assesment->status : null,
                'trainingplan_id' => $assesment ? $assesment->trainingplan_id : null,
                'fromdate'        => $assesment ? $assesment->fromdate : null,
                'todate'          => $assesment ? $assesment->todate : null,
                'today'           => $today,
                'user_id'         => auth()->user()->id,
                'user_name'       => auth()->user()->name,
                'user_team'       => auth()->user()->team ?? null,
                'user_department' => auth()->user()->department ?? null,
                'is_admin'        => auth()->user()->isAdmin(),
            ]);

            //jika bukan ADMIN
            if (!auth()->user()->isAdmin())
            {
                // Check Assigned Users
                $assignedCount = $assesment->assesments_assigned->count();

                \Log::info('[ASSESSMENT DEBUG] assigned check', [
                    'assessment_id'        => $assesment->id,
                    'assigned_count'       => $assignedCount,
                    'assigned_descriptions' => $assesment->assesments_assigned->pluck('assigned_description')->toArray(),
                ]);

                if ($assignedCount > 0) {
                    $assignedAssesments = [];
                    foreach ($assesment->assesments_assigned as $assign) {
                        $assignedAssesments[] = $assign->assigned_description;
                    }

                    // Division ditambahkan agar user yang di-assign via division juga bisa akses
                    $isAllowed = in_array(Auth::user()->name, $assignedAssesments)
                        || in_array(Auth::user()->team, $assignedAssesments)
                        || in_array(Auth::user()->department, $assignedAssesments)
                        || in_array(Auth::user()->division, $assignedAssesments);

                    \Log::info('[ASSESSMENT DEBUG] permission check', [
                        'assigned_list'   => $assignedAssesments,
                        'user_name'       => Auth::user()->name,
                        'user_team'       => Auth::user()->team ?? null,
                        'user_department' => Auth::user()->department ?? null,
                        'user_division'   => Auth::user()->division ?? null,
                        'is_allowed'      => $isAllowed,
                    ]);

                    if (!$isAllowed) {
                        \Log::warning('[ASSESSMENT DEBUG] 403 — user not in assigned list', [
                            'assessment_id' => $assesment->id,
                            'user_id'       => auth()->user()->id,
                        ]);
                        // Tampilkan halaman informatif agar user tahu alasan akses ditolak
                        return response()->view('errors.assessment-forbidden', [
                            'assesment' => $assesment,
                            'user'      => auth()->user(),
                        ], 403);
                    }
                }

                //jika status assesment tidak aktif, atau assesment link tidak valid
                if ($assesment === null or $assesment->status == "NOT") {
                    if ($assesment === null) {
                        abort(404);
                    }
                    \Log::warning('[ASSESSMENT DEBUG] status NOT — tampilkan halaman unavailable', [
                        'assessment_id' => $assesment->id,
                        'status'        => $assesment->status,
                    ]);
                    // Tampilkan pesan informatif agar user tidak melihat 403 default
                    return response()->view('errors.assessment-unavailable', [
                        'assesment' => $assesment,
                        'reason'    => 'inactive',
                    ], 403);
                }

                //hanya tampilkan asesment sesuai jadwal yg diinput
                if (($assesment->fromdate && $assesment->todate) != null) {
                    if(!($today >= $assesment->fromdate and $today <= $assesment->todate)){
                        $reason = ($today < $assesment->fromdate) ? 'before_start' : 'expired';
                        \Log::warning('[ASSESSMENT DEBUG] date check failed — tampilkan halaman unavailable', [
                            'assessment_id' => $assesment->id,
                            'reason'        => $reason,
                            'fromdate'      => $assesment->fromdate,
                            'todate'        => $assesment->todate,
                            'today'         => $today,
                        ]);
                        // Tampilkan pesan jadwal agar user tahu kapan assessment bisa diakses
                        return response()->view('errors.assessment-unavailable', [
                            'assesment' => $assesment,
                            'reason'    => $reason,
                            'fromdate'  => $assesment->fromdate,
                            'todate'    => $assesment->todate,
                        ], 403);
                    }
                }

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

            //cek jawaban draft (autosave) jika pengisian sebelumnya ada kendala (untuk di load ke option, jawaban sebelumnya ke ceklis)
            $assesment_result_draft = AssesmentResult::select('user_answer')->where('user_id', auth()->user()->id)->where('assesment_id', $assesment->id)->where('batch', $assesment->batch)->whereFinal(0)->first();

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

            return view('user.assesment.view', compact('assesment', 'assesment_question', 'assesment_result_draft'));
        }

        public function store(Request $request, $slug)
        {
            $assesment = Assesment::findBySlug($slug);

            if ($assesment === null) {
                abort(404);
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
            $user_answers             = $request->collect()->except(['_token', '_method'])->sortKeys()->toArray();

            $assesment_result = AssesmentResult::firstOrNew(['user_id' =>  auth()->user()->id, 'assesment_id' =>  $assesment->id, 'batch' =>  $assesment->batch, 'final' => 0]);
            // if ($assesment->type == 'global') {
            // } else {
            //     $assesment_result = new AssesmentResult;
            //     $assesment_result->user_id      =  auth()->user()->id;
            //     $assesment_result->assesment_id = $assesment->id;
            //     $assesment_result->batch        = $assesment->batch;
            // }

            $assesment_result->user_answer = $request->except(['_token', '_method']);

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

            $assesment_result->score = $score >= 100 ? 100 : $score;
            $assesment_result->batch = $assesment->batch;
            $assesment_result->pass  = ($score < $assesment->passing_grade) ? 0 : 1;

            $assesment_result->final = 1;
            $assesment_result->save();

            return to_route('assesment.user.result', $slug)->with('assesmentResultId', $assesment_result->id);
        }

        public function result($slug)
        {
            $assesment = Assesment::findBySlug($slug);
            if ($assesment === null) {
                abort(404);
            }
            $assesment_result = AssesmentResult::findOrFail(session('assesmentResultId'));

            return view('user.assesment.result', compact('assesment', 'assesment_result'));
        }

        public function autosave(Request $request, $slug)
        {
            $assesment = Assesment::findBySlug($slug);

            // counting total questions from total of questions amount of assessment qyuestion bank.
            $assessmentQuestionBank      = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get()->sum('questions_amount');
            $checkAssesmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->first();

            $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->pluck('answer', 'id')->toArray();
            if ($checkAssesmentQuestionBanks) {
                $assesment_question_count = $assessmentQuestionBank;
            } else {
                $assesment_question_count = count($assesment_question);
            }
            $user_answers             = $request->collect()->except(['_token', '_method'])->sortKeys()->toArray();

            $assesment_result              = AssesmentResult::firstOrNew(['user_id' =>  auth()->user()->id, 'assesment_id' =>  $assesment->id, 'batch' =>  $assesment->batch, 'final' => 0]);
            $assesment_result->user_answer = $request->except(['_token', '_method']);

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

            $assesment_result->score = $score >= 100 ? 100 : $score;
            $assesment_result->batch = $assesment->batch;
            $assesment_result->pass = ($score < $assesment->passing_grade) ? 0 : 1;

            //belum final answer, masih berupa draft
            $assesment_result->final = 0;
            $assesment_result->save();

            return response()->json([
                'status'=> 200,
                'message'=> 'Autosave berhasil',
            ]);
        }
    }
