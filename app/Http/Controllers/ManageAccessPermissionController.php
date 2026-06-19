<?php

namespace App\Http\Controllers;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Http\Request;

class ManageAccessPermissionController extends Controller
{
        private array $modules = [
        'admin_management'           => 'Admin Management',
        'user_management'            => 'User Management',
        'learning_management'        => 'Learning Management',
        'content_repository'         => 'Content Repository',
        'category_classification'    => 'Category & Classification',
        'assessment_management'      => 'Assessment Management',
        'question_bank'              => 'Question Bank',
        'playlist_learning_path'     => 'Playlist & Learning Path',
        'news_announcement'          => 'News & Announcement',
        'page_management'            => 'Page Management',
        'event_documentation'        => 'Event Documentation',
        'certificate_setting'        => 'Certificate Setting',
        'monitoring'                 => 'Monitoring',
    ];

    public function index()
    {
        return view('main.manage-access-permission.index', [
            'roles' => User::roles(),
            'modules' => $this->modules,
            'permissions' => RoleAccessPermission::all()
                ->groupBy('role')
                ->map(fn ($items) => $items->pluck('can_access', 'module_key'))
                ->toArray(),
        ]);
    }

    public function edit(string $role)
    {
        abort_if(!array_key_exists($role, User::roles()), 404);

        return view('main.manage-access-permission.edit', [
            'role' => $role,
            'roleName' => User::roles()[$role],
            'modules' => $this->modules,
            'permissions' => RoleAccessPermission::where('role', $role)
                ->pluck('can_access', 'module_key')
                ->toArray(),
        ]);
    }

    public function update(Request $request, string $role)
    {
        abort_if(!array_key_exists($role, User::roles()), 404);

        if ($role === User::ROLE_SUPER_ADMIN) {
            return redirect()
                ->route('manage.access-permission.index')
                ->with('warning', 'Super Admin selalu memiliki full access.');
        }

        $selectedModules = $request->input('permissions', []);

        foreach ($this->modules as $moduleKey => $label) {
            RoleAccessPermission::updateOrCreate(
                [
                    'role' => $role,
                    'module_key' => $moduleKey,
                ],
                [
                    'can_access' => in_array($moduleKey, $selectedModules),
                ]
            );
        }

        return redirect()
            ->route('manage.access-permission.index')
            ->with('success', 'Access permission role berhasil diperbarui.');
    }
}
