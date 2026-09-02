<?php

namespace App\Http\Controllers;

use App\Models\EventPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EventPhotoController extends Controller
{
    /**
     * AJAX live autocomplete search for active published event photo galleries.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        return $this->autocompleteSearch($request);
    }

    public function autocompleteSearch(Request $request): JsonResponse
    {
        $rawQuery = trim((string) ($request->get('q') ?? $request->get('search') ?? $request->get('s') ?? ''));
        if (empty($rawQuery)) {
            return response()->json([]);
        }

        // Replace straight apostrophe with curly apostrophe and vice versa for flexible matching
        $queryNormalized = str_replace("'", "’", $rawQuery);
        $queryOriginal = str_replace("’", "'", $rawQuery);

        $galleries = EventPhoto::where(function ($q) use ($rawQuery, $queryNormalized, $queryOriginal) {
                $q->where('title', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('title', 'LIKE', "%{$queryNormalized}%")
                  ->orWhere('title', 'LIKE', "%{$queryOriginal}%")
                  ->orWhere('slug', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('category', 'LIKE', "%{$rawQuery}%")
                  ->orWhere('uploader', 'LIKE', "%{$rawQuery}%");
            })
            ->latest('id')
            ->limit(10)
            ->get();

        $results = $galleries->map(function ($gallery) {
            $photosArr = $gallery->photos ? (json_decode($gallery->photos, true) ?? []) : [];
            $photoCount = is_array($photosArr) ? count($photosArr) : 0;

            return [
                'id'          => $gallery->id,
                'title'       => $gallery->title,
                'name'        => $gallery->title,
                'slug'        => $gallery->slug,
                'active'      => $gallery->active ?? $gallery->status,
                'status'      => $gallery->status ?? $gallery->active,
                'photo_count' => $photoCount,
                'category'    => $gallery->category,
                'uploader'    => $gallery->uploader,
                'event_date'  => $gallery->eventdate ? \Carbon\Carbon::parse($gallery->eventdate)->format('d M Y') : null,
                'views'       => (int) ($gallery->views ?? 0),
            ];
        });

        return response()->json($results->values());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = EventPhoto::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('uploader', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%');
            });
        }

        // Status Filter (Approved, Pending, Reject)
        if ($request->filled('status')) {
            $status = strtolower($request->status);
            if ($status === 'reject' || $status === 'rejected') {
                $query->whereIn('status', ['reject', 'rejected']);
            } else {
                $query->where('status', $status);
            }
        }

        // Year Filter
        if ($request->filled('year') && $request->year !== 'all') {
            $query->where(function ($q) use ($request) {
                $q->whereYear('eventdate', (int) $request->year)
                  ->orWhere(function ($sub) use ($request) {
                      $sub->whereNull('eventdate')->whereYear('created_at', (int) $request->year);
                  });
            });
        }

        // Month Filter
        if ($request->filled('month') && $request->month !== 'all') {
            $query->where(function ($q) use ($request) {
                $q->whereMonth('eventdate', (int) $request->month)
                  ->orWhere(function ($sub) use ($request) {
                      $sub->whereNull('eventdate')->whereMonth('created_at', (int) $request->month);
                  });
            });
        }

        // Uploader / Organizer Filter
        if ($request->filled('uploader_id')) {
            $query->where('user_id', $request->uploader_id);
        } elseif ($request->filled('uploader')) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->uploader)
                  ->orWhere('uploader', 'like', '%' . $request->uploader . '%');
            });
        }

        // Type / Category Filter
        if ($request->filled('category_id')) {
            $query->where('category', $request->category_id);
        } elseif ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Event Date Range Filter
        $fromDate = $request->input('from_date', $request->input('date_from'));
        $toDate = $request->input('to_date', $request->input('date_to'));

        if ($fromDate && $toDate) {
            $query->where(function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('eventdate', [$fromDate, $toDate])
                  ->orWhere(function ($sub) use ($fromDate, $toDate) {
                      $sub->whereNull('eventdate')->whereDate('created_at', '>=', $fromDate)->whereDate('created_at', '<=', $toDate);
                  });
            });
        } elseif ($fromDate) {
            $query->where(function ($q) use ($fromDate) {
                $q->whereDate('eventdate', '>=', $fromDate)
                  ->orWhere(function ($sub) use ($fromDate) {
                      $sub->whereNull('eventdate')->whereDate('created_at', '>=', $fromDate);
                  });
            });
        } elseif ($toDate) {
            $query->where(function ($q) use ($toDate) {
                $q->whereDate('eventdate', '<=', $toDate)
                  ->orWhere(function ($sub) use ($toDate) {
                      $sub->whereNull('eventdate')->whereDate('created_at', '<=', $toDate);
                  });
            });
        }

        // Views & Sorting
        $views = $request->input('views');
        $sort = $request->input('sort');

        if ($views === 'most' || $views === 'most_viewed' || $sort === 'most_viewed') {
            $query->orderBy('views', 'desc');
        } elseif ($views === 'least' || $views === 'least_viewed' || $sort === 'least_viewed') {
            $query->orderBy('views', 'asc');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $show = $request->input('show', 15);
        $eventphoto = $query->paginate($show)->withQueryString();

        // Statistics
        $totalEvent = EventPhoto::count();
        $activeEvent = EventPhoto::where(function ($q) {
            $q->where('active', 'Active')->orWhere('status', 'approved');
        })->count();
        $draftEvent = EventPhoto::where(function ($q) {
            $q->where('active', 'Inactive')->orWhere('status', '!=', 'approved');
        })->count();

        $totalPhotos = 0;
        foreach (EventPhoto::select('photos')->get() as $event) {
            $photos = json_decode($event->photos, true) ?? [];
            $totalPhotos += count($photos);
        }

        // Uploaders & Categories for Dropdowns
        $uploaders = \App\Models\User::whereIn('id', EventPhoto::whereNotNull('user_id')->distinct()->pluck('user_id'))->orderBy('name')->get();
        if ($uploaders->isEmpty()) {
            $uploaders = \App\Models\User::orderBy('name')->take(25)->get();
        }

        $dbCategories = EventPhoto::whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->toArray();

        $defaultCategories = ['Corporate Event', 'Workshop', 'Conference', 'Team Building', 'Gathering', 'Celebration'];
        $mergedCategories = array_unique(array_merge($defaultCategories, $dbCategories));

        $categories = collect($mergedCategories)->map(function ($cat) {
            return (object) [
                'id'   => $cat,
                'name' => $cat,
            ];
        });

        return view(
            'main.gallery.index',
            compact(
                'eventphoto',
                'show',
                'totalEvent',
                'activeEvent',
                'draftEvent',
                'totalPhotos',
                'uploaders',
                'categories'
            )
        );
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('main.gallery.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft' || $request->input('action') === 'save';

        $request->validate([
            'title' => $isDraft ? 'nullable|string|max:255' : 'required',
            'category' => 'nullable|string|max:255',
        ]);
        $eventphoto = new EventPhoto();

        $eventphoto->title = $request->input('title') ?: 'Draft Gallery Event ' . date('Y-m-d H:i');
        $eventphoto->category = $request->input('category');
        $eventphoto->status = 'approved';
        $eventphoto->active = ($request->input('action') === 'publish') ? 'Active' : 'Inactive';
        $eventphoto->eventdate = $request->input('eventdate') ?: date('Y-m-d');

        $eventphoto->likes = '0';
        $eventphoto->views = '0';

        $eventphoto->user_id = auth()->user()->id;
        $eventphoto->uploader = auth()->user()->name;

        $eventphoto->save();
        $newid = $eventphoto->id;
        $path = public_path('storage/gallery/photo/' . $newid . '');
        $arraydoc = array();
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }

        if ($request->has('documents')) {
            foreach ($request->documents as $doc) {
                $arraydoc[] = $doc;
                File::move(public_path('storage/gallery/tmp') . '/' . $doc, public_path('storage/gallery/photo/' . $newid . '') . '/' . $doc);
            }
            if (!empty($arraydoc)) {
                EventPhoto::where('id', $newid)
                    ->update([
                        'thumbnail' => $arraydoc[0],
                        'photos' => json_encode($arraydoc)
                    ]);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Event created successfully.',
                'redirect' => route('manage.eventphoto.index')
            ]);
        }

        return redirect()->route('manage.eventphoto.index')->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EventPhoto  $eventPhoto
     * @return \Illuminate\Http\Response
     */
    public function show(EventPhoto $eventPhoto)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EventPhoto  $eventPhoto
     * @return \Illuminate\Http\Response
     */
    public function edit(EventPhoto $eventphoto)
    {
        // return $eventphoto;
        return view('main.gallery.edit', compact('eventphoto'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EventPhoto  $eventPhoto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EventPhoto $eventPhoto)
    {
        $request->validate([
            'title' => 'required',
            'category' => 'nullable|string|max:255',
            'status' => 'required|in:approved,reject,pending',
        ]);
        //
        $eventphoto = EventPhoto::find($request->id);
        $updateData = [
            'title'     => $request->title,
            'category'  => $request->category,
            'status'    => $request->status,
            'eventdate' => $request->eventdate,
            'thumbnail' => $request->thumbnail
        ];

        if ($request->action == 'unpublish' || $request->action == 'draft') {
            $updateData['active'] = 'Inactive';
        } elseif ($request->action == 'publish') {
            $updateData['active'] = 'Active';
        }

        EventPhoto::where('id', $request->id)->update($updateData);

        if ($request->has('documents')) {
            $arraydoc = [];

            $targetDir = public_path('storage/gallery/photo/' . $request->id);

            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            foreach ($request->documents as $doc) {
                $tmpPath = public_path('storage/gallery/tmp/' . $doc);
                $targetPath = $targetDir . '/' . $doc;

                if (File::exists($tmpPath)) {
                    $arraydoc[] = $doc;
                    File::move($tmpPath, $targetPath);
                }
            }

            if (!empty($arraydoc)) {
                $search = ['[', ']', '"'];

                $oldarray = [];

                if (!empty($eventphoto->photos)) {
                    foreach (explode(',', $eventphoto->photos) as $listphoto) {
                        $cleanPhoto = str_replace($search, '', $listphoto);

                        if (!empty($cleanPhoto)) {
                            $oldarray[] = $cleanPhoto;
                        }
                    }
                }

                $newarray = array_merge($oldarray, $arraydoc);
                $updatePhotosData = [
                    'photos' => json_encode($newarray)
                ];
                if (empty($eventphoto->thumbnail) && !empty($newarray[0])) {
                    $updatePhotosData['thumbnail'] = $newarray[0];
                }
                EventPhoto::where('id', $request->id)->update($updatePhotosData);
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Event gallery saved successfully.',
                'redirect' => route('manage.eventphoto.index')
            ]);
        }

        return redirect()->route('eventphoto.edit', $eventphoto->slug)->with('success', 'Event saved successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EventPhoto  $eventPhoto
     * @return \Illuminate\Http\Response
     */
    public function destroy(EventPhoto $eventPhoto)
    {
        //
    }

    public function deleteImage(Request $request)
    {
        $id = $request->id;
        $option = $request->option;

        $eventphoto = EventPhoto::find($id);

        $photos = json_decode($eventphoto->photos, true) ?? [];
        $photos = array_values(array_diff($photos, [$option]));

        $path = public_path('storage/gallery/photo/' . $id . '/' . $option);
        if (File::exists($path)) {
            File::delete($path);
        }

        $eventphoto->update([
            'photos' => json_encode($photos)
        ]);

        return response()->json([
            'status' => 200
        ]);
    }

    public function uploadfileajax(Request $request)
    {
        $name = [];
        $original_name = [];
        foreach ($request->file('file') as $key => $value) {
            $image = str_replace(' ', '_', pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . date('YmdHi') . '.' . $value->getClientOriginalExtension();
            $destinationPath = 'storage/gallery/tmp/';
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
        $filename = $request->name;

        $path =
            public_path(
                'storage/gallery/tmp/' .
                $filename
            );

        if (File::exists($path)) {

            File::delete($path);

        }

        return response()->json([
            'success' => true
        ]);
    }
public function search(Request $request)
{
    $keyword = $request->s;

    $eventphoto = EventPhoto::query()

        ->where(
            'title',
            'LIKE',
            "%{$keyword}%"
        )

        ->latest()

        ->paginate(6);

    $totalEvent = EventPhoto::count();

    $activeEvent =
        EventPhoto::where(
            'active',
            'Active'
        )->count();

    $draftEvent =
        EventPhoto::where(
            'active',
            'Inactive'
        )->count();

    $totalPhotos = 0;

    foreach (EventPhoto::all() as $event) {

        $photos =
            json_decode(
                $event->photos,
                true
            ) ?? [];

        $totalPhotos += count($photos);
    }

    return view(
        'main.gallery.index',
        compact(
            'eventphoto',
            'totalEvent',
            'activeEvent',
            'draftEvent',
            'totalPhotos'
        )
    );
}

}
