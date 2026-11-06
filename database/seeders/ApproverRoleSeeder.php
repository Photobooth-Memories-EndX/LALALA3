<?php

namespace Database\Seeders;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApproverRoleSeeder extends Seeder
{
    /**
     * Standard module keys managed under Access Permission.
     */
    private const MODULES = [
        'admin_management',
        'user_management',
        'learning_management',
        'record_management',
        'content_repository',
        'category_classification',
        'assessment_management',
        'question_bank',
        'playlist_learning_path',
        'learning_hours_management',
        'playlist_debug_tools',
        'learning_engagement_time',
        'news_announcement',
        'page_management',
        'event_documentation',
        'certificate_setting',
        'monitoring',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Conflict detection & validation for Role ID 5
        $roleId = User::ROLE_APPROVER;
        $expectedRoleName = 'Approver';

        $definedRoles = User::roles();
        if (isset($definedRoles[$roleId]) && $definedRoles[$roleId] !== $expectedRoleName) {
            throw new \RuntimeException(
                "Role ID {$roleId} conflict: Already mapped to '{$definedRoles[$roleId]}' instead of '{$expectedRoleName}'."
            );
        }

        DB::transaction(function () use ($roleId, $expectedRoleName) {
            // 2. If a dedicated 'roles' table exists in the database, seed idempotently
            if (Schema::hasTable('roles')) {
                $existingRole = DB::table('roles')->where('id', $roleId)->first();
                if ($existingRole && isset($existingRole->name) && $existingRole->name !== $expectedRoleName) {
                    throw new \RuntimeException(
                        "Database 'roles' table conflict: ID {$roleId} is already used by '{$existingRole->name}'."
                    );
                }

                $updateData = ['updated_at' => now()];
                if (Schema::hasColumn('roles', 'name')) {
                    $updateData['name'] = $expectedRoleName;
                }

                DB::table('roles')->updateOrInsert(
                    ['id' => $roleId],
                    array_merge(['created_at' => now()], $updateData)
                );
            }

            // 3. Initialize default access permissions for Role 5 (Least Privilege: false for Manage LMS)
            // Use firstOrCreate to ensure existing admin modifications are NEVER overwritten on re-run.
            $existingModuleKeys = RoleAccessPermission::distinct()->pluck('module_key')->toArray();
            $modulesToSeed = array_values(array_unique(array_merge(self::MODULES, $existingModuleKeys)));

            foreach ($modulesToSeed as $moduleKey) {
                RoleAccessPermission::firstOrCreate(
                    [
                        'role' => (string) $roleId,
                        'module_key' => $moduleKey,
                    ],
                    [
                        'can_access' => false,
                    ]
                );
            }
        });

        if (isset($this->command)) {
            $this->command->info("ApproverRoleSeeder completed successfully. Role ID {$roleId} ({$expectedRoleName}) is ready.");
        }
    }
}
