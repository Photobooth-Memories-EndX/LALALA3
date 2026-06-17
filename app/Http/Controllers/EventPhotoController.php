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
public function index()
{
    $eventphoto = EventPhoto::latest()
        ->paginate(6);

    $totalEvent =
        EventPhoto::count();

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
    'category' => 'required'
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
    'category' => 'required'
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
                foreach ($request->documents as $doc) {
                    $arraydoc[] = $doc;
                    File::move(public_path('storage/gallery/tmp') . '/' . $doc, public_path('storage/gallery/photo/' . $request->id . '') . '/' . $doc);
                }
                $search = ['[', ']', '"'];

                $oldarray = array();
                // return $eventphoto;
                foreach (explode(',', $eventphoto->photos) as $listphoto) {
                    $oldarray[] = str_replace($search, '', $listphoto);
                }
                $newarray = array_merge($oldarray, $arraydoc);
                EventPhoto::where('id', $request->id)
                    ->update([
                        'photos' => json_encode($newarray)
                    ]);
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
