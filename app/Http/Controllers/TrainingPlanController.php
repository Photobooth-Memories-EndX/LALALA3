<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use Illuminate\Http\Request;

class TrainingPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        // $trainingplan = TrainingPlan::with([
        //     'trainingplandetail' => function ($query) {
        //         $query->select('id', 'content_id');
        //     },
        //     'trainingplandetail.content'
        //     => function ($query) {
        //         $query->select('id', 'title');
        //     }
        // ])->get();
        $trainingplan = TrainingPlan::with([
            'trainingplandetail' => function ($query) {
                $query->orderBy('content_order');
            }, 'trainingplandetail.content'
        ])->get();

        // return $trainingplan;
        return view('main.trainingplan.index', [
            "title" =>  "Training Plan List",
            "trainingplan" => $trainingplan
        ]);
        // return "aaa";
    }
    public function order($slug)
    {
        $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();
        $trainingplan = TrainingPlan::with(['trainingplandetail', 'trainingplandetail.content'])->where('deleted_at', NULL)->firstWhere('slug', $slug);


        $trainingplandetail = TrainingPlanDetail::with('content')->where('trainingplan_id', $trainingplan->id)->orderBy('content_order', 'ASC')->get();
        // return json_encode($activecontent);
        // return $trainingplandetail;
        return view('main.trainingplan.order', [
            "title" =>  "Edit Training Plan",
            "slug" =>  $trainingplan->slug,
            "trainingplandetail" =>  $trainingplandetail,
            "content" =>  $content
        ]);
    }
    // public function saveorder($slug)
    // {
    //     $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();
    //     $trainingplan = TrainingPlan::with(['trainingplandetail', 'trainingplandetail.content'])->where('deleted_at', NULL)->firstWhere('slug', $slug);
    //     $activecontent = array();
    //     foreach ($trainingplan->trainingplandetail as $contentlist) {
    //         $activecontent[] = $contentlist->content_id;
    //     }


    //     return redirect()->route('trainingplan.edit', $trainingplan->slug)
    //     ->with('success', 'Training plan updated successfully');
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();
        // return $content[0]->id;
        return view('main.trainingplan.create', [
            "title" =>  "Add Training Plan",
            "content" => $content
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        // return $request;

        if ($request->action == "publish") {
            $addmessage = " and published";
            $active = "Active";
        } else {
            $addmessage = "";
            $active = "Inactive";
        }
        $newtrainingplan = new TrainingPlan([
            'title' => $request->title,
            'active' => $active
        ]);
        $newtrainingplan->save();
        $newid = $newtrainingplan->id;
        foreach ($request->content as $key => $value) {
            $newtrainingplandetail = new TrainingPlanDetail([
                'trainingplan_id' => $newid,
                'content_id' => $value,
                'content_order' => $key
            ]);
            $newtrainingplandetail->save();
            // echo "$value<br>";
        }
        return to_route('trainingplan.edit', $newtrainingplan->slug)->with('success', 'Training plan successfully saved ' . $addmessage);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TrainingPlan  $trainingPlan
     * @return \Illuminate\Http\Response
     */
    public function show(TrainingPlan $trainingPlan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TrainingPlan  $trainingPlan
     * @return \Illuminate\Http\Response
     */
    public function edit(TrainingPlan $trainingplan)
    {
        $content = Content::where('deleted_at', NULL)->where('active', 'Active')->get();
        $trainingplan = TrainingPlan::with(['trainingplandetail', 'trainingplandetail.content'])->where('deleted_at', NULL)->firstWhere('id', $trainingplan->id);
        $activecontent = array();
        foreach ($trainingplan->trainingplandetail as $contentlist) {
            $activecontent[] = $contentlist->content_id;
        }

        // return json_encode($activecontent);
        return view('main.trainingplan.edit', [
            "title" =>  "Edit Training Plan",
            "trainingplan" =>  $trainingplan,
            "content" =>  $content,
            "activecontent" =>  $activecontent
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TrainingPlan  $trainingPlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TrainingPlan $trainingplan)
    {
        // return $request;
        if ($request->savetype == "reorder") {
            foreach ($request->order as $key => $value) {
                // echo $key . "-" . $value . "<br>";
                TrainingPlanDetail::firstWhere('id', $key)->update([
                    'content_order' => $value
                ]);
            }
            // return $request;
        } else {
            $request->validate([
                'title' => 'required'
                // 'description' => 'required',
                // 'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $trainingplan->title = $request->input('title');
            // $trainingplan->description = $request->input('description');
            if ($request->action == "publish") {
                $trainingplan->active = "Active";
            } else if ($request->action == "unpublish") {
                $trainingplan->active = "Inactive";
                TrainingPlan::where('id', $trainingplan->id)
                    ->update([
                        'active' => 'Inactive'
                    ]);
                return redirect()->route('trainingplan.edit', $trainingplan->slug)
                    ->with('success', 'Training plan updated successfully');
            }

            $trainingplan->save();
            //removecontent
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

            //addcontent
            if ($request->content) {
                foreach ($request->content as $key => $value) {
                    // echo $value . "<br>";
                    $checkcontent = TrainingPlanDetail::firstWhere([['trainingplan_id', $trainingplan->id], ['content_id', $value]]);
                    if (!$checkcontent) {
                        echo $value . "<br>";
                        $newtrainingplandetail = new TrainingPlanDetail([
                            'trainingplan_id' => $trainingplan->id,
                            'content_id' => $value,
                            'content_order' => $key
                        ]);
                        $newtrainingplandetail->save();
                    } else {
                    }
                    // return $newtrainingplandetail;
                    // $newtrainingplandetail = new TrainingPlanDetail([
                    //     'trainingplan_id' => $trainingplan->id,
                    //     'content_id' => $value,
                    // ]);
                    // $newtrainingplandetail->save();
                }
            }
        }
        return redirect()->route('trainingplan.edit', $trainingplan->slug)
            ->with('success', 'Training plan updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TrainingPlan  $trainingPlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(TrainingPlan $trainingplan)
    {
        $trainingplan->delete();
        // return $trainingPlan;
        // if ($aaa) {
        //     return "success";
        // } else {
        //     return "not success";
        // }
        return to_route('trainingplan.index')->with('success', 'Training plan successfully deleted');
    }
}
