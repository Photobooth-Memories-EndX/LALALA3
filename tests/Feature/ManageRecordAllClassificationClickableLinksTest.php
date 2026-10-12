<?php

namespace Tests\Feature;

use App\Models\OfflineTraining;
use App\Models\OfflineTrainingUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManageRecordAllClassificationClickableLinksTest extends TestCase
{
    protected User $admin;
    protected ?User $regularUser;
    protected array $cleanupTrainingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', '2')->first() ?? User::first();
        $this->regularUser = User::where('role', '1')->first();
    }

    protected function tearDown(): void
    {
        if (!empty($this->cleanupTrainingIds)) {
            OfflineTrainingUser::whereIn('offline_training_id', $this->cleanupTrainingIds)->forceDelete();
            OfflineTraining::whereIn('id', $this->cleanupTrainingIds)->forceDelete();
            $this->cleanupTrainingIds = [];
        }

        DB::table('offline_training_users')->where('company', 'TEST_CLICKABLE_LINKS_CORP')->delete();
        DB::table('offline_trainings')->where('category', 'TEST_CLICKABLE_LINKS_CAT')->delete();

        parent::tearDown();
    }

    protected function createTestOfflineTraining(array $trainingAttrs = [], array $participants = []): OfflineTraining
    {
        $training = OfflineTraining::create(array_merge([
            'course_name'           => 'Test Clickable Course ' . uniqid(),
            'type'                  => 'In-House',
            'category'              => 'TEST_CLICKABLE_LINKS_CAT',
            'main_classification'   => 'Basic & Common Duties',
            'counted_corporate_kpi' => 1,
            'duration_seconds'      => 14400,
            'learning_hours'        => 4.0,
            'start_date'            => '2026-09-01',
            'finish_date'           => '2026-09-01',
            'created_by'            => $this->admin->id,
        ], $trainingAttrs));

        $this->cleanupTrainingIds[] = $training->id;

        foreach ($participants as $p) {
            OfflineTrainingUser::create(array_merge([
                'offline_training_id' => $training->id,
                'user_id'             => $this->admin->id,
                'employee_id'         => '109988',
                'name'                => 'Clickable Test Participant',
                'grade'               => 'G4',
                'job_position'        => 'Analyst',
                'department'          => 'Information Technology',
                'company'             => 'TEST_CLICKABLE_LINKS_CORP',
                'progress'            => 100,
                'learning_hours'      => 4.0,
                'start_date'          => '2026-09-01',
                'finish_date'         => '2026-09-01',
            ], $p));
        }

        return $training;
    }

    /**
     * Test guest and unauthorized user access to participant detail.
     */
    public function test_guest_and_unauthorized_user_cannot_access_participant_detail(): void
    {
        $response = $this->get('/manage/record/all/participant/109988');
        $response->assertRedirect('/login');

        if ($this->regularUser) {
            $response = $this->actingAs($this->regularUser)
                ->get('/manage/record/all/participant/109988');
            $response->assertStatus(403);
        }
    }

    /**
     * Test classification DataTable renders clickable links for employee and learning title.
     */
    public function test_classification_datatable_renders_clickable_employee_and_course_links(): void
    {
        $training = $this->createTestOfflineTraining([], [
            [
                'employee_id' => '998877',
                'name'        => 'Nuryadi Click Test',
            ]
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/manage/record/all/classification/basic-common-duties/datatable?employee_id=998877');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];

        // Check employee link
        $this->assertArrayHasKey('employee_name', $row);
        $this->assertStringContainsString('participant-link', $row['employee_name']);
        $this->assertStringContainsString('/manage/record/all/participant/998877', $row['employee_name']);
        $this->assertStringContainsString('classification=basic-common-duties', $row['employee_name']);
        $this->assertStringContainsString('Nuryadi Click Test', $row['employee_name']);

        // Check learning title link
        $this->assertArrayHasKey('learning_title', $row);
        $this->assertStringContainsString('course-link', $row['learning_title']);
        $this->assertStringContainsString('/manage/record/offline/course/' . $training->id, $row['learning_title']);
        $this->assertStringContainsString('classification=basic-common-duties', $row['learning_title']);
        $this->assertStringContainsString('from=all', $row['learning_title']);
    }

    /**
     * Test participant detail view displays participant profile, back button with classification context, and KPI cards.
     */
    public function test_participant_detail_view_renders_successfully_with_context(): void
    {
        $training = $this->createTestOfflineTraining([], [
            [
                'employee_id'  => '998877',
                'name'         => 'Nuryadi Click Test',
                'grade'        => 'G4',
                'job_position' => 'Senior Developer',
                'department'   => 'Digital Innovation',
            ]
        ]);

        $url = route('manage.record.all.participant', [
            'identifier'     => '998877',
            'classification' => 'basic-common-duties',
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertSee('Nuryadi Click Test');
        $response->assertSee('998877');
        $response->assertSee('Back to Basic &amp; Common Duties', false);
        $response->assertSee(route('manage.record.all.classification', 'basic-common-duties'));
        $response->assertSee('Participant Detail');
        $response->assertSee('Total Learning');
        $response->assertSee('Finished');
        $response->assertSee('Ongoing');
        $response->assertSee('Learning Hours');
    }

    /**
     * Test participant detail view returns 404 for unknown participant.
     */
    public function test_participant_detail_returns_404_for_unknown_participant(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/manage/record/all/participant/NON_EXISTENT_ID_999999');

        $response->assertStatus(404);
    }

    /**
     * Test participant datatable returns valid JSON with combined records.
     */
    public function test_participant_datatable_returns_data(): void
    {
        $training = $this->createTestOfflineTraining([], [
            [
                'employee_id' => '998877',
                'name'        => 'Nuryadi Click Test',
            ]
        ]);

        $url = route('manage.record.all.participant.datatable', [
            'identifier'     => '998877',
            'classification' => 'basic-common-duties',
        ]);

        $response = $this->actingAs($this->admin)->getJson($url);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $row = $data[0];
        $this->assertArrayHasKey('source_badge', $row);
        $this->assertArrayHasKey('learning_title', $row);
        $this->assertStringContainsString('course-link', $row['learning_title']);
        $this->assertArrayHasKey('action', $row);
    }

    /**
     * Test participant exports (Excel, CSV, PDF).
     */
    public function test_participant_exports_download_successfully(): void
    {
        $training = $this->createTestOfflineTraining([], [
            [
                'employee_id' => '998877',
                'name'        => 'Nuryadi Click Test',
            ]
        ]);

        // Excel export
        $excelResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.all.participant.export.excel', [
                'identifier'     => '998877',
                'classification' => 'basic-common-duties',
            ]));
        $excelResponse->assertStatus(200);
        $this->assertTrue($excelResponse->headers->contains('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));

        // CSV export
        $csvResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.all.participant.export.csv', [
                'identifier'     => '998877',
                'classification' => 'basic-common-duties',
            ]));
        $csvResponse->assertStatus(200);
        $this->assertTrue(str_contains($csvResponse->headers->get('content-type') ?? '', 'text/csv'));

        // PDF export
        $pdfResponse = $this->actingAs($this->admin)
            ->get(route('manage.record.all.participant.export.pdf', [
                'identifier'     => '998877',
                'classification' => 'basic-common-duties',
            ]));
        $pdfResponse->assertStatus(200);
        $this->assertTrue($pdfResponse->headers->contains('content-type', 'application/pdf'));
    }

    /**
     * Test smart course redirect endpoint.
     */
    public function test_course_redirect_handles_offline_and_online(): void
    {
        $training = $this->createTestOfflineTraining([], []);

        $response = $this->actingAs($this->admin)
            ->get(route('manage.record.all.course', [
                'type'           => 'offline',
                'id'             => $training->id,
                'classification' => 'basic-common-duties',
            ]));

        $response->assertRedirect(route('manage.record.offline.course', [
            'training'       => $training->id,
            'from'           => 'all',
            'classification' => 'basic-common-duties',
        ]));

        // Online Content redirect test
        $content = DB::table('content')->first();
        if ($content) {
            $responseContent = $this->actingAs($this->admin)
                ->get(route('manage.record.all.course', [
                    'type'           => 'content',
                    'id'             => $content->id,
                    'classification' => 'basic-common-duties',
                ]));
            $responseContent->assertRedirect(route('learning.manage.content', [
                'slug'           => $content->slug,
                'from'           => 'all',
                'classification' => 'basic-common-duties',
            ]));
        }

        // Online Playlist redirect test
        $tp = DB::table('trainingplan')->first();
        if ($tp && !empty($tp->slug)) {
            $responseTp = $this->actingAs($this->admin)
                ->get(route('manage.record.all.course', [
                    'type'           => 'playlist',
                    'id'             => $tp->id,
                    'classification' => 'basic-common-duties',
                ]));
            $this->assertTrue($responseTp->isRedirect());
            $this->assertStringContainsString('/playlist/' . $tp->slug, $responseTp->headers->get('Location'));
        }
    }

    /**
     * Test offline course detail respects from=all and classification for back navigation.
     */
    public function test_offline_course_detail_respects_from_all_back_navigation(): void
    {
        $training = $this->createTestOfflineTraining([], []);

        $url = route('manage.record.offline.course', [
            'training'       => $training->id,
            'from'           => 'all',
            'classification' => 'basic-common-duties',
        ]);

        $response = $this->actingAs($this->admin)->get($url);

        $response->assertStatus(200);
        $response->assertSee('Back to Basic &amp; Common Duties', false);
        $response->assertSee(route('manage.record.all.classification', 'basic-common-duties'));
    }
}
