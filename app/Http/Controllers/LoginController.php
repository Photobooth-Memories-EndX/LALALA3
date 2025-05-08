<?php

namespace App\Http\Controllers;

use App\Models\Assesment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

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
        Session::flash('submit-type', $request->submit_type);

        if ($request->submit_type == 'login') {
            $validateUniqueCode = 'nullable';
        } else {
            $validateUniqueCode = 'required';
        }

        $request->validate([
            'id_emp'      => 'required',
            'password'    => 'required',
            'unique_code' => $validateUniqueCode,
        ]);

        if ($request->submit_type == 'login') {
            if (Auth::attempt(['id_emp' => $request->id_emp, 'password' => $request->password])) {
                $request->session()->regenerate();

                return redirect()->intended('/');
            }

            return back()->with('warning', 'Login failed');
        } else {
            Session::flash('id-emp', $request->id_emp);
            Session::flash('unique-code', $request->unique_code);

            if (Auth::attempt(['id_emp' => $request->id_emp, 'password' => $request->password])) {
                $request->session()->regenerate();
                $assessment = Assesment::where('unique_code', $request->unique_code)->first();

                if ($assessment) {
                    return redirect()->route('assesment.user.view', ['slug' => $assessment->slug]);
                } else {
                    Auth::logout();

                    return redirect()->route('loginpage')->with('warning', 'Your Assessment Unique Code is invalid!');
                }
            }

            return back()->with('warning', 'Employee ID or Password is not valid!');
        }
    }

    public function checksso()
    {
        Auth::logout();
        $getid = explode('$', request('ses'));
        $userget = User::firstWhere('ssohash', $getid[1]);
        
        //return $userget;
        if ($userget) {
            if (Auth::loginUsingId($userget->id)) {
                //if($userget->id_user==2315){
                    //return config('api.employee.base_url_hash') . $getid[1];
                    $employee = Http::get(config('api.employee.base_url_hash') . $getid[1] . '')->object();
                    foreach ($employee as $key => $value) {
                        $arrays[$key] = $value;
                    }
                    
                    $userget->update([
                        'team' => $arrays["team"],
                        'department' => $arrays["department"],
                        'division' => $arrays["division"],
                        'id_org_unit' => $arrays["id_ou"]
                    ]);
                //}
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
