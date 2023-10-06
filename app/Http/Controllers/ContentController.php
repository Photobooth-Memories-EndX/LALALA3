<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Models\ContentUser;
use App\Models\User;
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
    public function ongoingcontent()
    {
        // return "aaaa";
        $content = ContentUser::with('user')->get();
        // return $content;
        return view('main.content.ongoing', [
            "title" =>  "Content List",
            "content" => $content
        ]);
    }

    public function create()
    {
        $classification = Classification::all();
        return view('main.content.create', [
            "title" =>  "Add Content",
            "classification" => $classification
        ]);
    }
    public function uploadfileajax(Request $request)
    {
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
            $image = str_replace(' ', '_', pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . date('YmdHi') . '.' . $value->getClientOriginalExtension();
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
            // $ytid = "https://www.youtube.com/embed/" . $ytarray['v'];
            $ytid = $ytarray['v'];
        } else {
            $ytid = null;
        }
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
        $path = public_path('storage/files/content/' . $request->id . '');
        $arraydoc = array();
        if ($request->type == "YouTube") {
            parse_str(parse_url($request->link, PHP_URL_QUERY), $ytarray);
            $ytid = $ytarray['v'];
            Content::where('id', $request->contentid)
                ->update(['file' => null]);
            $url = "https://img.youtube.com/vi/$ytid/hqdefault.jpg";
            $image = file_get_contents($url);
            $thumbpath = public_path('storage/files/content/' . $request->contentid . '/thumb.jpg');
            file_put_contents($thumbpath, $image);
        } else {
            $ytid = null;
            if ($request->has('documents')) {
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                }
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
            if ($request->has('thumbnail')) {
                foreach ($request->thumbnail as $thumbnail) {
                    $arraythumbnail[] = $thumbnail;
                    File::move(public_path('storage/files/tmp/thumb') . '/' . $thumbnail, public_path('storage/files/content/' .  $request->contentid . '') . '/thumb.jpg');
                }
            }
        }

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
                'active' => $active,
                'link' => $ytid
            ]);

        return to_route('content.edit', $getcontent->slug)->with('success', $message)->with($redir, $redirmessage);
    }
    public function assign(Request $request)
    {
        $employee = $request->employee;
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
        return to_route('content.edit', $request->slug)->with('success', 'Content successfully assigned');



        // return $arrayexist;
    }
}
