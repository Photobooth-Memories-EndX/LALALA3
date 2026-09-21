<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RecordOfflineFoundationTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // In OWL LMS: role 2 is Admin (has record_management), role 1 is Regular User
        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    /**
     * 1. Test offline page requires permission and renders shell.
     */
    public function test_offline_page_requires_permission_and_renders_shell(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/offline');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->get('/manage/record/offline');
        $response->assertStatus(200);
        $response->assertViewIs('main.record.offline');
        $response->assertSee('Non KP OWL');
        $response->assertSee('Offline Training Record');
        $response->assertSee('offline-kpi-summary-container', false);
        $response->assertSee('offline-learning-table', false);
        $response->assertSee('offlineTrainingDetailModal', false);
    }

    /**
     * 2. Test summary endpoint requires permission and returns valid contract.
     */
    public function test_offline_summary_endpoint_requires_permission_and_returns_valid_contract(): void
    {
        // Guest redirected
        $guestResponse = $this->get('/manage/record/offline/ajax/summary');
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->getStatusCode() === 401);

        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->get('/manage/record/offline/ajax/summary');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/ajax/summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'total_trainings',
            'total_participants',
            'total_learning_hours',
            'completion_rate',
            'data',
            'html',
        ]);
    }

    /**
     * 3. Test datatable endpoint requires permission and returns valid DataTables JSON.
     */
    public function test_offline_datatable_endpoint_requires_permission_and_returns_valid_structure(): void
    {
        // Regular user forbidden
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->getJson('/manage/record/offline/datatable');
            $unauthResponse->assertStatus(403);
        }

        // Admin success
        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/datatable?draw=1');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    /**
     * 4. Test detail endpoint requires permission and handles non-existent record gracefully.
     */
    public function test_offline_detail_endpoint_requires_permission(): void
    {
        if ($this->regularUser) {
            $unauthResponse = $this->actingAs($this->regularUser)->getJson('/manage/record/offline/999999/detail');
            $unauthResponse->assertStatus(403);
        }

        $response = $this->actingAs($this->admin)->getJson('/manage/record/offline/999999/detail');
        $this->assertTrue(in_array($response->getStatusCode(), [200, 404], true));
    }

    /**
     * 5. Test migration file exists and strictly contains all 44 Excel aligned columns.
     */
    public function test_migration_file_contains_all_44_excel_columns_and_no_legacy_columns(): void
    {
        $migrationFile = database_path('migrations/2026_09_21_000001_create_offline_trainings_and_users_tables.php');
        $this->assertTrue(File::exists($migrationFile), 'Migration file must exist on disk.');

        $content = File::get($migrationFile);

        // Check offline_trainings table columns (1:1 with Excel)
        $this->assertStringContainsString("Schema::create('offline_trainings'", $content);
        $expectedTrainingCols = [
            'course_name',
            'sub_course_name',
            'type',
            'category',
            'level',
            'batch',
            'main_classification',
            'sub_classification',
            'counted_corporate_kpi',
            'sub_corporate_kpi',
            'delivery_method',
            'sub_delivery_method',
            'provider',
            'sub_provider',
            'provider_name',
            'venue',
            'instructor',
            'duration_seconds',
            'learning_hours',
            'start_date',
            'finish_date',
            'created_by',
        ];
        foreach ($expectedTrainingCols as $col) {
            $this->assertStringContainsString("'$col'", $content, "Missing offline_trainings column: $col");
        }

        // Check offline_training_users table columns (1:1 with Excel)
        $this->assertStringContainsString("Schema::create('offline_training_users'", $content);
        $expectedUserCols = [
            'offline_training_id',
            'user_id',
            'employee_id',
            'name',
            'grade',
            'job_position',
            'team',
            'department',
            'company',
            'learning_hours',
            'progress',
            'assessment_score',
            'certificate',
            'attendance',
            'attendance_reason',
            'bond_status',
            'bond_end_date',
            'bond_amount',
            'l1_program',
            'l2_numerical',
            'l2_verbal',
            'l3_numerical',
            'l3_verbal',
            'pic',
        ];
        foreach ($expectedUserCols as $col) {
            $this->assertStringContainsString("'$col'", $content, "Missing offline_training_users column: $col");
        }

        // Assert speculative columns are REMOVED
        $removedCols = [
            "'classification_id'",
            "'training_type'",
            "'duration_hours'",
            "'certificate_number'",
            "'id_emp'",
            "'employee_name'",
        ];
        foreach ($removedCols as $removedCol) {
            $this->assertStringNotContainsString($removedCol, $content, "Legacy/speculative column must not exist: $removedCol");
        }
    }

    /**
     * 6. Test Models have correct fillable attributes, casts, and relationships.
     */
    public function test_models_have_exact_fillables_and_relationships(): void
    {
        $training = new OfflineTraining();
        $this->assertEquals('offline_trainings', $training->getTable());
        $trainingFillables = [
            'course_name', 'sub_course_name', 'type', 'category', 'level', 'batch',
            'main_classification', 'sub_classification', 'counted_corporate_kpi', 'sub_corporate_kpi',
            'delivery_method', 'sub_delivery_method', 'provider', 'sub_provider', 'provider_name',
            'venue', 'instructor', 'duration_seconds', 'learning_hours', 'start_date', 'finish_date', 'created_by'
        ];
        foreach ($trainingFillables as $field) {
            $this->assertTrue(in_array($field, $training->getFillable(), true), "OfflineTraining missing fillable: $field");
        }
        $this->assertTrue(method_exists($training, 'creator'));
        $this->assertTrue(method_exists($training, 'participants'));
        $this->assertTrue(method_exists($training, 'users'));
        $this->assertTrue(method_exists($training, 'scopeByYear'));
        $this->assertTrue(method_exists($training, 'scopeByType'));
        $this->assertTrue(method_exists($training, 'scopeByClassification'));

        $userRecord = new OfflineTrainingUser();
        $this->assertEquals('offline_training_users', $userRecord->getTable());
        $userFillables = [
            'offline_training_id', 'user_id', 'employee_id', 'name', 'grade',
            'job_position', 'team', 'department', 'company', 'learning_hours',
            'progress', 'assessment_score', 'certificate', 'attendance', 'attendance_reason',
            'bond_status', 'bond_end_date', 'bond_amount', 'l1_program', 'l2_numerical',
            'l2_verbal', 'l3_numerical', 'l3_verbal', 'pic'
        ];
        foreach ($userFillables as $field) {
            $this->assertTrue(in_array($field, $userRecord->getFillable(), true), "OfflineTrainingUser missing fillable: $field");
        }
        $this->assertTrue(method_exists($userRecord, 'training'));
        $this->assertTrue(method_exists($userRecord, 'user'));
        $this->assertTrue(method_exists($userRecord, 'scopeFinished'));
        $this->assertTrue(method_exists($userRecord, 'scopeOngoing'));
        $this->assertTrue(method_exists($userRecord, 'scopeAttended'));
    }
}
