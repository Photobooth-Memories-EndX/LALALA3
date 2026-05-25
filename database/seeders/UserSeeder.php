<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        // ========== DAFA RIZQY (ROLE 2) ==========
        User::create([
            'id_user'    => 'USR00001',
            'id_emp'     => '000001',
            'id_org_unit'=> 'ORG001',
            'name'       => 'Dafa Rizqy',
            'email'      => 'dafa.rizqy@company.com',
            'password'   => Hash::make('password123'),
            'team'       => 'IT',
            'department' => 'Technology',
            'division'   => 'Development',
            'ssohash'    => 'sso_dafa123',
            'sap_code'   => 'SAP001',
            'avatar'     => null,
            'role'       => '3',
        ]);

        // ========== WAKHIDA FAJAR ARDHINI (ROLE 2) ==========
        User::create([
            'id_user'    => 'USR00002',
            'id_emp'     => '000002',
            'id_org_unit'=> 'ORG002',
            'name'       => 'Wakhida Fajar Ardhini',
            'email'      => 'wakhida.fajar@company.com',
            'password'   => Hash::make('password123'),
            'team'       => 'IT',
            'department' => 'Technology',
            'division'   => 'Development',
            'ssohash'    => 'sso_wakhida123',
            'sap_code'   => 'SAP002',
            'avatar'     => null,
            'role'       => '2',
        ]);

        // ========== TAMBAHKAN USER LAIN (BEBAS) ==========
        User::create([
            'name'       => 'Admin User',
            'email'      => 'admin@company.com',
            'password'   => Hash::make('password123'),
            'team'       => 'IT',
            'department' => 'Technology',
            'division'   => 'Digital',
            'ssohash'    => 'sso_admin',
            'sap_code'   => 'SAP000',
            'role'       => '1',
        ]);

        $this->command->info('User seeder completed!');
    }
}