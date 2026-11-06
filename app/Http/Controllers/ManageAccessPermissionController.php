<?php

namespace App\Http\Controllers;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Http\Request;

class ManageAccessPermissionController extends Controller
{
    private const ROLE_DISPLAY_ORDER = [
        User::ROLE_USER,
        User::ROLE_SUBADMIN_DIVISION,
        User::ROLE_ADMIN,
        User::ROLE_SUPER_ADMIN,
        User::ROLE_APPROVER,
    ];

    private array $modules = [
        'admin_management'           => 'Admin Management',
        'user_management'            => 'User Management',
        'learning_management'        => 'Learning Management',
        'record_management'          => 'Record Management',
        'content_repository'         => 'Content Repository',
        'category_classification'    => 'Category & Classification',
        'assessment_management'      => 'Assessment Management',
        'question_bank'              => 'Question Bank',
        'playlist_learning_path'     => 'Playlist & Learning Path',
        'learning_hours_management'  => 'Learning Hours Management',
        'playlist_debug_tools'       => 'Playlist Debug Tools',
        'learning_engagement_time'   => 'Learning Engagement Time',
        'news_announcement'          => 'News & Announcement',
        'page_management'            => 'Page Management',
        'event_documentation'        => 'Event Documentation',
        'certificate_setting'        => 'Certificate Setting',
        'task_management'            => 'Task Management',
        'monitoring'                 => 'Monitoring',
    ];

    public function index()
    {
        return view('main.manage-access-permission.index', [
            'roles' => $this->orderedRoles(),
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

    private function orderedRoles(): array
    {
        $roles = User::roles();

        return collect(self::ROLE_DISPLAY_ORDER)
            ->mapWithKeys(fn ($role) => [$role => $roles[$role] ?? $role])
            ->toArray();
    }
}
