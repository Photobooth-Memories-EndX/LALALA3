<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\User;
use App\Services\Record\OfflineOptionService;
use App\Services\Record\TrainingOptionManagementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ManageClassificationHistoryPageTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    /**
     * Test 1: Training Classification Tab renders 200 and displays all 7 master main classifications.
     */
    public function test_classification_tab_renders_canonical_master_data(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification');
        $response->assertStatus(200);
        $response->assertSee('Classification History');
        $response->assertSee('Basic & Common Duties');
        $response->assertSee('Compliance');
        $response->assertSee('Innovation');
        $response->assertSee('Job Functional');
        $response->assertSee('Language');
        $response->assertSee('Leadership');
        $response->assertSee('Assessment/Test');

        // Verify DataTable table structure and row display
        $response->assertSee('List Training Classification');
        $response->assertSee('22 row(s) displayed');
        $response->assertSee('Sub Classification');
        $response->assertSee('Course Examples');
        $response->assertDontSee('accordion classification-tree-accordion');
        $response->assertDontSee('btnToggleAllAccordions');

        // Verify metrics
        $response->assertSee('Main Class');
        $response->assertSee('Sub Class');
        $response->assertSee('Courses');
    }

    /**
     * Test 2: Training Type Tab renders OWL types (Document, Video, MOOC, Playlist).
     */
    public function test_training_type_tab_renders_owl_types(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=type');
        $response->assertStatus(200);
        $response->assertSee('Training Type');
        $response->assertSee('Document');
        $response->assertSee('Video');
        $response->assertSee('MOOC');
        $response->assertSee('Playlist');
    }

    /**
     * Test 3: Training Category Tab renders OWL categories and Non KP-OWL category.
     */
    public function test_training_category_tab_renders_owl_and_non_kp_owl(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=category');
        $response->assertStatus(200);
        $response->assertSee('Training Category');
        $response->assertSee('Playlist');
        $response->assertSee('All Content');
        $response->assertSee('Content Playlist');
        $response->assertSee('NON KP-OWL');
    }

    /**
     * Test 4: Delivery Method Tab renders hierarchy, sources, and explanations.
     */
    public function test_delivery_method_tab_renders_hierarchical_methods_and_explanations(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=delivery_method');
        $response->assertStatus(200);
        $response->assertSee('Delivery Method');
        $response->assertSee('Offline');
        $response->assertSee('On-site');
        $response->assertSee('Off-site');
        $response->assertSee('Online');
        $response->assertSee('E-learning');
        $response->assertSee('Webinar');
        $response->assertSee('Blended');
        $response->assertSee('Hybrid');
        $response->assertSee('Face-to-face training conducted at the PTKP');
    }

    /**
     * Test 5: Training Provider Tab renders hierarchy, sources, and explanations.
     */
    public function test_provider_tab_renders_hierarchical_providers_and_explanations(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/classification?type=provider');
        $response->assertStatus(200);
        $response->assertSee('Training Provider');
        $response->assertSee('Internal');
        $response->assertSee('Internal-Training Team');
        $response->assertSee('Internal-Unit');
        $response->assertSee('External');
        $response->assertSee('Institution');
        $response->assertSee('Individual');
    }

    /**
     * Test 6: Filters work properly (source filter, query filter, main filter).
     */
    public function test_filters_and_search_behavior(): void
    {
        // Query search
        $searchRes = $this->actingAs($this->admin)->get('/manage/classification?q=Korean');
        $searchRes->assertStatus(200);
        $searchRes->assertSee('Language');
        $searchRes->assertSee('Korean');

        // Source filter for type
        $owlRes = $this->actingAs($this->admin)->get('/manage/classification?type=category&source=owl');
        $owlRes->assertStatus(200);
        $owlRes->assertSee('Playlist');

        // Non KP-OWL source
        $nonKpOwlRes = $this->actingAs($this->admin)->get('/manage/classification?type=category&source=non_kp_owl');
        $nonKpOwlRes->assertStatus(200);
        $nonKpOwlRes->assertSee('NON KP-OWL');
    }

    /**
     * Test 7: AJAX subs-by-main endpoint returns child sub-classifications.
     */
    public function test_ajax_subs_by_main_endpoint(): void
    {
        $lang = Classification::where('classification_name', 'Language')->firstOrFail();

        $response = $this->actingAs($this->admin)->getJson("/manage/classification/options/subs-by-main/{$lang->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'value'],
            ],
        ]);

        $subs = collect($response->json('data'))->pluck('value')->all();
        $this->assertContains('Korean', $subs);
        $this->assertContains('Indonesian', $subs);
        $this->assertContains('English', $subs);
    }

    /**
     * Test 8: AJAX CRUD for Sub Classification and Course Example.
     */
    public function test_ajax_crud_for_sub_classification_and_course_example(): void
    {
        $compliance = Classification::where('classification_name', 'Compliance')->firstOrFail();

        // 1. Create Sub Classification
        $createSubRes = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type'       => OfflineOptionService::TYPE_SUB_CLASSIFICATION,
            'classification_id' => $compliance->id,
            'value'             => 'Test Safety Sub',
            'source'            => 'non_kp_owl',
            'explanation'       => 'Test safety explanation',
        ]);
        $createSubRes->assertStatus(200);
        $this->assertTrue($createSubRes->json('success'));
        $subId = $createSubRes->json('data.id');

        $this->assertDatabaseHas('offline_training_options', [
            'id'                => $subId,
            'option_type'       => OfflineOptionService::TYPE_SUB_CLASSIFICATION,
            'classification_id' => $compliance->id,
            'value'             => 'Test Safety Sub',
            'source'            => 'non_kp_owl',
            'explanation'       => 'Test safety explanation',
        ]);

        // 2. Create Course Example under the new Sub Classification
        $createCourseRes = $this->actingAs($this->admin)->postJson('/manage/classification/options', [
            'option_type'       => OfflineOptionService::TYPE_COURSE_NAME,
            'classification_id' => $compliance->id,
            'parent_id'         => $subId,
            'value'             => 'Test Course Example 101',
        ]);
        $createCourseRes->assertStatus(200);
        $this->assertTrue($createCourseRes->json('success'));
        $courseId = $createCourseRes->json('data.id');

        $this->assertDatabaseHas('offline_training_options', [
            'id'                => $courseId,
            'option_type'       => OfflineOptionService::TYPE_COURSE_NAME,
            'parent_id'         => $subId,
            'value'             => 'Test Course Example 101',
        ]);

        // 3. Toggle Status of Sub Classification
        $toggleRes = $this->actingAs($this->admin)->patchJson("/manage/classification/options/{$subId}/toggle-status");
        $toggleRes->assertStatus(200);
        $this->assertFalse((bool) $toggleRes->json('data.is_active'));

        // 4. Safe Delete Sub Classification (has child course example -> usage check blocks deletion)
        $usageRes = $this->actingAs($this->admin)->getJson("/manage/classification/options/{$subId}/usage");
        $usageRes->assertStatus(200);
        $this->assertTrue($usageRes->json('data.is_used'));

        // 5. Delete child course example first
        $delCourseRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/options/{$courseId}");
        $delCourseRes->assertStatus(200);
        $this->assertDatabaseMissing('offline_training_options', ['id' => $courseId]);

        // 6. Delete Sub Classification now succeeds
        $delSubRes = $this->actingAs($this->admin)->deleteJson("/manage/classification/options/{$subId}");
        $delSubRes->assertStatus(200);
        $this->assertDatabaseMissing('offline_training_options', ['id' => $subId]);
    }

    /**
     * Test 9: Offline Training Create Schedule page contains sub-classification and course example maps.
     */
    public function test_offline_training_create_schedule_integrates_hierarchical_data(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule/create');
        $response->assertStatus(200);
        $response->assertSee('subClassificationMap', false);
        $response->assertSee('courseExampleMap', false);
        $response->assertSee('Korean', false);
        $response->assertSee('TOPIK I Preparation', false);
    }
}
