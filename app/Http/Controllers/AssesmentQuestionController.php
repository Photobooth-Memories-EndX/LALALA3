<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Assesment;
use Illuminate\Http\Request;
use App\Models\AssesmentResult;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentScoreHistory;
use App\Models\Certificate;
use App\Models\User;
use App\Services\AssessmentDuplicateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssesmentQuestionController extends Controller
{
    public function question(Assesment $assesment)
    {
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->get();

        return view('main.assesment.question', compact('assesment', 'assesment_question'));
    }

    public function store(Request $request, Assesment $assesment)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'option' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $assesment_question = new AssesmentQuestion();
        $assesment_question->assesment_id = $assesment->id;
        $assesment_question->question = $request->input('question');
        $assesment_question->option = $request->input('option');
        $assesment_question->answer = $request->input('answer');
        if ($request->hasfile('image')) {
            $file = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extention;
            $file->move('storage/assesment/', $filename);
            $assesment_question->image = $filename;
        }
        $assesment_question->save();

        return to_route('assesment.question', $assesment->slug)->with('success','Assesment question created successfully.');
    }

    public function destroy(Assesment $assesment, $id)
    {
        $assesment_question = AssesmentQuestion::findOrFail($id);
        $assesment_question->delete();
        return to_route('assesment.question', $assesment->slug)->with('success','Assesment question deleted successfully');
    }

    public function edit(Assesment $assesment, $id)
    {
        $data = AssesmentQuestion::findOrFail($id);
        $assesment_question = AssesmentQuestion::where('assesment_id', $assesment->id)->latest()->get();
        return view('main.assesment.question_edit', compact('assesment', 'assesment_question',  'data'));
    }

    public function update(Request $request, Assesment $assesment, $id)
    {
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
            'option' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $assesment_question = AssesmentQuestion::findOrFail($id);
        $assesment_question->question = $request->input('question');
        $assesment_question->option = $request->input('option');
        $assesment_question->answer = $request->input('answer');
        if ($request->hasfile('image')) {
            $src = 'storage/assesment/' . $assesment_question->image;
            if (\File::exists(public_path($src)) && !is_dir($src)) {
                \File::delete(public_path($src));
            }

            $file = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extention;
            $file->move('storage/assesment/', $filename);
            $assesment_question->image = $filename;
        }
        $assesment_question->save();

        return to_route('assesment.question', $assesment->slug)->with('success','Assesment question updated successfully.');
    }

    public function result(Assesment $assesment, Request $request)
    {
        $entries = (int) ($request->show_entries ?? 25);
        if ($entries <= 0) {
            $entries = 25;
        }

        $search = $request->search_user ?? $request->search ?? "";
        $team = $request->team;
        $department = $request->department;
        $year = $request->year;
        $month = $request->month;

        // Modal / Advanced Filters
        $batch = $request->batch;
        $result = $request->result;
        $score = $request->score;
        $minScore = $request->min_score;
        $maxScore = $request->max_score;
        $startDateFrom = $request->start_date_from;
        $startDateTo = $request->start_date_to;
        $finishDateFrom = $request->finish_date_from;
        $finishDateTo = $request->finish_date_to;

        $query = AssesmentResult::select('assesment_results.*', 'users.id_emp', 'users.name', 'users.team', 'users.department')
            ->join('users', 'assesment_results.user_id', '=', 'users.id')
            ->where('assesment_id', $assesment->id)
            ->whereFinal(1);

        // 1. Search User (name, id_emp, team, department)
        if ($search !== null && trim($search) !== '') {
            $searchTerm = trim($search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('users.id_emp', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('users.name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('users.team', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('users.department', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // 2. Team Filter
        if ($team !== null && trim($team) !== '') {
            $query->where('users.team', trim($team));
        }

        // 3. Department Filter
        if ($department !== null && trim($department) !== '') {
            $query->where('users.department', trim($department));
        }

        // 4. Year Filter (based on assesment_results.created_at)
        if ($year !== null && trim($year) !== '') {
            $query->whereYear('assesment_results.created_at', $year);
        }

        // 5. Month Filter (based on assesment_results.created_at)
        if ($month !== null && trim($month) !== '') {
            $query->whereMonth('assesment_results.created_at', $month);
        }

        // 6. Batch Filter (Modal)
        if ($batch !== null && $batch !== '') {
            $query->where('assesment_results.batch', $batch);
        }

        // 7. Result Filter (Pass / Failed) (Modal)
        if ($result !== null && $result !== '') {
            $resultLower = strtolower(trim($result));
            if (in_array($resultLower, ['pass', 'passed', '1'])) {
                $query->where('assesment_results.pass', 1);
            } elseif (in_array($resultLower, ['failed', 'fail', '0'])) {
                $query->where('assesment_results.pass', 0);
            }
        }

        // 8. Score Filter (Presets & Min/Max) (Modal)
        $passingGrade = (int) ($assesment->passing_grade ?? 0);

        if ($minScore !== null && $minScore !== '') {
            $query->where('assesment_results.score', '>=', (int) $minScore);
        }

        if ($maxScore !== null && $maxScore !== '') {
            $query->where('assesment_results.score', '<=', (int) $maxScore);
        }

        if ($score !== null && trim($score) !== '') {
            $scoreLower = strtolower(trim($score));
            if ($scoreLower === 'passing') {
                if ($passingGrade > 0) {
                    $query->where('assesment_results.score', '>=', $passingGrade);
                } else {
                    $query->where('assesment_results.pass', 1);
                }
            } elseif (in_array($scoreLower, ['below_passing', 'below passing', 'failed'])) {
                if ($passingGrade > 0) {
                    $query->where('assesment_results.score', '<', $passingGrade);
                } else {
                    $query->where('assesment_results.pass', 0);
                }
            } elseif (str_contains($scoreLower, '-')) {
                $parts = explode('-', $scoreLower);
                if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                    $query->whereBetween('assesment_results.score', [(int) trim($parts[0]), (int) trim($parts[1])]);
                }
            } elseif (is_numeric($scoreLower)) {
                $query->where('assesment_results.score', (int) $scoreLower);
            }
        }

        // 9. Start Date Filter (Modal: assesment_results.created_at)
        if ($startDateFrom !== null && trim($startDateFrom) !== '') {
            try {
                $from = Carbon::parse($startDateFrom)->startOfDay();
                $query->where('assesment_results.created_at', '>=', $from);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }
        if ($startDateTo !== null && trim($startDateTo) !== '') {
            try {
                $to = Carbon::parse($startDateTo)->endOfDay();
                $query->where('assesment_results.created_at', '<=', $to);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }

        // 10. Finish Date Filter (Modal: assesment_results.updated_at)
        if ($finishDateFrom !== null && trim($finishDateFrom) !== '') {
            try {
                $from = Carbon::parse($finishDateFrom)->startOfDay();
                $query->where('assesment_results.updated_at', '>=', $from);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }
        if ($finishDateTo !== null && trim($finishDateTo) !== '') {
            try {
                $to = Carbon::parse($finishDateTo)->endOfDay();
                $query->where('assesment_results.updated_at', '<=', $to);
            } catch (\Throwable $e) {
                // Ignore invalid date format
            }
        }

        // Available Batches
        $available_batches = AssesmentResult::where('assesment_id', $assesment->id)
            ->whereFinal(1)
            ->whereNotNull('batch')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch')
            ->map(fn($b) => (int) $b)
            ->toArray();

        if (empty($available_batches) && !empty($assesment->batch)) {
            $available_batches = [(int) $assesment->batch];
        } elseif (!empty($assesment->batch) && !in_array((int) $assesment->batch, $available_batches)) {
            $available_batches[] = (int) $assesment->batch;
            sort($available_batches);
        }

        // Available Teams
        $available_teams = AssesmentResult::join('users', 'assesment_results.user_id', '=', 'users.id')
            ->where('assesment_id', $assesment->id)
            ->whereFinal(1)
            ->whereNotNull('users.team')
            ->whereRaw("TRIM(users.team) != ''")
            ->selectRaw('TRIM(users.team) as team')
            ->distinct()
            ->orderBy('team')
            ->pluck('team')
            ->toArray();

        if (empty($available_teams)) {
            $available_teams = User::whereNotNull('team')
                ->whereRaw("TRIM(team) != ''")
                ->selectRaw('TRIM(team) as team')
                ->distinct()
                ->orderBy('team')
                ->pluck('team')
                ->toArray();
        }

        // Available Departments
        $available_departments = AssesmentResult::join('users', 'assesment_results.user_id', '=', 'users.id')
            ->where('assesment_id', $assesment->id)
            ->whereFinal(1)
            ->whereNotNull('users.department')
            ->whereRaw("TRIM(users.department) != ''")
            ->selectRaw('TRIM(users.department) as department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->toArray();

        if (empty($available_departments)) {
            $available_departments = User::whereNotNull('department')
                ->whereRaw("TRIM(department) != ''")
                ->selectRaw('TRIM(department) as department')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->toArray();
        }

        // Distinct Years from Results created_at
        $years = AssesmentResult::where('assesment_id', $assesment->id)
            ->whereFinal(1)
            ->whereNotNull('created_at')
            ->selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn($y) => (string) $y)
            ->toArray();

        if (empty($years)) {
            $years = [(string) date('Y')];
        }

        // Months 01 - 12
        $months = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];

        $assesment_result = $query->paginate($entries)->withQueryString();
        $assesment_result->load(['scoreHistories.changedBy']);

        return view('main.assesment.result', compact(
            'assesment',
            'entries',
            'assesment_result',
            'search',
            'available_batches',
            'available_teams',
            'available_departments',
            'years',
            'months'
        ))->with('no', (request('page', 1) - 1) * $entries);
    }

    public function clone(Assesment $assesment, AssessmentDuplicateService $duplicateService)
    {
        try {
            $duplicateService->duplicate($assesment);

            return redirect()->route('assesment.index')
                ->with('success', 'Assessment duplicated successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to duplicate assessment: ' . $e->getMessage(), [
                'assessment_id' => $assesment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('assesment.index')
                ->with('danger', 'Failed to duplicate assessment. Please try again.');
        }
    }

    public function destroyResult(Assesment $assesment, $id)
    {
        if (!auth()->user()->canAccessManageModule('assessment_management')) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus assessment result.');
        }

        $result = AssesmentResult::where('assesment_id', $assesment->id)
            ->where('id', $id)
            ->first();

        if (!$result) {
            return back()->with('warning', 'Assessment result not found or has already been deleted.');
        }

        Certificate::where('assessment_result_id', $result->id)->update(['assessment_result_id' => null]);

        $result->delete();

        return back()->with('success', 'Assessment result deleted successfully.');
    }

    public function updateScore(Request $request, Assesment $assesment, $id)
    {
        if (!auth()->user()->canAccessManageModule('assessment_management')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Anda tidak memiliki akses untuk mengubah skor assessment.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki akses untuk mengubah skor assessment.');
        }

        $request->validate([
            'score' => 'required|numeric|min:0|max:100',
        ]);

        $result = AssesmentResult::where('assesment_id', $assesment->id)
            ->where('id', $id)
            ->first();

        if (!$result) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Assessment result not found or has already been deleted.',
                ], 404);
            }
            return back()->with('warning', 'Assessment result not found or has already been deleted.');
        }

        $newScore = (float) $request->score;
        $newScore = floor($newScore) == $newScore ? (int) $newScore : round($newScore, 2);

        $previousScore = $result->score !== null ? (float) $result->score : null;
        if ($previousScore !== null) {
            $previousScore = floor($previousScore) == $previousScore ? (int) $previousScore : round($previousScore, 2);
        }

        // Check if score changed (no-op check)
        $isScoreChanged = ($previousScore === null || (string) $previousScore !== (string) $newScore);

        $passingGrade = (float) ($assesment->passing_grade ?? 0);
        $pass = ($newScore < $passingGrade) ? 0 : 1;

        DB::transaction(function () use ($result, $assesment, $previousScore, $newScore, $pass, $isScoreChanged) {
            if ($isScoreChanged) {
                AssesmentScoreHistory::create([
                    'assesment_result_id' => $result->id,
                    'assesment_id'        => $assesment->id,
                    'user_id'             => $result->user_id,
                    'changed_by_user_id'  => auth()->id(),
                    'previous_score'      => $previousScore,
                    'new_score'           => $newScore,
                ]);
            }

            $result->score = $newScore;
            $result->pass  = $pass;
            $result->save();

            Certificate::where('assessment_result_id', $result->id)->update(['score' => $newScore]);
        });

        // Load latest score histories for response
        $histories = $result->scoreHistories()->with('changedBy')->get()->map(function ($h) {
            return [
                'id'             => $h->id,
                'previous_score' => $h->previous_score,
                'new_score'      => $h->new_score,
                'changed_by'     => $h->changedBy->name ?? 'System',
                'changed_at'     => $h->created_at ? $h->created_at->format('d M Y H:i') : '-',
            ];
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $isScoreChanged ? 'Score updated successfully.' : 'Score has not changed.',
                'data'    => [
                    'id'              => $result->id,
                    'score'           => $result->score,
                    'pass'            => (int) $result->pass,
                    'result_label'    => $result->pass ? 'Pass' : 'Failed',
                    'updated_at'      => $result->updated_at ? $result->updated_at->format('d/m/y H:i:s') : '-',
                    'is_changed'      => $isScoreChanged,
                    'score_histories' => $histories,
                    'history_count'   => $histories->count(),
                ],
            ]);
        }

        return back()->with('success', $isScoreChanged ? 'Score updated successfully.' : 'Score has not changed.');
    }
}
