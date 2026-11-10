<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\OfflineTrainingCreateService;
use App\Services\Record\OfflineTrainingExcelSchema;
use Database\Seeders\OfflineTrainingOptionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OfflineTrainingMasterOptionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected ?User $regularUser;
    protected OfflineOptionService $optionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
        $this->optionService = app(OfflineOptionService::class);
    }

    /**
     * 1. Database Schema & Migration verification.
     */
    public function test_offline_training_options_table_schema_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('offline_training_options'));

        $expectedColumns = [
            'id',
            'option_type',
            'value',
            'normalized_value',
            'year',
            'scope_key',
            'parent_id',
            'is_active',
            'sort_order',
            'created_from',
            'created_by',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(
                Schema::hasColumn('offline_training_options', $col),
                "Missing expected column '{$col}' in offline_training_options table."
            );
        }

        // Verify corporate_kpi_year in offline_trainings
        $this->assertTrue(
            Schema::hasColumn('offline_trainings', 'corporate_kpi_year'),
            "Missing corporate_kpi_year column in offline_trainings table."
        );
    }

    /**
     * 2. MySQL Unique Key Design & Scope Key Isolation.
     */
    public function test_unique_constraint_enforces_timeless_and_year_scoped_rules(): void
    {
        $uniqueTimelessVal = 'UniqueTimelessTest_' . uniqid();
        $normTimeless = strtolower($uniqueTimelessVal);

        // First insert succeeds
        DB::table('offline_training_options')->insert([
            'option_type'      => 'type',
            'value'            => $uniqueTimelessVal,
            'normalized_value' => $normTimeless,
            'scope_key'        => 'global',
            'year'             => null,
            'is_active'        => 1,
            'created_from'     => 'test',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Duplicate timeless insert must throw QueryException (1062 Duplicate entry)
        $this->expectException(QueryException::class);
        DB::table('offline_training_options')->insert([
            'option_type'      => 'type',
            'value'            => $uniqueTimelessVal,
            'normalized_value' => $normTimeless,
            'scope_key'        => 'global',
            'year'             => null,
            'is_active'        => 1,
            'created_from'     => 'test',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    /**
     * 2b. Year-scoped options allow identical values across different years, but reject same year.
     */
    public function test_year_scoped_options_allow_same_value_in_different_years(): void
    {
        $uniqueKpiVal = 'Cost Reduction ' . uniqid();
        $normKpi = strtolower($uniqueKpiVal);

        // 2026 insert
        $opt2026Id = DB::table('offline_training_options')->insertGetId([
            'option_type'      => 'sub_corporate_kpi',
            'value'            => $uniqueKpiVal,
            'normalized_value' => $normKpi,
            'year'             => 2026,
            'scope_key'        => 'year:2026',
            'is_active'        => 1,
            'created_from'     => 'test',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        $this->assertGreaterThan(0, $opt2026Id);

        // 2027 insert with identical value must succeed because scope_key differs
        $opt2027Id = DB::table('offline_training_options')->insertGetId([
            'option_type'      => 'sub_corporate_kpi',
            'value'            => $uniqueKpiVal,
            'normalized_value' => $normKpi,
            'year'             => 2027,
            'scope_key'        => 'year:2027',
            'is_active'        => 1,
            'created_from'     => 'test',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
        $this->assertGreaterThan(0, $opt2027Id);

        // Duplicate insert in 2026 must fail
        $failed = false;
        try {
            DB::table('offline_training_options')->insert([
                'option_type'      => 'sub_corporate_kpi',
                'value'            => $uniqueKpiVal,
                'normalized_value' => $normKpi,
                'year'             => 2026,
                'scope_key'        => 'year:2026',
                'is_active'        => 1,
                'created_from'     => 'test',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (QueryException $e) {
            $failed = true;
        }
        $this->assertTrue($failed, 'Expected duplicate insert for same year to fail unique constraint.');
    }

    /**
     * 3. OfflineOptionService string normalization & placeholder rejection.
     */
    public function test_offline_option_service_normalization_and_placeholder_rejection(): void
    {
        // Normalization checks
        $this->assertEquals('Advanced Mechanical', OfflineOptionService::normalizeString('   Advanced   Mechanical   '));
        $this->assertEquals('advanced mechanical', OfflineOptionService::normalizeKey('   Advanced   Mechanical   '));

        // Placeholders must return null
        $placeholders = ['-', ' - ', '--', 'N/A', 'n/a', 'na', 'NULL', 'null', 'None', '', '   '];
        foreach ($placeholders as $ph) {
            $this->assertNull(
                $this->optionService->firstOrCreateOption('type', $ph),
                "Placeholder '{$ph}' was not rejected as null."
            );
        }

        // Whitelist enforcement: main_classification is strictly forbidden in offline_training_options
        $this->expectException(\InvalidArgumentException::class);
        $this->optionService->firstOrCreateOption('main_classification', 'Technical Engineering');
    }

    /**
     * 4. Seeder idempotency.
     */
    public function test_seeder_is_idempotent_and_does_not_duplicate(): void
    {
        $seeder = new OfflineTrainingOptionSeeder();

        $countBefore = DB::table('offline_training_options')->count();

        // Run once
        $seeder->run();
        $countAfterFirst = DB::table('offline_training_options')->count();

        // Run second time (must not crash and count must stay identical)
        $seeder->run();
        $countAfterSecond = DB::table('offline_training_options')->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    /**
     * 5. Options API Endpoints (Index and Store).
     */
    public function test_options_api_endpoints_permissions_and_behavior(): void
    {
        // 1. Guest redirected
        $guestRes = $this->getJson('/manage/record/offline/options?type=type');
        $this->assertTrue($guestRes->status() === 401 || $guestRes->isRedirect());

        // 2. Regular user forbidden (role 1)
        if ($this->regularUser) {
            $unauthRes = $this->actingAs($this->regularUser)->getJson('/manage/record/offline/options?type=type');
            $unauthRes->assertStatus(403);
        }

        // 3. Admin GET active options
        $getRes = $this->actingAs($this->admin)->getJson('/manage/record/offline/options?type=delivery_method');
        $getRes->assertStatus(200);
        $getRes->assertJsonStructure(['results' => [['id', 'text', 'value']]]);

        // 4. Admin POST create option
        $testVal = 'Custom Delivery ' . uniqid();
        $postRes = $this->actingAs($this->admin)->postJson('/manage/record/offline/options', [
            'option_type' => 'delivery_method',
            'value'       => $testVal,
        ]);
        $postRes->assertStatus(201);
        $postRes->assertJson([
            'success'     => true,
            'is_existing' => false,
            'data'        => [
                'option_type' => 'delivery_method',
                'value'       => $testVal,
                'scope_key'   => 'global',
            ],
        ]);

        // 5. Admin POST duplicate option (returns 200 is_existing: true)
        $dupRes = $this->actingAs($this->admin)->postJson('/manage/record/offline/options', [
            'option_type' => 'delivery_method',
            'value'       => '   ' . strtoupper($testVal) . '   ',
        ]);
        $dupRes->assertStatus(200);
        $dupRes->assertJson([
            'success'     => true,
            'is_existing' => true,
        ]);

        // 6. Admin POST invalid placeholder rejected with 422
        $phRes = $this->actingAs($this->admin)->postJson('/manage/record/offline/options', [
            'option_type' => 'delivery_method',
            'value'       => '-',
        ]);
        $phRes->assertStatus(422);
    }

    /**
     * 6. Dynamic Yearly Corporate KPI and Excel Schema Header Matching.
     */
    public function test_dynamic_yearly_corporate_kpi_and_excel_schema(): void
    {
        // 1-indexed canonical headers directly from the schema
        $columns = OfflineTrainingExcelSchema::columns();
        $foundHeaders2026 = [];
        foreach ($columns as $idx => $col) {
            $foundHeaders2026[$idx] = $col['header'];
        }

        $detected2026 = OfflineTrainingExcelSchema::analyzeHeaders($foundHeaders2026);
        $this->assertEquals('counted_corporate_kpi', $detected2026['columns'][17]['key']);
        $this->assertEquals('sub_corporate_kpi', $detected2026['columns'][18]['key']);
        $this->assertTrue(in_array($detected2026['columns'][17]['status'], ['VALID', 'AUTO_MATCHED']));
        $this->assertTrue(in_array($detected2026['columns'][18]['status'], ['VALID', 'AUTO_MATCHED']));

        // Test headers for future year 2027
        $foundHeaders2027 = $foundHeaders2026;
        $foundHeaders2027[17] = "Counted as '27 Corporate KPI (Yes/No)";
        $foundHeaders2027[18] = "Sub '27 Corporate KPI";

        $detected2027 = OfflineTrainingExcelSchema::analyzeHeaders($foundHeaders2027);
        $this->assertEquals('counted_corporate_kpi', $detected2027['columns'][17]['key']);
        $this->assertEquals('sub_corporate_kpi', $detected2027['columns'][18]['key']);
        $this->assertEquals('AUTO_MATCHED', $detected2027['columns'][17]['status']);
        $this->assertEquals('AUTO_MATCHED', $detected2027['columns'][18]['status']);

        // Test headers for 2028
        $foundHeaders2028 = $foundHeaders2026;
        $foundHeaders2028[17] = "Counted as '28 Corporate KPI";
        $foundHeaders2028[18] = "Sub '28 Corporate KPI";

        $detected2028 = OfflineTrainingExcelSchema::analyzeHeaders($foundHeaders2028);
        $this->assertEquals('counted_corporate_kpi', $detected2028['columns'][17]['key']);
        $this->assertEquals('sub_corporate_kpi', $detected2028['columns'][18]['key']);
        $this->assertEquals('AUTO_MATCHED', $detected2028['columns'][17]['status']);
        $this->assertEquals('AUTO_MATCHED', $detected2028['columns'][18]['status']);
    }

    /**
     * 7. Auto-Registration on Create & Corporate KPI Year derivation.
     */
    public function test_auto_registration_on_create_training(): void
    {
        $createService = app(OfflineTrainingCreateService::class);

        $uniqueCourse = 'AutoReg Course ' . uniqid();
        $uniqueCategory = 'AutoReg Category ' . uniqid();
        $uniqueSubKpi = 'KPI 2027 Target ' . uniqid();

        $payload = [
            'course_name'            => $uniqueCourse,
            'category'               => $uniqueCategory,
            'type'                   => 'In-House',
            'level'                  => 'Basic',
            'main_classification'    => 'Technical',
            'sub_classification'     => 'Electrical Sub',
            'counted_corporate_kpi'  => '1',
            'sub_corporate_kpi'      => $uniqueSubKpi,
            'delivery_method'        => 'Classroom',
            'sub_delivery_method'    => 'Hands-on Lab',
            'provider'               => 'Internal',
            'sub_provider'           => 'Vendor',
            'provider_name'          => 'PT Auto Test',
            'venue'                  => 'Auditorium Z',
            'instructor'             => 'Trainer Z',
            'duration'               => '08:00:00',
            'learning_hours'         => '8.0',
            'start_date'             => '2027-05-10',
            'finish_date'            => '2027-05-10',
            'participants'           => [
                [
                    'employee_id'            => 'EMP' . rand(1000, 9999),
                    'name'                   => 'Participant Test',
                    'learning_hours'         => 8.0,
                    'attendance'             => '1',
                    'progress'               => 100,
                    'certificate'            => 'Yes',
                    'bond_status'            => 'No',
                ]
            ],
        ];

        $training = $createService->create($payload, $this->admin->id);

        $this->assertInstanceOf(OfflineTraining::class, $training);
        $this->assertEquals(2027, $training->corporate_kpi_year);

        // Verify options were auto-registered
        $courseOpt = OfflineTrainingOption::ofType('course_name')->where('normalized_value', strtolower($uniqueCourse))->first();
        $this->assertNotNull($courseOpt);
        $this->assertEquals('manual', $courseOpt->created_from);

        $catOpt = OfflineTrainingOption::ofType('category')->where('normalized_value', strtolower($uniqueCategory))->first();
        $this->assertNotNull($catOpt);

        // Sub Corporate KPI must be scoped to year 2027
        $kpiOpt = OfflineTrainingOption::ofType('sub_corporate_kpi')->where('scope_key', 'year:2027')->where('normalized_value', strtolower($uniqueSubKpi))->first();
        $this->assertNotNull($kpiOpt);
        $this->assertEquals(2027, $kpiOpt->year);
    }

    /**
     * 8. Historical Inactive Option Preservation in Edit View.
     */
    public function test_historical_inactive_option_preserved_in_edit_view(): void
    {
        // Create an inactive type
        $inactiveType = 'Deprecated Type ' . uniqid();
        DB::table('offline_training_options')->insert([
            'option_type'      => 'type',
            'value'            => $inactiveType,
            'normalized_value' => strtolower($inactiveType),
            'scope_key'        => 'global',
            'is_active'        => 0, // Inactive!
            'created_from'     => 'test',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Create training using this inactive type
        $trainingId = DB::table('offline_trainings')->insertGetId([
            'course_name'            => 'Legacy Training Course',
            'type'                   => $inactiveType,
            'main_classification'    => 'Technical',
            'start_date'             => '2026-03-01',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $participantId = DB::table('offline_training_users')->insertGetId([
            'offline_training_id' => $trainingId,
            'employee_id'         => '10999',
            'name'                => 'Legacy Participant',
            'attendance'          => 1,
            'progress'            => 100,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Visit edit page as admin
        $editRes = $this->actingAs($this->admin)->get("/manage/record/offline/{$participantId}/edit");
        $editRes->assertStatus(200);

        // The inactive type must be present in the HTML and selected
        $editRes->assertSee($inactiveType);
    }
}
