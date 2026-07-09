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

    public function assessmentAccess()
    {
        return view('login.assessment_access');
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

                    return redirect()->back()->with('warning', 'Your Assessment Unique Code is invalid!');
                }
            }

            return back()->with('warning', 'Employee ID or Password is not valid!');
        }
    }

    public function synchronizeData($id_employee)
    {
        $userSignIn = User::where('id_emp', $id_employee)->first();
        $duplicateUser = null;

        // Check duplicate employee
        $duplicateEmployees = User::where('name', $userSignIn->name)
            ->where('division', $userSignIn->division)
            ->where('department', $userSignIn->department)->get();

        if (count($duplicateEmployees) > 1) { // Condition when data is duplicate
            $signInEmp = null;
            $duplicateEmp = null;

            foreach ($duplicateEmployees as $duplicate) {
                if ($duplicate->id_emp == $userSignIn->id_emp) {
                    $signInEmp = $duplicate->toArray();
                } else {
                    $duplicateEmp = $duplicate->toArray();
                }
            }

            $signInUser = User::findOrFail($signInEmp['id']);
            $duplicateUser = User::findOrFail($duplicateEmp['id']);

            if (substr($duplicateUser->id_emp, 0, 1) != 1) {
                // Change user_id form duplicate to sign in user or update child relationship
                if (count($duplicateUser->news) > 1) { // Update child news
                    foreach ($duplicateUser->news as $news) {
                        $news->user_error = $signInUser->id;
                        $news->save();
                    }
                }
    
                if (count($duplicateUser->eventPhotos) > 1) { // Update child eventPhoto
                    foreach ($duplicateUser->eventPhotos as $eventPhoto) {
                        $eventPhoto->user_id = $signInUser->id;
                        $eventPhoto->save();
                    }
                }
    
                if (count($duplicateUser->contentUsers) > 1) { // Update child content_users
                    foreach ($duplicateUser->contentUsers as $contentUser) {
                        $contentUser->user_id = $signInUser->id;
                        $contentUser->save();
                    }
                }
    
                if (count($duplicateUser->comments) > 1) { // Update child comments
                    foreach ($duplicateUser->comments as $comment) {
                        $comment->user_id = $signInUser->id;
                        $comment->save();
                    }
                }
    
                if (count($duplicateUser->assesmentResults) > 1) { // Update child assessment_results
                    foreach ($duplicateUser->assesmentResults as $assessmentResult) {
                        $assessmentResult->user_id = $signInUser->id;
                        $assessmentResult->save();
                    }
                }
            }
        }

        // Update data user if there are any changes
        $apiEmployee = Http::get(config('api.employee.base_url') . $userSignIn->id_emp)->object(); // Fetch data employee
        if (!isset($apiEmployee->status)) { // Sync data if any data form api employee
            $userSignIn->id_user = $apiEmployee->id_user;
            $userSignIn->id_org_unit = $apiEmployee->id_ou;
            $userSignIn->email = $apiEmployee->email;
            $userSignIn->team = $apiEmployee->team;
            $userSignIn->department = $apiEmployee->department;
            $userSignIn->division = $apiEmployee->division;
            $userSignIn->ssohash = $apiEmployee->ssohash;
            $userSignIn->sap_code = $apiEmployee->sap_code;

            if ($duplicateUser) {
                if (substr($duplicateUser->id_emp, 0, 1) != 1) {
                    $userSignIn->role = $duplicateUser->role;
                }
            }

            $userSignIn->save();
        }

        // Delete duplicate user after moved the data
        if ($duplicateUser) {
            if (substr($duplicateUser->id_emp, 0, 1) != 1) {
                $duplicateUser->delete();
            }
        }

        return $userSignIn;
    }

    public function checksso()
    {
        Auth::logout();
        $getid = explode('$', request('ses'));
        $userget = User::firstWhere('ssohash', $getid[1]);
        $userSign = null;

        if ($userget) {
            $userSign = $this->synchronizeData($userget->id_emp);
        } else {
            $employee = Http::get(config('api.employee.base_url_hash') . $getid[1] . '')->object();
            if (isset($employee->status) == 404) {
                return back()->with('loginError', 'SSO failed, The user was not found in the employee master data.');
            }

            foreach ($employee as $key => $value) {
                $arrays[$key] = $value;
            }
            $arrays['password'] =  Hash::make($employee->id_emp);
            $user = User::create($arrays);

            $userSign = $this->synchronizeData($user->id_emp);
        }

        if ($userSign) {
            Auth::loginUsingId($userSign->id);
            return redirect()->intended('/');
        } else {
            return back()->with('loginError', 'SSO failed, user not found');
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

