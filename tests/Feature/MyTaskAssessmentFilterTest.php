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
        $response->assertSee('No tasks available');
        $response->assertDontSee('Gagal memuat konten');
    }
}
