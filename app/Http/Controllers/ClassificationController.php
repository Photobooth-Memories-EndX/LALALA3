<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    /**
     * AJAX live autocomplete search for Category Management.
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

        $categories = Classification::where(function ($q) use ($rawQuery, $queryNormalized, $queryOriginal) {
                $q->where('classification_name', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('classification_name', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('classification_name', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('slug', 'LIKE', "%{$rawQuery}%");
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $results = $categories->map(function ($item) {
            return [
                'id'                  => $item->id,
                'title'               => $item->classification_name,
                'name'                => $item->classification_name,
                'type'                => 'CATEGORY',
                'classification_name' => $item->classification_name,
                'slug'                => $item->slug,
                'url'                 => route('manage.classification.edit', $item->slug ?? $item->id),
            ];
        });

        return response()->json($results->values());
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->autocompleteSearch($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Classification::query();

        $search = trim((string) ($request->get('q') ?? $request->get('search') ?? $request->get('s') ?? ''));
        if ($search !== '') {
            $queryNormalized = str_replace("'", "’", $search);
            $queryOriginal = str_replace("’", "'", $search);
            $query->where(function ($q) use ($search, $queryNormalized, $queryOriginal) {
                $q->where('classification_name', 'LIKE', "%{$search}%")
                  ->orWhere('classification_name', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('classification_name', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        $classification = $query->latest('id')->get();
        $totalCategories = Classification::count();
        $activeCategories = $totalCategories; // All created categories are active
        $totalAssignedContent = \App\Models\Content::whereNotNull('classification_id')->count();
        $avgContent = $totalCategories > 0 ? round($totalAssignedContent / $totalCategories, 1) : 0;

        return view('main.classification.index', compact(
            'classification',
            'totalCategories',
            'activeCategories',
            'totalAssignedContent',
            'avgContent'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('main.classification.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'classification_name' => 'required',
        ]);

        $classification = new Classification([
            'classification_name' => $request->classification_name,
        ]);

        $classification->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Category created successfully.',
                'redirect' => route('manage.classification.index')
            ]);
        }

        return to_route('manage.classification.index')->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Classification  $classification
     * @return \Illuminate\Http\Response
     */
    public function show(Classification $classification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Classification  $classification
     * @return \Illuminate\Http\Response
     */
    public function edit(Classification $classification)
    {
        return view('main.classification.edit', compact('classification'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Classification  $classification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Classification $classification)
    {
        $request->validate([
            'classification_name' => 'required',
        ]);

        $classification->update($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Category updated successfully.',
                'redirect' => route('manage.classification.index')
            ]);
        }

        return to_route('manage.classification.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Classification  $classification
     * @return \Illuminate\Http\Response
     */
    public function destroy(Classification $classification)
    {
        $classification->delete();
        return redirect()->route('manage.classification.index')
            ->with('success', 'Category deleted successfully');
    }
}
