<?php

namespace App\Http\Controllers;

use App\Exports\QuestionBankQuestionsExport;
use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function create($question_bank_slug)
    {
        $questionBank = QuestionBank::findBySlug($question_bank_slug);
        $questions    = Question::where('question_bank_id', $questionBank->id)->latest()->get();

        return view('main.question_bank.questions.create', compact('questionBank', 'questions'));
    }

    public function store(Request $request, $question_bank_id)
    {
        $questionBank = is_numeric($question_bank_id)
            ? QuestionBank::findOrFail($question_bank_id)
            : (QuestionBank::findBySlug($question_bank_id) ?: QuestionBank::where('slug', $question_bank_id)->firstOrFail());

        $isDraft = $request->input('action') === 'draft';

        if ($isDraft) {
            $request->validate([
                'question' => 'nullable',
                'answer'   => 'nullable',
                'option'   => 'nullable',
                'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $rawQuestion = trim((string) $request->input('question'));
            $cleanText = trim(strip_tags($rawQuestion, '<img>'));
            $hasImage = $request->hasFile('image');
            $options = $request->input('option');
            $hasOptions = !empty($options) && is_array($options) && count(array_filter($options)) > 0;

            // If completely empty (user just clicked "Back" -> "Save as Draft & Exit" on empty form), exit cleanly without creating empty question row
            if ($cleanText === '' && !$hasImage && !$hasOptions && empty($request->input('answer'))) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'status'   => 200,
                        'message'  => 'No changes to save.',
                        'redirect' => route('question.bank.index')
                    ]);
                }
                return redirect()->route('question.bank.index');
            }

            $question = new Question;
            $question->question_bank_id = $questionBank->id;
            $question->question         = $rawQuestion !== '' ? $rawQuestion : 'Draft Question';
            $question->option           = is_array($options) ? array_values(array_filter($options)) : [];
            $question->answer           = (string) $request->input('answer', '');
            $question->status           = false; // 0 = Non Aktif / Draft

            if ($request->hasfile('image')) {
                $file      = $request->file('image');
                $extention = $file->getClientOriginalExtension();
                $filename  = 'id_' . $questionBank->id . '_' . time() . '.' . $extention;
                $file->move('storage/question_bank/image/', $filename);

                $question->question_image = $filename;
            }
            $question->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'   => 200,
                    'message'  => 'Question draft has been saved successfully.',
                    'redirect' => route('question.bank.index')
                ]);
            }

            return redirect()->route('question.bank.index')->with('success', 'Question draft has been saved successfully.');
        }

        $request->validate([
            'question' => 'required',
            'answer'   => 'required',
            'option'   => 'required',
            'image'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $question = new Question;
        $question->question_bank_id = $questionBank->id;
        $question->question         = $request->input('question');
        $question->option           = $request->input('option');
        $question->answer           = $request->input('answer');
        $question->status           = true;
        if ($request->hasfile('image')) {
            $file      = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename  = 'id_' . $questionBank->id . '_' . time() . '.' . $extention;
            $file->move('storage/question_bank/image/', $filename);

            $question->question_image = $filename;
        }
        $question->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Question has already been created successfully.',
                'redirect' => route('question.bank.index')
            ]);
        }

        return redirect()->route('question.bank.create.question', $questionBank->slug)->with('success', 'Question has already been created successfully.');
    }

    public function edit($question_bank_slug, $question_id)
    {
        $questionBank = QuestionBank::findBySlug($question_bank_slug);
        $question     = Question::findOrFail($question_id);
        $questions    = Question::where('question_bank_id', $questionBank->id)->latest()->get();

        return view('main.question_bank.questions.edit', compact('questionBank', 'question', 'questions'));
    }

    public function deleteQuestionImage($question_id) 
    {
        $question = Question::findorFail($question_id);

        $src = 'storage/question_bank/image/' . $question->question_image;
        if(File::exists(public_path($src)) && !is_dir($src))
        {
            File::delete(public_path($src));
        }

        $question->question_image = null;
        $question->save();

        return response()->json([
            'status'  =>200,
            'message' =>'Image question deleted successfully', 
        ]);
    }

    public function update(Request $request, $question_bank_slug, $question_id)
    {
        $questionBank = is_numeric($question_bank_slug)
            ? QuestionBank::findOrFail($question_bank_slug)
            : (QuestionBank::findBySlug($question_bank_slug) ?: QuestionBank::where('slug', $question_bank_slug)->firstOrFail());

        $question = Question::findOrFail($question_id);
        $isDraft = $request->input('action') === 'draft';

        if ($isDraft) {
            $request->validate([
                'question' => 'nullable',
                'answer'   => 'nullable',
                'option'   => 'nullable',
                'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($request->has('question')) {
                $question->question = (string) $request->input('question');
            }
            if ($request->has('option')) {
                $options = $request->input('option');
                $question->option = is_array($options) ? array_values(array_filter($options)) : [];
            }
            if ($request->has('answer')) {
                $question->answer = (string) $request->input('answer', '');
            }
            $question->status = false; // Mark as Draft / Non Aktif

            if ($request->hasfile('image')) {
                $src = 'storage/question_bank/image/' . $question->question_image;

                if (File::exists(public_path($src)) && !is_dir($src)) {
                    File::delete(public_path($src));
                }

                $file      = $request->file('image');
                $extention = $file->getClientOriginalExtension();
                $filename  = 'id_' . $questionBank->id . '_' . time() . '.' . $extention;
                $file->move('storage/question_bank/image/', $filename);
                $question->question_image = $filename;
            }
            $question->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'   => 200,
                    'message'  => 'Question draft has been saved successfully.',
                    'redirect' => route('question.bank.index')
                ]);
            }

            return redirect()->route('question.bank.index')->with('success', 'Question draft has been saved successfully.');
        }

        $request->validate([
            'question' => 'required',
            'answer'   => 'required',
            'option'   => 'required',
            'image'    => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $question->question = $request->input('question');
        $question->option   = $request->input('option');
        $question->answer   = $request->input('answer');
        $question->status   = true;
        if ($request->hasfile('image')) {
            $src = 'storage/question_bank/image/' . $question->question_image;

            if (File::exists(public_path($src)) && !is_dir($src)) {
                File::delete(public_path($src));
            }

            $file      = $request->file('image');
            $extention = $file->getClientOriginalExtension();
            $filename  = 'id_' . $questionBank->id . '_' . time() . '.' . $extention;
            $file->move('storage/question_bank/image/', $filename);
            $question->question_image = $filename;
        }
        $question->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Question has been updated successfully.',
                'redirect' => route('question.bank.index')
            ]);
        }

        return redirect()->route('question.bank.create.question', $questionBank->slug)->with('warning', "Qustion has already updated successfully.");
    }

    public function destroy($question_bank_slug, $question_id)
    {
        $question = Question::findOrFail($question_id);
        $question->delete();

        return redirect()->route('question.bank.create.question', $question_bank_slug)->with('danger', "The question deleted successfully!");
    }

    public function downloadQuestions($question_bank_id)
    {
        $questionBank = QuestionBank::findOrFail($question_bank_id);

        return Excel::download(new QuestionBankQuestionsExport($questionBank->id), 'Questions of ' . $questionBank->title . '.xlsx');
    }
}
