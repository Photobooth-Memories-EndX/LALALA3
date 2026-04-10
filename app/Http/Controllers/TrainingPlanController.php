<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingPlanController extends Controller
{
    public function index()
    {
        $trainingplan = TrainingPlan::with([
            'trainingplandetail' => function ($query) {
                $query->orderBy('content_order');
            }, 'trainingplandetail.content'
        ])->orderBy('created_at', 'desc')->get();

        return view('main.trainingplan.index', [
            "title" =>  "Playlist",
            "trainingplan" => $trainingplan
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
        DB::beginTransaction();

        try {
            $trainingplan = new TrainingPlan();
            $trainingplan->title = $request->title;
            $trainingplan->active = $request->action == "publish" ? "Active" : "Inactive";
            $trainingplan->url_link = $request->url_link;
            $trainingplan->save();

            if ($request->content != '') {
                foreach ($request->content as $key => $value) {
                    $trainingplanDetail = new TrainingPlanDetail([
                        'content_order' => $key,
                        'trainingplan_id' => $trainingplan->id,
                        'content_id' => $value,
                    ]);
                    $trainingplanDetail->save();
                }
            }

            DB::commit();
            $addmessage = $request->action == "publish" ? " and published" : "";
            return to_route('trainingplan.edit', $trainingplan->slug)->with('success', 'Playlist successfully saved ' . $addmessage);
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
                    'title' => 'required'
                ]);

                $trainingplan->title = $request->input('title');
                $trainingplan->url_link = $request->url_link;
                if ($request->action == "publish") {
                    $trainingplan->active = "Active";
                } else if ($request->action == "unpublish") {
                    $trainingplan->active = "Inactive";
                    TrainingPlan::where('id', $trainingplan->id)
                        ->update([
                            'active' => 'Inactive',
                            'url_link' => $request->url_link,
                        ]);

                    DB::commit();

                    session()->flash('success', 'Playlist updated successfully');
                    return redirect()->route('trainingplan.edit', $trainingplan->slug);
                }

                $trainingplan->save();

                // Remove Content
                if ($request->content) {
                    $removecontent = TrainingPlanDetail::Where('trainingplan_id', $trainingplan->id)->get();
                    foreach ($removecontent as $key => $value) {
                        if (!in_array($value, $request->content)) {
                            TrainingPlanDetail::firstWhere([['trainingplan_id', $trainingplan->id], ['content_id', $value->content_id]])->delete();
                        }
                    }
                } else {
                    TrainingPlanDetail::Where('trainingplan_id', $trainingplan->id)->delete();
                }

                // Add Content
                if ($request->content) {
                    foreach ($request->content as $key => $value) {
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

            DB::commit();

            session()->flash('success', 'Playlist updated successfully');
            return redirect()->route('trainingplan.edit', $trainingplan->slug);
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

        return to_route('trainingplan.index')->with('success', 'Playlist successfully deleted');
    }
}
