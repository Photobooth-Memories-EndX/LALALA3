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
    public function index()
    {
        $assesment = Assesment::latest()->get();
        return view('main.assesment.index', compact('assesment'));
    }

    public function create()
    {
        $contents = Content::latest()->get();
        $trainingplan = TrainingPlan::latest()->get();
        return view('main.assesment.create', compact('contents', 'trainingplan'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'type' => 'required',
            'timer' => 'required',
            'fromdate' => 'required_if:timer,y',
            'todate' => 'required_if:timer,y',
            'passing_grade' => 'required',
        ]);

        $assesment = new Assesment();
        $assesment->type = $request->input('type');
        $assesment->content_id = $request->input('content_id');
        $assesment->trainingplan_id = $request->input('trainingplan_id');
        $assesment->title = $request->input('title');
        $assesment->passing_grade = $request->input('passing_grade');
        $assesment->timer = $request->input('timer');
        $assesment->fromdate = $request->input('fromdate');
        $assesment->todate = $request->input('todate');
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
        $request->validate([
            'title' => 'required',
            'type' => 'required',
            'timer' => 'required',
            'fromdate' => 'required_if:timer,y',
            'todate' => 'required_if:timer,y',
            'passing_grade' => 'required',
        ]);

        $assesment->type = $request->input('type');
        $assesment->content_id = $request->input('content_id');
        $assesment->trainingplan_id = $request->input('trainingplan_id');
        $assesment->title = $request->input('title');
        $assesment->passing_grade = $request->input('passing_grade');
        $assesment->timer = $request->input('timer');
        $assesment->fromdate = $request->input('fromdate');
        $assesment->todate = $request->input('todate');
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
        $request->validate([
            //'slug' => 'required|unique:assesments,slug',
            'slug' => 'required',
            'batch' => 'required',
            'timer' => 'required',
            'fromdate' => 'required_if:timer,y',
            'todate' => 'required_if:timer,y',
        ]);


        $assesment->timer = $request->input('timer');
        $assesment->fromdate = $request->input('fromdate');
        $assesment->todate = $request->input('todate');
        $assesment->batch = $request->input('batch');
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
}