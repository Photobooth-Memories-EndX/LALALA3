<?php

namespace Tests\Feature;

use App\Exports\ExportMyLearningRecord;
use App\Http\Controllers\LearningRecordController;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MyLearningRecordDatabaseLearningHourTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordController $controller;
    protected CertificateEligibilityService $eligibilityService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(LearningRecordController::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);

        $this->user = User::factory()->create([
            'name'       => 'Test Learner ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-lh-' . Str::uuid(),
            'sap_code'   => 'SAP-LH-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Safety & Environment'],
            ['slug' => 'safety-environment']
        );
    }

    /**
     * TEST 1: Direct Content takes learning_hours directly from database column.
     */
    public function test_content_takes_learning_hours_directly_from_database(): void
    {
        // Custom learning_hours in DB that differs from what naive duration math would yield
        $content = Content::create([
            'title'             => 'DB Learning Hour Content',
            'slug'              => 'db-lh-content-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 1.5, // Explicitly set in DB
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'optional',
        ]);

        $this->actingAs($this->user);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildContentRows');
        $method->setAccessible(true);

        $certMethod = $reflection->getMethod('certificateContext');
        $certMethod->setAccessible(true);
        $cert = $certMethod->invoke($this->controller, $this->eligibilityService);

        $contentsMethod = $reflection->getMethod('getCompletedContents');
        $contentsMethod->setAccessible(true);
        $completedContents = $contentsMethod->invoke($this->controller, (int) $this->user->id);

        $rows = $method->invoke($this->controller, $completedContents, $cert);
        $row = $rows->firstWhere('slug', $content->slug);

        $this->assertNotNull($row);
        $this->assertEquals(0.0833, $row['learning_hour_bucket']);
        $this->assertEquals(0.0833, $row['learning_hour_value']);
        $this->assertEquals('0.08 hour', $row['learning_hour']);
        $this->assertEquals('Content', $row['category']);
        $this->assertEquals('Safety & Environment', $row['classification']);
        $this->assertEquals('Video', $row['type']);
        $this->assertEquals('Optional', $row['subtitle']);
    }

    /**
     * TEST 2: Playlist takes learning_hours directly from database column.
     */
    public function test_playlist_takes_learning_hours_directly_from_database(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'DB Learning Hour Playlist',
            'slug'                   => 'db-lh-plan-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 7200,
            'learning_hours'         => 3.5, // Explicitly set in DB
        ]);

        $content = Content::create([
            'title'             => 'Playlist Child',
            'slug'              => 'playlist-child-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '01:00:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 0,
        ]);

        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->actingAs($this->user);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('buildPlaylistRows');
        $method->setAccessible(true);

        $completedPlaylists = app(\App\Services\Learning\CompletedPlaylistService::class)->forUser((int) $this->user->id);
        $certMethod = $reflection->getMethod('certificateContext');
        $certMethod->setAccessible(true);
        $cert = $certMethod->invoke($this->controller, $this->eligibilityService);

        $slugsMethod = $reflection->getMethod('playlistFirstContentSlug');
        $slugsMethod->setAccessible(true);
        $slugs = $slugsMethod->invoke($this->controller, $completedPlaylists);

        $rows = $method->invoke($this->controller, $completedPlaylists, $cert, $slugs, collect());
        $row = $rows->firstWhere('title', 'DB Learning Hour Playlist');

        $this->assertNotNull($row);
        $this->assertEquals(1.0, $row['learning_hour_bucket']);
        $this->assertEquals(1.0, $row['learning_hour_value']);
        $this->assertEquals('1.00 hour', $row['learning_hour']);
        $this->assertEquals('Playlist', $row['category']);
        $this->assertEquals('-', $row['classification']);
        $this->assertEquals('Playlist', $row['type']);
    }

    /**
     * TEST 3: Sorting learning-hour uses numeric float comparisons.
     */
    public function test_sorting_learning_hour_uses_numeric_values(): void
    {
        $contentA = Content::create([
            'title'             => 'Small LH',
            'slug'              => 'small-lh-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        $contentB = Content::create([
            'title'             => 'Large LH',
            'slug'              => 'large-lh-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '02:00:00',
            'learning_hours'    => 6.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $contentA->id, 'progress' => 100, 'finish' => '1']);
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $contentB->id, 'progress' => 100, 'finish' => '1']);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', [
            'tab'                   => 'record',
            'record_sort'           => 'learning-hour',
            'record_sort_direction' => 'desc',
        ]));
        $response->assertStatus(200);

        $rows = $response->viewData('rows');
        $this->assertNotEmpty($rows);

        $firstRow = $rows->first();
        $this->assertEquals('Large LH', $firstRow['title']);
        $this->assertEquals(2.0, (float) $firstRow['learning_hour_bucket']);
    }

    /**
     * TEST 4: Export Learning Record uses database learning_hours and 10 columns.
     */
    public function test_export_learning_record_structure_and_values(): void
    {
        $content = Content::create([
            'title'             => 'Export Test Course',
            'slug'              => 'exp-test-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $this->actingAs($this->user);

        $export = new ExportMyLearningRecord();
        $array = $export->array();

        // 1. Headings check: 12 columns
        $headings = $array[0];
        $this->assertEquals([
            'No',
            'Course',
            'Type',
            'Category',
            'Classification',
            'Duration',
            'Learning Hour',
            'Learning Progress',
            'Start Date',
            'Finish Date',
            'Assessment Score',
            'Certificate',
        ], $headings);

        // 2. Data row check
        $rows = collect($array);
        $contentRow = $rows->firstWhere(1, 'Export Test Course');

        $this->assertNotNull($contentRow);
        $this->assertEquals('Document', $contentRow[2]); // Type
        $this->assertEquals('Content', $contentRow[3]); // Category
        $this->assertEquals('Safety & Environment', $contentRow[4]); // Classification
        $this->assertEquals('0.25 h', $contentRow[6]); // Learning Hour
        $this->assertEquals('100%', $contentRow[7]); // Progress
    }

    /**
     * TEST 5: Progress safety - Direct Content strictly reads content_users, Playlist strictly reads playlist_content_users.
     */
    public function test_progress_safety_strict_separation(): void
    {
        $plan = TrainingPlan::create(['title' => 'Safety Isolation Plan', 'slug' => 'safety-iso-' . rand(1000, 9999), 'active' => 'Active', 'learning_hours' => 2.0]);
        $content = Content::create(['title' => 'Safety Content', 'slug' => 'safety-c-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'active' => 'Active', 'playlistonly' => 'Y', 'learning_hours' => 1.0]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $content->id, 'content_order' => 0]);

        // Direct content user is 100% finished
        $cu = ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        // Playlist content user is 40% (not finished)
        $pcu = PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'progress'        => 40,
            'finish'          => '0',
        ]);

        $this->actingAs($this->user);

        // Playlist service must NOT consider this playlist completed
        $completedPlaylists = app(\App\Services\Learning\CompletedPlaylistService::class)->forUser((int) $this->user->id);
        $this->assertFalse($completedPlaylists->contains('id', $plan->id));

        // Direct content query still finds the finished direct content
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getCompletedContents');
        $method->setAccessible(true);
        $completedContents = $method->invoke($this->controller, (int) $this->user->id);
        $this->assertTrue($completedContents->contains('id', $cu->id));
    }

    /**
     * TEST 6: HTML rendering verifies 9 columns, compact Type badge under Course title, Category column, and NO Optional/Mandatory badge.
     */
    public function test_html_rendering_matches_table_design(): void
    {
        $content = Content::create([
            'title'             => 'HTML Rendered Course',
            'slug'              => 'html-rendered-course-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:30:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'mandatory',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Check Table Headers: 11 columns
        $this->assertStringContainsString('Course', $html);
        $this->assertStringContainsString('Type', $html);
        $this->assertStringContainsString('Category', $html);
        $this->assertStringContainsString('Classification', $html);
        $this->assertStringContainsString('Duration', $html);
        $this->assertStringContainsString('Learning Hour', $html);
        $this->assertStringContainsString('Progress', $html);
        $this->assertStringContainsString('Date Time', $html);
        $this->assertStringContainsString('Assessment Score', $html);
        $this->assertStringContainsString('Certificate', $html);

        // 2. Check that Type is rendered as compact badge in TYPE column
        $this->assertStringContainsString('HTML Rendered Course', $html);
        $this->assertStringContainsString('learning-record-type-badge', $html);
        $this->assertStringContainsString('Video', $html);

        // 3. Confirm Mandatory badge is restored in the Course column
        $this->assertStringContainsString('Mandatory', $html);

        // 4. Check Category column displays Content
        $this->assertStringContainsString('learning-record-category', $html);
        $this->assertStringContainsString('>Content<', $html);

        // 5. Check Classification column displays Classification name
        $this->assertStringContainsString('Safety &amp; Environment', $html);

        // 6. Check Learning Hour display
        $this->assertStringContainsString('0.50 h', $html);
    }

    /**
     * TEST 7: Filter by Learning Hour matches database values correctly.
     */
    public function test_filter_by_learning_hour_range(): void
    {
        $content1 = Content::create(['title' => 'C1 Less Than 1H', 'slug' => 'c1-lt1-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '00:10:00', 'learning_hours' => 0.5, 'active' => 'Active', 'playlistonly' => 'N']);
        $content2 = Content::create(['title' => 'C2 Between 1 and 3H', 'slug' => 'c2-1-3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '01:30:00', 'learning_hours' => 2.0, 'active' => 'Active', 'playlistonly' => 'N']);
        $content3 = Content::create(['title' => 'C3 Greater Than 3H', 'slug' => 'c3-gt3-' . rand(1000, 9999), 'type' => 'Video', 'classification_id' => $this->classification->id, 'duration' => '04:00:00', 'learning_hours' => 5.0, 'active' => 'Active', 'playlistonly' => 'N']);

        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content1->id, 'progress' => 100, 'finish' => '1']);
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content2->id, 'progress' => 100, 'finish' => '1']);
        ContentUser::create(['user_id' => $this->user->id, 'content_id' => $content3->id, 'progress' => 100, 'finish' => '1']);

        $this->actingAs($this->user);

        // Filter: 1-3 Hours
        $response = $this->get(route('user.learning.dashboard.partial', [
            'tab'                         => 'record',
            'record_filter_learning_hour' => '1-3',
        ]));
        $response->assertStatus(200);

        $rows = $response->viewData('rows');
        $this->assertTrue($rows->contains('title', 'C2 Between 1 and 3H'));
        $this->assertFalse($rows->contains('title', 'C1 Less Than 1H'));
        $this->assertFalse($rows->contains('title', 'C3 Greater Than 3H'));
    }

    /**
     * TEST 8: Fallback to duration calculation if database learning_hours is null.
     */
    public function test_fallback_when_learning_hours_is_null(): void
    {
        $content = Content::create([
            'title'             => 'Null LH Content',
            'slug'              => 'null-lh-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:45:00',
            'learning_hours'    => null, // Null in DB
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $content->id,
            'progress'   => 100,
            'finish'     => '1',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $rows = $response->viewData('rows');
        $row = $rows->firstWhere('slug', $content->slug);

        $this->assertNotNull($row);
        // Actual hours for 45 min duration -> 0.75 hour
        $this->assertEquals(0.75, (float) $row['learning_hour_bucket']);
        $this->assertEquals(0.75, $row['learning_hour_value']);
        $this->assertEquals('0.75 hour', $row['learning_hour']);
    }

    /**
     * TEST 9: Document and Playlist type badges are compact, Category and Classification are separated, and have no Optional/Mandatory badges.
     */
    public function test_document_and_playlist_badge_compact_rendering(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Compact Playlist',
            'slug'                   => 'compact-pl-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $docContent = Content::create([
            'title'             => 'Compact Document Course',
            'slug'              => 'compact-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'duration'          => '00:20:00',
            'learning_hours'    => 0.5,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $docContent->id, 'content_order' => 0]);

        // Complete doc content directly
        ContentUser::create([
            'user_id'    => $this->user->id,
            'content_id' => $docContent->id,
            'progress'   => 100,
            'finish'     => '1',
            'status'     => 'optional',
        ]);

        // Complete playlist
        PlaylistContentUser::create([
            'user_id'         => $this->user->id,
            'trainingplan_id' => $plan->id,
            'content_id'      => $docContent->id,
            'progress'        => 100,
            'finish'          => '1',
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('user.learning.dashboard.partial', ['tab' => 'record']));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Check Course column titles
        $this->assertStringContainsString('Compact Document Course', $html);
        $this->assertStringContainsString('Compact Playlist', $html);

        // Check badges are compact type badges
        $this->assertStringContainsString('learning-record-type-badge', $html);
        $this->assertStringContainsString('Document', $html);
        $this->assertStringContainsString('Playlist', $html);

        // Check Category separation
        $this->assertStringContainsString('>Content<', $html);
        $this->assertStringContainsString('>Playlist<', $html);

        // Check Classification separation
        $this->assertStringContainsString('Safety &amp; Environment', $html);

        // Check Optional is present
        $this->assertStringContainsString('Optional', $html);
    }
}
