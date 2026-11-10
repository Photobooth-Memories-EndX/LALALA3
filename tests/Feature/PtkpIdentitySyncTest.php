<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\EmployeeSync\EmployeeUserSyncService;
use App\Services\OfflineTraining\EmployeeIdentitySyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PtkpIdentitySyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected OfflineTraining $training;
    protected OfflineTrainingUser $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createTestUser([
            'id_emp'       => 'EMP_SYNC_01',
            'name'         => 'Original Name',
            'jobgrade'     => '2A',
            'jobposition'  => 'Junior Operator',
            'team'         => 'Team Alpha',
            'department'   => 'Dept Mechanical',
            'company_name' => 'PT Krakatau Posco',
        ]);

        $this->training = OfflineTraining::create([
            'course_name' => 'Boiler Maintenance',
            'batch'       => 'Batch 1',
            'type'        => 'In-House',
            'start_date'  => '2026-07-01',
            'finish_date' => '2026-07-02',
            'created_by'  => $this->user->id,
        ]);

        $this->participant = OfflineTrainingUser::create([
            'offline_training_id' => $this->training->id,
            'user_id'             => $this->user->id,
            'employee_id'         => 'EMP_SYNC_01',
            'name'                => 'Original Name',
            'grade'               => '2A',
            'job_position'        => 'Junior Operator',
            'team'                => 'Team Alpha',
            'department'          => 'Dept Mechanical',
            'company'             => 'PT Krakatau Posco',
            'learning_hours'      => 16.0,
            'progress'            => 100.0,
            'attendance'          => true,
            'pic'                 => 'PIC Maintenance',
        ]);
    }

    protected function createTestUser(array $attrs = []): User
    {
        static $seq = 6000;
        $seq++;
        return User::create(array_merge([
            'name'         => 'Sync User ' . $seq,
            'email'        => "syncuser_{$seq}@example.com",
            'password'     => bcrypt('secret'),
            'id_emp'       => 'EMP_S_' . $seq,
            'id_user'      => 'UID_S_' . $seq,
            'id_org_unit'  => 'OU_01',
            'team'         => 'Team Sync',
            'department'   => 'Dept Sync',
            'division'     => 'Division Sync',
            'sap_code'     => 'SAP_' . $seq,
            'ssohash'      => 'HASH_' . $seq,
            'role'         => User::ROLE_USER,
            'company_name' => 'PT Krakatau Posco',
        ], $attrs));
    }

    public function test_user_observer_synchronizes_grade_change_to_offline_records(): void
    {
        $this->user->jobgrade = '3A';
        $this->user->save();

        $this->participant->refresh();

        $this->assertEquals('3A', $this->participant->grade);
        $this->assertEquals('Original Name', $this->participant->name);
        $this->assertEquals('Boiler Maintenance', $this->participant->training->course_name);
        $this->assertEquals('PIC Maintenance', $this->participant->pic);
        $this->assertEquals(16.0, (float) $this->participant->learning_hours);
    }

    public function test_user_observer_synchronizes_department_and_position_changes(): void
    {
        $this->user->update([
            'department'  => 'Dept Electrical & Automation',
            'jobposition' => 'Senior Specialist',
            'team'        => 'Team Beta',
            'name'        => 'Updated Name',
        ]);

        $this->participant->refresh();

        $this->assertEquals('Dept Electrical & Automation', $this->participant->department);
        $this->assertEquals('Senior Specialist', $this->participant->job_position);
        $this->assertEquals('Team Beta', $this->participant->team);
        $this->assertEquals('Updated Name', $this->participant->name);
    }

    public function test_sync_service_directly_updates_records_belonging_to_user(): void
    {
        $service = app(EmployeeIdentitySyncService::class);

        $this->user->jobgrade = '4B';
        $this->user->department = 'Logistics Dept';

        $affected = $service->syncUserToOfflineRecords($this->user);

        $this->assertGreaterThanOrEqual(1, $affected);
        $this->participant->refresh();

        $this->assertEquals('4B', $this->participant->grade);
        $this->assertEquals('Logistics Dept', $this->participant->department);
    }

    public function test_employee_api_sync_path_triggers_offline_identity_sync(): void
    {
        $syncService = app(EmployeeUserSyncService::class);

        $apiPayload = [
            [
                'id_emp'     => 'EMP_SYNC_01',
                'name'       => 'Name Via Employee API',
                'email'      => $this->user->email,
                'department' => 'HR & Culture Dept',
                'team'       => 'People Development',
                'jobgrade'   => '5A',
                'jobposition'=> 'Manager',
            ]
        ];

        $result = $syncService->sync([
            'api_data' => $apiPayload,
            'dry_run'  => false,
        ]);

        $this->assertEquals(1, $result->updatedCount);

        $this->participant->refresh();
        $this->assertEquals('Name Via Employee API', $this->participant->name);
        $this->assertEquals('5A', $this->participant->grade);
        $this->assertEquals('Manager', $this->participant->job_position);
        $this->assertEquals('HR & Culture Dept', $this->participant->department);
        $this->assertEquals('People Development', $this->participant->team);
    }
}
