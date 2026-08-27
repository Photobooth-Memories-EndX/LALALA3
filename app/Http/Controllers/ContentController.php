<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Models\ContentUser;
use App\Models\User;
use App\Support\DurationFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $query = Content::query()->with('classification')->withCount('allComments');
        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $sevenDaysFromToday = $today->copy()->addDays(7)->toDateString();
        $thirtyDaysFromToday = $today->copy()->addDays(30)->toDateString();

        if ($request->filled('s')) {
            $search = $request->s;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('classification_id')) {
            $query->where('classification_id', $request->classification_id);
        }

        if ($request->filled('active')) {
            $query->where('active', $request->active);
        }

        if ($request->filled('exam')) {
            $query->where('exam', $request->exam);
        }

        if ($request->filled('playlistonly')) {
            $query->where('playlistonly', $request->playlistonly);
        }

        if ($request->filled('deadline')) {
            if ($request->deadline === 'with') {
                $query->where('has_deadline', 1);
            } elseif ($request->deadline === 'without') {
                $query->where(function ($q) {
                    $q->whereNull('has_deadline')->orWhere('has_deadline', 0);
                });
            } elseif ($request->deadline === 'expired') {
                $query->where('has_deadline', 1)
                      ->whereDate('to_date', '<', $todayDate);
            } elseif ($request->deadline === 'active') {
                $query->where('has_deadline', 1)
                      ->whereDate('from_date', '<=', $todayDate)
                      ->whereDate('to_date', '>=', $todayDate);
            }
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('content.date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('content.date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('content.date', '<=', $request->date_to);
        }

        if ($request->filled('deadline_from_date') && $request->filled('deadline_to_date')) {
            $query->whereBetween('content.date', [
                $request->deadline_from_date,
                $request->deadline_to_date,
            ]);
        } elseif ($request->filled('deadline_from_date')) {
            $query->whereDate('content.date', '>=', $request->deadline_from_date);
        } elseif ($request->filled('deadline_to_date')) {
            $query->whereDate('content.date', '<=', $request->deadline_to_date);
        }

        if ($request->filled('created_from_date')) {
            $query->whereDate('content.created_at', '>=', $request->created_from_date);
        }

        if ($request->filled('created_to_date')) {
            $query->whereDate('content.created_at', '<=', $request->created_to_date);
        }

        if ($request->filled('updated_from_date')) {
            $query->whereDate('content.updated_at', '>=', $request->updated_from_date);
        }

        if ($request->filled('updated_to_date')) {
            $query->whereDate('content.updated_at', '<=', $request->updated_to_date);
        }

        switch ($request->get('deadline_status')) {
            case 'expired':
                $query->whereDate('content.date', '<', $todayDate);
                break;
            case 'today':
                $query->whereDate('content.date', $todayDate);
                break;
            case 'less_than_7_days':
                $query->whereDate('content.date', '>', $todayDate)
                    ->whereDate('content.date', '<=', $sevenDaysFromToday);
                break;
            case 'less_than_30_days':
                $query->whereDate('content.date', '>', $todayDate)
                    ->whereDate('content.date', '<=', $thirtyDaysFromToday);
                break;
            case 'more_than_30_days':
                $query->whereDate('content.date', '>', $thirtyDaysFromToday);
                break;
        }

        switch ($request->get('sort')) {
            case 'oldest_course':
            case 'oldest':
                $query->orderBy('content.created_at', 'asc');
                break;
            case 'recently_updated':
                $query->orderBy('content.updated_at', 'desc');
                break;
            case 'old_update':
                $query->orderBy('content.updated_at', 'asc');
                break;
            case 'newest_course':
                $query->orderBy('content.created_at', 'desc');
                break;
            case 'views':
                $query->orderBy('content.views', 'desc');
                break;
            case 'title':
                $query->orderBy('content.title');
                break;
            default:
                $query->orderByRaw("
                    (
                        (CASE WHEN content.title IS NOT NULL AND TRIM(content.title) <> '' THEN 3 ELSE 0 END) +
                        (CASE WHEN content.classification_id IS NOT NULL AND TRIM(content.classification_id) <> '' AND content.classification_id <> '0' THEN 3 ELSE 0 END) +
                        (CASE WHEN content.date IS NOT NULL THEN 3 ELSE 0 END) +
                        (CASE WHEN content.slug IS NOT NULL AND TRIM(content.slug) <> '' THEN 2 ELSE 0 END) +
                        (CASE WHEN content.learning_hours IS NOT NULL AND content.learning_hours > 0 THEN 2 ELSE 0 END) +
                        (CASE WHEN content.duration IS NOT NULL AND TRIM(content.duration) <> '' THEN 1 ELSE 0 END) +
                        (CASE WHEN content.type IS NOT NULL AND TRIM(content.type) <> '' THEN 1 ELSE 0 END) +
                        (CASE WHEN content.description IS NOT NULL AND TRIM(content.description) <> '' THEN 1 ELSE 0 END) +
                        (CASE WHEN (content.file IS NOT NULL AND TRIM(content.file) <> '' AND content.file <> '[]') OR (content.link IS NOT NULL AND TRIM(content.link) <> '') THEN 1 ELSE 0 END) +
                        (CASE WHEN content.active = 'Active' THEN 2 ELSE 0 END)
                    ) DESC, content.created_at DESC
                ");
                break;
        }

        $contents = $query->paginate(10)->withQueryString();

        $types           = Content::whereNotNull('type')->distinct()->orderBy('type')->pluck('type');
        $classifications = Classification::orderBy('classification_name')->get();

        $totalContent   = Content::count();
        $activeContent  = Content::where('active', 'Active')->count();
        $inactiveContent = Content::where('active', 'Inactive')->count();
        $playlistOnly   = Content::where('playlistonly', 'Y')->count();
        $withDeadline   = Content::where('has_deadline', 1)->count();
        $totalViews     = Content::sum('views');

        return view('main.content.index', [
            'title'           => 'Content Management',
            'contents'        => $contents,
            'types'           => $types,
            'classifications' => $classifications,
            'totalContent'    => $totalContent,
            'activeContent'   => $activeContent,
            'inactiveContent' => $inactiveContent,
            'playlistOnly'    => $playlistOnly,
            'withDeadline'    => $withDeadline,
            'totalViews'      => $totalViews,
        ]);
    }
    // public function ongoingcontent()
    // {
    //     // return "aaaa";
    //     $content = ContentUser::with('user')->get();
    //     // return $content;
    //     return view('main.content.ongoing', [
    //         "title" =>  "Content List",
    //         "content" => $content
    //     ]);
    // }

    public function create()
    {
        $classification = Classification::all();
        $user = User::all();
        return view('main.content.create', [
            "title" =>  "Add Content",
            "classification" => $classification,
            "user" => $user
        ]);
    }
    public function uploadfileajax(Request $request)
    {
        $name = [];
        $original_name = [];
        foreach ($request->file('file') as $key => $value) {
            $image = str_replace(' ', '_', pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . Carbon::now()->format('YmdHi') . '.' . $value->getClientOriginalExtension();
            $destinationPath = 'storage/files/tmp/';
            $value->move($destinationPath, $image);
            $name[] = $image;
            $original_name[] = $value->getClientOriginalName();
        }

        return response()->json([
            'name'          => $name,
            'original_name' => $original_name
        ]);
    }
    public function deletefileajax(Request $request)
    {


        if ($request->get('name')) {
            $src = 'storage/files/tmp/' . $request->get('name');
            if (File::exists(public_path($src)) && !is_dir($src)) {
                File::delete(public_path($src));
            }
        }
    }

    public function uploadthumbnailajax(Request $request)
    {
        $name = [];
        $original_name = [];
        foreach ($request->file('file') as $key => $value) {
            $image = str_replace(' ', '_', pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . Carbon::now()->format('YmdHi') . '.' . $value->getClientOriginalExtension();
            $destinationPath = 'storage/files/tmp/thumb/';
            $value->move($destinationPath, $image);
            $name[] = $image;
            $original_name[] = $value->getClientOriginalName();
        }

        return response()->json([
            'name'          => $name,
            'original_name' => $original_name
        ]);
    }
    public function deletethumbnailajax(Request $request)
    {
        if ($request->get('name')) {
            $src = 'storage/files/tmp/thumb/' . $request->get('name');
            if (File::exists(public_path($src)) && !is_dir($src)) {
                File::delete(public_path($src));
            }
        }
    }

    public function store(Request $request)
    {
        Log::info('ContentController@store called', ['user_id' => auth()->id(), 'payload' => $request->all()]);

        $request->validate([
            'require_page_duration' => ['nullable'],
            'page_duration' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type === 'Document') {
                        $requireDuration = $request->has('require_page_duration')
                            ? $request->boolean('require_page_duration')
                            : true;

                        if ($requireDuration) {
                            if ($value === null || $value === '' || (int) $value < 1) {
                                $fail('Page Duration must be at least 1 second when duration is enabled.');
                            } elseif ((int) $value > 300) {
                                $fail('The page duration must not be greater than 300.');
                            }
                        }
                    }
                },
            ],
            'document_total_pages' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'duration' => ['nullable', function ($attribute, $value, $fail) {
                if (!DurationFormatter::isValid($value)) {
                    $fail('The duration format must be HH:MM:SS or mm:ss.');
                }
            }],
            'learning_hours' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if (fmod((float) $value * 2, 1.0) !== 0.0) {
                        $fail('Learning Hours must be in increments of 0.5.');
                    }
                },
            ],
        ]);

        if ($request->type == "YouTube") {
            parse_str(parse_url($request->link, PHP_URL_QUERY), $ytarray);
            // $ytid = "https://www.youtube.com/embed/" . $ytarray['v'];
            $ytid = $ytarray['v'];
        } else {
            $ytid = null;
        }
        $hasDeadline = $request->boolean('has_deadline');

        if ($request->type === 'Document') {
            $requireDuration = $request->has('require_page_duration')
                ? $request->boolean('require_page_duration')
                : ((int) ($request->page_duration ?? 30) > 0);
            $pageDuration = $requireDuration ? max(1, (int) $request->page_duration) : 0;
            $documentTotalPages = (int) $request->document_total_pages;
            $duration = ($documentTotalPages > 0 && $pageDuration > 0)
                ? DurationFormatter::fromSeconds($documentTotalPages * $pageDuration)
                : ($requireDuration ? DurationFormatter::normalize($request->duration) : '00:00:00');
        } else {
            $pageDuration = 30;
            $documentTotalPages = 0;
            $duration = DurationFormatter::normalize($request->duration);
        }

        $learningHours = (float) $request->input('learning_hours');
        $newcontent = new Content([
            'title'            => $request->title,
            'type'             => $request->type,
            'classification_id' => $request->classification,
            'date'             => $request->date ?? now()->toDateString(),
            'author'           => $request->author ?? '',
            'duration'         => $duration,
            'learning_hours'   => $learningHours,
            'page_duration'    => $pageDuration,
            'total_page'       => $documentTotalPages,
            'description'      => $request->description ?? '',
            'playlistonly'     => ($request->playlistonly === 'Y' || $request->playlistonly === 1 || $request->playlistonly === '1') ? 'Y' : 'N',
            'link'             => $ytid,
            'has_deadline'     => $hasDeadline,
            'from_date'        => $hasDeadline ? $request->from_date : null,
            'to_date'          => $hasDeadline ? $request->to_date : null,
        ]);
        $newcontent->save();
        $newid = $newcontent->id;
        $path = public_path('storage/files/content/' . $newid . '');
        $arraydoc = array();
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        if ($request->type == "YouTube") {
            $url = "https://img.youtube.com/vi/$ytid/hqdefault.jpg";
            $image = file_get_contents($url);
            $thumbpath = public_path('storage/files/content/' . $newid . '/thumb.jpg');
            file_put_contents($thumbpath, $image);
        }
        if ($request->has('documents')) {
            foreach ($request->documents as $doc) {
                $arraydoc[] = $doc;
                File::move(public_path('storage/files/tmp') . '/' . $doc, public_path('storage/files/content/' . $newid . '') . '/' . $doc);
            }
            Content::where('id', $newid)
                ->update([
                    'file' => json_encode($arraydoc)
                ]);
        }
        if ($request->has('thumbnail')) {
            foreach ($request->thumbnail as $thumbnail) {
                $arraythumbnail[] = $thumbnail;
                File::move(public_path('storage/files/tmp/thumb') . '/' . $thumbnail, public_path('storage/files/content/' . $newid . '') . '/thumb.jpg');
            }
        }
        if ($request->action == "publish") {
            if ($request->documents) {
                Content::where('id', $newid)
                    ->update([
                        'active' => 'Active'
                    ]);
                $addmessage = " and published";
            } else if ($request->type == "YouTube") {
                Content::where('id', $newid)
                    ->update([
                        'active' => 'Active'
                    ]);
                $addmessage = " and published";
            } else {
                $slug = Content::where('id', $newid)->first()->slug;
                return to_route('content.edit', $slug)->with('success', 'Content successfully saved')->with('warning', 'Cannot publish because content doesn\'t have media.');
            }
        } else {

            $addmessage = "";
        }


        //
        return to_route('content.index')->with('success', 'Content successfully saved' . $addmessage . '');
    }
    public function destroy(Content $content)
    {
        $content->delete();
        return to_route('content.index')->with('success', 'Content successfully deleted');
    }
    public function edit(Content $content)
    {
        $classification = Classification::all();
        $user = User::all();
        return view('main.content.edit', [
            "title" =>  "Edit Content",
            "classification" => $classification,
            "content" => $content,
            "user" => $user
        ]);
    }
    public function update(Request $request, Content $content)
    {
        $targetContent = ($request->filled('contentid') ? Content::find($request->contentid) : null)
            ?: ($content->exists ? $content : Content::findOrFail($request->id));

        if ($targetContent->isPublished()) {
            if ($request->action === 'unpublish') {
                $targetContent->active = 'Inactive';
                $targetContent->save();
                return to_route('content.edit', $targetContent->slug)->with('success', 'Content successfully unpublished');
            }

            return to_route('content.edit', $targetContent->slug)->with('warning', 'Cannot modify content while it is published. Please unpublish this content first before making changes.');
        }

        $request->validate([
            'require_page_duration' => ['nullable'],
            'page_duration' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request, $content) {
                    if ($request->type === 'Document') {
                        $requireDuration = $request->has('require_page_duration')
                            ? $request->boolean('require_page_duration')
                            : ((int) ($request->page_duration ?? $content->page_duration ?? 30) > 0);

                        if ($requireDuration) {
                            if ($value === null || $value === '' || (int) $value < 1) {
                                $fail('Page Duration must be at least 1 second when duration is enabled.');
                            } elseif ((int) $value > 300) {
                                $fail('The page duration must not be greater than 300.');
                            }
                        }
                    }
                },
            ],
            'total_page' => ['nullable', 'required_if:type,Document', 'integer', 'min:1', 'max:100000'],
            'duration' => ['nullable', function ($attribute, $value, $fail) {
                if (!DurationFormatter::isValid($value)) {
                    $fail('The duration format must be HH:MM:SS or mm:ss.');
                }
            }],
            'learning_hours' => ['nullable'],
        ]);

        $path = public_path('storage/files/content/' . $request->id . '');
        $arraydoc = array();
        if ($request->type == "YouTube") {
            parse_str(parse_url($request->link, PHP_URL_QUERY), $ytarray);
            $ytid = $ytarray['v'];
            Content::where('id', $request->contentid)
                ->update(['file' => null]);
            if ($request->action != 'unpublish') {
                $url = "https://img.youtube.com/vi/$ytid/hqdefault.jpg";
                $image = file_get_contents($url);
                $thumbpath = public_path('storage/files/content/' . $request->contentid . '/thumb.jpg');
                file_put_contents($thumbpath, $image);
            }
        } else {
            $ytid = null;
            $arraythumbnail = [];
            $targetDir = public_path('storage/files/content/' . $request->contentid);

            if ($request->has('documents')) {
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }
                foreach ($request->documents as $doc) {
                    $tmpPath    = public_path('storage/files/tmp/' . $doc);
                    $targetPath = $targetDir . '/' . $doc;
                    if (File::exists($tmpPath)) {
                        $arraydoc[] = $doc;
                        File::move($tmpPath, $targetPath);
                    }
                }
                // replacecontent
                $src = 'storage/files/content/' . $request->contentid . '/' . $request->replacecontent;
                if (File::exists(public_path($src)) && !is_dir($src)) {
                    File::delete(public_path($src));
                }
                Content::where('id', $request->contentid)
                    ->update(['file' => json_encode($arraydoc)]);
            }

            if ($request->boolean('remove_thumbnail')) {
                $targetThumb = public_path('storage/files/content/' . $request->contentid . '/thumb.jpg');
                if (File::exists($targetThumb) && !is_dir($targetThumb)) {
                    File::delete($targetThumb);
                }
            }

            if ($request->has('thumbnail')) {
                if (!File::isDirectory($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }
                foreach ($request->thumbnail as $thumbnail) {
                    $thumbTmpPath    = public_path('storage/files/tmp/thumb/' . $thumbnail);
                    $thumbTargetPath = $targetDir . '/thumb.jpg';
                    if (File::exists($thumbTmpPath)) {
                        $arraythumbnail[] = $thumbnail;
                        File::move($thumbTmpPath, $thumbTargetPath);
                    }
                }
            }
        }

        $targetContent = ($request->filled('contentid') ? Content::find($request->contentid) : null)
            ?: ($content->exists ? $content : Content::findOrFail($request->id));
        $targetId = $targetContent->id;

        if ($request->action == "publish") {
            if ($targetContent->file) {
                $message = "Content successfully saved and published";
                $active = "Active";
                $redir = "-";
                $redirmessage = "-";
            } else if ($request->type == "YouTube") {
                $message = "Content successfully saved and published";
                $active = "Active";
                $redir = "-";
                $redirmessage = "-";
            } else {
                $active = "Inactive";
                $message = "Content successfully saved ";
                $redir = "warning";
                $redirmessage = "Cannot publish because content doesn't have media.";
            }
        } else if ($request->action == "save") {
            $active = "Inactive";
            $message = "Content successfully saved ";
            $redir = "-";
            $redirmessage = "-";
        } else if ($request->action == "update") {
            $active = $targetContent->active;
            $message = "Content successfully updated ";
            $redir = "-";
            $redirmessage = "-";
        } else if ($request->action == "unpublish") {
            $active = "Inactive";
            $message = "Content successfully unpublished ";
            $redir = "-";
            $redirmessage = "-";
        } else {
            $active = $targetContent->active ?? 'Active';
            $message = "Content successfully updated ";
            $redir = "-";
            $redirmessage = "-";
        }
        $hasDeadlineUpdate = $request->boolean('has_deadline');

        if ($request->type === 'Document') {
            $requireDuration = $request->has('require_page_duration')
                ? filter_var($request->require_page_duration, FILTER_VALIDATE_BOOLEAN)
                : ((int) ($request->page_duration ?? $targetContent->page_duration ?? 30) > 0);
            $pageDuration = $requireDuration ? max(1, (int) $request->page_duration) : 0;
            $totalPage = (int) $request->total_page;
            $duration = ($pageDuration > 0 && $totalPage > 0)
                ? DurationFormatter::fromSeconds($totalPage * $pageDuration)
                : ($requireDuration ? DurationFormatter::normalize($request->duration) : '00:00:00');
        } else {
            $pageDuration = (int) ($targetContent->page_duration ?: 30);
            $totalPage = (int) ($targetContent->total_page ?: 0);
            $duration = DurationFormatter::normalize($request->duration);
        }

        $learningHours = app(\App\Services\LearningHours\LearningHourConverterService::class)->convert($duration);
        Content::where('id', $targetId)
            ->update([
                'title'            => $request->title,
                'type'             => $request->type,
                'classification_id' => $request->classification,
                'date'             => $request->date ?? ($targetContent->date ?? now()->toDateString()),
                'author'           => $request->author ?? ($targetContent->author ?? ''),
                'duration'         => $duration,
                'learning_hours'   => $learningHours,
                'page_duration'    => $pageDuration,
                'total_page'       => $totalPage,
                'description'      => $request->description ?? ($targetContent->description ?? ''),
                'playlistonly'     => $request->has('playlistonly') ? (($request->playlistonly === 'Y' || $request->playlistonly === 1 || $request->playlistonly === '1') ? 'Y' : 'N') : ($targetContent->playlistonly ?? 'N'),
                'active'           => $active,
                'link'             => $ytid,
                'has_deadline'     => $hasDeadlineUpdate,
                'from_date'        => $hasDeadlineUpdate ? $request->from_date : null,
                'to_date'          => $hasDeadlineUpdate ? $request->to_date : null,
            ]);

        app(\App\Services\PlaylistDurationService::class)->refreshForContent((int) $targetId);

        return to_route('content.edit', $targetContent->slug)->with('success', $message)->with($redir, $redirmessage);
    }
    public function assign(Request $request)
    {
        $targetContent = Content::where('id', $request->content_id)->orWhere('slug', $request->slug)->first();
        if ($targetContent && $targetContent->isPublished()) {
            return to_route('content.edit', $request->slug)->with('warning', 'Cannot assign employee while content is published. Please unpublish this content first.');
        }

        $employee = $request->employee;
        if (!empty($employee) && is_array($employee)) {
            foreach ($employee as $id) {
                $contentuser = ContentUser::where('content_id', $request->content_id)->firstWhere('user_id', $id);
                if ($contentuser) {
                    // $arrayexist[] = $contentuser->progress;
                    $content_user = ContentUser::where('id', $contentuser->id)->Update([
                        'status' => 'mandatory'
                    ]);
                } else {
                    $content_user = ContentUser::Create([
                        'user_id' => $id, 'content_id' => $request->content_id, 'status' => 'mandatory'
                    ]);
                }
            }
        }
        return to_route('content.edit', $request->slug)->with('success', 'Content successfully assigned');
    }

    public function synchronizeLearningHours(Request $request, Content $content, \App\Services\LearningHours\ContentLearningHourService $service): \Illuminate\Http\JsonResponse
    {
        if ($content->isPublished()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot synchronize learning hours while content is published. Please unpublish first.'
            ], 422);
        }

        $result = $service->syncSingleContent($content, $request->all());

        return response()->json($result->toArray());
    }

    public function documentPdfList(): \Illuminate\Http\JsonResponse
    {
        $contents = Content::where('type', 'Document')
            ->select(['id', 'title', 'type', 'file', 'total_page', 'page_duration'])
            ->get();

        $data = $contents->map(function ($item) {
            $contentfile = is_string($item->file) ? json_decode($item->file, true) : [];
            $getcontentfile = is_array($contentfile) ? ($contentfile[0] ?? '') : ($item->file ?? '');
            $filename = is_string($getcontentfile) ? trim($getcontentfile) : '';

            $filePath = !empty($filename) ? public_path('storage/files/content/' . $item->id . '/' . $filename) : '';
            $fileUrl = !empty($filename) ? asset('storage/files/content/' . $item->id . '/' . $filename) : '';

            return [
                'id' => $item->id,
                'title' => $item->title,
                'file_name' => $filename,
                'file_url' => $fileUrl,
                'file_exists' => !empty($filePath) && file_exists($filePath),
                'total_page' => (int) ($item->total_page ?? 0),
                'page_duration' => (int) ($item->page_duration ?? 0),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ]);
    }

    public function syncTotalPage(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:content,id',
            'total_page' => 'required|integer|min:1|max:100000',
        ]);

        $content = Content::findOrFail($validated['id']);

        if ($content->isPublished()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update total page while content is published. Please unpublish first.',
            ], 422);
        }

        if ($content->type !== 'Document') {
            return response()->json([
                'success' => false,
                'message' => 'Content is not of type Document.',
            ], 422);
        }

        $newTotalPage = (int) $validated['total_page'];
        $oldTotalPage = (int) ($content->total_page ?? 0);

        if ($oldTotalPage === $newTotalPage) {
            return response()->json([
                'success' => true,
                'updated' => false,
                'id' => $content->id,
                'total_page' => $content->total_page,
                'message' => 'Already synchronized.',
            ]);
        }

        $content->total_page = $newTotalPage;
        $content->save();

        return response()->json([
            'success' => true,
            'updated' => true,
            'id' => $content->id,
            'old_total_page' => $oldTotalPage,
            'new_total_page' => $content->total_page,
            'duration' => $content->duration,
            'learning_hours' => $content->learning_hours,
            'message' => 'Total page updated successfully.',
        ]);
    }
}