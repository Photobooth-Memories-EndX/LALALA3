<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\ContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\OngoingContentService;
use App\Services\Learning\OngoingPlaylistService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LearningOngoingContentTest extends TestCase
{
    use DatabaseTransactions;

    private OngoingContentService $contentService;
    private OngoingPlaylistService $playlistService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentService = app(OngoingContentService::class);
        $this->playlistService = app(OngoingPlaylistService::class);

        $this->user = User::factory()->create([
            'team' => 'Quality Assurance',
            'department' => 'Technology',
            'division' => 'Engineering',
            'ssohash' => 'ongoing-content-test-' . uniqid(),
            'sap_code' => 'ONGOING-TEST-' . rand(1000, 9999),
            'role' => User::ROLE_USER ?? 1,
        ]);
    }

    private function createDummyContent(string $title = 'Sample Content', string $type = 'video'): Content
    {
        return Content::create([
            'title' => $title,
            'type' => $type,
            'duration' => '00:10:00',
            'learning_hours' => 1.0,
            'has_deadline' => false,
            'active' => 'Active',
        ]);
    }

    private function attachUserContent(Content $content, int $progress, string $finish = '0'): ContentUser
    {
        return ContentUser::create([
            'user_id' => $this->user->id,
            'content_id' => $content->id,
            'progress' => $progress,
            'finish' => $finish,
            'status' => 'optional',
            'learning_status' => $progress > 0 ? 'in_progress' : 'not_started',
        ]);
    }

    private function defaultFilters(): array
    {
        return [
            'search' => '', 'status' => 'all', 'year' => 'all', 'activity_sort' => 'newest_activity',
            'progress_sort' => 'all', 'date_from' => null, 'date_to' => null, 'learning_hour' => 'all',
            'quick_status' => 'all', 'types' => [], 'learning_status' => [], 'progress_buckets' => [],
            'durations' => [], 'months' => [], 'current_times' => [], 'last_pages' => [], 'total_pages' => [],
        ];
    }

    /** @test */
    public function it_excludes_content_with_progress_0_4_and_under_5(): void
    {
        $c0 = $this->createDummyContent('Content 0%');
        $this->attachUserContent($c0, 0);

        $c4 = $this->createDummyContent('Content 4%');
        $this->attachUserContent($c4, 4);

        $cards = $this->contentService->cards($this->user->id, $this->defaultFilters());
        $contentIds = $cards->pluck('list.content_id')->toArray();

        $this->assertNotContains($c0->id, $contentIds);
        $this->assertNotContains($c4->id, $contentIds);
    }

    /** @test */
    public function it_includes_non_playlist_content_at_5_50_and_80_percent(): void
    {
        $c5 = $this->createDummyContent('Content 5%');
        $this->attachUserContent($c5, 5);

        $c50 = $this->createDummyContent('Content 50%');
        $this->attachUserContent($c50, 50);

        $c80 = $this->createDummyContent('Content 80%');
        $this->attachUserContent($c80, 80);

        $cards = $this->contentService->cards($this->user->id, $this->defaultFilters());
        $contentIds = $cards->pluck('list.content_id')->toArray();

        $this->assertContains($c5->id, $contentIds);
        $this->assertContains($c50->id, $contentIds);
        $this->assertContains($c80->id, $contentIds);
    }

    /** @test */
    public function it_excludes_playlist_child_content_at_5_and_80_percent_from_content_mode(): void
    {
        $child5 = $this->createDummyContent('Playlist Child 5%');
        $this->attachUserContent($child5, 5);

        $child80 = $this->createDummyContent('Playlist Child 80%');
        $this->attachUserContent($child80, 80);

        $plan = TrainingPlan::create([
            'title' => 'Test Playlist Package',
            'total_duration_seconds' => 1200,
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $child5->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $child80->id, 'content_order' => 2]);

        $cards = $this->contentService->cards($this->user->id, $this->defaultFilters());
        $contentIds = $cards->pluck('list.content_id')->toArray();

        $this->assertNotContains($child5->id, $contentIds);
        $this->assertNotContains($child80->id, $contentIds);
    }

    /** @test */
    public function it_correctly_excludes_content_belonging_to_multiple_playlists(): void
    {
        $multiChild = $this->createDummyContent('Multi Playlist Member');
        $this->attachUserContent($multiChild, 50);

        $planA = TrainingPlan::create(['title' => 'Playlist Alpha', 'total_duration_seconds' => 600]);
        $planB = TrainingPlan::create(['title' => 'Playlist Beta', 'total_duration_seconds' => 600]);

        TrainingPlanDetail::create(['trainingplan_id' => $planA->id, 'content_id' => $multiChild->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $planB->id, 'content_id' => $multiChild->id, 'content_order' => 1]);

        $cards = $this->contentService->cards($this->user->id, $this->defaultFilters());
        $contentIds = $cards->pluck('list.content_id')->toArray();

        $this->assertNotContains($multiChild->id, $contentIds);
    }

    /** @test */
    public function it_preserves_existing_playlist_ongoing_service_logic(): void
    {
        $playlistContent = $this->createDummyContent('Playlist Inner Content');
        $this->attachUserContent($playlistContent, 50);

        $plan = TrainingPlan::create([
            'title' => 'User Ongoing Playlist',
            'total_duration_seconds' => 600,
            'active' => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $playlistContent->id, 'content_order' => 1]);

        \App\Models\PlaylistContentUser::create([
            'user_id' => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id' => $playlistContent->id,
            'progress' => 50,
            'finish' => '0',
        ]);

        $userPlaylists = $this->playlistService->userPlaylists($this->user->id);
        $playlistIds = $userPlaylists->pluck('id')->toArray();

        $this->assertContains($plan->id, $playlistIds);
    }

    /** @test */
    public function it_allows_existing_filters_like_search_and_progress_buckets_to_continue_working(): void
    {
        $cUnique = $this->createDummyContent('Alpha Beta Gamma Search Target');
        $this->attachUserContent($cUnique, 30);

        $cOther = $this->createDummyContent('Delta Epsilon');
        $this->attachUserContent($cOther, 40);

        $filtersSearch = array_merge($this->defaultFilters(), ['search' => 'Alpha Beta']);
        $cardsSearch = $this->contentService->cards($this->user->id, $filtersSearch);
        $searchIds = $cardsSearch->pluck('list.content_id')->toArray();

        $this->assertContains($cUnique->id, $searchIds);
        $this->assertNotContains($cOther->id, $searchIds);

        // Progress bucket 26-50
        $filtersBucket = array_merge($this->defaultFilters(), ['progress_buckets' => ['26-50']]);
        $cardsBucket = $this->contentService->cards($this->user->id, $filtersBucket);
        $bucketIds = $cardsBucket->pluck('list.content_id')->toArray();

        $this->assertContains($cUnique->id, $bucketIds);
        $this->assertContains($cOther->id, $bucketIds);
    }
}
