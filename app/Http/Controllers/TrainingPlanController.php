<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrainingPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = TrainingPlan::query()->withCount('trainingplandetail');

        if ($request->filled('s')) {
            $search = $request->s;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('url_link', 'like', "%{$search}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->active);
        }

        if ($request->filled('deadline')) {
            if ($request->deadline === 'with') {
                $query->where('has_deadline', 1);
            } elseif ($request->deadline === 'without') {
                $query->where(function ($q) {
                    $q->whereNull('has_deadline')->orWhere('has_deadline', 0);
                });
            } elseif ($request->deadline === 'active') {
                $query->where('has_deadline', 1)
                      ->whereDate('from_date', '<=', now()->toDateString())
                      ->whereDate('to_date', '>=', now()->toDateString());
            } elseif ($request->deadline === 'expired') {
                $query->where('has_deadline', 1)
                      ->whereDate('to_date', '<', now()->toDateString());
            }
        }

        if ($request->filled('thumbnail')) {
            if ($request->thumbnail === 'with') {
                $query->whereNotNull('thumbnail')->where('thumbnail', '!=', '');
            } elseif ($request->thumbnail === 'without') {
                $query->where(function ($q) {
                    $q->whereNull('thumbnail')->orWhere('thumbnail', '');
                });
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        switch ($request->get('sort')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'materials':
                $query->orderByDesc('trainingplandetail_count');
                break;
            default:
                $query->latest();
                break;
        }

        $trainingplans = $query->paginate(10)->withQueryString();

        $totalTrainingPlan   = TrainingPlan::count();
        $activeTrainingPlan  = TrainingPlan::where('active', 'Active')->count();
        $inactiveTrainingPlan = TrainingPlan::where('active', 'Inactive')->count();
        $withDeadline        = TrainingPlan::where('has_deadline', 1)->count();
        $expiredDeadline     = TrainingPlan::where('has_deadline', 1)
                                ->whereDate('to_date', '<', now()->toDateString())
                                ->count();
        $totalMaterials      = TrainingPlanDetail::count();

        return view('main.trainingplan.index', [
            'title'               => 'Training Plan Management',
            'trainingplans'       => $trainingplans,
            'totalTrainingPlan'   => $totalTrainingPlan,
            'activeTrainingPlan'  => $activeTrainingPlan,
            'inactiveTrainingPlan' => $inactiveTrainingPlan,
            'withDeadline'        => $withDeadline,
            'expiredDeadline'     => $expiredDeadline,
            'totalMaterials'      => $totalMaterials,
        ]);
    }

    public function order($slug)
    {
        $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();
        $trainingplan = TrainingPlan::with(['trainingplandetail', 'trainingplandetail.content'])->where('deleted_at', NULL)->firstWhere('slug', $slug);
        $trainingplandetail = TrainingPlanDetail::with('content')->where('trainingplan_id', $trainingplan->id)->orderBy('content_order', 'ASC')->get();

        return view('main.trainingplan.order', [
            "title" =>  "Edit Playlist",
            "slug" =>  $trainingplan->slug,
            "trainingplandetail" =>  $trainingplandetail,
            "content" =>  $content
        ]);
    }

    public function create()
    {
        $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();

        return view('main.trainingplan.create', [
            "title" =>  "Add Playlist",
            "content" => $content
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required',
            'thumbnail'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'has_deadline' => 'nullable|boolean',
            'from_date'    => 'nullable|date|required_if:has_deadline,1',
            'to_date'      => 'nullable|date|required_if:has_deadline,1|after_or_equal:from_date',
        ]);

        DB::beginTransaction();

        try {
            $hasDeadline = $request->boolean('has_deadline');

            $trainingplan = new TrainingPlan();
            $trainingplan->title       = $request->title;
            $trainingplan->active      = $request->action == "publish" ? "Active" : "Inactive";
            $trainingplan->url_link    = $request->url_link;
            $trainingplan->has_deadline = $hasDeadline;
            $trainingplan->from_date   = $hasDeadline ? $request->from_date : null;
            $trainingplan->to_date     = $hasDeadline ? $request->to_date : null;

            if ($request->hasFile('thumbnail')) {
                $destDir = public_path('storage/files/training_plan');
                if (!File::isDirectory($destDir)) {
                    File::makeDirectory($destDir, 0777, true, true);
                }
                $ext      = $request->file('thumbnail')->getClientOriginalExtension();
                $filename = Str::uuid() . '.' . $ext;
                $request->file('thumbnail')->move($destDir, $filename);
                $trainingplan->thumbnail = 'files/training_plan/' . $filename;
            }

            $trainingplan->save();

            $contentInput = $request->input('content');
            if ($contentInput) {
                foreach ($contentInput as $key => $value) {
                    $trainingplanDetail = new TrainingPlanDetail([
                        'content_order' => $key,
                        'trainingplan_id' => $trainingplan->id,
                        'content_id' => $value,
                    ]);
                    $trainingplanDetail->save();
                }
            }

            app(\App\Services\PlaylistDurationService::class)->refresh((int) $trainingplan->id);
            DB::commit();
            $addmessage = $request->action == "publish" ? " and published" : "";
            return to_route('playlist.edit', $trainingplan->slug)->with('success', 'Playlist successfully saved ' . $addmessage);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error create playlist: ' . $e->getMessage());

            session()->flash('warning', 'Error create playlist: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function edit(TrainingPlan $trainingplan)
    {
        $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();
        $trainingplan = TrainingPlan::with(['trainingplandetail', 'trainingplandetail.content'])->where('deleted_at', NULL)->firstWhere('id', $trainingplan->id);
        $activecontent = array();
        foreach ($trainingplan->trainingplandetail as $contentlist) {
            $activecontent[] = $contentlist->content_id;
        }

        return view('main.trainingplan.edit', [
            "title" =>  "Edit Playlist",
            "trainingplan" =>  $trainingplan,
            "content" =>  $content,
            "activecontent" =>  $activecontent
        ]);
    }

    public function update(Request $request, TrainingPlan $trainingplan)
    {
        DB::beginTransaction();

        try {
            if ($request->savetype == "reorder") {
                foreach ($request->order as $key => $value) {
                    TrainingPlanDetail::firstWhere('id', $key)->update([
                        'content_order' => $value
                    ]);
                }
            } else {
                $request->validate([
                    'title'        => 'required',
                    'thumbnail'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                    'has_deadline' => 'nullable|boolean',
                    'from_date'    => 'nullable|date|required_if:has_deadline,1',
                    'to_date'      => 'nullable|date|required_if:has_deadline,1|after_or_equal:from_date',
                ]);

                $hasDeadline = $request->boolean('has_deadline');

                // Handle thumbnail upload
                $thumbnailPath = $trainingplan->thumbnail;
                if ($request->hasFile('thumbnail')) {
                    $old = $trainingplan->thumbnail;
                    if ($old
                        && !Str::startsWith($old, ['http://', 'https://'])
                        && File::exists(public_path('storage/' . $old))) {
                        File::delete(public_path('storage/' . $old));
                    }
                    $destDir = public_path('storage/files/training_plan');
                    if (!File::isDirectory($destDir)) {
                        File::makeDirectory($destDir, 0777, true, true);
                    }
                    $ext      = $request->file('thumbnail')->getClientOriginalExtension();
                    $filename = Str::uuid() . '.' . $ext;
                    $request->file('thumbnail')->move($destDir, $filename);
                    $thumbnailPath = 'files/training_plan/' . $filename;
                }

                $trainingplan->title        = $request->input('title');
                $trainingplan->url_link     = $request->url_link;
                $trainingplan->thumbnail    = $thumbnailPath;
                $trainingplan->has_deadline = $hasDeadline;
                $trainingplan->from_date    = $hasDeadline ? $request->from_date : null;
                $trainingplan->to_date      = $hasDeadline ? $request->to_date : null;

                if ($request->action == "publish") {
                    $trainingplan->active = "Active";
                } else if ($request->action == "unpublish") {
                    $trainingplan->active = "Inactive";
                    $trainingplan->save();

                    DB::commit();

                    session()->flash('success', 'Playlist updated successfully');
                    return redirect()->route('playlist.edit', $trainingplan->slug);
                }

                $trainingplan->save();

                $contentInput = $request->input('content');

                // Remove Content
                if ($contentInput) {
                    $removecontent = TrainingPlanDetail::Where('trainingplan_id', $trainingplan->id)->get();
                    foreach ($removecontent as $key => $value) {
                        if (!in_array($value, $contentInput)) {
                            TrainingPlanDetail::firstWhere([['trainingplan_id', $trainingplan->id], ['content_id', $value->content_id]])->delete();
                        }
                    }
                } else {
                    TrainingPlanDetail::Where('trainingplan_id', $trainingplan->id)->delete();
                }

                // Add Content
                if ($contentInput) {
                    foreach ($contentInput as $key => $value) {
                        $checkcontent = TrainingPlanDetail::firstWhere([['trainingplan_id', $trainingplan->id], ['content_id', $value]]);
                        if (!$checkcontent) {
                            $trainingplanDetail = new TrainingPlanDetail([
                                'trainingplan_id' => $trainingplan->id,
                                'content_id' => $value,
                                'content_order' => $key
                            ]);
                            $trainingplanDetail->save();
                        }
                    }
                }
            }

            app(\App\Services\PlaylistDurationService::class)->refresh((int) $trainingplan->id);
            DB::commit();

            session()->flash('success', 'Playlist updated successfully');
            return redirect()->route('playlist.edit', $trainingplan->slug);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error edit playlist: ' . $e->getMessage());

            session()->flash('warning', 'Error edit playlist: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy(TrainingPlan $trainingplan)
    {
        $trainingplan->delete();

        return to_route('playlist.index')->with('success', 'Playlist successfully deleted');
    }

    public function thumbnailTest()
    {
        $trainingplans = TrainingPlan::orderBy('created_at', 'desc')->get();

        $rows = $trainingplans->map(function ($tp) {
            $path = $tp->thumbnail;

            if (!$path) {
                $status = 'Empty';
            } elseif (Str::startsWith($path, ['http://', 'https://'])) {
                $status = 'External URL Ignored';
            } elseif (File::exists(public_path('storage/' . $path))) {
                $status = 'Local OK';
            } else {
                $status = 'File Missing';
            }

            return [
                'id'            => $tp->id,
                'title'         => $tp->title,
                'db_path'       => $path,
                'generated_url' => $tp->thumbnail_url,
                'status'        => $status,
            ];
        });

        return view('main.trainingplan.thumbnail-test', [
            'title' => 'Thumbnail Test — Training Plan',
            'rows'  => $rows,
        ]);
    }
}
