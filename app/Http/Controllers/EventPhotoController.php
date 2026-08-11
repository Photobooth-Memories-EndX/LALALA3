<?php

namespace App\Http\Controllers;

use App\Models\EventPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EventPhotoController extends Controller
{
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

    $query->where(
        'title',
        'like',
        '%' . $request->search . '%'
    );

}

// Status Filter
if ($request->filled('status')) {

    $query->where(
        'active',
        $request->status
    );

}

$show = $request->show ?? 25;

$eventphoto = $query
    ->latest()
    ->paginate($show)
    ->withQueryString();

$totalEvent = EventPhoto::count();

$activeEvent = EventPhoto::where(
    'active',
    'Active'
)->count();

$draftEvent = EventPhoto::where(
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
        'show',
        'totalEvent',
        'activeEvent',
        'draftEvent',
        'totalPhotos'
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
       $request->validate([
    'title' => 'required',
    'category' => 'nullable'
]);
        $eventphoto = new EventPhoto();

        $eventphoto->title = $request->input('title');
        $eventphoto->category = $request->input('category');
        $eventphoto->eventdate = $request->input('eventdate');

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
            EventPhoto::where('id', $newid)
                ->update([
                    'thumbnail' => $arraydoc[0],
                    'photos' => json_encode($arraydoc)
                ]);
        }
        if ($request->action == 'publish') {
            EventPhoto::where('id', $newid)
                ->update([
                    'active' => 'Active'
                ]);
        }
        return redirect()->route('eventphoto.index')->with('success', 'Event created successfully.');
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
    'category' => 'nullable'
]);
        //
        $eventphoto = EventPhoto::find($request->id);
        if ($request->action == 'unpublish') {
            EventPhoto::where('id', $request->id)
                ->update([
                    'active' => 'Inactive'
                ]);
        } else {
           EventPhoto::where('id', $request->id)
        ->update([
        'title' => $request->title,
        'category' => $request->category,
        'eventdate' => $request->eventdate,
        'thumbnail' => $request->thumbnail
    ]);
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
                    EventPhoto::where('id', $request->id)
                        ->update([
                            'photos' => json_encode($newarray)
                        ]);
                }
            }
            if ($request->action == 'publish') {
                EventPhoto::where('id', $request->id)
                    ->update([
                        'active' => 'Active'
                    ]);
            }
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
