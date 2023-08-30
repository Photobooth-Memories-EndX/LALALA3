<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use App\Models\Classification;
use Illuminate\Support\Facades\File;

class ContentController extends Controller
{
    public function index()
    {
        $content = Content::with('classification')->get();
        return view('main.content.index', [
            "title" =>  "Content List",
            "content" => $content
        ]);
    }
    public function create()
    {
        //
        // $content = Content::all();

        $classification = Classification::all();
        return view('main.content.create', [
            "title" =>  "Add Content",
            "classification" => $classification
        ]);
    }
    public function uploadfileajax(Request $request)
    {
        //
        // $content = Content::all();

        $name = [];
        $original_name = [];
        foreach ($request->file('file') as $key => $value) {
            $image = str_replace(' ', '_', pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . date('YmdHi') . '.' . $value->getClientOriginalExtension();
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
        //
        // $content = Content::all();

        if ($request->get('name')) {
            $src = 'storage/files/tmp/' . $request->get('name');
            if (File::exists(public_path($src)) && !is_dir($src)) {
                File::delete(public_path($src));
            }
        }
    }
    // public function deletecontentajax($slug)
    // {
    //     // $news = News::select('id', 'image')->where('slug', $slug)->first();
    //     $content = Content::findBySlug($slug);

    //     $src = 'storage/files/content/' . $content->id . '/' . $content->slug;
    //     if (File::exists(public_path($src)) && !is_dir($src)) {
    //         File::delete(public_path($src));
    //     }

    //     $content->file = '';
    //     $content->save();

    //     return response()->json(['success' => 'Content deleted successfully', 'status' => 200]);
    // }
    public function store(Request $request)
    {
        //
        // $request->validate([
        //     'title' => 'required',
        //     'type' => 'required',
        //     'classification' => 'required',
        //     'date' => 'required',
        //     'author' => 'required',
        //     'duration' => 'required',
        //     'description' => 'required'
        // ]);
        if ($request->type == "YouTube") {
            parse_str(parse_url($request->link, PHP_URL_QUERY), $ytarray);
            $ytid = "https://www.youtube.com/embed/" . $ytarray['v'];
        } else {
            $ytid = null;
        }

        // return $ytid;
        $newcontent = new Content([
            'title' => $request->title,
            'type' => $request->type,
            'classification_id' => $request->classification,
            'date' => $request->date,
            'author' => $request->author,
            'duration' => $request->duration,
            'description' => $request->description,
            'link' => $ytid
        ]);
        $newcontent->save();
        $newid = $newcontent->id;
        $path = public_path('storage/files/content/' . $newid . '');
        $arraydoc = array();
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
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
        // if ($request->type == "YouTube") {
        //     parse_str(parse_url($request->link, PHP_URL_QUERY), $ytarray);
        //     $ytid = "https://www.youtube.com/embed/" . $ytarray['v'];
        // } else {
        //     $ytid = null;
        // }
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
        // $content = Content::find($id);
        $content->delete();
        return to_route('content.index')->with('success', 'Content successfully deleted');
    }
    public function edit(Content $content)
    {
        // $content = Content::with('classification')->find($id);
        $classification = Classification::all();
        return view('main.content.edit', [
            "title" =>  "Edit Content",
            "classification" => $classification,
            "content" => $content
        ]);
    }
    public function update(Request $request, Content $content)
    {
        // return $request;
        $path = public_path('storage/files/content/' . $request->id . '');
        $arraydoc = array();
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        if ($request->has('documents')) {
            foreach ($request->documents as $doc) {
                $arraydoc[] = $doc;
                File::move(public_path('storage/files/tmp') . '/' . $doc, public_path('storage/files/content/' . $request->contentid . '') . '/' . $doc);
            }
            // replacecontent
            $src = 'storage/files/content/' . $request->contentid . '/' . $request->replacecontent;
            if (File::exists(public_path($src)) && !is_dir($src)) {
                File::delete(public_path($src));
            }
            Content::where('id', $request->contentid)
                ->update(['file' => json_encode($arraydoc)]);
        }
        // else {
        //     return ($request);
        // }
        $getcontent = Content::firstWhere('id', $request->contentid);
        if ($request->action == "publish") {
            if ($getcontent->file) {
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
        } else if ($request->action == "unpublish") {
            $active = "Inactive";
            $message = "Content successfully unpublished ";
            $redir = "-";
            $redirmessage = "-";
        }
        Content::where('id', $request->contentid)
            ->update([
                'title' => $request->title,
                'type' => $request->type,
                'classification_id' => $request->classification,
                'date' => $request->date,
                'author' => $request->author,
                'duration' => $request->duration,
                'description' => $request->description,
                'active' => $active
            ]);

        // $slug = Content::where('id', $request->contentid)->first()->slug;
        // return to_route('content.edit', $getcontent->slug)->with('success', 'Content successfully saved' . $addmessage . '');
        return to_route('content.edit', $getcontent->slug)->with('success', $message)->with($redir, $redirmessage);
    }
}
