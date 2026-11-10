<?php

namespace Database\Seeders;

use App\Models\RoleAccessPermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecordPermissionSeeder extends Seeder
{
    public const MODULE_KEY = 'record_management';

    public function run(): void
    {
        $permissions = [
            User::ROLE_USER => false,
            User::ROLE_ADMIN => true,
            User::ROLE_SUPER_ADMIN => true,
            User::ROLE_SUBADMIN_DIVISION => true,
        ];

        foreach ($permissions as $role => $canAccess) {
            RoleAccessPermission::updateOrCreate(
                [
                    'role' => $role,
                    'module_key' => self::MODULE_KEY,
                ],
                [
                    'can_access' => $canAccess,
                ]
            );
        }

        if (isset($this->command)) {
            $this->command->info('RecordPermissionSeeder completed successfully!');
        }
    }
}
