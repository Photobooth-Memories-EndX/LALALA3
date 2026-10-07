<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingOption;
use App\Models\User;
use App\Services\Record\OfflineOptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TrainingScheduleDropdownIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected OfflineOptionService $optionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::where('role', '3')->first() ?? User::first();
        $this->optionService = app(OfflineOptionService::class);
    }

    /**
     * 1. Create page renders with master classifications, dynamic options, and dependency map.
     */
    public function test_create_page_renders_with_database_master_options(): void
    {
        $response = $this->actingAs($this->admin)->get('/manage/training-schedule/create');
        $response->assertStatus(200);

        $response->assertViewHas('classifications');
        $response->assertViewHas('options');
        $response->assertViewHas('subClassificationMap');

        // Check dropdown select elements exist
        $response->assertSee('id="select-main-classification"', false);
        $response->assertSee('id="select-sub-classification"', false);
        $response->assertSee('id="select-category"', false);
        $response->assertSee('id="select-type"', false);
        $response->assertSee('id="select-level"', false);

        // Check 6 converted dropdown fields exist
        $response->assertSee('id="select-training-title"', false);
        $response->assertSee('id="select-sub-course-name"', false);
        $response->assertSee('id="select-instructor"', false);
        $response->assertSee('id="select-location"', false);
        $response->assertSee('id="select-target-employee"', false);
        $response->assertSee('id="select-prerequisites"', false);

        // Check [+] buttons exist for target fields
        $response->assertSee('data-type="classification"', false);
        $response->assertSee('data-type="sub_classification"', false);
        $response->assertSee('data-type="category"', false);
        $response->assertSee('data-type="type"', false);
        $response->assertSee('data-type="level"', false);
        $response->assertSee('data-type="course_name"', false);
        $response->assertSee('data-type="sub_course_name"', false);
        $response->assertSee('data-type="instructor"', false);
        $response->assertSee('data-type="venue"', false);
        $response->assertSee('data-type="target_employee"', false);
        $response->assertSee('data-type="prerequisites"', false);

        // Check modal component is included
        $response->assertSee('id="modal-schedule-add-option"', false);
    }

    /**
     * 2. Edit page renders with existing training record and backward-compatible option preservation.
     */
    public function test_edit_page_renders_with_existing_values_and_historical_compatibility(): void
    {
        $training = OfflineTraining::whereNotNull('category')->first();
        if (!$training) {
            $this->markTestSkipped('No offline training record found for edit test.');
        }

        $response = $this->actingAs($this->admin)->get("/manage/training-schedule/{$training->id}/edit");
        $response->assertStatus(200);

        $response->assertViewHas('training');
        $response->assertViewHas('classifications');
        $response->assertViewHas('options');
        $response->assertViewHas('subClassificationMap');

        $response->assertSee('id="select-main-classification"', false);
        $response->assertSee('id="select-sub-classification"', false);
        $response->assertSee('id="select-category"', false);
        $response->assertSee('id="select-type"', false);
        $response->assertSee('id="select-level"', false);

        // Check 6 converted dropdown fields exist on edit page
        $response->assertSee('id="select-training-title"', false);
        $response->assertSee('id="select-sub-course-name"', false);
        $response->assertSee('id="select-instructor"', false);
        $response->assertSee('id="select-location"', false);
        $response->assertSee('id="select-target-employee"', false);
        $response->assertSee('id="select-prerequisites"', false);

        $response->assertSee('id="modal-schedule-add-option"', false);
    }

    /**
     * 3. API endpoint stores new Main Classification in classification table.
     */
    public function test_store_option_api_creates_new_classification(): void
    {
        $uniqueName = 'Test Main Class ' . uniqid();

        $response = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'classification',
            'value'       => $uniqueName,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status'  => 'success',
            'success' => true,
            'data'    => [
                'value' => $uniqueName,
            ],
        ]);

        $this->assertDatabaseHas('classification', [
            'classification_name' => $uniqueName,
        ]);
    }

    /**
     * 4. API endpoint detects existing Main Classification and returns exists status.
     */
    public function test_store_option_api_handles_duplicate_classification_gracefully(): void
    {
        $existing = Classification::first();
        if (!$existing) {
            $existing = Classification::create(['classification_name' => 'Existing Classification Test']);
        }

        $response = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'classification',
            'value'       => strtolower($existing->classification_name),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'      => 'exists',
            'is_existing' => true,
            'data'        => [
                'value' => $existing->classification_name,
            ],
        ]);
    }

    /**
     * 5. API endpoint stores new Training Type in offline_training_options table.
     */
    public function test_store_option_api_creates_new_offline_training_option(): void
    {
        $uniqueType = 'Type Test ' . uniqid();

        $response = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'type',
            'value'       => $uniqueType,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'status'  => 'success',
            'success' => true,
            'data'    => [
                'value' => $uniqueType,
            ],
        ]);

        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'type',
            'value'       => $uniqueType,
        ]);
    }

    /**
     * 6. API endpoint handles duplicate option in offline_training_options gracefully.
     */
    public function test_store_option_api_handles_duplicate_option_gracefully(): void
    {
        $opt = OfflineTrainingOption::where('option_type', 'type')->first();
        if (!$opt) {
            $opt = OfflineTrainingOption::create([
                'option_type'      => 'type',
                'value'            => 'In-House',
                'normalized_value' => 'in-house',
                'scope_key'        => 'global',
            ]);
        }

        $response = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'type',
            'value'       => $opt->value,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'      => 'exists',
            'is_existing' => true,
            'data'        => [
                'value' => $opt->value,
            ],
        ]);
    }

    /**
     * 7. API endpoint rejects invalid option types or placeholder values.
     */
    public function test_store_option_api_rejects_invalid_values_or_types(): void
    {
        // Placeholder value rejection
        $response = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'type',
            'value'       => ' - ',
        ]);
        $response->assertStatus(422);

        // Invalid option type rejection
        $response = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'arbitrary_fake_type',
            'value'       => 'Valid Name',
        ]);
        $response->assertStatus(422);
    }

    /**
     * 8. Creating schedule successfully saves record and syncs options.
     */
    public function test_create_schedule_stores_and_syncs_options(): void
    {
        $uniqueTitle = 'Test Schedule ' . uniqid();
        $payload = [
            'title'               => $uniqueTitle,
            'main_classification' => 'Leadership',
            'sub_classification'  => 'Agile Team Management',
            'category'            => 'Leadership Competency',
            'type'                => 'In-House',
            'level'               => 'Intermediate',
            'code'                => 'TS-TEST-01',
            'instructor'          => 'Trainer John Doe',
            'location'            => 'Training Room 1',
            'target_employee'     => 'All Supervisors',
            'prerequisites'       => 'None',
            'quota'               => 25,
            'status'              => 'Open Registration',
            'start_date'          => date('Y-m-d', strtotime('+7 days')),
            'end_date'            => date('Y-m-d', strtotime('+8 days')),
            'start_time'          => '09:00',
            'end_time'            => '17:00',
            'description'         => 'Test schedule overview description',
            'objectives'          => 'Test schedule learning objectives',
        ];

        $response = $this->actingAs($this->admin)->post('/manage/training-schedule', $payload);
        $response->assertRedirect('/manage/training-schedule');

        $this->assertDatabaseHas('offline_trainings', [
            'title'              => $uniqueTitle,
            'sub_classification' => 'Agile Team Management',
        ]);

        // Verify sub_classification and new fields were synced into offline_training_options
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'sub_classification',
            'value'       => 'Agile Team Management',
        ]);
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'instructor',
            'value'       => 'Trainer John Doe',
        ]);
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'target_employee',
            'value'       => 'All Supervisors',
        ]);
    }

    /**
     * 9. API endpoint supports alias option types (title -> course_name, location -> venue, etc.).
     */
    public function test_store_option_api_supports_aliases_and_new_types(): void
    {
        // 1. Alias 'title' -> should map to 'course_name'
        $uniqueCourse = 'Advanced Data Analytics ' . uniqid();
        $response1 = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'title',
            'value'       => $uniqueCourse,
        ]);
        $response1->assertStatus(201);
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'course_name',
            'value'       => $uniqueCourse,
        ]);

        // 2. Alias 'location' -> should map to 'venue'
        $uniqueVenue = 'Auditorium Hall B ' . uniqid();
        $response2 = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'location',
            'value'       => $uniqueVenue,
        ]);
        $response2->assertStatus(201);
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'venue',
            'value'       => $uniqueVenue,
        ]);

        // 3. 'target_employee'
        $uniqueTarget = 'Quality Control Specialists ' . uniqid();
        $response3 = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'target_employee',
            'value'       => $uniqueTarget,
        ]);
        $response3->assertStatus(201);
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'target_employee',
            'value'       => $uniqueTarget,
        ]);

        // 4. 'prerequisites'
        $uniquePrereq = 'Certified Green Belt Six Sigma ' . uniqid();
        $response4 = $this->actingAs($this->admin)->postJson('/manage/training-schedule/options', [
            'option_type' => 'prerequisites',
            'value'       => $uniquePrereq,
        ]);
        $response4->assertStatus(201);
        $this->assertDatabaseHas('offline_training_options', [
            'option_type' => 'prerequisites',
            'value'       => $uniquePrereq,
        ]);
    }
}
