<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admin = User::select('id', 'name', 'id_emp')->where('role', '2')->whereNotIn('id_emp', ['813032', '106596'])->get();
        $trainingteam = User::select('id', 'name')->where('role', '1')->where('id_org_unit', 'OU230')->get();
        return view('main.admin.index', compact('admin', 'trainingteam'));
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
            'id_user' => 'required'
        ], [
            'id_user.required' => 'Please select employee. Add user if not exist yet',
        ]);

        $user = User::findOrFail($request->id_user);
        $user->role = '2';
        $user->save();

        return redirect()->route('admin.index')->with('success', 'New admin created successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $admin)
    {

        $admin->role = '1';
        $admin->save();
        return redirect()->route('admin.index')->with('success', 'Admin updated successfully');
    }


}
