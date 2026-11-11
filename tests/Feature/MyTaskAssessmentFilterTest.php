<?php

namespace Tests\Feature;

use App\Models\Assesment;
use App\Models\AssesmentAssigned;
use App\Models\AssesmentResult;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\Task;
use App\Models\TaskCodeRedemption;
use App\Models\TaskPlaylistUnlock;
use App\Models\TrainingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MyTaskAssessmentFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('role', '1')->first()
            ?? User::first();

        $this->admin = User::where('role', '2')->first()
            ?? User::where('role', '3')->first()
            ?? $this->user;
    }

    public function test_my_task_tab_includes_assessments_and_category_filters(): void
    {
        // 1. Create content completed 100% by user and link an assessment
        $content = Content::create([
            'title'      => 'Safety Induction Material',
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $content->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $assessment = Assesment::create([
            'title'               => 'Test Safety Assessment 2026',
            'slug'                => 'test-safety-assessment-2026-' . uniqid(),
            'type'                => 'content',
            'content_id'          => $content->id,
            'status'              => 'OK',
            'passing_grade'       => 75,
            'time_limit_minutes'  => 30,
        ]);

        // 2. Request My Task tab partial (default: all filter)
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));

        $response->assertStatus(200);

        // Verify Category Quick Filters are rendered
        $response->assertSee('Playlist');
        $response->assertSee('Content');
        $response->assertSee('Assessment');
        $response->assertSee('Others');

        // Verify Assessment is excluded from 'all' filter course table
        $response->assertDontSee('Test Safety Assessment 2026');

        // 3. Request My Task with 'asesment' filter
        $responseAssessment = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_filter' => 'asesment',
        ]));

        $responseAssessment->assertStatus(200);
        $responseAssessment->assertSee('Test Safety Assessment 2026');
        $responseAssessment->assertSee('taskModal-assessment_' . $assessment->id);
        $responseAssessment->assertSee('CATEGORY');
        $responseAssessment->assertSee('SCORE');
    }

    public function test_quick_filter_by_asesment_returns_only_assessments(): void
    {
        // 1. Create assessment with completed content
        $assContent = Content::create([
            'title'      => 'QC Training Video',
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $assContent->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $assessment = Assesment::create([
            'title'               => 'Quality Control Assessment 2026',
            'slug'                => 'quality-control-assessment-2026-' . uniqid(),
            'type'                => 'content',
            'content_id'          => $assContent->id,
            'status'              => 'OK',
            'passing_grade'       => 80,
        ]);

        // 2. Create a content task
        $content = Content::create([
            'title'      => 'Production Safety Video',
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $content->id,
            'user_id'    => $this->user->id,
            'progress'   => 50,
        ]);
        $task = Task::create([
            'parent_type' => 'content',
            'content_id'  => $content->id,
            'title'       => 'Production Safety Quiz Task',
            'type'        => 'action_plan',
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);

        // Filter by 'asesment'
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_filter' => 'asesment',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Quality Control Assessment 2026');
        $response->assertDontSee('Production Safety Quiz Task');
    }

    public function test_quick_filter_by_others_returns_global_standalone_tasks(): void
    {
        // Create a standalone task redeemed by user
        $globalTask = Task::create([
            'parent_type' => 'standalone',
            'title'       => 'Annual Company Feedback Task',
            'type'        => 'action_plan',
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);
        TaskCodeRedemption::create([
            'task_id' => $globalTask->id,
            'user_id' => $this->user->id,
        ]);

        // Create an assessment with completed content
        $assContent3 = Content::create([
            'title'      => 'Prerequisite Content',
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $assContent3->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);
        $assessment = Assesment::create([
            'title'      => 'Hidden Assessment in Others Filter',
            'slug'       => 'hidden-assessment-' . uniqid(),
            'type'       => 'content',
            'content_id' => $assContent3->id,
            'status'     => 'OK',
        ]);

        // Filter by 'others'
        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_filter' => 'others',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Annual Company Feedback Task');
        $response->assertDontSee('Hidden Assessment in Others Filter');
    }

    public function test_empty_task_tab_renders_cleanly_without_exception(): void
    {
        $freshUser = User::create([
            'name'       => 'Empty State Fresh User',
            'email'      => 'freshuser_' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'role'       => '1',
            'team'       => 'TEST-TEAM',
            'department' => 'TEST-DEPT',
            'division'   => 'TEST-DIV',
            'id_emp'     => 'EMP' . rand(10000, 99999),
            'ssohash'    => \Illuminate\Support\Str::random(32),
            'sap_code'   => 'SAP' . rand(1000, 9999),
            'active'     => 1,
        ]);

        $response = $this->actingAs($freshUser)->get(route('user.learning.dashboard.partial', [
            'tab' => 'my-task',
        ]));

        $response->assertStatus(200);
        $response->assertSee('My Tasks');
        $response->assertSee('All Tasks');
        $response->assertSee('Total Tasks');
        $response->assertSee('Content Playlist');
        $response->assertSee('No tasks available');
        $response->assertDontSee('Gagal memuat konten');
    }

    public function test_quick_filter_by_content_playlist_returns_only_content_playlist_tasks(): void
    {
        // 1. Create a training plan (playlist)
        $plan = TrainingPlan::create([
            'title'       => 'Onboarding Leadership Program',
            'slug'        => 'onboarding-leadership-' . uniqid(),
            'status'      => 'publish',
            'created_by'  => $this->admin->id,
        ]);

        // 2. Create a content that belongs to this playlist (Content Playlist)
        $cpContent = Content::create([
            'title'        => 'Leadership Video Part 1',
            'type'         => 'video',
            'status'       => 'mandatory',
            'playlistonly' => 'Y',
            'active'       => 'Active',
            'created_by'   => $this->admin->id,
        ]);
        $plan->contents()->attach($cpContent->id, ['content_order' => 1, 'created_at' => now(), 'updated_at' => now()]);

        // User opens this content
        ContentUser::create([
            'content_id' => $cpContent->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // Create task under this playlist content
        $cpTask = Task::create([
            'parent_type' => 'content',
            'content_id'  => $cpContent->id,
            'title'       => 'Leadership Reflection Activity',
            'type'        => 'action_plan',
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);

        // 3. Create a regular standalone content (All Content, not in any playlist)
        $regContent = Content::create([
            'title'        => 'General Excel Mastery',
            'type'         => 'document',
            'status'       => 'mandatory',
            'playlistonly' => 'N',
            'active'       => 'Active',
            'created_by'   => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $regContent->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);
        $regTask = Task::create([
            'parent_type' => 'content',
            'content_id'  => $regContent->id,
            'title'       => 'Excel Formula Exercise',
            'type'        => 'file_submission',
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);

        // 4. Request with content_playlist filter
        $responseCp = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_filter' => 'content_playlist',
        ]));

        $responseCp->assertStatus(200);
        $responseCp->assertSee('Leadership Reflection Activity');
        $responseCp->assertSee('Content Playlist');
        $responseCp->assertDontSee('Excel Formula Exercise');

        // 5. Request with regular content filter
        $responseReg = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_filter' => 'content',
        ]));

        $responseReg->assertStatus(200);
        $responseReg->assertSee('Excel Formula Exercise');
        $responseReg->assertDontSee('Leadership Reflection Activity');
    }

    public function test_assessment_does_not_show_revision_badge_but_task_revision_shows_on_all_tasks(): void
    {
        // 1. Create a failed assessment
        $assContent = Content::create([
            'title'      => 'Failed Assessment Content ' . uniqid(),
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $assContent->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);
        $assessment = Assesment::create([
            'title'         => 'Failed Exam ' . uniqid(),
            'slug'          => 'failed-exam-' . uniqid(),
            'type'          => 'content',
            'content_id'    => $assContent->id,
            'status'        => 'OK',
            'passing_grade' => 80,
        ]);
        \App\Models\AssesmentResult::create([
            'assesment_id' => $assessment->id,
            'user_id'      => $this->user->id,
            'batch'        => '1',
            'score'        => 50,
            'pass'         => 0,
            'final'        => 1,
            'user_answer'  => [],
        ]);

        // When only assessment is failed (no task revision), neither All Tasks nor Assessment has badge
        $resp1 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));
        $resp1->assertStatus(200);
        $this->assertStringNotContainsString('revision required', $resp1->getContent());

        // 2. Add a task with resubmit_requested
        $plan = \App\Models\TrainingPlan::create([
            'title' => 'Revision Plan ' . uniqid(),
            'slug'  => 'rev-plan-' . uniqid(),
        ]);
        \App\Models\TaskPlaylistUnlock::firstOrCreate([
            'user_id'          => $this->user->id,
            'training_plan_id' => $plan->id,
        ]);
        $task = Task::create([
            'parent_type'      => 'playlist',
            'training_plan_id' => $plan->id,
            'title'            => 'Task Needing Revision ' . uniqid(),
            'type'             => 'open_ended',
            'status'           => 'published',
            'created_by'       => $this->admin->id,
        ]);
        \App\Models\TaskSubmission::create([
            'task_id'            => $task->id,
            'user_id'            => $this->user->id,
            'status'             => 'rejected',
            'resubmit_requested' => true,
            'feedback'           => 'Please revise question 1',
            'submitted_at'       => now(),
        ]);

        // Now, All Tasks and Playlist DO show the revision badge, but Assessment does NOT
        $resp2 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));
        $resp2->assertStatus(200);
        $html = $resp2->getContent();

        // All Tasks pill has badge 1
        $this->assertStringContainsString('Filter by All Tasks (1 revision required)', $html);
        // Playlist pill has badge 1
        $this->assertStringContainsString('Filter by Playlist (1 revision required)', $html);
        // Assessment pill does NOT have revision required
        $this->assertStringNotContainsString('Filter by Assessment (', $html);
    }

    public function test_assessments_are_ordered_from_newest_first_at_the_top(): void
    {
        $content = Content::create([
            'title'      => 'Safety Ind ' . uniqid(),
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);
        ContentUser::create([
            'content_id' => $content->id,
            'user_id'    => $this->user->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $assOlder = Assesment::create([
            'title'         => 'AAA_Older Assessment ' . uniqid(),
            'slug'          => 'older-ass-' . uniqid(),
            'type'          => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 70,
            'created_at'    => now()->subDays(5),
        ]);

        $assNewer = Assesment::create([
            'title'         => 'ZZZ_Newer Assessment ' . uniqid(),
            'slug'          => 'newer-ass-' . uniqid(),
            'type'          => 'content',
            'content_id'    => $content->id,
            'status'        => 'OK',
            'passing_grade' => 70,
            'created_at'    => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', [
            'tab'            => 'my-task',
            'my_task_filter' => 'asesment',
        ]));

        $response->assertStatus(200);
        $html = $response->getContent();

        $posNewer = strpos($html, $assNewer->title);
        $posOlder = strpos($html, $assOlder->title);

        $this->assertNotFalse($posNewer, 'Newer assessment must be visible');
        $this->assertNotFalse($posOlder, 'Older assessment must be visible');
        $this->assertTrue($posNewer < $posOlder, 'Newer assessment must appear before (above) older assessment');
    }

    public function test_content_task_does_not_appear_in_my_task_until_content_is_100_percent(): void
    {
        $content = Content::create([
            'title'      => 'Specialized Machine Operation ' . uniqid(),
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $this->admin->id,
        ]);

        // User only opened content, progress 40% (not 100%)
        $cu = ContentUser::create([
            'content_id'      => $content->id,
            'user_id'         => $this->user->id,
            'progress'        => 40,
            'finish'          => '0',
            'learning_status' => 'in_progress',
        ]);

        $task = Task::create([
            'parent_type' => 'content',
            'content_id'  => $content->id,
            'title'       => 'Machine Operation Checklist ' . uniqid(),
            'type'        => 'file_submission',
            'status'      => 'published',
            'created_by'  => $this->admin->id,
        ]);

        // 1. When progress < 100%, task must NOT appear in My Task
        $resp1 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));
        $resp1->assertStatus(200);
        $resp1->assertDontSee($task->title);

        // 2. When user completes content 100%, task appears in My Task
        $cu->update([
            'progress'        => 100,
            'finish'          => '1',
            'learning_status' => 'completed',
        ]);

        $resp2 = $this->actingAs($this->user)->get(route('user.learning.dashboard.partial', ['tab' => 'my-task']));
        $resp2->assertStatus(200);
        $resp2->assertSee($task->title);
    }
}
