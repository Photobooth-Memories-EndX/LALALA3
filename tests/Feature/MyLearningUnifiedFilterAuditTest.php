<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Certificate;
use App\Models\CertificateSetting;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningUnifiedFilterAuditTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Audit Learner',
            'team'       => 'Learner Team',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'audit-' . Str::uuid(),
            'sap_code'   => 'SAP-' . rand(1000, 9999),
            'id_emp'     => 'EMP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Audit Classification'],
            ['slug' => 'audit-classification']
        );
    }

    // ==========================================
    // 1. LEARNING RECORD TAB TESTS
    // ==========================================

    public function test_learning_record_year_filter_strictly_isolates_dataset()
    {
        $content2024 = Content::create([
            'title'             => 'Content 2024 Course',
            'slug'              => 'content-2024-' . rand(1000, 9999),
            'classification_id' => $this->classification->id,
            'type'              => 'Video',
            'duration'          => '00:10:00',
            'learning_hours'    => 1.0,
            'status'            => 'OK',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'creator'           => $this->user->name,
        ]);

        $content2025 = Content::create([
            'title'             => 'Content 2025 Course',
            'slug'              => 'content-2025-' . rand(1000, 9999),
            'classification_id' => $this->classification->id,
            'type'              => 'Video',
            'duration'          => '00:10:00',
            'learning_hours'    => 2.0,
            'status'            => 'OK',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'creator'           => $this->user->name,
        ]);

        $cu2024 = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content2024->id,
            'finish'           => '1',
            'progress'         => 100,
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cu2024->id)->update([
            'created_at'   => '2024-03-15 10:00:00',
            'updated_at'   => '2024-03-15 10:00:00',
            'completed_at' => '2024-03-15 10:00:00',
        ]);

        $cu2025 = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $content2025->id,
            'finish'           => '1',
            'progress'         => 100,
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cu2025->id)->update([
            'created_at'   => '2025-06-20 10:00:00',
            'updated_at'   => '2025-06-20 10:00:00',
            'completed_at' => '2025-06-20 10:00:00',
        ]);

        // Filter Year = 2024 via partial
        $response2024 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_filter_year' => '2024']));

        $response2024->assertOk();
        $response2024->assertSee('Content 2024 Course');
        $response2024->assertDontSee('Content 2025 Course');

        // Check fallback query param ?year=2025
        $response2025 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'year' => '2025']));

        $response2025->assertOk();
        $response2025->assertSee('Content 2025 Course');
        $response2025->assertDontSee('Content 2024 Course');
    }

    public function test_learning_record_month_filter_supports_name_and_number()
    {
        $contentJan = Content::create([
            'title'             => 'January Special Course',
            'slug'              => 'jan-course-' . rand(1000, 9999),
            'classification_id' => $this->classification->id,
            'type'              => 'Video',
            'duration'          => '00:10:00',
            'learning_hours'    => 1.0,
            'status'            => 'OK',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'creator'           => $this->user->name,
        ]);

        $contentFeb = Content::create([
            'title'             => 'February Special Course',
            'slug'              => 'feb-course-' . rand(1000, 9999),
            'classification_id' => $this->classification->id,
            'type'              => 'Video',
            'duration'          => '00:10:00',
            'learning_hours'    => 1.0,
            'status'            => 'OK',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'creator'           => $this->user->name,
        ]);

        $cuJan = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $contentJan->id,
            'finish'           => '1',
            'progress'         => 100,
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cuJan->id)->update([
            'created_at'   => '2025-01-15 10:00:00',
            'updated_at'   => '2025-01-15 10:00:00',
            'completed_at' => '2025-01-15 10:00:00',
        ]);

        $cuFeb = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $contentFeb->id,
            'finish'           => '1',
            'progress'         => 100,
            'current_time'     => 300,
            'duration_seconds' => 600,
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cuFeb->id)->update([
            'created_at'   => '2025-02-15 10:00:00',
            'updated_at'   => '2025-02-15 10:00:00',
            'completed_at' => '2025-02-15 10:00:00',
        ]);

        // Filter month by name 'January'
        $responseJan = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', [
                'tab'   => 'record',
                'year'  => '2025',
                'month' => 'January',
            ]));

        $responseJan->assertOk();
        $responseJan->assertSee('January Special Course');
        $responseJan->assertDontSee('February Special Course');

        // Filter month by number '2'
        $responseFeb = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', [
                'tab'   => 'record',
                'year'  => '2025',
                'month' => '2',
            ]));

        $responseFeb->assertOk();
        $responseFeb->assertSee('February Special Course');
        $responseFeb->assertDontSee('January Special Course');
    }

    // ==========================================
    // 2. ASSESSMENT TAB TESTS
    // ==========================================

    public function test_assessment_year_and_month_filters()
    {
        $assessment2024 = Assesment::create([
            'title'         => 'Assessment 2024 Year Test',
            'type'          => 'global',
            'status'        => 'OK',
            'fromdate'      => '2024-01-10',
            'todate'        => '2024-01-20',
            'time'          => 30,
            'minimum_score' => 70,
            'passing_grade' => 70,
        ]);

        $assessment2025 = Assesment::create([
            'title'         => 'Assessment 2025 Year Test',
            'type'          => 'global',
            'status'        => 'OK',
            'fromdate'      => '2025-05-10',
            'todate'        => '2025-05-20',
            'time'          => 30,
            'minimum_score' => 70,
            'passing_grade' => 70,
        ]);

        // Direct assignment to user with required fields
        AssesmentAssigned::create([
            'assesment_id'         => $assessment2024->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test 2024',
        ]);

        AssesmentAssigned::create([
            'assesment_id'         => $assessment2025->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test 2025',
        ]);

        // Query with year=2024
        $respYear2024 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => '2024']));

        $respYear2024->assertOk();
        $respYear2024->assertSee('Assessment 2024 Year Test');
        $respYear2024->assertDontSee('Assessment 2025 Year Test');

        // Query with month=May
        $respMonthMay = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => '2025', 'month' => 'May']));

        $respMonthMay->assertOk();
        $respMonthMay->assertSee('Assessment 2025 Year Test');
        $respMonthMay->assertDontSee('Assessment 2024 Year Test');

        // Query with month=5
        $respMonth5 = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'year' => '2025', 'month' => '5']));

        $respMonth5->assertOk();
        $respMonth5->assertSee('Assessment 2025 Year Test');
        $respMonth5->assertDontSee('Assessment 2024 Year Test');
    }

    public function test_assessment_status_filter_passed_failed_and_pending()
    {
        $assessmentPassed = Assesment::create([
            'title'         => 'Assessment Passed Subject',
            'type'          => 'global',
            'status'        => 'OK',
            'fromdate'      => '2025-01-10',
            'todate'        => '2025-01-20',
            'time'          => 30,
            'minimum_score' => 70,
            'passing_grade' => 70,
        ]);

        $assessmentFailed = Assesment::create([
            'title'         => 'Assessment Failed Subject',
            'type'          => 'global',
            'status'        => 'OK',
            'fromdate'      => '2025-01-10',
            'todate'        => '2025-01-20',
            'time'          => 30,
            'minimum_score' => 70,
            'passing_grade' => 70,
        ]);

        $assessmentPending = Assesment::create([
            'title'         => 'Assessment Pending Subject',
            'type'          => 'global',
            'status'        => 'OK',
            'fromdate'      => '2025-01-10',
            'todate'        => '2025-01-20',
            'time'          => 30,
            'minimum_score' => 70,
            'passing_grade' => 70,
        ]);

        AssesmentAssigned::create([
            'assesment_id'         => $assessmentPassed->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test Passed',
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $assessmentFailed->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test Failed',
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $assessmentPending->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test Pending',
        ]);

        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assessmentPassed->id,
            'batch'        => 1,
            'score'        => 85,
            'pass'         => 1,
            'final'        => 1,
            'status'       => 'finish',
            'user_answer'  => ['1' => 'a'],
        ]);

        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $assessmentFailed->id,
            'batch'        => 1,
            'score'        => 45,
            'pass'         => 0,
            'final'        => 1,
            'status'       => 'finish',
            'user_answer'  => ['1' => 'b'],
        ]);

        // Filter status=passed
        $respPassed = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'status' => 'passed']));
        $respPassed->assertOk();
        $respPassed->assertSee('Assessment Passed Subject');
        $respPassed->assertDontSee('Assessment Failed Subject');
        $respPassed->assertDontSee('Assessment Pending Subject');

        // Filter status=failed
        $respFailed = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'status' => 'failed']));
        $respFailed->assertOk();
        $respFailed->assertSee('Assessment Failed Subject');
        $respFailed->assertDontSee('Assessment Passed Subject');
        $respFailed->assertDontSee('Assessment Pending Subject');

        // Filter status=pending (maps to not_started)
        $respPending = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'assessment', 'status' => 'pending']));
        $respPending->assertOk();
        $respPending->assertSee('Assessment Pending Subject');
        $respPending->assertDontSee('Assessment Passed Subject');
        $respPending->assertDontSee('Assessment Failed Subject');
    }

    // ==========================================
    // 3. CERTIFICATE TAB TESTS
    // ==========================================

    public function test_certificate_filters_by_year_type_and_search()
    {
        $settingContent = CertificateSetting::create([
            'certificate_type' => 'content',
            'issuer_name'      => 'Acme Academy',
        ]);

        $settingPlaylist = CertificateSetting::create([
            'certificate_type' => 'playlist',
            'issuer_name'      => 'Global Institute',
        ]);

        $cert1 = Certificate::create([
            'user_id'                => $this->user->id,
            'certificate_setting_id' => $settingContent->id,
            'certificate_type'       => 'content',
            'certificate_number'     => 'CERT-2024-001',
            'credential_id'          => 'CRED-AAA',
            'title'                  => 'Laravel Mastery 2024',
            'participant_name'       => $this->user->name,
            'issued_at'              => '2024-04-10 09:00:00',
        ]);

        $cert2 = Certificate::create([
            'user_id'                => $this->user->id,
            'certificate_setting_id' => $settingPlaylist->id,
            'certificate_type'       => 'playlist',
            'certificate_number'     => 'CERT-2025-002',
            'credential_id'          => 'CRED-BBB',
            'title'                  => 'PHP Architect 2025',
            'participant_name'       => $this->user->name,
            'issued_at'              => '2025-07-20 09:00:00',
        ]);

        // Filter Year = 2024
        $respCertYear = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'year' => '2024']));
        $respCertYear->assertOk();
        $respCertYear->assertSee('Laravel Mastery 2024');
        $respCertYear->assertDontSee('PHP Architect 2025');

        // Filter Type = playlist
        $respCertType = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'type' => 'playlist']));
        $respCertType->assertOk();
        $respCertType->assertSee('PHP Architect 2025');
        $respCertType->assertDontSee('Laravel Mastery 2024');

        // Search by keyword
        $respCertSearch = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'search' => 'Mastery']));
        $respCertSearch->assertOk();
        $respCertSearch->assertSee('Laravel Mastery 2024');
        $respCertSearch->assertDontSee('PHP Architect 2025');
    }

    // ==========================================
    // 4. ONGOING TAB TESTS
    // ==========================================

    public function test_ongoing_mode_routing_supports_type_playlist()
    {
        $respPlaylist = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'ongoing', 'type' => 'playlist']));
        $respPlaylist->assertOk();
        $respPlaylist->assertViewIs('user.learning.dashboard-partials.ongoing-playlist');

        $respContent = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'ongoing', 'type' => 'content']));
        $respContent->assertOk();
        $respContent->assertViewIs('user.learning.dashboard-partials.ongoing-content');
    }

    public function test_assessment_export_respects_filters()
    {
        $assessment2024 = Assesment::create([
            'title'         => 'Export Assessment 2024',
            'type'          => 'global',
            'status'        => 'OK',
            'fromdate'      => '2024-01-10',
            'todate'        => '2024-01-20',
            'time'          => 30,
            'minimum_score' => 70,
            'passing_grade' => 70,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $assessment2024->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Export Test',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('user.learning.assessment.export', ['year' => '2024']));
        $response->assertOk();
        $this->assertStringContainsString('Assessment_List_', (string) $response->headers->get('content-disposition'));
    }

    public function test_certificate_hero_stats_and_export_respects_filters()
    {
        $settingContent = CertificateSetting::create([
            'certificate_type' => 'content',
            'issuer_name'      => 'Acme Academy',
        ]);

        Certificate::create([
            'user_id'                => $this->user->id,
            'certificate_setting_id' => $settingContent->id,
            'certificate_type'       => 'content',
            'certificate_number'     => 'CERT-EXP-001',
            'credential_id'          => 'CRED-EXP',
            'title'                  => 'Certificate For Export 2024',
            'participant_name'       => $this->user->name,
            'issued_at'              => '2024-04-10 09:00:00',
        ]);

        // Export with year filter
        $responseExport = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'year' => '2024', 'certificate_export' => '1']));
        $responseExport->assertOk();
        $this->assertStringContainsString('My_Certificates_', (string) $responseExport->headers->get('content-disposition'));

        // Partial check template data-learning-certificate-hero
        $responsePartial = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'certificate', 'year' => '2024']));
        $responsePartial->assertOk();
        $responsePartial->assertSee('data-learning-certificate-hero', false);
    }

    public function test_ongoing_content_filters_with_fallback_year_and_month()
    {
        $contentOngoing = Content::create([
            'title'             => 'Ongoing Fallback Course',
            'slug'              => 'ongoing-fall-' . rand(1000, 9999),
            'classification_id' => $this->classification->id,
            'type'              => 'Video',
            'duration'          => '00:10:00',
            'learning_hours'    => 1.0,
            'status'            => 'OK',
            'active'            => 'Active',
            'playlistonly'      => 'N',
            'creator'           => $this->user->name,
        ]);

        $cuOngoing = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $contentOngoing->id,
            'finish'           => '0',
            'progress'         => 50,
            'current_time'     => 150,
            'duration_seconds' => 600,
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cuOngoing->id)->update([
            'created_at' => '2025-03-10 10:00:00',
            'updated_at' => '2025-03-10 10:00:00',
        ]);

        $resp = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', [
                'tab'   => 'ongoing',
                'year'  => '2025',
                'month' => 'March',
            ]));
        $resp->assertOk();
        $resp->assertSee('Ongoing Fallback Course');

        // Unmatching year
        $respMismatch = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', [
                'tab'   => 'ongoing',
                'year'  => '2024',
            ]));
        $respMismatch->assertOk();
        $respMismatch->assertDontSee('Ongoing Fallback Course');
    }
}
