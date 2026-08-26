<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Assesment;
use Illuminate\Http\Request;
use App\Models\AssesmentResult;
use App\Models\AssesmentQuestion;
use App\Services\AssessmentDuplicateService;
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
        $entries    = $request->show_entries ?? 25;
        $search     = $request->search_user ?? "";
        $query      = AssesmentResult::select('assesment_results.*', 'users.id_emp', 'users.name', 'users.team', 'users.department')
            ->join('users', 'assesment_results.user_id', '=', 'users.id')
            ->where('assesment_id', $assesment->id)
            ->whereFinal(1);

        if ($search)
            $query->where(function ($query) use ($search) {
                $query->where('users.id_emp', 'LIKE', '%' . $search . '%')
                    ->orWhere('users.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('users.team', 'LIKE', '%' . $search . '%')
                    ->orWhere('users.department', 'LIKE', '%' . $search . '%');
            });

        $assesment_result   = $query->paginate($entries)->withQueryString();

        return view('main.assesment.result', compact('assesment', 'entries', 'assesment_result', 'search'))
            ->with('no', (request('page', 1) - 1) * $entries);
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
}
