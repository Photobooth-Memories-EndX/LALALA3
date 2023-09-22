<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function index()
    {
        return view('login.index', [
            "title" => 'Login'
        ]);
    }
    public function authenticate(Request $request)
    {

        // return "aa";

        $credentials = $request->validate([
            'id_emp' => 'required',
            'password' => 'required'
        ]);
        // return  $credentials;
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }
        return back()->with('warning', 'Login failed');
    }

    public function checksso()
    {
        Auth::logout();
        $getid = explode('$', request('ses'));
        $userget = User::firstWhere('ssohash', $getid[1]);
        if ($userget) {
            if (Auth::loginUsingId($userget->id)) {
                return redirect()->intended('/');
            } else {
                return back()->with('loginError', 'SSO failed, user not found');
            }
        } else {
            $employee = Http::get(config('api.employee.base_url_hash') . $getid[1] . '')->object();
            foreach ($employee as $key => $value) {
                $arrays[$key] = $value;
            }
            $arrays['password'] =  Hash::make($employee->id_emp);
            $user = User::create($arrays);
            if (Auth::loginUsingId($user->id)) {
                return redirect()->intended('/');
            } else {
                return back()->with('loginError', 'SSO failed, user not found');
            }
        }
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('successlogout', 'Successfully Logout');
    }
}
