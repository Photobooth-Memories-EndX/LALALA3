<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use App\Services\OfflineCertificate\OfflineCertificateEligibilityService;
use App\Services\OfflineCertificate\OfflineCertificateGenerationService;
use App\Services\OfflineCertificate\OfflineCertificateSettingService;
use App\Services\Record\OfflineTrainingCreateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineCertificateAutomationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $otherUser;
    protected OfflineCertificateEligibilityService $eligibilityService;
    protected OfflineCertificateGenerationService $generationService;
    protected OfflineCertificateSettingService $settingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'id_emp'     => 'EMP-' . rand(10000, 99999),
            'email'      => 'offcert_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-' . Str::uuid(),
            'sap_code'   => 'SAP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->otherUser = User::factory()->create([
            'id_emp'     => 'EMP-' . rand(10000, 99999),
            'email'      => 'other_' . uniqid() . '@example.com',
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-' . Str::uuid(),
            'sap_code'   => 'SAP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->eligibilityService = app(OfflineCertificateEligibilityService::class);
        $this->generationService = app(OfflineCertificateGenerationService::class);
        $this->settingService = app(OfflineCertificateSettingService::class);
    }

    /**
     * 1. Automatic CertificateSetting creation when training is created.
     */
    public function test_automatic_certificate_setting_creation(): void
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Security Operations Offline Workshop',
            'type'                => 'Internal',
            'category'            => 'Security',
            'main_classification' => 'Technology',
            'learning_hours'      => 4.0,
            'start_date'          => '2026-10-15',
            'finish_date'         => '2026-10-15',
        ]);

        $setting = $this->settingService->ensureSettingForTraining($training);

        $this->assertNotNull($setting);
        $this->assertEquals('training_offline', $setting->certificate_type);
        $this->assertEquals($training->id, $setting->offline_training_id);
        $this->assertEquals(100, (int) $setting->minimum_progress);
        $this->assertEquals(80, (int) $setting->minimum_score);
        $this->assertFalse((bool) $setting->is_assessment_required);
        $this->assertTrue((bool) $setting->is_active);

        // Ensure idempotence: subsequent call does not duplicate
        $secondSetting = $this->settingService->ensureSettingForTraining($training);
        $this->assertEquals($setting->id, $secondSetting->id);
    }

    /**
     * 2. Full Eligibility Matrix Testing.
     * Matrix:
     * - Progress 100%, Present, Assessment Required, Score 90 -> Eligible
     * - Progress 100%, Present, Assessment Required, Score 80 -> Eligible
     * - Progress 100%, Present, Assessment Required, Score 79 -> Not Eligible
     * - Progress 100%, Present, Assessment Not Required, Score NULL -> Eligible
     * - Progress 100%, Absent (false/0), Assessment Required, Score 95 -> Not Eligible
     * - Progress 100%, Attendance NULL, Assessment Not Required -> Not Eligible
     * - Progress 75%, Present, Assessment Required, Score 100 -> Not Eligible
     * - Kirkpatrick L1-L3 ignored -> Eligible
     */
    public function test_eligibility_matrix_progress_100_present_assessment_required_score_90_is_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 1, 'minimum_score' => 80]);
        $participant = $this->createParticipant($training, [
            'progress'         => 100,
            'attendance'       => true,
            'assessment_score' => 90,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertTrue($result->eligible);
        $this->assertNull($result->reason);
    }

    public function test_eligibility_matrix_progress_100_present_assessment_required_score_80_is_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 1, 'minimum_score' => 80]);
        $participant = $this->createParticipant($training, [
            'progress'         => 100,
            'attendance'       => true,
            'assessment_score' => 80,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertTrue($result->eligible);
    }

    public function test_eligibility_matrix_progress_100_present_assessment_required_score_79_is_not_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 1, 'minimum_score' => 80]);
        $participant = $this->createParticipant($training, [
            'progress'         => 100,
            'attendance'       => true,
            'assessment_score' => 79,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('Assessment score (79.0) is below the minimum passing score of 80.0', (string) $result->reason);
    }

    public function test_eligibility_matrix_progress_100_present_assessment_not_required_score_null_is_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 0]);
        $participant = $this->createParticipant($training, [
            'progress'         => 100,
            'attendance'       => true,
            'assessment_score' => null,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertTrue($result->eligible);
    }

    public function test_eligibility_matrix_progress_100_absent_assessment_required_score_95_is_not_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 1, 'minimum_score' => 80]);
        $participant = $this->createParticipant($training, [
            'progress'         => 100,
            'attendance'       => false,
            'assessment_score' => 95,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('Attendance must be Present', (string) $result->reason);
    }

    public function test_eligibility_matrix_progress_100_attendance_null_is_not_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 0]);
        $participant = $this->createParticipant($training, [
            'progress'         => 100,
            'attendance'       => null,
            'assessment_score' => null,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('Attendance must be Present', (string) $result->reason);
    }

    public function test_eligibility_matrix_progress_75_present_score_100_is_not_eligible(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 1, 'minimum_score' => 80]);
        $participant = $this->createParticipant($training, [
            'progress'         => 75,
            'attendance'       => true,
            'assessment_score' => 100,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('Learning progress (75.0%) does not meet the minimum requirement', (string) $result->reason);
    }

    public function test_kirkpatrick_l1_l2_l3_evaluations_do_not_block_eligibility(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 0]);
        $participant = $this->createParticipant($training, [
            'progress'     => 100,
            'attendance'   => true,
            'l1_program'   => null,
            'l2_numerical' => null,
            'l3_numerical' => null,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertTrue($result->eligible);
    }

    public function test_inactive_certificate_setting_blocks_eligibility(): void
    {
        $training = $this->createTrainingWithSetting(['is_active' => 0]);
        $participant = $this->createParticipant($training, [
            'progress'   => 100,
            'attendance' => true,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('inactive', (string) $result->reason);
    }

    public function test_cancelled_training_blocks_eligibility(): void
    {
        $training = $this->createTrainingWithSetting();
        $training->update(['status' => 'cancelled']);
        $participant = $this->createParticipant($training, [
            'progress'   => 100,
            'attendance' => true,
        ]);

        $result = $this->eligibilityService->evaluate($participant);
        $this->assertFalse($result->eligible);
        $this->assertStringContainsString('cancelled', (string) $result->reason);
    }

    /**
     * 3. Lazy Generation & Idempotence.
     */
    public function test_lazy_certificate_generation_and_idempotence(): void
    {
        $training = $this->createTrainingWithSetting(['is_assessment_required' => 0]);
        $participant = $this->createParticipant($training, [
            'progress'   => 100,
            'attendance' => true,
        ]);

        // Generate certificate
        $cert1 = $this->generationService->generateForParticipant($participant, $this->user);

        $this->assertNotNull($cert1);
        $this->assertEquals('training_offline', $cert1->certificate_type);
        $this->assertEquals($this->user->id, $cert1->user_id);
        $this->assertEquals($training->id, $cert1->source_id);
        $this->assertEquals($participant->id, $cert1->offline_training_user_id);
        $this->assertStringStartsWith('OWL-CERT-', $cert1->certificate_number);
        $this->assertStringStartsWith('OWL-', $cert1->credential_id);
        $this->assertNotNull($cert1->credential_url);

        // Subsequent call returns same certificate (idempotent)
        $cert2 = $this->generationService->generateForParticipant($participant, $this->user);
        $this->assertEquals($cert1->id, $cert2->id);
        $this->assertEquals($cert1->certificate_number, $cert2->certificate_number);
    }

    /**
     * 4. Controller Access Control & Route Testing.
     */
    public function test_unauthorized_user_cannot_generate_another_user_certificate(): void
    {
        $training = $this->createTrainingWithSetting();
        $participant = $this->createParticipant($training, [
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'attendance' => true,
        ]);

        // otherUser attempts to generate this certificate -> 403
        $response = $this->actingAs($this->otherUser)
            ->get(route('certificate.generate.offline', $participant));

        $response->assertStatus(403);
    }

    public function test_ineligible_user_cannot_generate_certificate(): void
    {
        $training = $this->createTrainingWithSetting();
        $participant = $this->createParticipant($training, [
            'user_id'    => $this->user->id,
            'progress'   => 50, // Ineligible progress
            'attendance' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('certificate.generate.offline', $participant));

        $response->assertStatus(403);
    }

    public function test_eligible_user_generates_certificate_and_redirects_to_show(): void
    {
        $training = $this->createTrainingWithSetting();
        $participant = $this->createParticipant($training, [
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'attendance' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('certificate.generate.offline', $participant));

        $response->assertStatus(302);

        $cert = Certificate::where('offline_training_user_id', $participant->id)->first();
        $this->assertNotNull($cert);
        $response->assertRedirect(route('certificate.show', $cert->credential_id));
    }

    /**
     * 5. Strict Attendance variants: Absent, Cancelled, 0, NULL.
     */
    public function test_attendance_variants_absent_cancelled_and_zero_are_strictly_ineligible(): void
    {
        $training = $this->createTrainingWithSetting();

        // attendance = 0
        $p1 = $this->createParticipant($training, ['attendance' => 0]);
        $r1 = $this->eligibilityService->evaluate($p1);
        $this->assertFalse($r1->eligible);

        // attendance = 'cancelled' (stored in reason or raw)
        $p2 = $this->createParticipant($training, ['attendance' => false, 'attendance_reason' => 'Cancelled']);
        $r2 = $this->eligibilityService->evaluate($p2);
        $this->assertFalse($r2->eligible);
    }

    /**
     * 6. Admin items AJAX returns offline trainings when type=training_offline.
     */
    public function test_admin_ajax_items_returns_offline_trainings(): void
    {
        $training = $this->createTrainingWithSetting();

        $response = $this->withoutMiddleware(\App\Http\Middleware\CheckManagePermission::class)
            ->actingAs($this->user)
            ->get(route('certificate.setting.ajax.items', ['type' => 'training_offline']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id'    => $training->id,
            'title' => $training->course_name,
        ]);
    }

    /**
     * 7. My Learning UI table renders active certificate button for eligible row and disabled for ineligible row.
     */
    public function test_my_learning_ui_renders_active_and_disabled_buttons(): void
    {
        $trainingEligible = $this->createTrainingWithSetting(['is_assessment_required' => 0]);
        $participantEligible = $this->createParticipant($trainingEligible, [
            'progress'   => 100,
            'attendance' => true,
        ]);

        $trainingIneligible = $this->createTrainingWithSetting(['is_assessment_required' => 0]);
        $participantIneligible = $this->createParticipant($trainingIneligible, [
            'progress'   => 60,
            'attendance' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/my-learning/partial/record?record_mode=offline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Contains active green button with generate route for eligible row
        $this->assertStringContainsString(route('certificate.generate.offline', $participantEligible), $content);

        // Contains disabled button with tooltip for ineligible row
        $this->assertStringContainsString('btn-learning-certificate-disabled', $content);
        $this->assertStringContainsString('Learning progress (60.0%) does not meet the minimum requirement', $content);
    }

    /**
     * Helper to create an offline training with a certificate setting.
     */
    private function createTrainingWithSetting(array $settingOverrides = []): OfflineTraining
    {
        $training = OfflineTraining::create([
            'course_name'         => 'Test Offline Course ' . Str::random(5),
            'type'                => 'Offline',
            'category'            => 'Technical',
            'main_classification' => 'General',
            'learning_hours'      => 4.0,
            'start_date'          => '2026-10-10',
            'finish_date'         => '2026-10-10',
        ]);

        $this->settingService->ensureSettingForTraining($training, $settingOverrides);

        return $training;
    }

    /**
     * Helper to create an offline training participant.
     */
    private function createParticipant(OfflineTraining $training, array $attributes = []): OfflineTrainingUser
    {
        return OfflineTrainingUser::create(array_merge([
            'offline_training_id' => $training->id,
            'user_id'             => $this->user->id,
            'employee_id'         => $this->user->id_emp,
            'name'                => $this->user->name,
            'learning_hours'      => 4.0,
            'progress'            => 100,
            'attendance'          => true,
            'assessment_score'    => null,
        ], $attributes));
    }
}
