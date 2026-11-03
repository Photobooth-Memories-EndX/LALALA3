<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContentLearningSidebarTaskCardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_content_learning_sidebar_task_card_renders_english_text(): void
    {
        $user = User::factory()->create(['role' => '1']);
        $admin = User::where('role', '2')->first() ?? $user;

        $content = Content::create([
            'title'      => 'Test Content Task Card ' . uniqid(),
            'type'       => 'video',
            'status'     => 'mandatory',
            'active'     => 'Active',
            'created_by' => $admin->id,
        ]);

        Task::create([
            'parent_type' => 'content',
            'content_id'  => $content->id,
            'title'       => 'Sidebar Task Test',
            'type'        => 'action_plan',
            'status'      => 'published',
            'created_by'  => $admin->id,
        ]);

        // 1. Content completed (progress = 100)
        $completedCu = new ContentUser();
        $completedCu->learning_status = 'completed';

        $htmlCompleted = view('user.content.components.content-learning-sidebar', [
            'content'      => $content,
            'content_user' => $completedCu,
            'progress'     => 100,
            'status'       => 'completed',
        ])->render();

        $this->assertStringContainsString('Content completed. Start your task now.', $htmlCompleted);
        $this->assertStringContainsString('Open Task', $htmlCompleted);
        $this->assertStringNotContainsString('Materi telah selesai', $htmlCompleted);
        $this->assertStringNotContainsString('Buka Task', $htmlCompleted);

        // 2. Content not completed (progress = 50)
        $uncompletedCu = new ContentUser();
        $uncompletedCu->learning_status = 'in_progress';

        $htmlUncompleted = view('user.content.components.content-learning-sidebar', [
            'content'      => $content,
            'content_user' => $uncompletedCu,
            'progress'     => 50,
            'status'       => 'in_progress',
        ])->render();

        $this->assertStringContainsString('Complete 100% of the content to unlock Task.', $htmlUncompleted);
        $this->assertStringContainsString('Task Locked', $htmlUncompleted);
        $this->assertStringNotContainsString('Selesaikan materi', $htmlUncompleted);
        $this->assertStringNotContainsString('Task Terkunci', $htmlUncompleted);
    }
}
