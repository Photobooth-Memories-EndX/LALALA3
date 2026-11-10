<?php

namespace Tests\Feature;

use App\Exports\ExportMyAllLearningRecord;
use App\Exports\ExportMyLearningRecord;
use App\Models\Classification;
use App\Models\Content;
use App\Models\ContentUser;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnlineLearningRecordRecognizedDurationConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordRecognizedDurationService $recognizedService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Online Consistency Tester ' . rand(1000, 9999),
            'id_emp'     => 'EMP-OCT-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-oct-' . Str::uuid(),
            'sap_code'   => 'SAP-OCT-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Consistency Test Classification'],
            ['slug' => 'consistency-test-classification-' . rand(1000, 9999)]
        );

        $this->recognizedService = app(LearningRecordRecognizedDurationService::class);
    }

    /**
     * Helper to create test content with 98 seconds duration (00:01:38).
     */
    protected function create98sContent(): Content
    {
        $content = Content::create([
            'title'             => 'Test 98s Module ' . rand(10000, 99999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:01:38', // 98 seconds
            'learning_hours'    => 0.03,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $content->refresh();

        return $content;
    }

    /**
     * Helper to create a playlist containing the given content.
     */
    protected function createPlaylistWithContent(Content $content, string $suffix = 'A'): TrainingPlan
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Playlist ' . $suffix . ' ' . rand(10000, 99999),
            'active'                 => 'Active',
            'playlistonly'           => 'Y',
            'total_duration_seconds' => 98,
            'learning_hours'         => 0.03,
            'has_deadline'           => 0,
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => (string) $plan->id,
            'content_id'      => (string) $content->id,
            'content_order'   => '1',
        ]);

        $plan->refresh();

        return $plan;
    }

    /**
     * Assert consistency of Course Duration and Total Recognized Duration across:
     * 1. Direct Content View (/content/{slug})
     * 2. My Learning Record Online (/my-learning/partial/record?record_mode=online)
     * 3. Detail Modal (/my-learning/learning-record/detail/online/{cu_id})
     * 4. Export My Learning Record (ExportMyLearningRecord)
     * 5. Export All Learning Record (ExportMyAllLearningRecord)
     */
    protected function assertRecognizedDurationConsistency(
        Content $content,
        int $expectedSeconds,
        string $expectedDuration,
        ?ContentUser $cu = null,
        ?User $user = null
    ): void {
        $testUser = $user ?? $this->user;
        $canonicalSeconds = $this->recognizedService->resolveGlobalContentRecognizedSeconds($testUser->id, $content);
        $canonicalDuration = $this->recognizedService->resolveGlobalContentRecognizedDuration($testUser->id, $content);

        // Canonical service verification
        $this->assertSame($expectedSeconds, $canonicalSeconds, 'Canonical service seconds mismatch');
        $this->assertSame($expectedDuration, $canonicalDuration, 'Canonical service duration mismatch');

        // 1. Direct Content View
        $directResponse = $this->actingAs($testUser)->get('/content/' . $content->slug);
        $directResponse->assertOk();
        $directResponse->assertSee('<span id="contentTotalRecognizedDuration">' . $expectedDuration . '</span>', false);

        // 2. My Learning Record Online
        $onlineResponse = $this->actingAs($testUser)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));
        $onlineResponse->assertOk();
        $rows = $onlineResponse->viewData('rows');
        $this->assertNotNull($rows, 'Learning record rows must be loaded');

        $row = $rows->firstWhere('slug', $content->slug);
        if ($cu && (int) ($cu->finish ?? 0) === 1) {
            $this->assertNotNull($row, "Completed content {$content->slug} must appear in Online Learning Record");
            $this->assertSame('00:01:38', $row['duration'], 'Course duration in UI must be 00:01:38');
            $this->assertSame($expectedSeconds, $row['recognized_duration_seconds'], 'UI recognized_duration_seconds mismatch');
            $this->assertSame($expectedDuration, $row['recognized_duration'], 'UI recognized_duration mismatch');
            $this->assertSame($expectedDuration, $row['total_recognized_duration'], 'UI total_recognized_duration mismatch');
        }

        // 3. Learning Record Detail Modal (if ContentUser exists)
        if ($cu) {
            $detailResponse = $this->actingAs($testUser)
                ->get(route('user.learning.record.detail', ['source' => 'online', 'id' => $cu->id]));
            $detailResponse->assertOk();
            $detailResponse->assertJsonPath('data.duration', '00:01:38');
            $detailResponse->assertJsonPath('data.recognized_duration', $expectedDuration);
            $detailResponse->assertJsonPath('data.recognized_duration_seconds', $expectedSeconds);
            $detailResponse->assertJsonPath('data.total_recognized_duration', $expectedDuration);
        }

        // 4. Export My Learning Record
        if ($cu && (int) ($cu->finish ?? 0) === 1) {
            \Illuminate\Support\Facades\Auth::login($testUser);
            $export = new ExportMyLearningRecord();
            $exportData = $export->array();
            array_shift($exportData); // header

            // Find row for this content (Title is column index 4)
            $exportRow = collect($exportData)->first(fn ($r) => ($r[4] ?? '') === $content->title);
            $this->assertNotNull($exportRow, "Export My Learning Record must contain content {$content->title}");

            // Column 8 (index 8): Course Duration
            $this->assertEqualsWithDelta(98 / 86400, (float) $exportRow[8], 0.0001, 'Course Duration in Excel must be native time for 98s');

            // Column 9 (index 9): Total Recognized Duration
            $expectedExcelRec = $expectedSeconds > 0 ? ($expectedSeconds / 86400) : 0;
            $this->assertEqualsWithDelta($expectedExcelRec, (float) $exportRow[9], 0.0001, 'Total Recognized Duration in Excel must be native time');

            // 5. Export All Learning Record
            $exportAll = new ExportMyAllLearningRecord();
            $exportAllData = $exportAll->array();
            array_shift($exportAllData); // header

            // Find row for this content (Title is column index 5 in All Record)
            $exportAllRow = collect($exportAllData)->first(fn ($r) => ($r[5] ?? '') === $content->title);
            $this->assertNotNull($exportAllRow, "Export All Learning Record must contain content {$content->title}");

            // Column 9 (index 9): Course Duration
            $this->assertEqualsWithDelta(98 / 86400, (float) $exportAllRow[9], 0.0001, 'Course Duration in Export All must be native time for 98s');

            // Column 10 (index 10): Total Recognized Duration
            $this->assertEqualsWithDelta($expectedExcelRec, (float) $exportAllRow[10], 0.0001, 'Total Recognized Duration in Export All must be native time');
        }
    }

    /**
     * Scenario 1: Canonical Table Validation on Direct Only:
     * 98s  -> 00:00:00
     * 120s -> 00:00:22
     * 150s -> 00:00:52
     * 196s -> 00:01:38
     * 250s -> 00:01:38
     */
    public function test_canonical_duration_table_direct_only(): void
    {
        $testCases = [
            98  => [0,  '00:00:00'],
            120 => [22, '00:00:22'],
            150 => [52, '00:00:52'],
            196 => [98, '00:01:38'],
            250 => [98, '00:01:38'],
        ];

        foreach ($testCases as $actualWatch => [$expectedSeconds, $expectedDuration]) {
            $user = User::factory()->create([
                'name'       => 'Direct User ' . rand(1000, 9999),
                'id_emp'     => 'EMP-DIR-' . rand(1000, 9999),
                'role'       => 1,
                'active'     => 1,
            ]);

            $content = $this->create98sContent();

            $cu = ContentUser::create([
                'user_id'              => $user->id,
                'content_id'           => $content->id,
                'actual_watch_seconds' => $actualWatch,
                'current_time'         => min($actualWatch, 98),
                'duration_seconds'     => 98,
                'progress'             => 100,
                'finish'               => '1',
                'learning_status'      => 'completed',
            ]);

            $this->assertRecognizedDurationConsistency($content, $expectedSeconds, $expectedDuration, $cu, $user);
        }
    }

    /**
     * Scenario 2: Direct (First Watch 98s) + Playlist A (Rewatch 22s) = 120s -> 00:00:22
     */
    public function test_direct_first_watch_and_playlist_rewatch(): void
    {
        $content = $this->create98sContent();
        $planA = $this->createPlaylistWithContent($content, 'A');

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 98,
            'current_time'         => 98,
            'duration_seconds'     => 98,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planA->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 22,
            'current_time'         => 22,
            'duration_seconds'     => 98,
            'progress'             => 22,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $this->assertRecognizedDurationConsistency($content, 22, '00:00:22', $cu);
    }

    /**
     * Scenario 3: Playlist (First Watch 98s) + Direct (Rewatch 22s) = 120s -> 00:00:22
     */
    public function test_playlist_first_watch_and_direct_rewatch(): void
    {
        $content = $this->create98sContent();
        $planA = $this->createPlaylistWithContent($content, 'A');

        // Finished 100% via Playlist first
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planA->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 98,
            'current_time'         => 98,
            'duration_seconds'     => 98,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        // Rewatch on Direct (22s) without completing standalone full quota
        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 22,
            'current_time'         => 22,
            'duration_seconds'     => 98,
            'progress'             => 22,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $this->assertRecognizedDurationConsistency($content, 22, '00:00:22', $cu);
    }

    /**
     * Scenario 4: Direct (98s) + Playlist A (22s) + Playlist B (30s) = 150s -> 00:00:52
     */
    public function test_direct_and_multiple_playlists_rewatch(): void
    {
        $content = $this->create98sContent();
        $planA = $this->createPlaylistWithContent($content, 'A');
        $planB = $this->createPlaylistWithContent($content, 'B');

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 98,
            'current_time'         => 98,
            'duration_seconds'     => 98,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planA->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 22,
            'current_time'         => 22,
            'duration_seconds'     => 98,
            'progress'             => 22,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planB->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 30,
            'current_time'         => 30,
            'duration_seconds'     => 98,
            'progress'             => 30,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $this->assertRecognizedDurationConsistency($content, 52, '00:00:52', $cu);
    }

    /**
     * Scenario 5: Multiple playlists overwatch reaches 1x duration rewatch cap (98s = 00:01:38).
     * Direct (98s) + Playlist A (60s) + Playlist B (92s) = 250s actual -> capped at 98s (00:01:38).
     */
    public function test_multi_playlist_overwatch_clamped_at_max_rewatch_cap(): void
    {
        $content = $this->create98sContent();
        $planA = $this->createPlaylistWithContent($content, 'A');
        $planB = $this->createPlaylistWithContent($content, 'B');

        $cu = ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 98,
            'current_time'         => 98,
            'duration_seconds'     => 98,
            'progress'             => 100,
            'finish'               => '1',
            'learning_status'      => 'completed',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planA->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 60,
            'current_time'         => 60,
            'duration_seconds'     => 98,
            'progress'             => 61,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $planB->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 92,
            'current_time'         => 92,
            'duration_seconds'     => 98,
            'progress'             => 93,
            'finish'               => '0',
            'learning_status'      => 'in_progress',
        ]);

        $this->assertRecognizedDurationConsistency($content, 98, '00:01:38', $cu);
    }

    /**
     * Scenario 6: Reference Content 'fadwdwas' (Content ID 2418) validation.
     * Content duration: 22s (00:00:22).
     * If user watches 44s, Recognized Rewatch is 22s (00:00:22).
     */
    public function test_reference_content_fadwdwas_consistency(): void
    {
        $fadwdwas = Content::where('slug', 'fadwdwas')->first();
        if (!$fadwdwas) {
            $this->markTestSkipped("Content 'fadwdwas' not present in database");
        }

        $cu = ContentUser::updateOrCreate(
            ['user_id' => $this->user->id, 'content_id' => $fadwdwas->id],
            [
                'actual_watch_seconds' => 44,
                'current_time'         => 22,
                'duration_seconds'     => 22,
                'progress'             => 100,
                'finish'               => '1',
                'learning_status'      => 'completed',
            ]
        );

        $canonicalSeconds = $this->recognizedService->resolveGlobalContentRecognizedSeconds($this->user->id, $fadwdwas);
        $canonicalDuration = $this->recognizedService->resolveGlobalContentRecognizedDuration($this->user->id, $fadwdwas);

        $this->assertSame(22, $canonicalSeconds);
        $this->assertSame('00:00:22', $canonicalDuration);

        // 1. Direct Content View
        $directResponse = $this->actingAs($this->user)->get('/content/fadwdwas');
        $directResponse->assertOk();
        $directResponse->assertSee('<span id="contentTotalRecognizedDuration">00:00:22</span>', false);

        // 2. Online Learning Record
        $onlineResponse = $this->actingAs($this->user)
            ->get(route('user.learning.dashboard.partial', ['tab' => 'record', 'record_mode' => 'online']));
        $onlineResponse->assertOk();
        $row = $onlineResponse->viewData('rows')->firstWhere('slug', 'fadwdwas');
        $this->assertNotNull($row);
        $this->assertSame('00:00:22', $row['duration']);
        $this->assertSame('00:00:22', $row['recognized_duration']);
        $this->assertSame(22, $row['recognized_duration_seconds']);

        // 3. Detail Modal
        $detailResponse = $this->actingAs($this->user)
            ->get(route('user.learning.record.detail', ['source' => 'online', 'id' => $cu->id]));
        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('data.duration', '00:00:22');
        $detailResponse->assertJsonPath('data.recognized_duration', '00:00:22');
        $detailResponse->assertJsonPath('data.recognized_duration_seconds', 22);

        // 4. Export My Learning Record
        \Illuminate\Support\Facades\Auth::login($this->user);
        $export = new ExportMyLearningRecord();
        $exportData = $export->array();
        array_shift($exportData);
        $exportRow = collect($exportData)->first(fn ($r) => ($r[4] ?? '') === $fadwdwas->title);
        $this->assertNotNull($exportRow);
        $this->assertEqualsWithDelta(22 / 86400, (float) $exportRow[8], 0.0001); // Course Duration
        $this->assertEqualsWithDelta(22 / 86400, (float) $exportRow[9], 0.0001); // Recognized Duration

        // 5. Export All Learning Record
        $exportAll = new ExportMyAllLearningRecord();
        $exportAllData = $exportAll->array();
        array_shift($exportAllData);
        $exportAllRow = collect($exportAllData)->first(fn ($r) => ($r[5] ?? '') === $fadwdwas->title);
        $this->assertNotNull($exportAllRow);
        $this->assertEqualsWithDelta(22 / 86400, (float) $exportAllRow[9], 0.0001); // Course Duration
        $this->assertEqualsWithDelta(22 / 86400, (float) $exportAllRow[10], 0.0001); // Recognized Duration
    }
}
