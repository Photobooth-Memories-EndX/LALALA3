<?php

namespace Tests\Feature;

use App\Models\EmployeeSyncLog;
use App\Models\User;
use App\Services\EmployeeSync\EmployeeApiClient;
use App\Services\EmployeeSync\EmployeeUserSyncService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncEmployeeUsersTest extends TestCase
{
    use DatabaseTransactions;

    protected string $apiBaseUrl;
    protected string $apiFullUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiFullUrl = config('api.employee.full_url', 'http://172.21.23.73/employee-api/public/api/employees-full');
        $this->apiBaseUrl = config('api.employee.base_url', 'http://172.21.23.73/employee-api/public/api/employees/');
        // Ensure execution lock is clear before each test
        Cache::lock('owl:sync-employee-users:lock', 300)->forceRelease();
    }

    protected function tearDown(): void
    {
        Cache::lock('owl:sync-employee-users:lock', 300)->forceRelease();
        parent::tearDown();
    }

    protected function createMockUser(array $attributes = []): User
    {
        $uniqueNum = rand(100000, 999999);
        $user = new User();
        $user->forceFill(array_merge([
            'id_emp' => 'T' . $uniqueNum, // Max 7 chars <= 12 chars
            'name' => 'Original Name ' . $uniqueNum,
            'email' => 'user' . $uniqueNum . '@example.com',
            'password' => Hash::make('secret_initial_pass'),
            'team' => 'Old Team',
            'department' => 'Old Department',
            'division' => 'Old Division',
            'sap_code' => 'SAP1111',
            'id_org_unit' => 'OU01',
            'id_user' => '100',
            'ssohash' => 'old_hash_' . $uniqueNum,
            'role' => User::ROLE_ADMIN, // Role 2
            'active' => 1,
            'avatar' => 'avatar_' . $uniqueNum . '.png',
            'company_name' => 'PT Krakatau Posco',
        ], $attributes));
        $user->save();

        return $user;
    }

    /**
     * Skenario 1: id_emp baru terdeteksi sebagai kandidat user baru.
     */
    public function test_scenario_01_new_id_emp_detected_and_created_as_new_user()
    {
        $newEmpId = 'N' . rand(100000, 999999);
        $newEmail = 'new_' . $newEmpId . '@krakatauposco.co.id';

        $apiPayload = [
            [
                'id_user' => 999,
                'id_emp' => $newEmpId,
                'id_ou' => 'OU99',
                'name' => 'Budi Baru',
                'email' => $newEmail,
                'team' => 'New Tech Team',
                'department' => 'IT Dept',
                'division' => 'Digital Div',
                'sap_code' => 'SAP9999',
                'ssohash' => 'new_sso_hash',
                'resign' => 'N',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(1, $result->createdCount);
        $this->assertDatabaseHas('users', [
            'id_emp' => $newEmpId,
            'name' => 'Budi Baru',
            'email' => $newEmail,
            'team' => 'New Tech Team',
            'department' => 'IT Dept',
            'division' => 'Digital Div',
            'role' => User::ROLE_USER, // 1
            'active' => 0, // Safe default inactive
            'id_org_unit' => 'OU99',
        ]);

        $createdUser = User::where('id_emp', $newEmpId)->first();
        $this->assertTrue(Hash::check($newEmpId, $createdUser->password));
    }

    /**
     * Skenario 2: id_emp yang sudah ada tidak membuat duplikasi.
     */
    public function test_scenario_02_existing_id_emp_does_not_create_duplicate()
    {
        $existing = $this->createMockUser();
        $initialUserCount = User::where('id_emp', $existing->id_emp)->count();
        $this->assertEquals(1, $initialUserCount);

        $apiPayload = [
            [
                'id_emp' => $existing->id_emp,
                'name' => $existing->name,
                'email' => $existing->email,
                'team' => $existing->team,
                'department' => $existing->department,
                'division' => $existing->division,
                'sap_code' => $existing->sap_code,
                'id_ou' => $existing->id_org_unit,
                'resign' => 'N',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(0, $result->createdCount);
        $this->assertEquals(1, $result->unchangedCount);
        $this->assertEquals(1, User::where('id_emp', $existing->id_emp)->count());
    }

    /**
     * Skenario 3: Perubahan name disinkronkan.
     */
    public function test_scenario_03_name_change_is_synchronized()
    {
        $user = $this->createMockUser(['name' => 'Original Old Name']);

        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => 'Updated Brand New Name',
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(1, $result->updatedCount);
        $this->assertEquals(1, $result->updatedActiveCount);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Brand New Name',
        ]);
    }

    /**
     * Skenario 4: Perubahan email ditangani sesuai aturan unique.
     */
    public function test_scenario_04_email_change_handled_safely_with_unique_rules()
    {
        $userA = $this->createMockUser(['email' => 'user_a_' . uniqid() . '@example.com']);
        $userB = $this->createMockUser(['email' => 'user_b_' . uniqid() . '@example.com']);

        // Case 4A: User A tries to change email to User B's email (Conflict!)
        $conflictPayload = [
            [
                'id_emp' => $userA->id_emp,
                'name' => $userA->name,
                'email' => $userB->email, // Collision!
                'team' => $userA->team,
                'department' => $userA->department,
                'division' => $userA->division,
                'sap_code' => $userA->sap_code,
                'id_ou' => $userA->id_org_unit,
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $conflictPayload, 'dry_run' => false]);

        $this->assertEquals(1, $result->conflictCount);
        // User A's email should NOT have been changed to User B's
        $userA->refresh();
        $this->assertNotEquals($userB->email, $userA->email);

        // Case 4B: User A changes to a fresh unique email
        $freshEmail = 'fresh_' . uniqid() . '@example.com';
        $successPayload = [
            [
                'id_emp' => $userA->id_emp,
                'name' => $userA->name,
                'email' => $freshEmail,
                'team' => $userA->team,
                'department' => $userA->department,
                'division' => $userA->division,
                'sap_code' => $userA->sap_code,
                'id_ou' => $userA->id_org_unit,
            ]
        ];

        $resultSuccess = $service->sync(['api_data' => $successPayload, 'dry_run' => false]);
        $this->assertEquals(1, $resultSuccess->updatedCount);
        $userA->refresh();
        $this->assertEquals($freshEmail, $userA->email);
    }

    /**
     * Skenario 5: Perubahan division, department, team, dan sap_code disinkronkan.
     */
    public function test_scenario_05_division_department_team_sap_code_synchronized()
    {
        $user = $this->createMockUser([
            'team' => 'Old Team',
            'department' => 'Old Dept',
            'division' => 'Old Div',
            'sap_code' => 'SAP1111',
            'id_org_unit' => 'OU01',
        ]);

        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => $user->name,
                'email' => $user->email,
                'team' => 'Upgraded Team',
                'department' => 'Upgraded Dept',
                'division' => 'Upgraded Div',
                'sap_code' => 'SAP9999',
                'id_ou' => 'OU99',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(1, $result->updatedCount);
        $user->refresh();
        $this->assertEquals('Upgraded Team', $user->team);
        $this->assertEquals('Upgraded Dept', $user->department);
        $this->assertEquals('Upgraded Div', $user->division);
        $this->assertEquals('SAP9999', $user->sap_code);
        $this->assertEquals('OU99', $user->id_org_unit);
    }

    /**
     * Skenario 6: Field yang tidak dikelola API tidak berubah.
     */
    public function test_scenario_06_unmanaged_owl_fields_remain_intact()
    {
        $user = $this->createMockUser([
            'avatar' => 'my_custom_avatar.jpg',
            'company_name' => 'PT Krakatau Posco',
        ]);

        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => 'Changed Name',
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
                'avatar' => 'should_be_ignored.jpg',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $user->refresh();
        $this->assertEquals('my_custom_avatar.jpg', $user->avatar);
        $this->assertEquals('PT Krakatau Posco', $user->company_name);
    }

    /**
     * Skenario 7: password, role, active, dan atribut autentikasi tidak berubah tanpa aturan resmi.
     */
    public function test_scenario_07_password_role_and_active_never_overwritten()
    {
        $initialPasswordHash = Hash::make('my_secure_custom_password');
        $user = $this->createMockUser([
            'password' => $initialPasswordHash,
            'role' => User::ROLE_ADMIN, // 2
            'active' => 1,
        ]);

        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => 'New Name Here',
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
                'role' => 1,
                'active' => 0,
                'password' => 'new_pass_from_api',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $user->refresh();
        $this->assertEquals($initialPasswordHash, $user->password);
        $this->assertEquals(User::ROLE_ADMIN, (int) $user->role);
        $this->assertEquals(1, (int) $user->active);
    }

    /**
     * Skenario 8: Data API dengan id_emp kosong ditolak.
     */
    public function test_scenario_08_empty_id_emp_is_rejected()
    {
        $initialUserCount = User::count();

        $apiPayload = [
            [
                'id_emp' => '',
                'name' => 'Ghost Employee',
                'email' => 'ghost@example.com',
            ],
            [
                'id_emp' => '   ',
                'name' => 'Whitespace Employee',
                'email' => 'whitespace@example.com',
            ],
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(2, $result->conflictCount);
        $this->assertEquals(0, $result->createdCount);
        $this->assertEquals($initialUserCount, User::count());
    }

    /**
     * Skenario 9: Duplikasi id_emp pada respons API terdeteksi.
     */
    public function test_scenario_09_duplicate_id_emp_in_api_payload_detected_as_conflict()
    {
        $dupEmpId = 'D' . rand(100000, 999999);

        $apiPayload = [
            [
                'id_emp' => $dupEmpId,
                'name' => 'Duplicate Entry One',
                'email' => 'dup1@example.com',
            ],
            [
                'id_emp' => $dupEmpId,
                'name' => 'Duplicate Entry Two',
                'email' => 'dup2@example.com',
            ],
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertGreaterThanOrEqual(1, $result->conflictCount);
        $this->assertEquals(0, $result->createdCount);
        $this->assertDatabaseMissing('users', ['id_emp' => $dupEmpId]);
    }

    /**
     * Skenario 10: Duplikasi id_emp pada database ditangani aman.
     */
    public function test_scenario_10_duplicate_id_emp_in_database_handled_safely()
    {
        // Simulate two records with same id_emp in database (less than 12 chars)
        $dupId = 'DUP' . rand(10000, 99999);
        $user1 = $this->createMockUser(['id_emp' => $dupId, 'email' => 'u1_' . uniqid() . '@ex.com']);
        $user2 = new User();
        $user2->forceFill([
            'id_emp' => $dupId,
            'name' => 'Second Dup User',
            'email' => 'u2_' . uniqid() . '@ex.com',
            'password' => Hash::make('secret'),
            'team' => 'Team',
            'department' => 'Dept',
            'division' => 'Div',
            'sap_code' => 'SAP0',
            'ssohash' => 'hash',
        ]);
        $user2->save();

        $apiPayload = [
            [
                'id_emp' => $dupId,
                'name' => 'API Wants To Update Both',
                'email' => 'u1@ex.com',
                'team' => 'Team',
                'department' => 'Dept',
                'division' => 'Div',
                'sap_code' => 'SAP0',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertGreaterThanOrEqual(1, $result->conflictCount);
        $this->assertEquals(0, $result->updatedCount);
    }

    /**
     * Skenario 11: API timeout atau mengembalikan HTTP 500 tidak merusak data OWL.
     */
    public function test_scenario_11_api_500_or_timeout_does_not_corrupt_database()
    {
        $user = $this->createMockUser();
        $originalName = $user->name;

        Http::fake([
            $this->apiFullUrl . '*' => Http::response('Internal Server Error', 500),
            $this->apiBaseUrl . '*' => Http::response('Internal Server Error', 500),
        ]);

        $client = new EmployeeApiClient($this->apiFullUrl, $this->apiBaseUrl, 1, 0, 0);
        $service = new EmployeeUserSyncService($client);
        $result = $service->sync(['dry_run' => false]);

        $this->assertEquals('failed', $result->status);
        $user->refresh();
        $this->assertEquals($originalName, $user->name);
    }

    /**
     * Skenario 12: Respons JSON tidak valid ditangani aman.
     */
    public function test_scenario_12_malformed_json_response_handled_safely()
    {
        Http::fake([
            $this->apiFullUrl . '*' => Http::response('{invalid_json: true,,', 200),
            $this->apiBaseUrl . '*' => Http::response('{invalid_json: true,,', 200),
        ]);

        $client = new EmployeeApiClient($this->apiFullUrl, $this->apiBaseUrl, 1, 0, 0);
        $service = new EmployeeUserSyncService($client);
        $result = $service->sync(['dry_run' => false]);

        $this->assertEquals('failed', $result->status);
        $this->assertStringContainsString('JSON', $result->errorMessage);
    }

    /**
     * Skenario 13: Respons kosong tidak menonaktifkan atau menghapus user.
     */
    public function test_scenario_13_empty_api_response_does_not_delete_or_deactivate_users()
    {
        $user = $this->createMockUser(['active' => 1]);

        Http::fake([
            $this->apiFullUrl . '*' => Http::response('[]', 200),
            $this->apiBaseUrl . '*' => Http::response('[]', 200),
        ]);

        $client = new EmployeeApiClient($this->apiFullUrl, $this->apiBaseUrl, 1, 0, 0);
        $service = new EmployeeUserSyncService($client);
        $result = $service->sync(['dry_run' => false]);

        $this->assertEquals('failed', $result->status);
        $user->refresh();
        $this->assertEquals(1, (int) $user->active);
        $this->assertNull($user->deleted_at);
    }

    /**
     * Skenario 14: Dry-run tidak mengubah database dan zero-write ke log.
     */
    public function test_scenario_14_dry_run_mode_does_not_modify_database()
    {
        $user = $this->createMockUser(['name' => 'Original DryRun Name']);
        $newEmpId = 'D' . rand(100000, 999999);
        $initialLogCount = DB::table('employee_sync_logs')->count();

        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => 'Changed In DryRun Only',
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
            ],
            [
                'id_emp' => $newEmpId,
                'name' => 'New DryRun User',
                'email' => 'dry_' . $newEmpId . '@example.com',
                'team' => 'Team',
                'department' => 'Dept',
                'division' => 'Div',
                'sap_code' => 'SAP',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => true]);

        $this->assertTrue($result->isDryRun);
        $this->assertEquals(1, $result->updatedCount);
        $this->assertEquals(1, $result->createdCount);

        // Database should be completely unchanged (Zero-write)
        $user->refresh();
        $this->assertEquals('Original DryRun Name', $user->name);
        $this->assertDatabaseMissing('users', ['id_emp' => $newEmpId]);
        $this->assertEquals($initialLogCount, DB::table('employee_sync_logs')->count());
    }

    /**
     * Skenario 15: Proses yang dijalankan dua kali tidak menghasilkan duplikasi.
     */
    public function test_scenario_15_idempotent_execution_twice_produces_no_duplicates()
    {
        $empId = 'M' . rand(100000, 999999);
        $apiPayload = [
            [
                'id_emp' => $empId,
                'name' => 'Idempotent User',
                'email' => 'idem_' . $empId . '@krakatauposco.co.id',
                'team' => 'Team Alpha',
                'department' => 'Dept Alpha',
                'division' => 'Div Alpha',
                'sap_code' => 'SAPALPHA',
                'id_ou' => 'OU01',
            ]
        ];

        $service = new EmployeeUserSyncService();

        // Run 1: Should create user
        $result1 = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);
        $this->assertEquals(1, $result1->createdCount);

        // Run 2: Should find existing and report unchanged
        $result2 = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);
        $this->assertEquals(0, $result2->createdCount);
        $this->assertEquals(1, $result2->unchangedCount);

        // Verify only 1 record in DB
        $this->assertEquals(1, User::where('id_emp', $empId)->count());
    }

    /**
     * Skenario 16: Scheduler terdaftar pada pukul 06.00 dengan timezone Asia/Jakarta.
     */
    public function test_scenario_16_scheduler_is_registered_at_06_00_wib()
    {
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $syncEvent = $events->first(function (Event $event) {
            return str_contains($event->command, 'owl:sync-employee-users');
        });

        $this->assertNotNull($syncEvent, 'owl:sync-employee-users command is not registered in schedule.');
        $this->assertEquals('0 6 * * *', $syncEvent->expression);
        $this->assertEquals('Asia/Jakarta', $syncEvent->timezone);
    }

    /**
     * Skenario 17: Pembaruan user soft-deleted dihitung terpisah dan tidak di-restore.
     */
    public function test_scenario_17_soft_deleted_user_updates_counted_separately_and_not_restored()
    {
        $softDeletedUser = $this->createMockUser([
            'team' => 'Old Soft Team',
            'deleted_at' => Carbon::now()->subMonths(2),
        ]);
        $activeUser = $this->createMockUser([
            'team' => 'Old Active Team',
        ]);

        $apiPayload = [
            [
                'id_emp' => $softDeletedUser->id_emp,
                'name' => $softDeletedUser->name,
                'email' => $softDeletedUser->email,
                'team' => 'New Soft Team',
                'department' => $softDeletedUser->department,
                'division' => $softDeletedUser->division,
                'sap_code' => $softDeletedUser->sap_code,
                'id_ou' => $softDeletedUser->id_org_unit,
            ],
            [
                'id_emp' => $activeUser->id_emp,
                'name' => $activeUser->name,
                'email' => $activeUser->email,
                'team' => 'New Active Team',
                'department' => $activeUser->department,
                'division' => $activeUser->division,
                'sap_code' => $activeUser->sap_code,
                'id_ou' => $activeUser->id_org_unit,
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(2, $result->updatedCount);
        $this->assertEquals(1, $result->updatedActiveCount);
        $this->assertEquals(1, $result->updatedSoftDeletedCount);

        // Verify soft-deleted user updated in place without restoring
        $softDeletedUser->refresh();
        $this->assertEquals('New Soft Team', $softDeletedUser->team);
        $this->assertNotNull($softDeletedUser->deleted_at);

        // Verify active user updated
        $activeUser->refresh();
        $this->assertEquals('New Active Team', $activeUser->team);
        $this->assertNull($activeUser->deleted_at);
    }

    /**
     * Skenario 18: User soft-deleted tanpa perubahan tidak dihitung sebagai update.
     */
    public function test_scenario_18_soft_deleted_user_without_changes_is_unchanged()
    {
        $softDeletedUser = $this->createMockUser([
            'deleted_at' => Carbon::now()->subMonths(1),
        ]);

        $apiPayload = [
            [
                'id_emp' => $softDeletedUser->id_emp,
                'name' => $softDeletedUser->name,
                'email' => $softDeletedUser->email,
                'team' => $softDeletedUser->team,
                'department' => $softDeletedUser->department,
                'division' => $softDeletedUser->division,
                'sap_code' => $softDeletedUser->sap_code,
                'id_ou' => $softDeletedUser->id_org_unit,
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $this->assertEquals(0, $result->updatedCount);
        $this->assertEquals(0, $result->updatedSoftDeletedCount);
        $this->assertEquals(1, $result->unchangedCount);
        $this->assertNotNull($softDeletedUser->fresh()->deleted_at);
    }

    /**
     * Skenario 19: Dry-run zero-write total (tidak ada penulisan log).
     */
    public function test_scenario_19_dry_run_zero_write_persists_no_sync_logs()
    {
        $initialLogCount = DB::table('employee_sync_logs')->count();
        $user = $this->createMockUser();

        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => 'Different DryRun Name',
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => true]);

        $this->assertTrue($result->isDryRun);
        $this->assertEquals(1, $result->updatedCount);
        $this->assertEquals($initialLogCount, DB::table('employee_sync_logs')->count());
    }

    /**
     * Skenario 20: Concurrency lock mencegah eksekusi ganda bersamaan.
     */
    public function test_scenario_20_command_concurrency_lock_prevents_simultaneous_execution()
    {
        $lockKey = 'owl:sync-employee-users:lock';
        $lock = Cache::lock($lockKey, 300);
        $this->assertTrue($lock->get(), 'Failed to acquire test lock');

        // While lock is held, calling the artisan command should abort with failure exit code
        $exitCode = Artisan::call('owl:sync-employee-users', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Another sync process is currently running', $output);

        // Release lock
        $lock->release();

        // Fake API response for live command run
        Http::fake([
            $this->apiFullUrl . '*' => Http::response([
                [
                    'id_emp' => 'CMD123',
                    'name' => 'Command User',
                    'email' => 'cmd@krakatauposco.co.id',
                    'team' => 'Team',
                    'department' => 'Dept',
                    'division' => 'Div',
                ]
            ], 200),
            $this->apiBaseUrl . '*' => Http::response([], 200),
        ]);

        // Now calling the command should succeed
        $exitCodeAfterRelease = Artisan::call('owl:sync-employee-users', ['--dry-run' => true, '--limit' => 1]);
        $this->assertContains($exitCodeAfterRelease, [Command::SUCCESS, 0]);
    }

    /**
     * Skenario 21: Nilai ssohash kosong dari API tidak menimpa nilai lama dan nilai hash disamarkan di log.
     */
    public function test_scenario_21_ssohash_empty_from_api_does_not_overwrite_and_hash_is_redacted_in_logs()
    {
        $existingHash = 'my_secret_sha1_hash_' . rand(1000, 9999);
        $user = $this->createMockUser([
            'ssohash' => $existingHash,
        ]);

        // API sends '-' or '' for ssohash/newhash
        $apiPayload = [
            [
                'id_emp' => $user->id_emp,
                'name' => 'Name Update',
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
                'ssohash' => '-', // Dummy / empty
                'newhash' => '',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);

        $user->refresh();
        $this->assertEquals($existingHash, $user->ssohash);

        // Now test when valid hash changes, ensure hash is redacted in updatedDetails
        $newValidHash = 'new_brand_sha1_hash_' . rand(1000, 9999);
        $apiPayloadValid = [
            [
                'id_emp' => $user->id_emp,
                'name' => $user->name,
                'email' => $user->email,
                'team' => $user->team,
                'department' => $user->department,
                'division' => $user->division,
                'sap_code' => $user->sap_code,
                'id_ou' => $user->id_org_unit,
                'ssohash' => $newValidHash,
            ]
        ];

        $resultHash = $service->sync(['api_data' => $apiPayloadValid, 'dry_run' => false]);
        $user->refresh();
        $this->assertEquals($newValidHash, $user->ssohash);

        // Check that updatedDetails does not expose raw hash
        $updatedItem = collect($resultHash->updatedDetails)->firstWhere('id_emp', $user->id_emp);
        $this->assertNotNull($updatedItem);
        $this->assertEquals('[REDACTED]', $updatedItem['changes']['ssohash']['from']);
        $this->assertEquals('[REDACTED]', $updatedItem['changes']['ssohash']['to']);
    }

    /**
     * Skenario 22: Semua 18 kolom profil HR baru tersimpan saat pembuatan user baru dan tersinkronisasi saat perubahan.
     */
    public function test_scenario_22_extended_hr_fields_are_provisioned_and_synchronized()
    {
        $newEmpId = 'H' . rand(100000, 999999);
        $newEmail = 'hr_' . $newEmpId . '@krakatauposco.co.id';

        $apiPayloadNew = [
            [
                'id_user' => 777,
                'id_emp' => $newEmpId,
                'id_ou' => 'OU55',
                'name' => 'HR Test User',
                'email' => $newEmail,
                'team' => 'Automation Team',
                'department' => 'HR Dept',
                'division' => 'People Div',
                'sap_code' => 'SAP8888',
                'ssohash' => 'hash_test_123',
                // 18 Extended HR Fields
                'id_team' => 'TM01',
                'id_department' => 'DP02',
                'id_division' => 'DV03',
                'cell_phone' => '081234567890',
                'sex' => 'M',
                'orgunit' => 'Digital Transformation Org',
                'id_job_position' => 'POS10',
                'jobposition' => 'Software Engineer',
                'positionID' => '00050123',
                'appr1' => '00010001',
                'appr2' => '00010002',
                'resign' => 'N',
                'flag' => 1,
                'jobgrade' => 'JG04',
                'id_job_clan' => 'JC01',
                'job_clan' => 'Engineering Clan',
                'id_job_family' => 'JF02',
                'job_family' => 'IT & Systems Family',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $resultNew = $service->sync(['api_data' => $apiPayloadNew, 'dry_run' => false]);

        $this->assertEquals(1, $resultNew->createdCount);

        // Verify all 18 HR fields in database
        $this->assertDatabaseHas('users', [
            'id_emp' => $newEmpId,
            'id_team' => 'TM01',
            'id_department' => 'DP02',
            'id_division' => 'DV03',
            'cell_phone' => '081234567890',
            'sex' => 'M',
            'orgunit' => 'Digital Transformation Org',
            'id_job_position' => 'POS10',
            'jobposition' => 'Software Engineer',
            'positionID' => '00050123',
            'appr1' => '00010001',
            'appr2' => '00010002',
            'resign' => 'N',
            'flag' => 1,
            'jobgrade' => 'JG04',
            'id_job_clan' => 'JC01',
            'job_clan' => 'Engineering Clan',
            'id_job_family' => 'JF02',
            'job_family' => 'IT & Systems Family',
        ]);

        // Test update on existing user HR fields
        $apiPayloadUpdate = [
            [
                'id_emp' => $newEmpId,
                'name' => 'HR Test User',
                'email' => $newEmail,
                'team' => 'Automation Team',
                'department' => 'HR Dept',
                'division' => 'People Div',
                'sap_code' => 'SAP8888',
                'id_ou' => 'OU55',
                'ssohash' => 'hash_test_123',
                // Changes in HR fields
                'id_team' => 'TM02',
                'cell_phone' => '089876543210',
                'jobposition' => 'Senior Lead Engineer',
                'jobgrade' => 'JG05',
                'flag' => 0,
            ]
        ];

        $resultUpdate = $service->sync(['api_data' => $apiPayloadUpdate, 'dry_run' => false]);
        $this->assertEquals(1, $resultUpdate->updatedCount);

        $this->assertDatabaseHas('users', [
            'id_emp' => $newEmpId,
            'id_team' => 'TM02',
            'cell_phone' => '089876543210',
            'jobposition' => 'Senior Lead Engineer',
            'jobgrade' => 'JG05',
            'flag' => 0,
            // Untouched extended fields remain intact
            'job_clan' => 'Engineering Clan',
            'job_family' => 'IT & Systems Family',
            'appr1' => '00010001',
        ]);

        // Test safe non-destruction: Empty API string does not overwrite existing value
        $apiPayloadEmptySafe = [
            [
                'id_emp' => $newEmpId,
                'name' => 'HR Test User',
                'email' => $newEmail,
                'team' => 'Automation Team',
                'department' => 'HR Dept',
                'division' => 'People Div',
                'sap_code' => 'SAP8888',
                'id_ou' => 'OU55',
                'ssohash' => 'hash_test_123',
                'cell_phone' => '', // API sends empty string
            ]
        ];

        $resultEmpty = $service->sync(['api_data' => $apiPayloadEmptySafe, 'dry_run' => false]);
        $this->assertEquals(1, $resultEmpty->unchangedCount);

        $createdUser = User::where('id_emp', $newEmpId)->first();
        $this->assertEquals('089876543210', $createdUser->cell_phone);
    }

    /**
     * Skenario 23: API Client mendukung pagination otomatis dan menggabungkan semua halaman data.
     */
    public function test_scenario_23_pagination_support_fetches_all_pages()
    {
        $page1Url = $this->apiFullUrl;
        $page2Url = $this->apiFullUrl . '?page=2';

        Http::fake([
            $page1Url => Http::response([
                'current_page' => 1,
                'last_page' => 2,
                'next_page_url' => $page2Url,
                'data' => [
                    ['id_emp' => 'PAG001', 'name' => 'Page One Employee', 'email' => 'p1@ex.com'],
                    ['id_emp' => 'PAG002', 'name' => 'Page One Second Employee', 'email' => 'p2@ex.com'],
                ],
            ], 200),
            $page2Url => Http::response([
                'current_page' => 2,
                'last_page' => 2,
                'next_page_url' => null,
                'data' => [
                    ['id_emp' => 'PAG003', 'name' => 'Page Two Employee', 'email' => 'p3@ex.com'],
                ],
            ], 200),
        ]);

        $client = new EmployeeApiClient($this->apiFullUrl, $this->apiBaseUrl, 2, 0, 0);
        $employees = $client->fetchAllEmployees();

        $this->assertCount(3, $employees);
        $this->assertEquals('PAG001', $employees[0]['id_emp']);
        $this->assertEquals('PAG002', $employees[1]['id_emp']);
        $this->assertEquals('PAG003', $employees[2]['id_emp']);
    }

    /**
     * Skenario 24: Endpoint detail single employee (fetchEmployeeById) tetap menggunakan endpoint lama.
     */
    public function test_scenario_24_single_employee_detail_endpoint()
    {
        $testEmpId = 'DET999';
        $detailEndpoint = rtrim($this->apiBaseUrl, '/') . '/' . $testEmpId;

        Http::fake([
            $detailEndpoint => Http::response([
                'id_emp' => $testEmpId,
                'name' => 'Detailed Employee Single',
                'email' => 'single@krakatauposco.co.id',
                'team' => 'Detail Team',
                'department' => 'Detail Dept',
                'division' => 'Detail Div',
                'sap_code' => 'SAPDETAIL',
                'id_ou' => 'OU99',
            ], 200),
            rtrim($this->apiBaseUrl, '/') . '/NOTFOUND' => Http::response(['message' => 'Not found'], 404),
        ]);

        $client = new EmployeeApiClient($this->apiFullUrl, $this->apiBaseUrl, 2, 0, 0);

        // Found employee
        $detail = $client->fetchEmployeeById($testEmpId);
        $this->assertNotNull($detail);
        $this->assertEquals('Detailed Employee Single', $detail['name']);
        $this->assertEquals($testEmpId, $detail['id_emp']);

        // 404 employee returns null
        $notFound = $client->fetchEmployeeById('NOTFOUND');
        $this->assertNull($notFound);

        // Empty string returns null
        $this->assertNull($client->fetchEmployeeById(''));
    }

    /**
     * Skenario 25: Employee baru disinkronkan otomatis tampil di halaman Manage Role dan Live Search.
     */
    public function test_scenario_25_manage_role_page_reflects_newly_synced_employees_and_changes()
    {
        // 1. Create a superadmin user to authenticate for /manage/role
        $superAdmin = $this->createMockUser([
            'role' => User::ROLE_SUPER_ADMIN, // 3
            'email' => 'superadmin_' . uniqid() . '@krakatauposco.co.id',
        ]);

        // 2. Sync a new employee into OWL
        $uniqueEmpId = 'MR' . rand(10000, 99999);
        $uniqueName = 'ManageRole Sync User ' . $uniqueEmpId;
        $uniqueDiv = 'Special Division ' . $uniqueEmpId;
        $uniqueDept = 'Special Dept ' . $uniqueEmpId;

        $apiPayload = [
            [
                'id_emp' => $uniqueEmpId,
                'name' => $uniqueName,
                'email' => 'mr_' . $uniqueEmpId . '@krakatauposco.co.id',
                'division' => $uniqueDiv,
                'department' => $uniqueDept,
                'team' => 'Special Team',
                'sap_code' => 'SAPMR1',
                'id_ou' => 'OU_MR',
            ]
        ];

        $service = new EmployeeUserSyncService();
        $result = $service->sync(['api_data' => $apiPayload, 'dry_run' => false]);
        $this->assertEquals(1, $result->createdCount);

        // Verify newly synced user exists in database
        $this->assertDatabaseHas('users', [
            'id_emp' => $uniqueEmpId,
            'name' => $uniqueName,
            'division' => $uniqueDiv,
            'role' => User::ROLE_USER, // 1
        ]);

        // 3. Test HTTP request to /manage/role as Superadmin
        $response = $this->actingAs($superAdmin)->get(route('manage.role.index', ['q' => $uniqueEmpId]));
        $response->assertStatus(200);
        $response->assertSee($uniqueEmpId);
        $response->assertSee($uniqueName);

        // 4. Test Autocomplete / Live Search endpoint
        $liveSearchResponse = $this->actingAs($superAdmin)->get(route('manage.role.live-search', ['q' => $uniqueEmpId]));
        $liveSearchResponse->assertStatus(200);
        $liveSearchResponse->assertJsonFragment([
            'id_emp' => $uniqueEmpId,
            'name' => $uniqueName,
        ]);

        // 5. Verify Superadmin role was NOT affected by synchronization
        $superAdmin->refresh();
        $this->assertEquals(User::ROLE_SUPER_ADMIN, (int) $superAdmin->role);
    }
}
