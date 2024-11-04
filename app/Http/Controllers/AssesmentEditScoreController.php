<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\AssesmentResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class AssesmentEditScoreController extends Controller
{
    public function index()
    {
        $assessments = Assesment::latest()->get();
        $employees   = collect(Http::get(config('api.employee.base_url'))->object())
            ->filter(function ($value) { 
                return $value->id_emp != '';
            });

        return view('main.assesment.edit_score', compact('assessments', 'employees'));
    }

    public function updateScore(Request $request)
    {
        if ($request->is_paper_based == false) {
            $this->validate($request,[
                'assessment' => 'required',
                'employee'   => 'required',
                'score'      => 'required|numeric|min:0|max:100',
            ]);
        } else {
            $this->validate($request,[
                'assessment'           => 'required',
                'employee'             => 'required',
                'score'                => 'required|numeric|min:0|max:100',
                'evidence_paper_based' => 'required|mimes:pdf'
            ]);
        }

        $user                     = User::where('id_emp', $request->employee)->first();
        $assessment               = Assesment::findOrFail($request->assessment);
        $existingAssessmentResult = AssesmentResult::with('user')
            ->whereHas('user', function($query) use ($request) {
                $query->where('id_emp', $request->employee);
            })->where('assesment_id', $request->assessment)->first();

        if ($existingAssessmentResult) {
            $existingAssessmentResult->paper_based = $request->is_paper_based;

            if ($request->hasfile('evidence_paper_based')) {
                $src = 'storage/assesment/paper_based/' . $existingAssessmentResult->evidence_paper_based;
                if (File::exists(public_path($src)) && !is_dir($src)) {
                    File::delete(public_path($src));
                }

                $file = $request->file('evidence_paper_based');
                $extention = $file->getClientOriginalExtension();
                $filename = $request->employee . '_' . $assessment->slug . '_' . time() . '.' . $extention;
                $file->move('storage/assesment/paper_based/', $filename);

                $existingAssessmentResult->evidence_paper_based = $filename;
            }

            $existingAssessmentResult->score = $request->score;
            if ($request->score < $assessment->passing_grade) {
                $existingAssessmentResult->pass = false;
            } elseif ($request->score >= $assessment->passing_grade) {
                $existingAssessmentResult->pass = true;
            }
            $existingAssessmentResult->save();
        } else {
            $assessmentResult = new AssesmentResult;
            $assessmentResult->user_id      = $user->id;
            $assessmentResult->assesment_id = $request->assessment;
            $assessmentResult->user_answer  = [];
            $assessmentResult->final        = true;
            $assessmentResult->score        = $request->score;
            $assessmentResult->batch        = $assessment->batch;
            if ($request->score < $assessment->passing_grade) {
                $assessmentResult->pass = false;
            } elseif ($request->score >= $assessment->passing_grade) {
                $assessmentResult->pass = true;
            }
            $assessmentResult->paper_based  = $request->is_paper_based;
            if ($request->hasfile('evidence_paper_based')) {
                $file = $request->file('evidence_paper_based');
                $extention = $file->getClientOriginalExtension();
                $filename = $request->employee . '_' . $assessment->slug . '_' . time() . '.' . $extention;
                $file->move('storage/assesment/paper_based/', $filename);

                $assessmentResult->evidence_paper_based = $filename;
            }
            $assessmentResult->save();
        }

        return redirect()->back()->with('success', 'The Score of ' . $user->name . ' for ' . $assessment->title . ' has already been updated.');
    }
}
