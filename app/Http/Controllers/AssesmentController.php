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

        $types = Assesment::select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $questionFilters = [
            '1-10'   => '1 - 10 Questions',
            '11-20'  => '11 - 20 Questions',
            '21-30'  => '21 - 30 Questions',
            '31-50'  => '31 - 50 Questions',
            '51-100' => '51 - 100 Questions',
            '100+'   => '100+ Questions',
        ];

        $timers = Assesment::select('timer')
            ->whereNotNull('timer')
            ->distinct()
            ->orderBy('timer')
            ->pluck('timer');

        $statuses = Assesment::select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $passingGradeOptions = [
            'lt60'   => '< 60',
            '60-69'  => '60 - 69',
            '70-79'  => '70 - 79',
            '80-89'  => '80 - 89',
            '90-100' => '90 - 100',
        ];

        $sortOptions = [
            'newest'     => 'Newest',
            'oldest'     => 'Oldest',
            'title_asc'  => 'Title A - Z',
            'title_desc' => 'Title Z - A',
        ];

        $query = Assesment::with([
            'assessment_question_banks',
            'questions',
        ]);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('timer')) {
            $query->where('timer', $request->timer);
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

        if ($request->filled('questions')) {
            $query->whereHas('assessment_question_banks', function ($q) use ($request) {
                switch ($request->questions) {
                    case '1-10':
                        $q->whereBetween('questions_amount', [1, 10]);
                        break;

                    case '11-20':
                        $q->whereBetween('questions_amount', [11, 20]);
                        break;

                    case '21-30':
                        $q->whereBetween('questions_amount', [21, 30]);
                        break;

                    case '31-50':
                        $q->whereBetween('questions_amount', [31, 50]);
                        break;

                    case '51-100':
                        $q->whereBetween('questions_amount', [51, 100]);
                        break;

                    case '100+':
                        $q->where('questions_amount', '>', 100);
                        break;
                }
            });
        }

        if ($request->filled('passing_grade')) {
            switch ($request->passing_grade) {
                case 'lt60':
                    $query->where('passing_grade', '<', 60);
                    break;
                case '60-69':
                    $query->whereBetween('passing_grade', [60, 69]);
                    break;
                case '70-79':
                    $query->whereBetween('passing_grade', [70, 79]);
                    break;
                case '80-89':
                    $query->whereBetween('passing_grade', [80, 89]);
                    break;
                case '90-100':
                    $query->whereBetween('passing_grade', [90, 100]);
                    break;
                default:
                    if (is_numeric($request->passing_grade)) {
                        $query->where('passing_grade', (int) $request->passing_grade);
                    }
                    break;
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->filled('schedule_from')) {
            $query->whereDate('fromdate', '>=', $request->schedule_from);
        }

        if ($request->filled('schedule_to')) {
            $query->whereDate('todate', '<=', $request->schedule_to);
        }

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
            'types',
            'timers',
            'statuses',
            'questionFilters',
            'passingGradeOptions',
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
