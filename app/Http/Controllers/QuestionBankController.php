<?php

namespace App\Http\Controllers;

use App\Models\QuestionBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QuestionBankController extends Controller
{
    /**
     * AJAX live autocomplete search for Question Bank Management.
     */
    public function autocompleteSearch(Request $request): JsonResponse
    {
        $rawQuery = trim((string) ($request->get('q') ?? $request->get('search') ?? $request->get('s') ?? ''));
        if (empty($rawQuery)) {
            return response()->json([]);
        }

        // Replace straight apostrophe with curly apostrophe and vice versa for flexible matching
        $queryNormalized = str_replace("'", "’", $rawQuery);
        $queryOriginal = str_replace("’", "'", $rawQuery);

        $questionBanks = QuestionBank::withCount('questions')
            ->where(function ($q) use ($rawQuery, $queryNormalized, $queryOriginal) {
                $q->where('title', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('title', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('slug', 'LIKE', "%{$rawQuery}%");
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $results = $questionBanks->map(function ($item) {
            return [
                'id'              => $item->id,
                'title'           => $item->title,
                'name'            => $item->title,
                'slug'            => $item->slug,
                'questions_count' => (int) ($item->questions_count ?? 0),
                'created_date'    => $item->created_date ? Carbon::parse($item->created_date)->format('d M Y') : ($item->created_at ? $item->created_at->format('d M Y') : null),
            ];
        });

        return response()->json($results->values());
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->autocompleteSearch($request);
    }

    public function index(Request $request)
    {
        $query = QuestionBank::with('questions')->withCount('questions');

        // Search Filter
        $search = trim((string) ($request->get('search') ?? $request->get('s') ?? ''));
        if ($search !== '') {
            $queryNormalized = str_replace("'", "’", $search);
            $queryOriginal = str_replace("’", "'", $search);
            $query->where(function ($q) use ($search, $queryNormalized, $queryOriginal) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('title', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        // Total Questions Filter
        if ($request->filled('total_questions')) {
            $totalQ = $request->get('total_questions');
            if ($totalQ === '1_20' || $totalQ === '1-20') {
                $query->has('questions', '>=', 1)->has('questions', '<=', 20);
            } elseif ($totalQ === '20_40' || $totalQ === '20-40') {
                $query->has('questions', '>=', 20)->has('questions', '<=', 40);
            } elseif ($totalQ === '40_50' || $totalQ === '40-50') {
                $query->has('questions', '>=', 40)->has('questions', '<=', 50);
            } elseif ($totalQ === '50_100' || $totalQ === '50-100') {
                $query->has('questions', '>=', 50)->has('questions', '<=', 100);
            } elseif ($totalQ === 'gt_100' || $totalQ === '100+') {
                $query->has('questions', '>', 100);
            }
        }

        // Sort by Created Date
        $sortCreated = $request->get('sort_created', 'all');
        if ($sortCreated === 'oldest') {
            $query->orderBy('created_at', 'asc')->orderBy('id', 'asc');
        } elseif ($sortCreated === 'newest') {
            $query->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        } else {
            // 'all' -> default order
            $query->latest('id');
        }

        $totalQuestionBank = QuestionBank::count();
        $questionBanks = $query->paginate(15)->withQueryString();

        return view('main.question_bank.index', compact('questionBanks', 'totalQuestionBank'));
    }

    public function create() 
    {
        return view('main.question_bank.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title'        => 'required|unique:question_banks,title',
            'created_date' => 'required|date',
        ]);

        $questionBank = new QuestionBank;
        $questionBank->title        = $request->input('title');
        $questionBank->created_date = $request->input('created_date');
        $questionBank->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => $request->title . " has already successfully created in the question bank.",
                'redirect' => route('question.bank.index')
            ]);
        }

        return redirect()->route('question.bank.index')->with('success',  $request->title . " has already successfully created in the question bank.");
    }

    public function edit($slug)
    {
        $questionBank = QuestionBank::findBySlug($slug);

        return view('main.question_bank.edit', compact('questionBank'));
    }

    public function update(Request $request, $id)
    {
        $questionBank = QuestionBank::findOrFail($id);

        $this->validate($request, [
            'title'        => 'required|unique:question_banks,title,' . $id,
            'created_date' => 'required|date',
        ]);

        $questionBank->title        = $request->title;
        $questionBank->created_date = $request->created_date;
        $questionBank->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => "Question Bank has already successfully updated.",
                'redirect' => route('question.bank.index')
            ]);
        }

        return redirect()->route('question.bank.index')->with('warning',  "Question Bank has already successfully updated.");
    }

    public function destroy($id)
    {
        $questionBank = QuestionBank::findOrFail($id);
        $questionBank->delete();

        return redirect()->route('question.bank.index')->with('danger',  $questionBank->title . " has already successfully deleted!");
    }

    public function clone($slug)
    {
        $questionBank = QuestionBank::findBySlug($slug);

        // Clone Question Bank
        $cloneQuestionBank = $questionBank->replicate();
        $cloneQuestionBank->created_date = Carbon::now()->format('Y-m-d');
        $cloneQuestionBank->created_at   = Carbon::now();
        $cloneQuestionBank->push();

        // Add relationship clone
        // Gathering old assessment question, then clone to new
        foreach ($questionBank->questions as $question) {
            $cloneQuestionBank->questions()->create($question->toArray());
        }
        $cloneQuestionBank->save();

        return redirect()->route('question.bank.index')->with('success',  $questionBank->title . " has already successfully cloned/duplicated.");
    }
}
