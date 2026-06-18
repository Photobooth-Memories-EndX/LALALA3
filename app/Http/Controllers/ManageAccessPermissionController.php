<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAccessPermission;
use Illuminate\Http\Request;

class ManageAccessPermissionController extends Controller
{
    private array $modules = [
        'learning_management'        => 'Learning Management',
        'user_management'            => 'User Management',
        'admin_management'           => 'Admin Management',
        'page_management'            => 'Page Management',
        'news_announcement'          => 'News & Announcement',
        'content_repository'         => 'Content Repository',
        'category_classification'    => 'Category & Classification',
        'assessment_management'      => 'Assessment Management',
        'question_bank'              => 'Question Bank',
        'playlist_learning_path'     => 'Playlist & Learning Path',
        'event_documentation'        => 'Event Documentation',
        'certificate_setting'        => 'Certificate Setting',
    ];

    public function index(Request $request)
    {
        $search = $request->input('s');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('id_emp', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('main.manage-access-permission.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function edit(User $user)
    {
        $permissions = $user->accessPermissions()
            ->pluck('can_access', 'module_key')
            ->toArray();

        return view('main.manage-access-permission.edit', [
            'user' => $user,
            'modules' => $this->modules,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return redirect()
                ->route('manage.access-permission.index')
                ->with('warning', 'Super Admin tidak perlu diatur permission-nya karena sudah full access.');
        }

        $selectedModules = $request->input('permissions', []);

        foreach ($this->modules as $moduleKey => $moduleLabel) {
            UserAccessPermission::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'module_key' => $moduleKey,
                ],
                [
                    'can_access' => in_array($moduleKey, $selectedModules),
                ]
            );
        }

        return redirect()
            ->route('manage.access-permission.index')
            ->with('success', 'Access permission berhasil diperbarui.');
    }
}