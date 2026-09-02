<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoleAccessPermission;
use App\Models\User;

class RoleAccessPermissionSeeder extends Seeder
{
    public function run()
    {
    $modules = [
        'admin_management',
        'user_management',
        'learning_management',
        'content_repository',
        'category_classification',
        'assessment_management',
        'question_bank',
        'playlist_learning_path',
        'playlist_debug_tools',
        'news_announcement',
        'page_management',
        'event_documentation',
        'certificate_setting',

        // tambahan
        'monitoring',
        'learning_hours_management',
    ];
        /*
        |--------------------------------------------------------------------------
        | ROLE 1 - USER
        |--------------------------------------------------------------------------
        */
        foreach ($modules as $module) {
            RoleAccessPermission::updateOrCreate(
                [
                    'role' => User::ROLE_USER,
                    'module_key' => $module,
                ],
                [
                    'can_access' => false,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE 2 - ADMIN TRAINING
        |--------------------------------------------------------------------------
        */
        foreach ($modules as $module) {
            RoleAccessPermission::updateOrCreate(
                [
                    'role' => User::ROLE_ADMIN,
                    'module_key' => $module,
                ],
                [
                    'can_access' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE 3 - SUPER ADMIN
        |--------------------------------------------------------------------------
        */
        foreach ($modules as $module) {
            RoleAccessPermission::updateOrCreate(
                [
                    'role' => User::ROLE_SUPER_ADMIN,
                    'module_key' => $module,
                ],
                [
                    'can_access' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ROLE 4 - SUBADMIN DIVISION
        |--------------------------------------------------------------------------
        */

        foreach ($modules as $module) {

            $allowed = in_array($module, [
                'learning_management',
            ]);

            RoleAccessPermission::updateOrCreate(
                [
                    'role' => User::ROLE_SUBADMIN_DIVISION,
                    'module_key' => $module,
                ],
                [
                    'can_access' => $allowed,
                ]
            );
        }

        $this->command->info('RoleAccessPermissionSeeder completed!');
    }
}
