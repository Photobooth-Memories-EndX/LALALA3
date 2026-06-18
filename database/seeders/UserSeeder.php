<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Seeder aman dijalankan berkali-kali (idempoten) memakai updateOrCreate
     * berdasarkan email. Tidak menghapus user existing & tanpa hard delete.
     */
    public function run()
    {
        $defaultPassword = Hash::make('pecellele');

        $users = [
            // ROLE 1 - User / Employee
            [
                'id_user'     => 'USR00010',
                'id_emp'      => '11111111',
                'id_org_unit' => 'ORG010',
                'name'        => 'Employee User OWL',
                'email'       => 'employee.owl@gmail.com',
                'team'        => 'Operasional',
                'department'  => 'Production',
                'division'    => 'Production Division',
                'ssohash'     => 'sso_employee010',
                'sap_code'    => 'SAP010',
                'role'        => User::ROLE_USER,
            ],

            // ROLE 2 - Admin Training
            [
                'id_user'     => 'USR00001',
                'id_emp'      => '22222222',
                'id_org_unit' => 'ORG001',
                'name'        => 'Dafa Rizqy',
                'email'       => 'dafarizqy1@gmail.com',
                'team'        => 'IT',
                'department'  => 'Technology',
                'division'    => 'Technology and Business Development Division',
                'ssohash'     => 'sso_dafa123',
                'sap_code'    => 'SAP001',
                'role'        => User::ROLE_ADMIN,
            ],

            // ROLE 3 - Super Admin (TUNGGAL)
            [
                
                'id_user'     => 'USR0010',
                'id_emp'      => '33333333',
                'id_org_unit' => 'ORG10',
                'name'       => 'SUPER ADMIN OWL',
                'email'      => 'owl.krakratau.posco@gmail.com',
                'team'       => 'IT',
                'department' => 'Technology',
                'division'   => 'Technology and Business Development Division',
                'ssohash'    => 'sso_admin',
                'sap_code'   => 'SAP000',
                'role'       => User::ROLE_SUPER_ADMIN,
            ],

            // ROLE 4 - Subadmin Division (per division nyata)
            [
                'id_user'     => 'USR00101',
                'id_emp'      => '4444444',
                'id_org_unit' => 'ORG101',
                'name'        => 'Subadmin HR & GA',
                'email'       => 'subadmin.hrga@gmail.com',
                'team'        => 'HR',
                'department'  => 'Human Resources',
                'division'    => 'Human Resources & General Affairs Division',
                'ssohash'     => 'sso_subadmin_hrga',
                'sap_code'    => 'SAP101',
                'role'        => User::ROLE_SUBADMIN_DIVISION,
            ],
            [
                'id_user'     => 'USR00102',
                'id_emp'      => '4444454',
                'id_org_unit' => 'ORG102',
                'name'        => 'Subadmin Technology & BD',
                'email'       => 'subadmin.techbd@gmail.com',
                'team'        => 'IT',
                'department'  => 'Technology',
                'division'    => 'Technology and Business Development Division',
                'ssohash'     => 'sso_subadmin_techbd',
                'sap_code'    => 'SAP102',
                'role'        => User::ROLE_SUBADMIN_DIVISION,
            ],
            [
                'id_user'     => 'USR00103',
                'id_emp'      => '4444464',
                'id_org_unit' => 'ORG103',
                'name'        => 'Subadmin Production',
                'email'       => 'subadmin.production@gmail.com',
                'team'        => 'Operasional',
                'department'  => 'Production',
                'division'    => 'Production Division',
                'ssohash'     => 'sso_subadmin_production',
                'sap_code'    => 'SAP103',
                'role'        => User::ROLE_SUBADMIN_DIVISION,
            ],
            [
                'id_user'     => 'USR00104',
                'id_emp'      => '4444474',
                'id_org_unit' => 'ORG104',
                'name'        => 'Subadmin Commercial',
                'email'       => 'subadmin.commercial@gmail.com',
                'team'        => 'Commercial',
                'department'  => 'Commercial',
                'division'    => 'Commercial Division',
                'ssohash'     => 'sso_subadmin_commercial',
                'sap_code'    => 'SAP104',
                'role'        => User::ROLE_SUBADMIN_DIVISION,
            ],
        ];

        foreach ($users as $data) {
            $email = $data['email'];
            unset($data['email']);

            // Password hanya diset saat membuat baru, tidak menimpa password existing.
            User::updateOrCreate(
                ['email' => $email],
                array_merge($data, ['password' => $defaultPassword])
            );
        }

        $this->command->info('User seeder completed (idempotent)!');
    }
}
