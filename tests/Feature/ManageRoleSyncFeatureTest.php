<?php

namespace Tests\Feature;

use App\Models\EmployeeSyncLog;
use App\Models\User;
use App\Services\EmployeeSync\EmployeeApiClient;
use App\Services\EmployeeSync\EmployeeApiException;
use App\Services\EmployeeSync\EmployeeUserSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManageRoleSyncFeatureTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private User $regularUser;
    private User $resignedUser;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::lock('owl:sync-employee-users:lock', 300)->forceRelease();
        Cache::forget(EmployeeUserSyncService::PROGRESS_CACHE_KEY);

        $this->superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'name' => 'Super Admin Tester',
            'email' => 'superadmin_' . Str::random(8) . '@example.com',
            'id_emp' => 'SA' . rand(10000, 99999),
        ]);

        $this->regularUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Existing Active Employee',
            'email' => 'active_' . Str::random(8) . '@example.com',
            'id_emp' => 'EMP' . rand(10000, 99999),
            'company_name' => 'PT Krakatau Posco',
            'team' => 'Old Team',
            'department' => 'Old Department',
            'division' => 'Old Division',
            'jobposition' => 'Staff',
            'jobgrade' => 'G3',
            'active' => 1,
            'password' => Hash::make('secret_password_123'),
        ]);

        $this->resignedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'name' => 'Resigned Employee',
            'email' => 'resigned_' . Str::random(8) . '@example.com',
            'id_emp' => 'RES' . rand(10000, 99999),
            'company_name' => 'PT Krakatau Posco',
            'team' => 'Former Team',
            'department' => 'Former Department',
            'division' => 'Former Division',
            'deleted_at' => now()->subMonths(3),
        ]);
    }

    protected function tearDown(): void
    {
        Cache::lock('owl:sync-employee-users:lock', 300)->forceRelease();
        Cache::forget(EmployeeUserSyncService::PROGRESS_CACHE_KEY);

        parent::tearDown();
    }

    /**
     * Test: Dry-Run is 100% ZERO-WRITE.
     */
    public function test_dry_run_is_100_percent_zero_write(): void
    {
        $initialUserCount = User::withTrashed()->count();
        $initialLogCount = EmployeeSyncLog::count();
        $oldPassword = $this->regularUser->password;

        $newEmpId = 'NEW' . rand(10000, 99999);
        $dataset = [
            [
                'id_emp' => $this->regularUser->id_emp,
                'name' => 'Modified Name In Dry Run',
                'email' => $this->regularUser->email,
                'team' => 'New Dry Run Team',
                'department' => 'New Dry Run Dept',
                'division' => 'New Dry Run Div',
                'jobposition' => 'Senior Staff',
                'jobgrade' => 'G4',
            ],
            [
                'id_emp' => $newEmpId,
                'name' => 'Candidate New Employee',
                'email' => 'candidate_' . Str::random(8) . '@example.com',
                'team' => 'IT',
                'department' => 'Software',
                'division' => 'Technology',
            ],
        ];

        $syncService = new EmployeeUserSyncService();
        $result = $syncService->sync([
            'dry_run' => true,
            'api_data' => $dataset,
        ]);

        // Assert zero write
        $this->assertTrue($result->isDryRun);
        $this->assertEquals(1, $result->createdCount);
        $this->assertEquals(1, $result->updatedCount);
        $this->assertEquals($initialUserCount, User::withTrashed()->count(), 'User count must not change in Dry-Run.');
        $this->assertEquals($initialLogCount, EmployeeSyncLog::count(), 'EmployeeSyncLog must not be created in Dry-Run.');

        // Assert database records remain unchanged
        $this->regularUser->refresh();
        $this->assertEquals('Existing Active Employee', $this->regularUser->name);
        $this->assertEquals('Old Team', $this->regularUser->team);
        $this->assertEquals($oldPassword, $this->regularUser->password);
        $this->assertNull(User::withTrashed()->where('id_emp', $newEmpId)->first());
    }

    /**
     * Test: Live Sync creates new employees and updates existing employees with 18 HR columns.
     */
    public function test_live_sync_provisions_new_users_and_updates_hr_columns(): void
    {
        $newEmpId = 'NEW' . rand(10000, 99999);
        $dataset = [
            [
                'id_emp' => $this->regularUser->id_emp,
                'name' => 'Updated Active Name',
                'email' => $this->regularUser->email,
                'team' => 'Updated Team Alpha',
                'department' => 'Updated Dept Beta',
                'division' => 'Updated Div Gamma',
                'sap_code' => 'SAP-999',
                'id_ou' => 'OU-88',
                'id_team' => 'T-01',
                'id_department' => 'D-02',
                'id_division' => 'DIV-03',
                'jobgrade' => 'G5',
                'jobposition' => 'Lead Engineer',
                'positionID' => 'POS-1234',
                'appr1' => 'APPR-A',
                'appr2' => 'APPR-B',
                'orgunit' => 'Engineering Unit',
                'id_job_position' => 'JP-55',
            ],
            [
                'id_emp' => $newEmpId,
                'name' => 'Brand New Hire',
                'email' => 'newhire_' . Str::random(8) . '@example.com',
                'team' => 'Operations',
                'department' => 'Plant',
                'division' => 'Production',
                'sap_code' => 'SAP-NEW',
                'id_ou' => 'OU-10',
                'id_team' => 'T-10',
                'id_department' => 'D-20',
                'id_division' => 'DIV-30',
                'jobgrade' => 'G1',
                'jobposition' => 'Junior Operator',
                'positionID' => 'POS-900',
                'appr1' => 'MGR-1',
                'appr2' => 'DIR-1',
                'orgunit' => 'Plant Unit',
                'id_job_position' => 'JP-10',
            ],
        ];

        $syncService = new EmployeeUserSyncService();
        $result = $syncService->sync([
            'dry_run' => false,
            'api_data' => $dataset,
        ]);

        $this->assertFalse($result->isDryRun);
        $this->assertEquals(1, $result->createdCount);
        $this->assertEquals(1, $result->updatedCount);

        // Verify existing user updated
        $this->regularUser->refresh();
        $this->assertEquals('Updated Active Name', $this->regularUser->name);
        $this->assertEquals('Updated Team Alpha', $this->regularUser->team);
        $this->assertEquals('Updated Dept Beta', $this->regularUser->department);
        $this->assertEquals('Updated Div Gamma', $this->regularUser->division);
        $this->assertEquals('SAP-999', $this->regularUser->sap_code);
        $this->assertEquals('OU-88', $this->regularUser->id_org_unit);
        $this->assertEquals('G5', $this->regularUser->jobgrade);
        $this->assertEquals('Lead Engineer', $this->regularUser->jobposition);
        $this->assertEquals('POS-1234', $this->regularUser->positionID);
        $this->assertEquals('APPR-A', $this->regularUser->appr1);
        $this->assertEquals('APPR-B', $this->regularUser->appr2);

        // Verify newly created user
        $newUser = User::where('id_emp', $newEmpId)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Brand New Hire', $newUser->name);
        $this->assertEquals(User::ROLE_USER, (int) $newUser->role);
        $this->assertEquals(0, (int) $newUser->active);
        $this->assertTrue(Hash::check($newEmpId, $newUser->password));
        $this->assertEquals('Operations', $newUser->team);
        $this->assertEquals('G1', $newUser->jobgrade);
        $this->assertEquals('Junior Operator', $newUser->jobposition);
    }

    /**
     * Test: Credentials (Password, Role, Active Status) are strictly protected on existing users.
     */
    public function test_credentials_and_roles_are_protected_for_existing_users(): void
    {
        $oldPassword = $this->regularUser->password;
        $oldRole = $this->regularUser->role;
        $oldActive = $this->regularUser->active;

        $dataset = [
            [
                'id_emp' => $this->regularUser->id_emp,
                'name' => 'Updated Name',
                'email' => $this->regularUser->email,
                'role' => 3, // Malicious / invalid attempt in API payload
                'password' => 'malicious_pass',
                'active' => 0,
            ],
        ];

        $syncService = new EmployeeUserSyncService();
        $syncService->sync([
            'dry_run' => false,
            'api_data' => $dataset,
        ]);

        $this->regularUser->refresh();
        $this->assertEquals($oldPassword, $this->regularUser->password, 'Password must never be changed by sync.');
        $this->assertEquals($oldRole, $this->regularUser->role, 'Role must never be changed by sync.');
        $this->assertEquals($oldActive, $this->regularUser->active, 'Active status must never be changed by sync.');
    }

    /**
     * Test: Soft-deleted (resigned) employee is updated with latest HR data but NOT restored.
     */
    public function test_soft_deleted_user_is_updated_without_being_restored(): void
    {
        $resignedDeletedAt = $this->resignedUser->deleted_at;
        $this->assertNotNull($resignedDeletedAt);

        $dataset = [
            [
                'id_emp' => $this->resignedUser->id_emp,
                'name' => 'Resigned Employee Name Updated',
                'email' => $this->resignedUser->email,
                'team' => 'Archived Team',
                'department' => 'Archived Dept',
                'division' => 'Archived Div',
                'jobposition' => 'Former Specialist',
            ],
        ];

        $syncService = new EmployeeUserSyncService();
        $result = $syncService->sync([
            'dry_run' => false,
            'api_data' => $dataset,
        ]);

        $this->assertEquals(1, $result->updatedSoftDeletedCount);

        $this->resignedUser->refresh();
        $this->assertTrue($this->resignedUser->trashed(), 'Resigned user must remain soft-deleted.');
        $this->assertEquals($resignedDeletedAt->toDateTimeString(), $this->resignedUser->deleted_at->toDateTimeString());
        $this->assertEquals('Resigned Employee Name Updated', $this->resignedUser->name);
        $this->assertEquals('Archived Team', $this->resignedUser->team);
    }

    /**
     * Test: Concurrency lock prevents overlapping synchronization runs.
     */
    public function test_concurrency_lock_prevents_simultaneous_sync(): void
    {
        $lockKey = 'owl:sync-employee-users:lock';
        $lock = Cache::lock($lockKey, 60);
        $this->assertTrue($lock->get());

        try {
            $response = $this->actingAs($this->superAdmin)
                ->postJson(route('manage.role.sync.start'), ['dry_run' => true]);

            $response->assertStatus(409);
            $response->assertJson([
                'success' => false,
                'is_running' => true,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Test: Stale concurrency lock older than 5 minutes is automatically recovered.
     */
    public function test_stale_lock_recovery_allows_new_sync(): void
    {
        $lockKey = 'owl:sync-employee-users:lock';
        $lock = Cache::lock($lockKey, 300);
        $this->assertTrue($lock->get());

        // Set progress as started 6 minutes ago (stale run)
        EmployeeUserSyncService::updateProgress('processing', 10, 4621, 'Stale progress...', [
            'started_at' => now()->subMinutes(6)->toISOString(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('manage.role.sync.start'), ['dry_run' => true]);

        // Should recover and return success 200
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_dry_run' => true,
        ]);
    }

    /**
     * Test: Progress cache reports accurate metrics and stage progression.
     */
    public function test_progress_cache_reports_comprehensive_metrics(): void
    {
        EmployeeUserSyncService::resetProgress(true);

        $progress1 = EmployeeUserSyncService::getProgress();
        $this->assertEquals('preparing', $progress1['status']);
        $this->assertEquals(0, $progress1['percentage']);
        $this->assertTrue($progress1['is_dry_run']);
        $this->assertTrue($progress1['is_running']);

        EmployeeUserSyncService::updateProgress('validating', 0, 4621, 'Validating database...');
        $progress2 = EmployeeUserSyncService::getProgress();
        $this->assertEquals('validating', $progress2['status']);
        $this->assertEquals(25, $progress2['percentage']);

        EmployeeUserSyncService::updateProgress('completed', 4621, 4621, 'Sync completed', [
            'percentage' => 100,
            'total_api' => 4621,
            'total_owl' => 3439,
            'created' => 1182,
            'updated' => 3399,
            'updated_active_count' => 3200,
            'updated_soft_deleted_count' => 199,
            'unchanged_count' => 40,
            'conflict_count' => 0,
            'failed' => 0,
            'is_dry_run' => true,
        ]);

        $progress3 = EmployeeUserSyncService::getProgress();
        $this->assertEquals('completed', $progress3['status']);
        $this->assertEquals(100, $progress3['percentage']);
        $this->assertFalse($progress3['is_running']);
        $this->assertEquals(4621, $progress3['total_api']);
        $this->assertEquals(3439, $progress3['total_owl']);
        $this->assertEquals(1182, $progress3['created']);
        $this->assertEquals(3399, $progress3['updated']);
        $this->assertEquals(3200, $progress3['updated_active_count']);
        $this->assertEquals(199, $progress3['updated_soft_deleted_count']);
        $this->assertEquals(40, $progress3['unchanged_count']);

        // Test syncProgress endpoint returns all fields
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('manage.role.sync.progress'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'completed',
            'percentage' => 100,
            'total_api' => 4621,
            'total_owl' => 3439,
            'created' => 1182,
            'updated' => 3399,
            'updated_active_count' => 3200,
            'updated_soft_deleted_count' => 199,
            'unchanged_count' => 40,
        ]);
    }
}
