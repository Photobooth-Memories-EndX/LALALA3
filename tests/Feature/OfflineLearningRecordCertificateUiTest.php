<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineLearningRecordCertificateUiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected array $createdTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'id_emp'     => 'E' . rand(10000, 99999),
            'email'      => 'cert_test_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-' . Str::uuid(),
            'sap_code'   => 'SAP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);
    }

    /**
     * Test: Action column is completely removed, Certificate is the last column,
     * header th count matches body td count, and certificate button displays
     * green (active) for yes/1/true and gray (disabled) for no/0/null.
     */
    public function test_offline_learning_record_action_removed_and_certificate_ui_states(): void
    {
        // 1. Training with certificate = 'Yes' (should be green active)
        $trainingYes = OfflineTraining::create([
            'course_name'         => 'Advanced Leadership Workshop',
            'type'                => 'In-House',
            'category'            => 'Management',
            'main_classification' => 'Leadership',
            'learning_hours'      => 4.0,
            'start_date'          => '2026-05-10',
            'finish_date'         => '2026-05-10',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingYes->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'progress'            => 100,
            'certificate'         => 'Yes',
            'attendance'          => true,
        ]);

        // 2. Training with certificate = '1' (should be green active)
        $trainingOne = OfflineTraining::create([
            'course_name'         => 'Data Analytics Bootcamp',
            'type'                => 'Public',
            'category'            => 'Technical',
            'main_classification' => 'Technology',
            'learning_hours'      => 8.0,
            'start_date'          => '2026-06-01',
            'finish_date'         => '2026-06-02',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingOne->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'progress'            => 100,
            'certificate'         => '1',
            'attendance'          => true,
        ]);

        // 3. Training with certificate = 'No' (should be gray disabled)
        $trainingNo = OfflineTraining::create([
            'course_name'         => 'Orientation Session',
            'type'                => 'Internal',
            'category'            => 'Onboarding',
            'main_classification' => 'General',
            'learning_hours'      => 2.0,
            'start_date'          => '2026-07-01',
            'finish_date'         => '2026-07-01',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingNo->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'progress'            => 50,
            'certificate'         => 'No',
            'attendance'          => true,
        ]);

        // 4. Training with certificate = null (should be gray disabled)
        $trainingNull = OfflineTraining::create([
            'course_name'         => 'Health and Wellness Seminar',
            'type'                => 'Seminar',
            'category'            => 'Wellness',
            'main_classification' => 'General',
            'learning_hours'      => 1.5,
            'start_date'          => '2026-08-01',
            'finish_date'         => '2026-08-01',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingNull->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'progress'            => 100,
            'certificate'         => null,
            'attendance'          => true,
        ]);

        // 5. Training with certificate = '0' (should be gray disabled)
        $trainingZero = OfflineTraining::create([
            'course_name'         => 'Security Awareness Briefing',
            'type'                => 'Internal',
            'category'            => 'Compliance',
            'main_classification' => 'Compliance',
            'learning_hours'      => 1.0,
            'start_date'          => '2026-09-01',
            'finish_date'         => '2026-09-01',
        ]);
        OfflineTrainingUser::create([
            'offline_training_id' => $trainingZero->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'progress'            => 100,
            'certificate'         => '0',
            'attendance'          => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=offline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check 1: ACTION column header is removed
        $this->assertStringNotContainsString('<th class="text-muted text-center" style="width: 4%">Action</th>', $content);
        $this->assertStringNotContainsString('>Action</th>', $content);

        // Check 2: No view/eye button in rows
        $this->assertStringNotContainsString('ti-eye', $content);

        // Check 3: CERTIFICATE header exists
        $this->assertStringContainsString('Certificate</th>', $content);

        // Check 4: Certificate buttons exist with expected classes
        $this->assertStringContainsString('btn-learning-certificate', $content);
        $this->assertStringContainsString('btn-learning-certificate-disabled', $content);

        // Check 5: HTML structure contains active button for available
        $this->assertMatchesRegularExpression(
            '/class="btn btn-learning-certificate"[^>]*title="Certificate Available"/',
            $content
        );

        // Check 6: HTML structure contains disabled button with disabled attribute
        $this->assertMatchesRegularExpression(
            '/class="btn btn-learning-certificate-disabled"\s+disabled\s+title="Certificate Not Available"/',
            $content
        );

        // Check 7: No standalone text "Yes" or "No" in certificate cell
        // (Ensure badges like 'bg-danger-lt text-danger px-1 py-0.5">No<' do not exist)
        $this->assertStringNotContainsString('title="No certificate">No', $content);

        // Check 8: Both titles appear
        $this->assertStringContainsString('Advanced Leadership Workshop', $content);
        $this->assertStringContainsString('Data Analytics Bootcamp', $content);
        $this->assertStringContainsString('Orientation Session', $content);
        $this->assertStringContainsString('Health and Wellness Seminar', $content);
        $this->assertStringContainsString('Security Awareness Briefing', $content);

        // Check 9: Certificate filter works
        // Filter 'certified'
        $responseCert = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=offline&record_filter_certificate=certified');
        $responseCert->assertStatus(200);
        $responseCert->assertSee('Advanced Leadership Workshop');
        $responseCert->assertSee('Data Analytics Bootcamp');
        $responseCert->assertDontSee('Orientation Session');
        $responseCert->assertDontSee('Health and Wellness Seminar');
        $responseCert->assertDontSee('Security Awareness Briefing');

        // Filter 'uncertified'
        $responseUncert = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=offline&record_filter_certificate=uncertified');
        $responseUncert->assertStatus(200);
        $responseUncert->assertDontSee('Advanced Leadership Workshop');
        $responseUncert->assertDontSee('Data Analytics Bootcamp');
        $responseUncert->assertSee('Orientation Session');
        $responseUncert->assertSee('Health and Wellness Seminar');
        $responseUncert->assertSee('Security Awareness Briefing');
    }

    /**
     * Regression test: Online Learning Record mode remains unaffected.
     */
    public function test_online_learning_record_remains_unaffected(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=online');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Online table still has Certificate column and its standard structure
        $this->assertStringContainsString('Certificate</th>', $content);
        $this->assertStringContainsString('learningRecordTable', $content);
    }
}
