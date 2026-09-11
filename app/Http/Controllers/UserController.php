<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Search Filter (name, id_emp, email, team, department, division)
        $search = $request->q ?: ($request->search_user ?: ($request->s ?: $request->search));
        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('id_emp', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('team', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%")
                  ->orWhere('division', 'LIKE', "%{$search}%");
            });
        }

        // Status Filter (1/Active, 0/Inactive)
        $status = $request->status ?? $request->active;
        if (filled($status) && $status !== 'all') {
            if ($status === '1' || strtolower($status) === 'active') {
                $query->where('active', 1);
            } elseif ($status === '0' || strtolower($status) === 'inactive') {
                $query->where('active', 0);
            }
        }

        // Company Type Filter (ptkp / non-ptkp / non_ptkp)
        $companyType = $request->company_type;
        if (filled($companyType) && $companyType !== 'all') {
            if ($companyType === 'ptkp') {
                $query->where('id_emp', 'NOT LIKE', 'ex%');
            } elseif ($companyType === 'non-ptkp' || $companyType === 'non_ptkp' || $companyType === 'outsourcing') {
                $query->where('id_emp', 'LIKE', 'ex%');
            }
        }

        // Team Filter
        if ($request->filled('team') && $request->team !== 'all') {
            $query->where('team', $request->team);
        }

        // Department Filter
        if ($request->filled('department') && $request->department !== 'all') {
            $query->where('department', $request->department);
        }

        // Division Filter
        if ($request->filled('division') && $request->division !== 'all') {
            $query->where('division', $request->division);
        }

        // Stats calculation
        $totalUser = User::count();
        $totalActive = User::where('active', 1)->count();
        $totalInactive = User::where('active', 0)->count();
        $totalPtkp = User::where('id_emp', 'NOT LIKE', 'ex%')->count();
        $totalNonPtkp = User::where('id_emp', 'LIKE', 'ex%')->count();

        // Distinct filter lists
        $teams = User::whereNotNull('team')
            ->where('team', '!=', '')
            ->where('team', '!=', '-')
            ->where('team', '!=', 'ZZZZZZZZZZ')
            ->distinct()
            ->orderBy('team')
            ->pluck('team');

        $departments = User::whereNotNull('department')
            ->where('department', '!=', '')
            ->where('department', '!=', '-')
            ->where('department', '!=', 'ZZZZZZZZZZ')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $divisions = User::whereNotNull('division')
            ->where('division', '!=', '')
            ->where('division', '!=', '-')
            ->where('division', '!=', 'ZZZZZZZZZZ')
            ->distinct()
            ->orderBy('division')
            ->pluck('division');

        $entries = (int) ($request->entries ?: ($request->show_entries ?: 25));
        if (!in_array($entries, [10, 25, 50, 100])) {
            $entries = 25;
        }

        $user = $query->paginate($entries)->withQueryString();

        return view('main.user.index', compact(
            'user',
            'search',
            'entries',
            'totalUser',
            'totalActive',
            'totalInactive',
            'totalPtkp',
            'totalNonPtkp',
            'teams',
            'departments',
            'divisions'
        ))->with('no', (request('page', 1) - 1) * $entries);
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

        return redirect()->route('user.index')
            ->with('success', 'User reset password successfully. Password baru: ' . $user->id_emp);
    }

    public function changestatus(Request $request)
    {
        $user = User::findOrFail($request->id);

        $user->active = $request->active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $request->active == 1
                ? 'User activated successfully'
                : 'User deactivated successfully'
        ]);
    }

    public function searchUser(Request $request)
    {
        return $this->index($request);
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

    public function getEmployeeData($id_emp)
    {
        $response = Http::get(config('api.employee.base_url') . $id_emp);

        if ($response->failed()) {
            return response()->json([
                'status' => 500,
                'message' => 'Gagal mengambil data employee.'
            ], 500);
        }

        $data = $response->json();

        if (($data['status'] ?? null) == 404) {
            return response()->json([
                'status' => 404,
                'message' => $data['message'] ?? 'Employee tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'sap_code' => $data['sap_code'] ?? '-',
            'ssohash' => $data['newhash'] ?? '-',
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'team' => $data['team'] ?? '',
            'department' => $data['department'] ?? '',
            'division' => $data['division'] ?? '',
            'id_org_unit' => $data['id_ou'] ?? null,
            'id_user' => $data['id_user'] ?? null,
        ]);
    }
}
