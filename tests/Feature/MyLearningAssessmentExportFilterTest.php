<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class MyLearningAssessmentExportFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Content $content;
    protected TrainingPlan $playlist;
    protected Assesment $assessmentPassed;
    protected Assesment $assessmentFailed;
    protected Assesment $assessmentPending;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'team'       => 'Export Team',
            'department' => 'Operations',
            'division'   => 'Plant',
            'ssohash'    => 'exp-' . Str::uuid(),
            'sap_code'   => 'SAP-EXP-' . rand(1000, 9999),
            'id_emp'     => 'EMP-EXP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $classification = Classification::first() ?? Classification::create([
            'name'        => 'Class ' . Str::random(5),
            'description' => 'Desc',
        ]);

        $this->content = Content::create([
            'title'             => 'Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $classification->id,
            'active'            => 'Active',
        ]);

        $this->playlist = TrainingPlan::create([
            'title'       => 'Playlist ' . Str::random(5),
            'description' => 'Desc',
            'status'      => 'published',
        ]);
        $this->playlist->contents()->attach($this->content->id, ['content_order' => 1]);

        $tag = Str::random(6);

        // 1. Passed Assessment
        $this->assessmentPassed = Assesment::create([
            'title'                 => "AssessmentPassed_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $this->content->id,
            'status'                => 'OK',
            'fromdate'              => '2026-05-01 09:00:00',
            'todate'                => '2026-05-30 17:00:00',
            'passing_grade'         => 75,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentPassed->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $this->assessmentPassed->id,
            'score'        => 90,
            'pass'         => 1,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['1' => 'A'],
        ]);

        // 2. Failed Assessment (with remedial chance)
        $this->assessmentFailed = Assesment::create([
            'title'                 => "AssessmentFailed_{$tag}",
            'type'                  => 'global',
            'assasment_content_for' => 'content',
            'status'                => 'OK',
            'fromdate'              => '2026-06-01 09:00:00',
            'todate'                => '2026-06-30 17:00:00',
            'passing_grade'         => 80,
            'total_remedials'       => 0,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentFailed->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $this->assessmentFailed->id,
            'score'        => 55,
            'pass'         => 0,
            'final'        => 1,
            'batch'        => 1,
            'user_answer'  => ['1' => 'B'],
        ]);

        // 3. Pending Assessment (untaken, available)
        $this->assessmentPending = Assesment::create([
            'title'                 => "AssessmentPending_{$tag}",
            'type'                  => 'trainingplan',
            'assasment_content_for' => 'content',
            'trainingplan_id'       => $this->playlist->id,
            'status'                => 'OK',
            'fromdate'              => now()->subDays(5)->format('Y-m-d H:i:s'),
            'todate'                => now()->addDays(30)->format('Y-m-d H:i:s'),
            'passing_grade'         => 70,
        ]);
        AssesmentAssigned::create([
            'assesment_id'         => $this->assessmentPending->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);
        AssesmentResult::create([
            'user_id'      => $this->user->id,
            'assesment_id' => $this->assessmentPending->id,
            'score'        => null,
            'pass'         => 0,
            'final'        => 0,
            'batch'        => 1,
            'user_answer'  => [],
        ]);
    }

    public function test_export_endpoint_returns_download_and_applies_status_filter(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->user)->get(route('user.learning.assessment.export', [
            'assessment_filter_status' => 'passed',
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            $array = $export->array();
            $headers = $array[0];
            $this->assertSame(['No', 'Assessment', 'Type', 'Category', 'Date & Time', 'Score', 'Passing Grade', 'Status', 'Action / Result'], $headers);

            // Row count should be 1 data row (header + 1 passed assessment)
            $this->assertCount(2, $array);
            $this->assertStringContainsString('AssessmentPassed', $array[1][1]);
            $this->assertSame('Passed', $array[1][7]);
            $this->assertSame('Passed', $array[1][8]);
            return true;
        });
    }

    public function test_export_applies_type_and_search_filter(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->user)->get(route('user.learning.assessment.export', [
            'assessment_filter_type' => 'trainingplan',
            'assessment_search'      => 'AssessmentPending',
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            $array = $export->array();
            $this->assertCount(2, $array);
            $this->assertStringContainsString('AssessmentPending', $array[1][1]);
            $this->assertSame('Playlist Assessment', $array[1][2]);
            $this->assertSame('Pending', $array[1][7]);
            $this->assertSame('Continue', $array[1][8]);
            return true;
        });
    }

    public function test_export_without_filter_returns_all_assessments(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->user)->get(route('user.learning.assessment.export'));

        $response->assertSuccessful();

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            $array = $export->array();
            $this->assertGreaterThanOrEqual(4, count($array));
            $titles = collect($array)->pluck(1)->implode(' ');
            $this->assertStringContainsString('AssessmentPassed', $titles);
            $this->assertStringContainsString('AssessmentFailed', $titles);
            $this->assertStringContainsString('AssessmentPending', $titles);
            return true;
        });
    }

    public function test_export_applies_year_and_month_filter(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->user)->get(route('user.learning.assessment.export', [
            'assessment_filter_year'  => '2026',
            'assessment_filter_month' => '5',
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            $array = $export->array();
            $this->assertCount(2, $array);
            $this->assertStringContainsString('AssessmentPassed', $array[1][1]);
            return true;
        });
    }

    public function test_export_status_and_action_consistency_for_expired_assessment(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->user)->get(route('user.learning.assessment.export', [
            'assessment_search' => 'AssessmentFailed',
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) {
            $array = $export->array();
            $this->assertCount(2, $array);
            $this->assertStringContainsString('AssessmentFailed', $array[1][1]);
            // 2026-06-30 is in the past, so it is expired
            $this->assertSame('Expired', $array[1][7]);
            $this->assertSame('Expired', $array[1][8]);
            return true;
        });
    }

    public function test_export_shows_start_test_for_untaken_eligible_assessment(): void
    {
        Excel::fake();

        $contentUntaken = Content::create([
            'title'             => 'Untaken Content ' . Str::random(5),
            'type'              => 'video',
            'classification_id' => $this->content->classification_id,
            'active'            => 'Active',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $contentUntaken->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $tag = Str::random(6);
        $assessmentUntaken = Assesment::create([
            'title'                 => "AssessmentUntaken_{$tag}",
            'type'                  => 'content',
            'assasment_content_for' => 'content',
            'content_id'            => $contentUntaken->id,
            'status'                => 'OK',
            'fromdate'              => now()->subDays(2)->format('Y-m-d H:i:s'),
            'todate'                => now()->addDays(20)->format('Y-m-d H:i:s'),
            'passing_grade'         => 70,
        ]);

        AssesmentAssigned::create([
            'assesment_id'         => $assessmentUntaken->id,
            'assigned_id'          => $this->user->id_emp,
            'assigned_type'        => 'user',
            'assigned_description' => 'Test',
        ]);

        $response = $this->actingAs($this->user)->get(route('user.learning.assessment.export', [
            'assessment_search' => "AssessmentUntaken_{$tag}",
        ]));

        $response->assertSuccessful();

        Excel::assertDownloaded('Assessment_List_' . now()->format('Y-m-d') . '.xlsx', function ($export) use ($tag) {
            $array = $export->array();
            $this->assertCount(2, $array);
            $this->assertStringContainsString("AssessmentUntaken_{$tag}", $array[1][1]);
            $this->assertSame('Pending', $array[1][7]);
            $this->assertSame('Start Test', $array[1][8]);
            return true;
        });
    }
}
