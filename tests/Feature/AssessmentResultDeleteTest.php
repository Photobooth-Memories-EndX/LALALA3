<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentQuestion;
use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssessmentResultDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private User $superAdmin;
    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = $this->createTestUser(User::ROLE_SUPER_ADMIN, 'Super Admin Tester');
        $this->admin = $this->createTestUser(User::ROLE_ADMIN, 'Admin Tester');
        $this->regularUser = $this->createTestUser(User::ROLE_USER, 'Regular User Tester');
    }

    private function createTestUser(int $role, string $namePrefix): User
    {
        return User::create([
            'name'       => $namePrefix . ' ' . uniqid(),
            'email'      => 'user_' . uniqid() . '@example.com',
            'password'   => bcrypt('secret'),
            'role'       => $role,
            'team'       => 'TEST-TEAM',
            'department' => 'TEST-DEPT',
            'division'   => 'TEST-DIV',
            'id_emp'     => 'EMP' . rand(10000, 99999),
            'ssohash'    => Str::random(32),
            'sap_code'   => 'SAP' . rand(1000, 9999),
            'active'     => 1,
        ]);
    }

    private function createAssessment(int $batch = 1): Assesment
    {
        $assessment = Assesment::create([
            'title'           => 'Assessment Delete Test ' . uniqid(),
            'type'            => 'global',
            'status'          => 'OK',
            'passing_grade'   => 70,
            'batch'           => $batch,
            'total_remedials' => 0,
            'unique_code'     => 'TD' . strtoupper(Str::random(6)),
        ]);

        AssesmentQuestion::create([
            'assesment_id' => $assessment->id,
            'question'     => 'Question 1 for delete test',
            'option'       => ['a' => 'Ans 1', 'b' => 'Ans 2'],
            'answer'       => 'a',
        ]);

        return $assessment;
    }

    private function makeAssessmentResult(Assesment $assessment, User $user, int $score = 80, int $pass = 1): AssesmentResult
    {
        return AssesmentResult::create([
            'user_id'      => $user->id,
            'assesment_id' => $assessment->id,
            'batch'        => $assessment->batch,
            'final'        => 1,
            'score'        => $score,
            'pass'         => $pass,
            'user_answer'  => ['1' => 'a'],
        ]);
    }

    /**
     * TEST 1: Authorized Super Admin can delete assessment result.
     */
    public function test_authorized_super_admin_can_delete_assessment_result(): void
    {
        $assessment = $this->createAssessment();
        $result = $this->makeAssessmentResult($assessment, $this->regularUser);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('assesment.result', $assessment->slug))
            ->delete(route('assesment.result.destroy', [$assessment->slug, $result->id]));

        $response->assertRedirect(route('assesment.result', $assessment->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('assesment_results', [
            'id' => $result->id,
        ]);
    }

    /**
     * TEST 2: Authorized Admin can delete assessment result.
     */
    public function test_authorized_admin_can_delete_assessment_result(): void
    {
        $assessment = $this->createAssessment();
        $result = $this->makeAssessmentResult($assessment, $this->regularUser);

        $response = $this->actingAs($this->admin)
            ->from(route('assesment.result', $assessment->slug))
            ->delete(route('assesment.result.destroy', [$assessment->slug, $result->id]));

        $response->assertRedirect(route('assesment.result', $assessment->slug));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('assesment_results', [
            'id' => $result->id,
        ]);
    }

    /**
     * TEST 3: Unauthorized regular user cannot delete assessment result.
     */
    public function test_unauthorized_regular_user_cannot_delete_assessment_result(): void
    {
        $assessment = $this->createAssessment();
        $result = $this->makeAssessmentResult($assessment, $this->regularUser);

        $response = $this->actingAs($this->regularUser)
            ->delete(route('assesment.result.destroy', [$assessment->slug, $result->id]));

        $response->assertStatus(403);

        $this->assertDatabaseHas('assesment_results', [
            'id' => $result->id,
        ]);
    }

    /**
     * TEST 4: Deleting target record does NOT delete other records.
     */
    public function test_delete_only_removes_target_record_and_keeps_others(): void
    {
        $assessment = $this->createAssessment();
        $userA = $this->createTestUser(User::ROLE_USER, 'User A');
        $userB = $this->createTestUser(User::ROLE_USER, 'User B');

        $resultA = $this->makeAssessmentResult($assessment, $userA, 90, 1);
        $resultB = $this->makeAssessmentResult($assessment, $userB, 60, 0);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('assesment.result', $assessment->slug))
            ->delete(route('assesment.result.destroy', [$assessment->slug, $resultA->id]));

        $response->assertRedirect(route('assesment.result', $assessment->slug));

        $this->assertDatabaseMissing('assesment_results', ['id' => $resultA->id]);
        $this->assertDatabaseHas('assesment_results', ['id' => $resultB->id]);
    }

    /**
     * TEST 5: Gracefully handles non-existing / invalid result ID.
     */
    public function test_invalid_or_non_existing_result_id_handled_gracefully(): void
    {
        $assessment = $this->createAssessment();
        $nonExistingId = 9999999;

        $response = $this->actingAs($this->superAdmin)
            ->from(route('assesment.result', $assessment->slug))
            ->delete(route('assesment.result.destroy', [$assessment->slug, $nonExistingId]));

        $response->assertRedirect(route('assesment.result', $assessment->slug));
        $response->assertSessionHas('warning');
    }

    /**
     * TEST 6: Gracefully nullifies certificate reference if result is deleted.
     */
    public function test_delete_result_nullifies_certificate_reference(): void
    {
        $assessment = $this->createAssessment();
        $result = $this->makeAssessmentResult($assessment, $this->regularUser);

        $setting = CertificateSetting::create([
            'title'               => 'Certificate Setting ' . uniqid(),
            'certificate_type'    => 'assessment',
            'assessment_id'       => $assessment->id,
            'certificate_code'    => 'CERT-' . strtoupper(Str::random(6)),
            'issuer_name'         => 'Test Issuer',
            'status'              => 'active',
        ]);

        $cert = Certificate::create([
            'user_id'                => $this->regularUser->id,
            'certificate_setting_id' => $setting->id,
            'certificate_type'       => 'assessment',
            'source_id'              => $assessment->id,
            'assessment_result_id'   => $result->id,
            'certificate_number'     => 'CN-' . uniqid(),
            'credential_id'          => 'CRED-' . uniqid(),
            'title'                  => 'Test Cert',
            'participant_name'       => $this->regularUser->name,
            'score'                  => $result->score,
            'issued_at'              => now(),
        ]);

        $this->assertEquals($result->id, $cert->assessment_result_id);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('assesment.result', $assessment->slug))
            ->delete(route('assesment.result.destroy', [$assessment->slug, $result->id]));

        $response->assertRedirect(route('assesment.result', $assessment->slug));
        $this->assertDatabaseMissing('assesment_results', ['id' => $result->id]);

        $cert->refresh();
        $this->assertNull($cert->assessment_result_id);
    }

    /**
     * TEST 7: Result page renders ACTION header, trash icon-only button, and delete confirmation modal.
     */
    public function test_result_page_renders_action_column_and_trash_icon_modal(): void
    {
        $assessment = $this->createAssessment();
        $result = $this->makeAssessmentResult($assessment, $this->regularUser);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('assesment.result', $assessment->slug));

        $response->assertStatus(200);
        $response->assertSee('Action');
        
        // Trash icon button attributes
        $response->assertSee('btn-delete-result');
        $response->assertSee('fa-trash-alt');
        $response->assertSee('title="Delete"', false);
        $response->assertSee('aria-label="Delete"', false);
        $response->assertSee('data-url="' . route('assesment.result.destroy', [$assessment->slug, $result->id]) . '"', false);

        // Reusable delete confirmation modal elements
        $response->assertSee('id="modal-delete-result"', false);
        $response->assertSee('id="form-delete-result"', false);
        $response->assertSee('Delete Assessment Result');
        $response->assertSee('Are you sure you want to delete this assessment result?');
        $response->assertSee('Deleted data cannot be recovered.');
        $response->assertSee('Cancel');
        $response->assertSee('Delete');
    }
}
