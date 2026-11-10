<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfflineTrainingRefactorUiTest extends TestCase
{
    protected User $admin;
    protected array $createdTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('role', '2')->first() ?? User::first();
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdTrainingIds)) {
            DB::table('offline_training_users')->whereIn('offline_training_id', $this->createdTrainingIds)->delete();
            DB::table('offline_trainings')->whereIn('id', $this->createdTrainingIds)->delete();
        }

        parent::tearDown();
    }

    protected function trackTraining(int $id): int
    {
        $this->createdTrainingIds[] = $id;
        return $id;
    }

    /**
     * Test offline landing page DIV reordering:
     * 1. Banner (record-hero)
     * 2. Statistic Cards (offline-kpi-summary-container)
     * 3. Toolbar (record-section-header with Add Training, Import, Download)
     * 4. Navigation Cards (record-navigation-row)
     * 5. Main Content (classification-summary-card)
     */
    public function test_offline_landing_page_reordered_div_layout(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline'));
        $response->assertStatus(200);

        $content = $response->getContent();

        $body = substr($content, strpos($content, '<body'));

        $posHero = strpos($body, 'manage-record-hero');
        $posKpi = strpos($body, 'id="offline-kpi-summary-container"');
        $posToolbar = strpos($body, 'record-section-header');
        $posNav = strpos($body, 'record-navigation-row');
        $posAction = strpos($body, 'id="btn-add-training-dropdown"');
        $posClassification = strpos($body, 'classification-summary-card');

        $this->assertNotFalse($posHero, 'Banner (manage-record-hero) must exist');
        $this->assertNotFalse($posKpi, 'Statistic cards (offline-kpi-summary-container) must exist');
        $this->assertFalse($posToolbar, 'Header section (record-section-header) must be removed');
        $this->assertNotFalse($posNav, 'Navigation cards (record-navigation-row) must exist');
        $this->assertNotFalse($posAction, 'Action bar (btn-add-training-dropdown) must exist below navigation cards');
        $this->assertNotFalse($posClassification, 'Classification summary (classification-summary-card) must exist');

        $this->assertLessThan($posKpi, $posHero, 'Banner must precede Statistic Cards');
        $this->assertLessThan($posNav, $posKpi, 'Statistic Cards must precede Navigation Cards');
        $this->assertLessThan($posAction, $posNav, 'Navigation Cards must precede Action Bar');
        $this->assertLessThan($posClassification, $posAction, 'Action Bar must precede Classification Summary');

        // Verify required action bar elements
        $response->assertSee('id="btn-open-offline-add"', false);
        $response->assertSee('id="btn-open-offline-import"', false);
        $response->assertSee('id="btn-download-dropdown"', false);
    }

    /**
     * Test Case 1, 2, 3, 4, 8:
     * Check Create page renders with:
     * - Schedule header renamed to SCHEDULE & DURATION PROGRAM EVALUATION
     * - L1 Program in General Information (parallel to Type)
     * - Kirkpatrick Evaluation renamed to (L2 - L3) and no longer contains L1 in section 3
     * - Save Result button renamed to Save and Submit with primary styling
     */
    public function test_create_page_ui_refactor_elements(): void
    {
        $response = $this->actingAs($this->admin)->get(route('manage.record.offline.create'));

        $response->assertStatus(200);

        // TASK 1: Check Schedule Header
        $response->assertSee('Schedule &amp; Duration Program Evaluation', false);

        // TASK 2: Check Save and Submit button
        $response->assertSee('id="btn-save-participant-result"', false);
        $response->assertSee('Save and Submit');
        $response->assertDontSee('Save Result for This Participant');
        $response->assertSee('btn-primary');

        // TASK 3: Check L1 Program in General Information
        $content = $response->getContent();
        $this->assertStringContainsString('id="res-l1-program"', $content);
        $this->assertStringContainsString('placeholder="e.g. 4.8 / Very Good"', $content);

        // Verify L1 Program appears before Type in the markup
        $posL1 = strpos($content, 'id="res-l1-program"');
        $posType = strpos($content, 'id="add-type"');
        $this->assertNotFalse($posL1);
        $this->assertNotFalse($posType);
        $this->assertLessThan($posType, $posL1, 'L1 Program must appear before Type in General Information');

        // Verify Kirkpatrick Evaluation header is (L2 - L3)
        $this->assertStringContainsString('Kirkpatrick Evaluation (L2 - L3)', $content);
        $this->assertStringNotContainsString('Kirkpatrick Evaluation (L1 - L3)', $content);

        // Verify Sub Course Name is full-width (col-12) without empty spacer
        $this->assertMatchesRegularExpression('/<div\s+class="col-12">\s*<label\s+for="add-sub-course-name"/s', $content);
        $this->assertStringNotContainsString('Spacer to align Sub Course Name', $content);

        // Verify L2 & L3 evaluation fields exist in Section 3
        $this->assertStringContainsString('id="res-l2-numerical"', $content);
        $this->assertStringContainsString('id="res-l2-verbal"', $content);
        $this->assertStringContainsString('id="res-l3-numerical"', $content);
        $this->assertStringContainsString('id="res-l3-verbal"', $content);
        $this->assertStringContainsString('id="res-pic"', $content);
    }

    /**
     * Test Case 6, 7, 9:
     * Submit offline training with individual L1 Program values for multiple participants.
     * Verify L1 Program is persisted per participant in offline_training_users.
     */
    public function test_submit_training_persists_l1_program_per_participant(): void
    {
        $payload = [
            'course_name'            => 'TEST_REFACTOR_COURSE_' . uniqid(),
            'batch'                  => 'Batch Refactor',
            'sub_course_name'        => 'Advanced Module',
            'type'                   => 'In-House',
            'category'               => 'Technical',
            'level'                  => 'Advanced',
            'main_classification'    => 'Technical',
            'sub_classification'     => 'Engineering',
            'counted_corporate_kpi'  => '0',
            'delivery_method'        => 'Classroom',
            'sub_delivery_method'    => 'Internal',
            'provider'               => 'Internal',
            'sub_provider'           => 'Plant',
            'provider_name'          => 'Internal HR',
            'duration'               => '08:00:00',
            'learning_hours'         => 8.0,
            'start_date'             => Carbon::now()->addDays(5)->format('Y-m-d'),
            'finish_date'            => Carbon::now()->addDays(6)->format('Y-m-d'),
            'start_time'             => '08:00',
            'end_time'               => '16:00',
            'venue'                  => 'Room Alpha',
            'instructor'             => 'Trainer Smith',
            'participants'           => [
                [
                    'employee_id'      => 'EMP_TEST_1_' . uniqid(),
                    'name'             => 'Participant One',
                    'grade'            => '3A',
                    'job_position'     => 'Staff',
                    'team'             => 'Operations',
                    'department'       => 'Manufacturing',
                    'company'          => 'PT Krakatau Posco',
                    'learning_hours'   => 8.0,
                    'progress'         => 100,
                    'assessment_score' => 90.0,
                    'certificate'      => 'Yes',
                    'attendance'       => 1,
                    'l1_program'       => '4.8 Excellent',
                    'l2_numerical'     => 88.5,
                    'l2_verbal'        => 'Very Good',
                    'l3_numerical'     => 92.0,
                    'l3_verbal'        => 'Satisfactory',
                    'pic'              => 'HRDC Team',
                ],
                [
                    'employee_id'      => 'EMP_TEST_2_' . uniqid(),
                    'name'             => 'Participant Two',
                    'grade'            => '4A',
                    'job_position'     => 'Lead',
                    'team'             => 'Operations',
                    'department'       => 'Manufacturing',
                    'company'          => 'PT Krakatau Posco',
                    'learning_hours'   => 8.0,
                    'progress'         => 100,
                    'assessment_score' => 95.0,
                    'certificate'      => 'Yes',
                    'attendance'       => 1,
                    'l1_program'       => '4.9 Outstanding',
                    'l2_numerical'     => 94.0,
                    'l2_verbal'        => 'Excellent',
                    'l3_numerical'     => 96.0,
                    'l3_verbal'        => 'Implemented successfully',
                    'pic'              => 'HRDC Team',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->postJson(route('manage.record.offline.store'), $payload);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        $trainingId = $response->json('data.id');
        $this->trackTraining($trainingId);

        $this->assertDatabaseHas('offline_trainings', [
            'id'          => $trainingId,
            'course_name' => $payload['course_name'],
            'type'        => 'In-House',
        ]);

        // Verify participant 1 has their specific L1 value
        $this->assertDatabaseHas('offline_training_users', [
            'offline_training_id' => $trainingId,
            'employee_id'         => $payload['participants'][0]['employee_id'],
            'l1_program'          => '4.8 Excellent',
            'l2_numerical'        => 88.5,
            'l2_verbal'           => 'Very Good',
        ]);

        // Verify participant 2 has their specific L1 value (not mixed up or overwritten)
        $this->assertDatabaseHas('offline_training_users', [
            'offline_training_id' => $trainingId,
            'employee_id'         => $payload['participants'][1]['employee_id'],
            'l1_program'          => '4.9 Outstanding',
            'l2_numerical'        => 94.0,
            'l2_verbal'           => 'Excellent',
        ]);
    }

    /**
     * Test Case 10, 11, 12:
     * Check Edit page:
     * - Schedule header renamed
     * - L1 Program in General Information
     * - Kirkpatrick Evaluation (L2 - L3)
     * - L1 Program value prepopulated
     * - Updating L1 Program persists properly
     */
    public function test_edit_page_renders_and_updates_l1_program(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'TEST_EDIT_L1_' . uniqid(),
            'category'            => 'General',
            'type'                => 'Public',
            'main_classification' => 'Technical',
            'start_date'          => '2026-09-01',
            'finish_date'         => '2026-09-02',
            'duration_seconds'    => 28800,
            'learning_hours'      => 8.0,
            'created_by'          => $this->admin->id,
        ]);
        $this->trackTraining($training->id);

        $participant = OfflineTrainingUser::create([
            'offline_training_id' => $training->id,
            'employee_id'         => 'EMP_EDIT_' . uniqid(),
            'name'                => 'Edit Participant',
            'learning_hours'      => 8.0,
            'progress'            => 100,
            'l1_program'          => '4.7 Very Good Initial',
            'l2_numerical'        => 85.0,
            'l2_verbal'           => 'Passed',
            'l3_numerical'        => 88.0,
            'l3_verbal'           => 'Good',
            'pic'                 => 'HRDC Team',
        ]);

        // Open edit page
        $resEdit = $this->actingAs($this->admin)->get(route('manage.record.offline.edit', $participant->id));
        $resEdit->assertStatus(200);

        // Check Schedule Header in Edit
        $resEdit->assertSee('Schedule &amp; Duration Program Evaluation', false);

        // Check L1 Program value prepopulated in edit-l1-program
        $editContent = $resEdit->getContent();
        $this->assertStringContainsString('id="edit-l1-program"', $editContent);
        $this->assertStringContainsString('4.7 Very Good Initial', $editContent);

        // Check Kirkpatrick Evaluation (L2 - L3) in Edit
        $this->assertStringContainsString('Kirkpatrick Evaluation (L2 - L3)', $editContent);
        $this->assertStringNotContainsString('Kirkpatrick Evaluation (L1 - L3)', $editContent);

        // Verify L1 Program appears before Type in General Information
        $posL1 = strpos($editContent, 'id="edit-l1-program"');
        $posType = strpos($editContent, 'id="edit-type"');
        $this->assertNotFalse($posL1);
        $this->assertNotFalse($posType);
        $this->assertLessThan($posType, $posL1, 'edit-l1-program must appear before edit-type in General Information');

        // Verify Sub Course Name is full-width (col-12) without empty spacer in Edit
        $this->assertMatchesRegularExpression('/<div\s+class="col-12">\s*<label\s+for="edit-sub-course-name"/s', $editContent);
        $this->assertStringNotContainsString('Spacer to align Sub Course Name', $editContent);

        // Test Update
        $updatePayload = [
            'course_name'         => $training->course_name,
            'type'                => 'Public',
            'main_classification' => 'Technical',
            'start_date'          => '2026-09-01',
            'finish_date'         => '2026-09-02',
            'duration'            => '08:00:00',
            'learning_hours'      => 8.0,
            'name'                => $participant->name,
            'employee_id'         => $participant->employee_id,
            'progress'            => 100,
            'attendance'          => 1,
            'l1_program'          => '5.0 Outstanding Updated',
            'l2_numerical'        => 92.0,
            'l2_verbal'           => 'Excellent',
            'l3_numerical'        => 95.0,
            'l3_verbal'           => 'Completed',
            'pic'                 => 'HRDC Team',
        ];

        $resUpdate = $this->actingAs($this->admin)
            ->put(route('manage.record.offline.update', $participant->id), $updatePayload);

        $resUpdate->assertStatus(302);

        $this->assertDatabaseHas('offline_training_users', [
            'id'         => $participant->id,
            'l1_program' => '5.0 Outstanding Updated',
            'l2_verbal'  => 'Excellent',
        ]);
    }
}
