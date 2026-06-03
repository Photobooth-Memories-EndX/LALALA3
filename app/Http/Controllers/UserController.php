<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $entries    = 25;
        $user       = User::paginate($entries)->withQueryString();
        $search     = "";

        return view('main.user.index', compact('user', 'search', 'entries'))->with('no', (request('page', 1) - 1) * $entries);
    }

    public function create()
    {
        return view('main.user.create');
    }

    public function createOutsourcing()
    {
        return view('main.user.create_outsourcing');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_emp' => 'required|unique:users,id_emp',
            'name'   => 'required',
            'email'  => 'nullable|unique:users,email',
        ]);

        User::create([
            'id_emp'      => $request->id_emp,
            'id_org_unit' => $request->id_org_unit ?: '0',
            'id_user'     => $request->id_user ?: '0',
            'ssohash'     => $request->ssohash ?: '-',
            'sap_code'    => $request->sap_code ?: '-',
            'name'        => $request->name,
            'email'       => $request->email,
            'team'        => $request->team ?: '-',
            'department'  => $request->department ?: '-',
            'division'    => $request->division ?: '-',
            'password'    => Hash::make($request->id_emp),
        ]);

        return redirect()->route('user.index')->with('success', 'New user created successfully.');
    }

    public function storeOutsourcing(Request $request)
    {
        // Change request value before validation
        $request->merge([
            'id_employee' => 'ex' . $request->id_employee,
        ]);

        $request->validate([
            'company_name' => 'required',
            'id_employee'  => 'required|max:10|unique:users,id_emp',
            'name'         => 'required',
            'email'        => 'required|unique:users,email',
        ], [
            'company_name.required' => 'The Company Name is required.',
            'id_employee.required'  => 'The ID Employee is required.',
            'id_employee.max'       => 'The ID Employee must not exceed 10 characters.',
            'id_employee.unique'    => 'The ID Employee has been registered.',
            'name.required'         => 'The Name is required.',
            'email.required'        => 'The Email is required.',
            'email.unique'          => 'The Email has been registered.',
        ]);

        $user = new User;
        $user->company_name = $request->company_name;
        $user->id_emp       = $request->id_employee;
        $user->name         = $request->name;
        $user->email        = $request->email;
        $user->password     = Hash::make('ex' . $request->id_employee);
        $user->team         = 'ZZZZZZZZZZ';
        $user->department   = 'ZZZZZZZZZZ';
        $user->division     = 'ZZZZZZZZZZ';
        $user->ssohash      = 'ZZZZZZZZZZ';
        $user->sap_code     = 'ZZZZZZZZZZ';
        $user->save();

        return redirect()->route('user.index')->with('success', 'New Outsourcing User created successfully.');
    }

    public function show(User $user)
    {
        //
    }

    public function edit(User $user)
    {
        if (substr($user->id_emp, 0, 2) == 'ex') {
            return view('main.user.edit_outsourcing', compact('user'));
        } else {
            return view('main.user.edit', compact('user'));
        }
    }

        public function update(Request $request, User $user)
        {
            $data = $request->only([
                'id_emp',
                'id_org_unit',
                'id_user',
                'name',
                'email',
                'team',
                'department',
                'division',
                'ssohash',
                'sap_code',
            ]);

            foreach (['id_org_unit', 'id_user', 'ssohash', 'sap_code'] as $field) {
                if (!$request->filled($field)) {
                    unset($data[$field]);
                }
            }

            $user->update($data);

            return redirect()->route('user.index')
                ->with('success', 'User updated successfully');
        }

    public function updateOutsourcing(Request $request, User $user)
    {
        $request->merge([
            'id_employee' => 'ex' . $request->id_employee,
        ]);

        $request->validate([
            'company_name' => 'required',
            'id_employee'  => 'required|max:10|unique:users,id_emp,' . $user->id,
            'name'         => 'required',
            'email'        => 'required|unique:users,email,' . $user->id,
        ], [
            'company_name.required' => 'The Company Name is required.',
            'id_employee.required'  => 'The ID Employee is required.',
            'id_employee.max'       => 'The ID Employee must not be greater than 10 characters',
            'id_employee.unique'    => 'The ID Employee has been registered.',
            'name.required'         => 'The Name is required.',
            'email.required'        => 'The Email is required.',
            'email.unique'          => 'The Email has been registered.',
        ]);

        $user->company_name = $request->company_name;
        $user->id_emp       = $request->id_employee;
        $user->name         = $request->name;
        $user->email        = $request->email;
        $user->password     = Hash::make($request->id_employee);
        $user->team         = 'ZZZZZZZZZZ';
        $user->department   = 'ZZZZZZZZZZ';
        $user->division     = 'ZZZZZZZZZZ';
        $user->ssohash      = 'ZZZZZZZZZZ';
        $user->sap_code     = 'ZZZZZZZZZZ';
        $user->save();

        return redirect()->route('user.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $hasLearningData = \App\Models\ContentUser::where('user_id', $user->id)->exists();

        if ($hasLearningData) {
            return redirect()->route('user.index')
                ->with('warning', 'User tidak bisa dihapus karena masih memiliki data learning.');
        }

        $user->delete();

        return redirect()->route('user.index')
            ->with('success', 'User deleted successfully');
    }

    public function resetpassword(User $user)
    {

        User::whereId($user->id)->update([
            'password' => Hash::make($user->id_emp)
        ]);
        return redirect()->route('user.index')->with('success', 'User reset password successfully');
    }

    public function changestatus(Request $request)
    {
        $user = User::findOrFail($request->id);

        $user->active = $request->active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $request->active == 0
                ? 'User activated successfully'
                : 'User deactivated successfully'
        ]);
    }

    public function searchUser(Request $request)
    {
        $entries    = $request->show_entries;
        $search     = $request->search_user;
        $user = User::where('name', 'LIKE', "%$search%")
            ->orWhere('id_emp', 'LIKE', "%$search%")
            ->orWhere('team', 'LIKE', "%$search%")
            ->orWhere('department', 'LIKE', "%$search%")
            ->orWhere('division', 'LIKE', "%$search%")
            ->paginate($entries)->withQueryString();

        return view('main.user.index', compact('user', 'search', 'entries'))->with('no', (request('page', 1) - 1) * $entries);
    }

    public function detail(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'id_emp' => $user->id_emp,
            'name' => $user->name,
            'email' => $user->email,
            'team' => $user->team,
            'department' => $user->department,
            'division' => $user->division,
            'id_org_unit' => $user->id_org_unit,
            'id_user' => $user->id_user,
            'ssohash' => $user->ssohash,
            'sap_code' => $user->sap_code,
            'company_name' => $user->company_name,
            'is_outsourcing' => substr($user->id_emp, 0, 2) === 'ex',
            'id_employee_clean' => substr($user->id_emp, 0, 2) === 'ex'
                ? substr($user->id_emp, 2)
                : $user->id_emp,
        ]);
    }
}
