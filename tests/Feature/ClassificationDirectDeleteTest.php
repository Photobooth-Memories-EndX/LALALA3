<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Content;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\RoleAccessPermission;
use App\Models\User;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClassificationDirectDeleteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup authorized admin user
        $this->adminUser = User::factory()->create([
            'role'   => '1',
            'active' => 1,
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => '1', 'module_key' => 'category_classification'],
            ['can_access' => true]
        );

        // 2. Setup unauthorized user (role 0 - standard employee)
        $this->unauthorizedUser = User::factory()->create([
            'role'   => '0',
            'active' => 1,
        ]);

        RoleAccessPermission::updateOrCreate(
            ['role' => '0', 'module_key' => 'category_classification'],
            ['can_access' => false]
        );
    }

    /**
     * Test 1: Direct delete active Main Classification without relations -> Removed immediately from database.
     */
    public function test_direct_delete_classification_without_relations_removes_from_database(): void
    {
        $classification = Classification::create([
            'classification_name' => 'Safe Delete Cls ' . uniqid(),
            'slug'                => 'safe-delete-cls-' . uniqid(),
            'is_active'           => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('manage.classification.main.destroy', $classification->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Directly deleted from DB (NOT soft deleted)
        $this->assertDatabaseMissing('classification', ['id' => $classification->id]);
    }

    /**
     * Test 2: Direct delete active Option without relations -> Removed immediately from database.
     */
    public function test_direct_delete_option_without_relations_removes_from_database(): void
    {
        $option = OfflineTrainingOption::create([
            'option_type'      => OfflineOptionService::TYPE_TYPE,
            'value'            => 'Safe Delete Type ' . uniqid(),
            'normalized_value' => 'safe delete type ' . uniqid(),
            'scope_key'        => 'global',
            'is_active'        => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('manage.classification.options.destroy', $option->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Directly deleted from DB
        $this->assertDatabaseMissing('offline_training_options', ['id' => $option->id]);
    }

    /**
     * Test 3: Delete Main Classification linked to Learning Content -> Blocked with 422.
     */
    public function test_delete_classification_linked_to_content_blocked_with_422(): void
    {
        $classification = Classification::create([
            'classification_name' => 'Linked Content Cls ' . uniqid(),
            'slug'                => 'linked-content-cls-' . uniqid(),
            'is_active'           => 1,
        ]);

        $content = Content::create([
            'title'             => 'Test Content ' . uniqid(),
            'classification_id' => $classification->id,
            'type'              => 'Course',
            'status'            => 'mandatory',
            'active'            => 'Active',
            'author'            => 'Test Author',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('manage.classification.main.destroy', $classification->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'is_used' => true,
        ]);
        $response->assertSee('learning content');

        // Verify still in database
        $this->assertDatabaseHas('classification', ['id' => $classification->id]);
    }

    /**
     * Test 4: Delete Main Classification linked to Sub Classification -> Blocked with 422.
     */
    public function test_delete_classification_linked_to_sub_classification_blocked_with_422(): void
    {
        $classification = Classification::create([
            'classification_name' => 'Linked Sub Cls ' . uniqid(),
            'slug'                => 'linked-sub-cls-' . uniqid(),
            'is_active'           => 1,
        ]);

        $sub = OfflineTrainingOption::create([
            'option_type'       => OfflineOptionService::TYPE_SUB_CLASSIFICATION,
            'value'             => 'Sub Option ' . uniqid(),
            'normalized_value'  => 'sub option ' . uniqid(),
            'classification_id' => $classification->id,
            'scope_key'         => 'global',
            'is_active'         => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('manage.classification.main.destroy', $classification->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'is_used' => true,
        ]);
        $response->assertSee('sub classification');

        // Verify still in database
        $this->assertDatabaseHas('classification', ['id' => $classification->id]);
    }

    /**
     * Test 5: Delete Option linked to Offline Training record -> Blocked with 422.
     */
    public function test_delete_option_linked_to_offline_training_blocked_with_422(): void
    {
        $option = OfflineTrainingOption::create([
            'option_type'      => OfflineOptionService::TYPE_DELIVERY_METHOD,
            'value'            => 'Method In Use ' . uniqid(),
            'normalized_value' => 'method in use ' . uniqid(),
            'scope_key'        => 'global',
            'is_active'        => 1,
        ]);

        $training = OfflineTraining::create([
            'title'               => 'Training Test ' . uniqid(),
            'delivery_method'     => $option->value,
            'main_classification'=> 'General',
            'date_start'          => '2026-11-01',
            'date_end'            => '2026-11-02',
            'training_type'       => 'External',
            'organizer'           => 'Vendor',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('manage.classification.options.destroy', $option->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'is_used' => true,
        ]);
        $response->assertSee('training record');

        // Verify still in database
        $this->assertDatabaseHas('offline_training_options', ['id' => $option->id]);
    }

    /**
     * Test 6: Delete Sub Classification with Child Course Examples -> Blocked with 422.
     */
    public function test_delete_option_with_child_options_blocked_with_422(): void
    {
        $parentSub = OfflineTrainingOption::create([
            'option_type'      => OfflineOptionService::TYPE_SUB_CLASSIFICATION,
            'value'            => 'Parent Sub ' . uniqid(),
            'normalized_value' => 'parent sub ' . uniqid(),
            'scope_key'        => 'global',
            'is_active'        => 1,
        ]);

        $childCourse = OfflineTrainingOption::create([
            'option_type'      => OfflineOptionService::TYPE_COURSE_NAME,
            'value'            => 'Child Course ' . uniqid(),
            'normalized_value' => 'child course ' . uniqid(),
            'parent_id'        => $parentSub->id,
            'scope_key'        => 'global',
            'is_active'        => 1,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('manage.classification.options.destroy', $parentSub->id));

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'is_used' => true,
        ]);
        $response->assertSee('child option');

        // Verify still in database
        $this->assertDatabaseHas('offline_training_options', ['id' => $parentSub->id]);
    }

    /**
     * Test 7: Access to Trash route returns 404 (Trash features hidden/removed).
     */
    public function test_trash_route_returns_404_not_found(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/manage/classification/trash');

        $response->assertStatus(404);
    }

    /**
     * Test 8: Non-admin unauthorized user receives 403 Forbidden.
     */
    public function test_unauthorized_user_cannot_delete_classification_or_option(): void
    {
        $classification = Classification::create([
            'classification_name' => 'Forbidden Cls ' . uniqid(),
            'slug'                => 'forbidden-cls-' . uniqid(),
            'is_active'           => 1,
        ]);

        $opt = OfflineTrainingOption::create([
            'option_type'      => OfflineOptionService::TYPE_CATEGORY,
            'value'            => 'Forbidden Cat ' . uniqid(),
            'normalized_value' => 'forbidden cat ' . uniqid(),
            'scope_key'        => 'global',
            'is_active'        => 1,
        ]);

        // Attempt delete classification
        $resCls = $this->actingAs($this->unauthorizedUser)
            ->deleteJson(route('manage.classification.main.destroy', $classification->id));
        $resCls->assertStatus(403);

        // Attempt delete option
        $resOpt = $this->actingAs($this->unauthorizedUser)
            ->deleteJson(route('manage.classification.options.destroy', $opt->id));
        $resOpt->assertStatus(403);

        // Assert data still exists
        $this->assertDatabaseHas('classification', ['id' => $classification->id]);
        $this->assertDatabaseHas('offline_training_options', ['id' => $opt->id]);
    }

    /**
     * Test 9: Resource route DELETE /manage/classification/{slug} also enforces safe direct delete.
     */
    public function test_resource_route_destroy_enforces_safe_direct_delete(): void
    {
        $cls = Classification::create([
            'classification_name' => 'Resource Route Cls ' . uniqid(),
            'slug'                => 'resource-route-cls-' . uniqid(),
            'is_active'           => 1,
        ]);

        // Delete via resource route
        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/manage/classification/{$cls->slug}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('classification', ['id' => $cls->id]);
    }

    /**
     * Test 10: All other master data types (Batch, Level, Venue, Instructor, etc.) direct delete.
     */
    public function test_multiple_option_types_direct_deletion(): void
    {
        $types = [
            OfflineOptionService::TYPE_BATCH,
            OfflineOptionService::TYPE_LEVEL,
            OfflineOptionService::TYPE_VENUE,
            OfflineOptionService::TYPE_INSTRUCTOR,
            OfflineOptionService::TYPE_PROVIDER,
        ];

        foreach ($types as $type) {
            $opt = OfflineTrainingOption::create([
                'option_type'      => $type,
                'value'            => 'Unit Test ' . $type . ' ' . uniqid(),
                'normalized_value' => 'unit test ' . $type . ' ' . uniqid(),
                'scope_key'        => 'global',
                'is_active'        => 1,
            ]);

            $response = $this->actingAs($this->adminUser)
                ->deleteJson(route('manage.classification.options.destroy', $opt->id));

            $response->assertStatus(200);
            $this->assertDatabaseMissing('offline_training_options', ['id' => $opt->id]);
        }
    }
}
