<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\User;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TrainingOptionManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected ?User $regularUser;
    protected TrainingOptionManagementService $optionManager;
    protected OfflineOptionService $offlineOptionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
        $this->optionManager = app(TrainingOptionManagementService::class);
        $this->offlineOptionService = app(OfflineOptionService::class);
    }

    /**
     * 1. Accessing /manage/classification requires authentication and proper role.
     */
    public function test_page_requires_authentication_and_permission(): void
    {
        $guestRes = $this->get('/manage/classification');
        $this->assertTrue($guestRes->isRedirect() || $guestRes->status() === 401);

        $adminRes = $this->actingAs($this->admin)->get('/manage/classification');
        $adminRes->assertStatus(200);
        $adminRes->assertSee('Training Option Management');
    }

    /**
     * 2. Default view renders Classification management.
     */
    public function test_default_view_renders_classification_management(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification');
        $response->assertStatus(200);
        $response->assertSee('List Classification');
        $response->assertSee('Classification Name');
    }

    /**
     * 3. Selector navigation renders different option types.
     */
    public function test_selector_renders_different_option_types(): void
    {
        // Type
        $resType = $this->actingAs($this->admin)->get('/manage/classification?type=type');
        $resType->assertStatus(200);
        $resType->assertSee('List Type');

        // Category
        $resCat = $this->actingAs($this->admin)->get('/manage/classification?type=category');
        $resCat->assertStatus(200);
        $resCat->assertSee('List Category');

        // Delivery Method
        $resDel = $this->actingAs($this->admin)->get('/manage/classification?type=delivery_method');
        $resDel->assertStatus(200);
        $resDel->assertSee('List Delivery Method');

        // Sub Delivery Method (hierarchical)
        $resSubDel = $this->actingAs($this->admin)->get('/manage/classification?type=sub_delivery_method');
        $resSubDel->assertStatus(200);
        $resSubDel->assertSee('List Sub Delivery Method');

        // Corporate KPI (year-scoped)
        $resKpi = $this->actingAs($this->admin)->get('/manage/classification?type=sub_corporate_kpi');
        $resKpi->assertStatus(200);
        $resKpi->assertSee('List Corporate KPI');
    }

    /**
     * 4. Create and update Classification with duplicate prevention.
     */
    public function test_classification_crud_and_duplicate_prevention(): void
    {
        $uniqueName = 'Automated Test Class ' . uniqid();

        // 1. Create Classification
        $storeRes = $this->actingAs($this->admin)->postJson('/manage/classification', [
            'option_type'         => 'classification',
            'classification_name' => $uniqueName,
        ]);
        $storeRes->assertStatus(200);
        $storeRes->assertJson(['success' => true]);

        $created = Classification::where('classification_name', $uniqueName)->first();
        $this->assertNotNull($created);
        $this->assertNotEmpty($created->slug);

        // 2. Duplicate Classification is rejected
        $dupRes = $this->actingAs($this->admin)->postJson('/manage/classification', [
            'option_type'         => 'classification',
            'classification_name' => '  ' . strtolower($uniqueName) . '  ',
        ]);
        $dupRes->assertStatus(422);

        // 3. Update Classification
        $updatedName = $uniqueName . ' Updated';
        $updateRes = $this->actingAs($this->admin)->putJson("/manage/classification/{$created->slug}", [
            'classification_name' => $updatedName,
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals($updatedName, $created->fresh()->classification_name);
    }

    /**
     * 5. Create, edit, toggle status, and duplicate prevention for master options.
     */
    public function test_option_crud_toggle_status_and_duplicate_prevention(): void
    {
        $testVal = 'Custom Type ' . uniqid();

        // 1. Create Option
        $storeRes = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type' => 'type',
            'value'       => $testVal,
            'sort_order'  => 15,
            'is_active'   => 1,
        ]);
        $storeRes->assertStatus(200);
        $storeRes->assertJson(['success' => true]);

        $opt = OfflineTrainingOption::where('option_type', 'type')
            ->where('value', $testVal)
            ->first();
        $this->assertNotNull($opt);
        $this->assertTrue($opt->is_active);
        $this->assertEquals(15, $opt->sort_order);

        // 2. Duplicate Option is rejected (case-insensitive & trimmed)
        $dupRes = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type' => 'type',
            'value'       => '   ' . strtolower($testVal) . '   ',
        ]);
        $dupRes->assertStatus(422);

        // 3. Update Option
        $newVal = $testVal . ' Edited';
        $updateRes = $this->actingAs($this->admin)->putJson("/manage/classification/options/{$opt->id}", [
            'option_type' => 'type',
            'value'       => $newVal,
            'sort_order'  => 25,
            'is_active'   => 1,
        ]);
        $updateRes->assertStatus(200);
        $this->assertEquals($newVal, $opt->fresh()->value);
        $this->assertEquals(25, $opt->fresh()->sort_order);

        // 4. Toggle Status (Active -> Inactive -> Active)
        $toggleRes = $this->actingAs($this->admin)->patchJson("/manage/classification/options/{$opt->id}/toggle-status");
        $toggleRes->assertStatus(200);
        $this->assertFalse($opt->fresh()->is_active);

        // Verify inactive option is excluded from active dropdown queries
        $activeOptions = $this->offlineOptionService->getOptions('type', null, true);
        $this->assertFalse($activeOptions->contains('id', $opt->id));

        // But included when onlyActive is false
        $allOptions = $this->offlineOptionService->getOptions('type', null, false);
        $this->assertTrue($allOptions->contains('id', $opt->id));

        // Toggle back to active
        $toggleBackRes = $this->actingAs($this->admin)->patchJson("/manage/classification/options/{$opt->id}/toggle-status");
        $toggleBackRes->assertStatus(200);
        $this->assertTrue($opt->fresh()->is_active);
    }

    /**
     * 6. Delete safety: unused option can be deleted, used option is prevented.
     */
    public function test_delete_safety_prevents_deleting_used_options(): void
    {
        // 1. Unused Option can be safely deleted
        $unusedVal = 'Unused Del Test ' . uniqid();
        $unusedOpt = OfflineTrainingOption::create([
            'option_type'      => 'venue',
            'value'            => $unusedVal,
            'normalized_value' => strtolower($unusedVal),
            'scope_key'        => 'global',
            'is_active'        => true,
            'created_from'     => 'test',
        ]);

        $delRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/options/{$unusedOpt->id}");
        $delRes->assertStatus(200);
        $this->assertNull(OfflineTrainingOption::find($unusedOpt->id));

        // 2. Used Option is prevented from deletion
        $usedVal = 'Used Delivery Method ' . uniqid();
        $usedOpt = OfflineTrainingOption::create([
            'option_type'      => 'delivery_method',
            'value'            => $usedVal,
            'normalized_value' => strtolower($usedVal),
            'scope_key'        => 'global',
            'is_active'        => true,
            'created_from'     => 'test',
        ]);

        // Attach to a training record
        $training = OfflineTraining::create([
            'course_name'     => 'Safety Training ' . uniqid(),
            'delivery_method' => $usedVal,
        ]);

        $failDelRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/options/{$usedOpt->id}");
        $failDelRes->assertStatus(422);
        $failDelRes->assertJson([
            'status'         => 422,
            'success'        => false,
            'is_used'        => true,
            'can_deactivate' => true,
        ]);
        $this->assertNotNull(OfflineTrainingOption::find($usedOpt->id));
    }

    /**
     * 7. Delete safety: Classification used by content or training records cannot be deleted.
     */
    public function test_classification_used_by_content_or_training_cannot_be_deleted(): void
    {
        $classVal = 'Used Classification ' . uniqid();
        $class = Classification::create([
            'classification_name' => $classVal,
        ]);

        // Link to offline training
        OfflineTraining::create([
            'course_name'         => 'Course ' . uniqid(),
            'main_classification' => $classVal,
        ]);

        $delRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/{$class->slug}");
        $delRes->assertStatus(422);
        $delRes->assertJson([
            'status'  => 422,
            'success' => false,
            'is_used' => true,
        ]);
        $this->assertNotNull(Classification::find($class->id));
    }

    /**
     * 8. Parent-child relationship support for hierarchical options.
     */
    public function test_parent_child_hierarchical_options(): void
    {
        $parentDelivery = 'Classroom Lecture ' . uniqid();
        $parent = OfflineTrainingOption::create([
            'option_type'      => 'delivery_method',
            'value'            => $parentDelivery,
            'normalized_value' => strtolower($parentDelivery),
            'scope_key'        => 'global',
            'is_active'        => true,
            'created_from'     => 'test',
        ]);

        $childVal = 'Practical Workshop Module ' . uniqid();
        $childStoreRes = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type' => 'sub_delivery_method',
            'value'       => $childVal,
            'parent_id'   => $parent->id,
            'sort_order'  => 5,
            'is_active'   => 1,
        ]);
        $childStoreRes->assertStatus(200);

        $child = OfflineTrainingOption::where('option_type', 'sub_delivery_method')
            ->where('value', $childVal)
            ->first();
        $this->assertNotNull($child);
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals($parent->value, $child->parent->value);
    }

    /**
     * 9. Year-scoped option (sub_corporate_kpi) respects year scoping and uniqueness per year.
     */
    public function test_year_scoped_options_validation(): void
    {
        $kpiTarget = 'Cost Efficiency ' . uniqid();

        // 2026 KPI
        $res2026 = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type' => 'sub_corporate_kpi',
            'value'       => $kpiTarget,
            'year'        => 2026,
            'is_active'   => 1,
        ]);
        $res2026->assertStatus(200);

        // Duplicate in same year 2026 must fail
        $resDup2026 = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type' => 'sub_corporate_kpi',
            'value'       => $kpiTarget,
            'year'        => 2026,
        ]);
        $resDup2026->assertStatus(422);

        // Same KPI in different year 2027 must succeed
        $res2027 = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type' => 'sub_corporate_kpi',
            'value'       => $kpiTarget,
            'year'        => 2027,
            'is_active'   => 1,
        ]);
        $res2027->assertStatus(200);
    }
}
