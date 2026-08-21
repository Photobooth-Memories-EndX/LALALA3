<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentQuestionBank;
use App\Models\QuestionBank;
use App\Models\TrainingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AssesmentController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 15);

        $entries = in_array((int) $entries, [10, 15, 20, 25, 50])
            ? (int) $entries
            : 15;

        // Distinct years from assessments created_at
        $years = Assesment::selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

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

        $types = Assesment::select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $statuses = Assesment::select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $questionUsedFilters = [
            '1-10'   => '1 – 10',
            '11-20'  => '11 – 20',
            '21-30'  => '21 – 30',
            '31-50'  => '31 – 50',
            '51-100' => '51 – 100',
            '100+'   => '> 100',
        ];

        $questionBankFilters = [
            '1-10'   => '1 – 10',
            '11-20'  => '11 – 20',
            '21-30'  => '21 – 30',
            '31-50'  => '31 – 50',
            '51-100' => '51 – 100',
            '100+'   => '> 100',
        ];

        $durationFilters = [
            'lt10'  => '< 10 Minutes',
            '10-30' => '10 – 30 Minutes',
            '30-60' => '30 – 60 Minutes',
            'gt60'  => '> 1 Hour',
            'none'  => 'No Duration',
        ];

        $sortOptions = [
            'newest'     => 'Newest',
            'oldest'     => 'Oldest',
            'title_asc'  => 'Assessment Title A–Z',
            'title_desc' => 'Assessment Title Z–A',
        ];

        $query = Assesment::with([
            'assessment_question_banks.question_bank' => function ($q) {
                $q->withCount('questions');
            },
            'questions',
        ]);

        // Quick Filters
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('unique_code', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
            });
        }

        // More Filters: Total Question Used
        $questionUsed = $request->input('question_used', $request->input('questions'));
        if (!empty($questionUsed)) {
            $usedSubquery = "(CASE 
                WHEN (SELECT COUNT(*) FROM assesment_question_banks WHERE assesment_question_banks.assesment_id = assesments.id) > 0 
                THEN (SELECT COALESCE(SUM(questions_amount), 0) FROM assesment_question_banks WHERE assesment_question_banks.assesment_id = assesments.id)
                ELSE (SELECT COUNT(*) FROM assesment_questions WHERE assesment_questions.assesment_id = assesments.id)
            END)";

            if ($questionUsed === '100+' || $questionUsed === 'gt100') {
                $query->whereRaw("$usedSubquery > 100");
            } elseif (str_contains($questionUsed, '-')) {
                [$min, $max] = explode('-', $questionUsed);
                $query->whereRaw("$usedSubquery BETWEEN ? AND ?", [(int) $min, (int) $max]);
            }
        }

        // More Filters: Total Question Bank (Pool of questions in Question Banks attached to assessment)
        if ($request->filled('question_bank')) {
            $qbPoolSubquery = "(SELECT COALESCE(SUM(qb_pool.cnt), 0) FROM assesment_question_banks aqb 
                JOIN (SELECT question_bank_id, COUNT(*) as cnt FROM questions GROUP BY question_bank_id) qb_pool 
                ON qb_pool.question_bank_id = aqb.question_bank_id 
                WHERE aqb.assesment_id = assesments.id)";

            $qbVal = $request->question_bank;
            if ($qbVal === '100+' || $qbVal === 'gt100') {
                $query->whereRaw("$qbPoolSubquery > 100");
            } elseif (str_contains($qbVal, '-')) {
                [$min, $max] = explode('-', $qbVal);
                $query->whereRaw("$qbPoolSubquery BETWEEN ? AND ?", [(int) $min, (int) $max]);
            }
        }

        // More Filters: Period
        $periodFrom = $request->input('period_from', $request->input('schedule_from'));
        if (!empty($periodFrom)) {
            $query->whereDate('fromdate', '>=', $periodFrom);
        }

        $periodTo = $request->input('period_to', $request->input('schedule_to'));
        if (!empty($periodTo)) {
            $query->whereDate('todate', '<=', $periodTo);
        }

        // More Filters: Duration
        if ($request->filled('duration') && $request->duration !== 'all') {
            switch ($request->duration) {
                case 'lt10':
                    $query->where('timer', 'y')->whereNotNull('time_limit_minutes')->where('time_limit_minutes', '<', 10)->where('time_limit_minutes', '>', 0);
                    break;
                case '10-30':
                    $query->where('timer', 'y')->whereBetween('time_limit_minutes', [10, 30]);
                    break;
                case '30-60':
                    $query->where('timer', 'y')->whereBetween('time_limit_minutes', [30, 60]);
                    break;
                case 'gt60':
                    $query->where('timer', 'y')->where('time_limit_minutes', '>', 60);
                    break;
                case 'none':
                    $query->where(function ($sq) {
                        $sq->where('timer', '!=', 'y')
                            ->orWhereNull('timer')
                            ->orWhereNull('time_limit_minutes')
                            ->orWhere('time_limit_minutes', '<=', 0);
                    });
                    break;
            }
        }

        // Sort By
        switch ($request->input('sort')) {
            case 'oldest':
                $query->oldest('id');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'newest':
            default:
                $query->latest('id');
                break;
        }

        $assesment = $query
            ->paginate($entries)
            ->withQueryString();

        return view('main.assesment.index', compact(
            'assesment',
            'years',
            'months',
            'types',
            'statuses',
            'questionUsedFilters',
            'questionBankFilters',
            'durationFilters',
            'sortOptions'
        ));
    }

    public function create()
    {
        $contents = Content::latest()->get();
        $trainingplan = TrainingPlan::latest()->get();
        return view('main.assesment.create', compact('contents', 'trainingplan'));
    }

    public function store(Request $request)
    {
        $isGlobal        = $request->input('type') === 'global';
        $hasAccessPeriod = $request->boolean('has_access_period') || in_array($request->input('has_access_period'), ['1', 'y', 'yes', true], true);
        $hasTimeLimit    = !$isGlobal && in_array($request->input('timer'), ['1', 'y', 'yes', true], true);

        $request->validate([
            'title'              => 'required',
            'unique_code'        => 'nullable|max:8|alpha_dash|unique:assesments,unique_code',
            'type'               => 'required',
            'fromdate'           => $hasAccessPeriod ? 'required' : 'nullable',
            'todate'             => $hasAccessPeriod ? 'required' : 'nullable',
            'time_limit_minutes' => $hasTimeLimit ? 'required|integer|min:1' : 'nullable',
            'passing_grade'      => 'required',
            'total_remedials'    => $isGlobal ? 'nullable' : 'required',
        ]);

        $assesment = new Assesment();
        $assesment->type               = $request->input('type');
        $assesment->content_id         = $request->input('type') === 'content' ? $request->input('content_id') : null;
        $assesment->trainingplan_id    = $request->input('type') === 'trainingplan' ? $request->input('trainingplan_id') : null;
        $assesment->title              = $request->input('title');
        $assesment->unique_code        = $request->input('unique_code');
        $assesment->passing_grade      = $request->input('passing_grade');

        $assesment->has_access_period  = $hasAccessPeriod;
        $assesment->fromdate           = $hasAccessPeriod ? $request->input('fromdate') : null;
        $assesment->todate             = $hasAccessPeriod ? $request->input('todate') : null;

        $assesment->timer              = $hasTimeLimit ? 'y' : 'n';
        $assesment->time_limit_minutes = $hasTimeLimit ? $request->input('time_limit_minutes') : null;

        if ($hasAccessPeriod && $hasTimeLimit) {
            $assesment->deadline_type = 'period';
        } elseif ($hasAccessPeriod) {
            $assesment->deadline_type = 'period';
        } elseif ($hasTimeLimit) {
            $assesment->deadline_type = 'time_limit';
        } else {
            $assesment->deadline_type = 'none';
        }

        $assesment->total_remedials = $isGlobal ? 0 : ($request->input('total_remedials') ?? 0);
        $assesment->save();

        return redirect()->route('assesment.edit', $assesment->slug)->with('success','Assesment created successfully.');
    }

    public function edit(Assesment $assesment)
    {
        $contents      = Content::latest()->get();
        $trainingplan  = TrainingPlan::latest()->get();
        $questionBanks = QuestionBank::get();

        $totalQuestions          = 0;
        $assessmentQuestionBanks = AssesmentQuestionBank::where('assesment_id', $assesment->id)->get();
        $totalQuestions          = $assessmentQuestionBanks->sum('questions_amount');

        return view('main.assesment.edit', compact('assesment', 'contents', 'trainingplan', 'questionBanks', 'assessmentQuestionBanks', 'totalQuestions'));
    }

    public function update(Request $request, Assesment $assesment)
    {
        $isGlobal        = $request->input('type') === 'global';
        $hasAccessPeriod = $request->boolean('has_access_period') || in_array($request->input('has_access_period'), ['1', 'y', 'yes', true], true);
        $hasTimeLimit    = !$isGlobal && in_array($request->input('timer'), ['1', 'y', 'yes', true], true);

        $request->validate([
            'title'              => 'required',
            'unique_code'        => 'nullable|max:8|alpha_dash|unique:assesments,unique_code,' . $assesment->id,
            'type'               => 'required',
            'fromdate'           => $hasAccessPeriod ? 'required' : 'nullable',
            'todate'             => $hasAccessPeriod ? 'required' : 'nullable',
            'time_limit_minutes' => $hasTimeLimit ? 'required|integer|min:1' : 'nullable',
            'passing_grade'      => 'required',
            'total_remedials'    => $isGlobal ? 'nullable' : 'required',
        ]);

        $assesment->type               = $request->input('type');
        $assesment->content_id         = $request->input('type') === 'content' ? $request->input('content_id') : null;
        $assesment->trainingplan_id    = $request->input('type') === 'trainingplan' ? $request->input('trainingplan_id') : null;
        $assesment->title              = $request->input('title');
        $assesment->unique_code        = $request->input('unique_code');
        $assesment->passing_grade      = $request->input('passing_grade');

        $assesment->has_access_period  = $hasAccessPeriod;
        $assesment->fromdate           = $hasAccessPeriod ? $request->input('fromdate') : null;
        $assesment->todate             = $hasAccessPeriod ? $request->input('todate') : null;

        $assesment->timer              = $hasTimeLimit ? 'y' : 'n';
        $assesment->time_limit_minutes = $hasTimeLimit ? $request->input('time_limit_minutes') : null;

        if ($hasAccessPeriod && $hasTimeLimit) {
            $assesment->deadline_type = 'period';
        } elseif ($hasAccessPeriod) {
            $assesment->deadline_type = 'period';
        } elseif ($hasTimeLimit) {
            $assesment->deadline_type = 'time_limit';
        } else {
            $assesment->deadline_type = 'none';
        }

        $assesment->total_remedials = $isGlobal ? 0 : ($request->input('total_remedials') ?? 0);
        $assesment->save();

        return to_route('assesment.index')->with('success','Assesment updated successfully');
    }

    public function destroy(Assesment $assesment)
    {
        $assesment->delete();
        return to_route('assesment.index')->with('success','Assesment deleted successfully');
    }

    public function changestatus(Request $request)
    {
        $assesment = Assesment::findOrFail($request->id);
        $assesment->status = $request->status;
        $assesment->save();

        return response()->json(['success'=>'Status change successfully.']);
    }

    public function newlink(Assesment $assesment)
    {
        return view('main.assesment.newlink', compact('assesment'));
    }

    public function updatelink(Request $request, Assesment $assesment)
    {
        $hasAccessPeriod = $request->boolean('has_access_period') || in_array($request->input('has_access_period'), ['1', 'y', 'yes', true], true);
        $hasTimeLimit    = in_array($request->input('timer'), ['1', 'y', 'yes', true], true);

        $request->validate([
            'slug'               => 'required',
            'batch'              => 'required',
            'fromdate'           => $hasAccessPeriod ? 'required' : 'nullable',
            'todate'             => $hasAccessPeriod ? 'required' : 'nullable',
            'time_limit_minutes' => $hasTimeLimit ? 'required|integer|min:1' : 'nullable',
        ]);

        $assesment->batch              = $request->input('batch');
        $assesment->has_access_period  = $hasAccessPeriod;
        $assesment->fromdate           = $hasAccessPeriod ? $request->input('fromdate') : null;
        $assesment->todate             = $hasAccessPeriod ? $request->input('todate') : null;

        $assesment->timer              = $hasTimeLimit ? 'y' : 'n';
        $assesment->time_limit_minutes = $hasTimeLimit ? $request->input('time_limit_minutes') : null;

        if ($hasAccessPeriod && $hasTimeLimit) {
            $assesment->deadline_type = 'period';
        } elseif ($hasAccessPeriod) {
            $assesment->deadline_type = 'period';
        } elseif ($hasTimeLimit) {
            $assesment->deadline_type = 'time_limit';
        } else {
            $assesment->deadline_type = 'none';
        }

        $assesment->save();
        $assesment->slug = $request->input('slug');
        $assesment->update();

        return to_route('assesment.index')->with('success','Assesment updated successfully');
    }

    public function assignUsersIndex($slug)
    {
        $assesment   = Assesment::findBySlug($slug);
        $employees   = collect(Http::get(config('api.employee.base_url'))->object());
        $teams       = $employees->unique('id_team');
        $departments = $employees->unique('id_department');
        $assigned    = AssesmentAssigned::where('assesment_id', $assesment->id)->get();


        return view('main.assesment.assign_users', compact('assesment', 'employees', 'teams', 'departments', 'assigned'));
    }

    public function assignUserProcess(Request $request, $slug)
    {
        $assesment     = Assesment::findBySlug($slug);

        $assignEmployee   = $request->assign_employee;
        $assignTeam       = $request->assign_team;
        $assignDepartment = $request->assign_department;

        if ($assignEmployee != '') {
            $checkAssigned = AssesmentAssigned::where('assesment_id', $assesment->id)->where('assigned_id', $assignEmployee)->first();

            $apiEmployee = Http::get(config('api.employee.base_url'));
            $employee    = collect(json_decode($apiEmployee))->filter(function ($employee) use ($assignEmployee){
                return $employee->id_emp == $assignEmployee;
            })->first();

            if ($checkAssigned) {
                dd($assignEmployee . " Employee id exist");
            } else {
                AssesmentAssigned::create([
                    'assesment_id'         => $assesment->id,
                    'assigned_id'          => $assignEmployee,
                    'assigned_type'        => 'employee',
                    'assigned_description' => $employee->name,
                ]);
            }
        }

        if ($assignTeam != '') {
            $checkAssigned = AssesmentAssigned::where('assesment_id', $assesment->id)->where('assigned_id', $assignTeam)->first();

            $apiEmployee = Http::get(config('api.employee.base_url'));
            $employee    = collect(json_decode($apiEmployee))->filter(function ($employee) use ($assignTeam){
                return $employee->id_team == $assignTeam;
            })->first();

            if ($checkAssigned) {
                dd($assignTeam . " Employee id exist");
            } else {
                AssesmentAssigned::create([
                    'assesment_id'         => $assesment->id,
                    'assigned_id'          => $assignTeam,
                    'assigned_type'        => 'team',
                    'assigned_description' => $employee->team,
                ]);
            }
        }

        if ($assignDepartment != '') {
            $checkAssigned = AssesmentAssigned::where('assesment_id', $assesment->id)->where('assigned_id', $assignDepartment)->first();

            $apiEmployee = Http::get(config('api.employee.base_url'));
            $employee    = collect(json_decode($apiEmployee))->filter(function ($employee) use ($assignDepartment){
                return $employee->id_department == $assignDepartment;
            })->first();

            if ($checkAssigned) {
                dd($assignDepartment . " Employee id exist");
            } else {
                AssesmentAssigned::create([
                    'assesment_id'         => $assesment->id,
                    'assigned_id'          => $assignDepartment,
                    'assigned_type'        => 'department',
                    'assigned_description' => $employee->department,
                ]);
            }
        }

        return redirect()->back()->with('success', "The users assignment was successful.");
    }

    public function deleteAssignedUser($id)
    {
        $assignedUser = AssesmentAssigned::findOrFail($id);
        $assignedUser->delete();

        session()->flash('danger', $assignedUser->assigned_description . ' was successfully removed from the list of assigned users!');
        return redirect()->back();
    }
}
