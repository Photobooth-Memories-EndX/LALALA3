<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Assesment;
use Illuminate\Http\Request;

class AssesmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $assesment = Assesment::latest()->get();
        return view('main.assesment.index', compact('assesment'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $contents = Content::latest()->get();
        return view('main.assesment.create', compact('contents'));
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
            'content_id' => 'required',
            'duration' => 'required',
            'date' => 'required',
        ]);

        $assesment = new Assesment();
        $assesment->content_id = $request->input('content_id');
        $assesment->title = $request->input('title');
        $assesment->duration = $request->input('duration');
        $assesment->date = $request->input('date');
        $assesment->save();

        return to_route('assesment.index')->with('success','Assesment created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Assesment  $assesment
     * @return \Illuminate\Http\Response
     */
    public function show(Assesment $assesment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Assesment  $assesment
     * @return \Illuminate\Http\Response
     */
    public function edit(Assesment $assesment)
    {
        $contents = Content::latest()->get();
        return view('main.assesment.edit', compact('assesment', 'contents'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Assesment  $assesment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Assesment $assesment)
    {
        $request->validate([
            'title' => 'required',
            'content_id' => 'required',
            'duration' => 'required',
            'date' => 'required',
        ]);

        $assesment->content_id = $request->input('content_id');
        $assesment->title = $request->input('title');
        $assesment->duration = $request->input('duration');
        $assesment->date = $request->input('date');
        $assesment->save();

        return to_route('assesment.index')->with('success','Assesment updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Assesment  $assesment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Assesment $assesment)
    {
        $assesment->delete();
        return to_route('assesment.index')->with('success','Assesment deleted successfully');
    }

}
