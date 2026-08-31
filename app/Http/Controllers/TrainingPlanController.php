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

        // Search Filter
        if ($request->filled('search') || $request->filled('s')) {
            $search = $request->filled('search') ? $request->search : $request->s;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('url_link', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status') || $request->filled('active')) {
            $status = $request->filled('status') ? $request->status : $request->active;
            $query->where('active', $status);
        }

        // Year & Month Filter
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        // Author Filter
        if ($request->filled('author_id') || $request->filled('author')) {
            $authorVal = $request->filled('author_id') ? $request->author_id : $request->author;
            $query->where(function ($q) use ($authorVal) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('trainingplan', 'user_id') && is_numeric($authorVal)) {
                    $q->where('user_id', $authorVal);
                }
                $q->orWhereHas('trainingplandetail.content', function ($cq) use ($authorVal) {
                    $cq->where('author', $authorVal)->orWhere('author', 'like', "%{$authorVal}%");
                });
            });
        }

        // Created Date Filter
        if ($request->filled('created_from') && $request->filled('created_to')) {
            $query->whereBetween('created_at', [
                $request->created_from . ' 00:00:00',
                $request->created_to . ' 23:59:59'
            ]);
        } elseif ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        } elseif ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Period Range (Deadline from/to)
        if ($request->filled('period_from') && $request->filled('period_to')) {
            $query->where(function ($q) use ($request) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('trainingplan', 'start_date')) {
                    $q->whereBetween('start_date', [$request->period_from, $request->period_to]);
                } else {
                    $q->whereDate('from_date', '>=', $request->period_from)
                      ->whereDate('to_date', '<=', $request->period_to);
                }
            });
        } elseif ($request->filled('period_from')) {
            $query->where(function ($q) use ($request) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('trainingplan', 'start_date')) {
                    $q->whereDate('start_date', '>=', $request->period_from);
                } else {
                    $q->whereDate('from_date', '>=', $request->period_from);
                }
            });
        } elseif ($request->filled('period_to')) {
            $query->where(function ($q) use ($request) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('trainingplan', 'start_date')) {
                    $q->whereDate('start_date', '<=', $request->period_to);
                } else {
                    $q->whereDate('to_date', '<=', $request->period_to);
                }
            });
        }

        // Duration Filter
        if ($request->filled('duration') && $request->duration !== 'all') {
            if ($request->duration === 'under_10' || $request->duration === 'lt10') {
                $query->where('total_duration_seconds', '<', 600);
            } elseif ($request->duration === '10_30' || $request->duration === '10-30') {
                $query->whereBetween('total_duration_seconds', [600, 1800]);
            } elseif ($request->duration === '30_60' || $request->duration === '30-60') {
                $query->whereBetween('total_duration_seconds', [1800, 3600]);
            } elseif ($request->duration === 'above_60' || $request->duration === 'gt60') {
                $query->where('total_duration_seconds', '>', 3600);
            }
        }

        // Deadline Filter
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

        // Thumbnail Filter
        if ($request->filled('thumbnail')) {
            if ($request->thumbnail === 'with') {
                $query->whereNotNull('thumbnail')->where('thumbnail', '!=', '');
            } elseif ($request->thumbnail === 'without') {
                $query->where(function ($q) {
                    $q->whereNull('thumbnail')->orWhere('thumbnail', '');
                });
            }
        }

        // Sort Views Filter
        if ($request->sort_views === 'most') {
            if (\Illuminate\Support\Facades\Schema::hasColumn('trainingplan', 'views')) {
                $query->orderBy('views', 'desc');
            }
        } elseif ($request->sort_views === 'least') {
            if (\Illuminate\Support\Facades\Schema::hasColumn('trainingplan', 'views')) {
                $query->orderBy('views', 'asc');
            }
        }

        // Sort By Filter
        switch ($request->get('sort')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'title':
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'materials':
                $query->orderByDesc('trainingplandetail_count');
                break;
            case 'newest':
            default:
                if (!$request->filled('sort_views')) {
                    $query->latest();
                }
                break;
        }

        $trainingplans = $query->paginate(10)->withQueryString();

        $authors = Content::whereNotNull('author')
            ->where('author', '!=', '')
            ->distinct()
            ->orderBy('author')
            ->pluck('author')
            ->map(fn($a) => (object)['id' => $a, 'name' => $a]);

        if ($authors->isEmpty()) {
            $authors = \App\Models\User::orderBy('name')->get(['id', 'name']);
        }

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
            'authors'             => $authors,
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
        $content = Content::whereNull('deleted_at')->orderBy('title')->get();
        $trainingplan = TrainingPlan::with([
            'trainingplandetail' => fn ($q) => $q->orderByRaw('CAST(content_order AS UNSIGNED) ASC'),
            'trainingplandetail.content'
        ])->whereNull('deleted_at')->firstWhere('slug', $slug);
        $trainingplandetail = TrainingPlanDetail::with('content')
            ->where('trainingplan_id', $trainingplan->id)
            ->orderByRaw('CAST(content_order AS UNSIGNED) ASC')
            ->get();

        return view('main.trainingplan.order', [
            "title" =>  "Edit Playlist",
            "slug" =>  $trainingplan->slug,
            "trainingplandetail" =>  $trainingplandetail,
            "content" =>  $content
        ]);
    }

    public function create()
    {
        $content = Content::whereNull('deleted_at')->orderBy('title')->get();

        return view('main.trainingplan.create', [
            "title" =>  "Add Playlist",
            "content" => $content
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required',
            'total_duration' => 'nullable',
            'learning_hours' => 'nullable',
            'thumbnail'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'has_deadline'   => 'nullable|boolean',
            'from_date'      => 'nullable|date|required_if:has_deadline,1',
            'to_date'        => 'nullable|date|required_if:has_deadline,1|after_or_equal:from_date',
        ]);

        DB::beginTransaction();

        try {
            $hasDeadline = $request->boolean('has_deadline');

            $parser = app(\App\Services\LearningHours\DurationParserService::class);
            $durationInput = $request->input('total_duration', $request->input('total_duration_seconds'));
            $totalSeconds = ($durationInput !== null && trim((string) $durationInput) !== '')
                ? $parser->toSeconds((string) $durationInput)
                : 0;
            $learningHours = (float) $request->input('learning_hours', 0);

            $trainingplan = new TrainingPlan();
            $trainingplan->title                  = $request->title;
            $trainingplan->total_duration_seconds = $totalSeconds;
            $trainingplan->learning_hours         = $learningHours;
            $trainingplan->active                 = $request->action == "publish" ? "Active" : "Inactive";
            $trainingplan->url_link               = $request->url_link;
            $trainingplan->has_deadline           = $hasDeadline;
            $trainingplan->from_date              = $hasDeadline ? $request->from_date : null;
            $trainingplan->to_date                = $hasDeadline ? $request->to_date : null;

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

            $contentInput = array_values(array_filter((array) $request->input('content', [])));
            if ($contentInput) {
                foreach ($contentInput as $key => $value) {
                    $trainingplanDetail = new TrainingPlanDetail([
                        'content_order'   => $key,
                        'trainingplan_id' => $trainingplan->id,
                        'content_id'      => $value,
                    ]);
                    $trainingplanDetail->save();
                }
            }

            // Ensure manual duration & learning hours set by admin on Create are preserved
            $trainingplan->forceFill([
                'total_duration_seconds' => $totalSeconds,
                'learning_hours'         => $learningHours,
            ])->saveQuietly();

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

    public function edit(Request $request, mixed $trainingplan = null)
    {
        if (!$trainingplan instanceof TrainingPlan) {
            $param = $trainingplan ?? $request->route('trainingplan');
            if ($param instanceof TrainingPlan) {
                $trainingplan = $param;
            } else {
                $trainingplan = TrainingPlan::where('slug', $param)->orWhere('id', $param)->firstOrFail();
            }
        }

        $content = Content::whereNull('deleted_at')->orderBy('title')->get();
        $trainingplan = TrainingPlan::with([
            'trainingplandetail' => fn ($q) => $q->orderByRaw('CAST(content_order AS UNSIGNED) ASC'),
            'trainingplandetail.content'
        ])->whereNull('deleted_at')->firstWhere('id', $trainingplan->id);

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

    public function update(Request $request, mixed $trainingplan = null)
    {
        if (!$trainingplan instanceof TrainingPlan) {
            $param = $trainingplan ?? $request->route('trainingplan');
            if ($param instanceof TrainingPlan) {
                $trainingplan = $param;
            } else {
                $trainingplan = TrainingPlan::where('slug', $param)->orWhere('id', $param)->firstOrFail();
            }
        }

        DB::beginTransaction();

        try {
            if ($request->savetype == "reorder") {
                foreach ($request->order as $key => $value) {
                    TrainingPlanDetail::where('id', $key)->update([
                        'content_order' => $value
                    ]);
                }
            } else {
                $request->validate([
                    'title'          => 'required',
                    'learning_hours' => 'nullable',
                    'thumbnail'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                    'has_deadline'   => 'nullable|boolean',
                    'from_date'      => 'nullable|date|required_if:has_deadline,1',
                    'to_date'        => 'nullable|date|required_if:has_deadline,1|after_or_equal:from_date',
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

                $trainingplan->title          = $request->input('title');
                $trainingplan->url_link       = $request->url_link;
                $trainingplan->thumbnail      = $thumbnailPath;
                $trainingplan->has_deadline   = $hasDeadline;
                $trainingplan->from_date      = $hasDeadline ? $request->from_date : null;
                $trainingplan->to_date        = $hasDeadline ? $request->to_date : null;

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

                $contentInput = array_values(array_filter((array) $request->input('content', [])));

                // Sync Content & Content Orders
                TrainingPlanDetail::where('trainingplan_id', $trainingplan->id)
                    ->whereNotIn('content_id', $contentInput)
                    ->delete();

                foreach ($contentInput as $orderIndex => $contentId) {
                    TrainingPlanDetail::updateOrCreate(
                        [
                            'trainingplan_id' => $trainingplan->id,
                            'content_id'      => $contentId,
                        ],
                        [
                            'content_order'   => $orderIndex,
                        ]
                    );
                }
            }

            app(\App\Services\PlaylistDurationService::class)->refresh((int) $trainingplan->id);
            app(\App\Services\LearningHours\PlaylistLearningHourService::class)->syncPlaylist($trainingplan);
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

    public function synchronizeLearningHours(Request $request, mixed $trainingplan = null, \App\Services\LearningHours\PlaylistLearningHourService $service = null): \Illuminate\Http\JsonResponse
    {
        $service ??= app(\App\Services\LearningHours\PlaylistLearningHourService::class);
        $parser = app(\App\Services\LearningHours\DurationParserService::class);

        if (!$trainingplan instanceof TrainingPlan) {
            $param = $trainingplan ?? $request->route('trainingplan');
            if ($param) {
                if ($param instanceof TrainingPlan) {
                    $trainingplan = $param;
                } else {
                    $trainingplan = TrainingPlan::where('slug', $param)->orWhere('id', $param)->first();
                }
            }
        }

        if ($request->has('content')) {
            $contentIds = (array) $request->input('content', []);
            $hours = $service->calculateLearningHoursFromContentIds($contentIds);

            $totalSeconds = 0;
            $ids = array_filter(array_map('intval', $contentIds));
            if (!empty($ids)) {
                $contents = Content::whereIn('id', array_unique($ids))->get(['id', 'duration'])->keyBy('id');
                foreach ($ids as $id) {
                    $c = $contents->get($id);
                    if ($c && $c->duration) {
                        $totalSeconds += $parser->toSeconds($c->duration);
                    }
                }
            }

            if ($trainingplan && $trainingplan->exists) {
                $trainingplan->forceFill([
                    'total_duration_seconds' => $totalSeconds,
                    'learning_hours'         => (float) $hours,
                ])->saveQuietly();
            }

            return response()->json([
                'success'                => true,
                'total_duration_seconds' => $totalSeconds,
                'duration'               => $parser->fromSeconds($totalSeconds),
                'learning_hours'         => (float) $hours,
                'message'                => 'Playlist duration synchronized successfully.',
            ]);
        }

        if ($trainingplan && $trainingplan->exists) {
            $result = $service->syncSinglePlaylist($trainingplan);
            $data = $result->toArray();
            $totalSeconds = (int) ($data['total_duration_seconds'] ?? 0);

            return response()->json([
                'success'                => true,
                'total_duration_seconds' => $totalSeconds,
                'duration'               => $data['duration'] ?? $parser->fromSeconds($totalSeconds),
                'learning_hours'         => (float) ($data['learning_hours'] ?? 0),
                'message'                => $data['message'] ?? 'Playlist duration synchronized successfully.',
            ]);
        }

        return response()->json([
            'success'                => true,
            'total_duration_seconds' => 0,
            'duration'               => '00:00:00',
            'learning_hours'         => 0.0,
            'message'                => 'No playlist or contents to synchronize.',
        ]);
    }
}