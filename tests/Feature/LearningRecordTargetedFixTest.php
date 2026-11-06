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
use App\Services\Learning\LearningRecordLearningHourService;
use App\Services\Learning\LearningRecordRecognizedDurationService;
use App\Support\DurationFormatter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordTargetedFixTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;
    protected LearningRecordController $controller;
    protected CertificateEligibilityService $eligibilityService;
    protected LearningRecordLearningHourService $hourService;
    protected LearningRecordRecognizedDurationService $recService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Fix Tester ' . rand(1000, 9999),
            'id_emp'     => 'EMP-FIX-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-fix-' . Str::uuid(),
            'sap_code'   => 'SAP-FIX-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Fix Classification'],
            ['slug' => 'fix-classification-' . rand(1000, 9999)]
        );

        $this->controller = app(LearningRecordController::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);
        $this->hourService = app(LearningRecordLearningHourService::class);
        $this->recService = app(LearningRecordRecognizedDurationService::class);

        Auth::login($this->user);
    }

    /**
     * Test A: Direct + Playlist (Prevent Double Mutation in buildContentRows)
     * Content: 200 seconds (00:03:20).
     * Playlist actual watch: 200 seconds.
     * Direct actual watch: 205 seconds.
     * Global 2x Cap = 400 seconds.
     * Playlist claims 200s. Direct has 205s actual watch, capped at remaining 200s.
     * Direct Recognized Duration MUST be 00:03:20 (200s), NEVER 00:00:00!
     * Direct Learning Hours MUST be 0.06 hour, consistent with Recognized Duration.
     */
    public function test_a_direct_plus_playlist_no_double_mutation_zeroing_recognized(): void
    {
        $suffix = rand(1000, 9999);
        $content = Content::create([
            'title'             => 'Test A Content ' . $suffix,
            'slug'              => 'test-a-content-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20', // 200s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan = TrainingPlan::create([
            'title'                  => 'Test A Playlist ' . $suffix,
            'slug'                   => 'test-a-playlist-' . $suffix,
            'total_duration_seconds' => 200,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create([
            'trainingplan_id' => $plan->id,
            'content_id'      => $content->id,
            'content_order'   => 1,
        ]);

        // 1. Playlist completion (claims 200s)
        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 200,
            'current_time'         => 200,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        // 2. Direct watch (205s actual watch)
        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 205,
            'current_time'         => 200,
            'duration_seconds'     => 200,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $rows = $this->controller->getRawOnlineRows($this->user->id, $this->eligibilityService);

        // Find direct content row
        $directRow = $rows->firstWhere('title', $content->title);
        $this->assertNotNull($directRow, 'Direct content row should exist in Learning Record');

        // Verify Course Duration is 00:03:20
        $this->assertSame('00:03:20', $directRow['duration']);
        $this->assertEquals(200, $directRow['duration_sort']);

        // Verify Total Recognized Duration is NOT 00:00:00, but exactly 00:03:20 (200s)
        $this->assertSame('00:03:20', $directRow['total_recognized_duration']);
        $this->assertEquals(200, $directRow['recognized_duration_seconds']);

        // Verify Learning Hours is consistent (200/3600 = 0.0556 -> 0.06 hour)
        $this->assertSame('0.06 hour', $directRow['learning_hour']);
        $this->assertEquals(0.0556, $directRow['learning_hour_value']);

        // Find playlist row
        $playlistRow = $rows->firstWhere('title', $plan->title);
        $this->assertNotNull($playlistRow, 'Playlist row should exist');
        $this->assertSame('00:03:20', $playlistRow['duration']);
        $this->assertSame('00:03:20', $playlistRow['total_recognized_duration']);
        $this->assertEquals(200, $playlistRow['recognized_duration_seconds']);
    }

    /**
     * Test B: Multiple Playlists Priority Allocation
     * Content: 200s (cap 400s).
     * Playlist 1: claims 200s.
     * Playlist 2: claims 150s. (Total playlist claimed = 350s).
     * Direct watch: 100s actual watch.
     * Remaining cap for Direct: max(0, 400 - 350) = 50s.
     * Direct Recognized Duration must be 00:00:50 (50s).
     */
    public function test_b_multiple_playlists_priority_allocation_direct_gets_remainder(): void
    {
        $suffix = rand(1000, 9999);
        $content = Content::create([
            'title'             => 'Test B Content ' . $suffix,
            'slug'              => 'test-b-content-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:20', // 200s
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);

        $plan1 = TrainingPlan::create([
            'title'                  => 'Test B Playlist 1 ' . $suffix,
            'slug'                   => 'test-b-plan1-' . $suffix,
            'total_duration_seconds' => 200,
            'active'                 => 'Active',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan1->id, 'content_id' => $content->id, 'content_order' => 1]);

        $plan2 = TrainingPlan::create([
            'title'                  => 'Test B Playlist 2 ' . $suffix,
            'slug'                   => 'test-b-plan2-' . $suffix,
            'total_duration_seconds' => 200,
            'active'                 => 'Active',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan2->id, 'content_id' => $content->id, 'content_order' => 1]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan1->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 200,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $plan2->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 150,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        ContentUser::create([
            'user_id'              => $this->user->id,
            'content_id'           => $content->id,
            'actual_watch_seconds' => 100,
            'progress'             => 100,
            'finish'               => '1',
            'status'               => 'non mandatory',
        ]);

        $rows = $this->controller->getRawOnlineRows($this->user->id, $this->eligibilityService);
        $directRow = $rows->firstWhere('title', $content->title);

        $this->assertNotNull($directRow);
        // Direct receives remaining quota 50s
        $this->assertSame('00:00:50', $directRow['total_recognized_duration']);
        $this->assertEquals(50, $directRow['recognized_duration_seconds']);
        $this->assertEquals(round(50 / 3600, 4), $directRow['learning_hour_value']);
    }

    /**
     * Test C: Legacy Playlist Course Duration Fallback
     * TrainingPlan with total_duration_seconds = 0.
     * Child 1: 220s (00:03:40).
     * Child 2: 257s (00:04:17).
     * Expected Course Duration: 477s (00:07:57).
     */
    public function test_c_legacy_playlist_duration_zero_falls_back_to_sum_of_modules(): void
    {
        $suffix = rand(1000, 9999);
        $child1 = Content::create([
            'title'             => 'Test C Child 1 ' . $suffix,
            'slug'              => 'test-c-child1-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:40', // 220s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $child2 = Content::create([
            'title'             => 'Test C Child 2 ' . $suffix,
            'slug'              => 'test-c-child2-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:17', // 257s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        // Legacy playlist with total_duration_seconds = 0
        $legacyPlan = TrainingPlan::create([
            'title'                  => 'Test C Legacy Playlist ' . $suffix,
            'slug'                   => 'test-c-legacy-plan-' . $suffix,
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $legacyPlan->id, 'content_id' => $child1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $legacyPlan->id, 'content_id' => $child2->id, 'content_order' => 2]);

        // Explicitly set total_duration_seconds = 0 to simulate legacy playlist created before PlaylistDurationService
        TrainingPlan::where('id', $legacyPlan->id)->update(['total_duration_seconds' => 0]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $legacyPlan->id,
            'content_id'           => $child1->id,
            'actual_watch_seconds' => 220,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $legacyPlan->id,
            'content_id'           => $child2->id,
            'actual_watch_seconds' => 257,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $rows = $this->controller->getRawOnlineRows($this->user->id, $this->eligibilityService);
        $legacyRow = $rows->firstWhere('title', $legacyPlan->title);

        $this->assertNotNull($legacyRow, 'Legacy playlist row should be present');
        // Course Duration must fallback to sum of modules (477s = 00:07:57)
        $this->assertSame('00:07:57', $legacyRow['duration']);
        $this->assertEquals(477, $legacyRow['duration_sort']);

        // Recognized duration also 477s
        $this->assertSame('00:07:57', $legacyRow['total_recognized_duration']);
        $this->assertEquals(477, $legacyRow['recognized_duration_seconds']);
    }

    /**
     * Test D: Normal Playlist retains official duration
     * TrainingPlan with total_duration_seconds = 11929 (03:18:49).
     * Must NOT be altered by module fallback.
     */
    public function test_d_normal_playlist_duration_greater_than_zero_retains_official_duration(): void
    {
        $suffix = rand(1000, 9999);
        $child = Content::create([
            'title'             => 'Test D Child ' . $suffix,
            'slug'              => 'test-d-child-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:05:00', // 300s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $normalPlan = TrainingPlan::create([
            'title'                  => 'Test D Normal Playlist ' . $suffix,
            'slug'                   => 'test-d-plan-' . $suffix,
            'total_duration_seconds' => 11929, // official 03:18:49
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $normalPlan->id, 'content_id' => $child->id, 'content_order' => 1]);
        TrainingPlan::where('id', $normalPlan->id)->update(['total_duration_seconds' => 11929]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $normalPlan->id,
            'content_id'           => $child->id,
            'actual_watch_seconds' => 300,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $rows = $this->controller->getRawOnlineRows($this->user->id, $this->eligibilityService);
        $normalRow = $rows->firstWhere('title', $normalPlan->title);

        $this->assertNotNull($normalRow);
        // Course duration must retain official 11929s = 03:18:49
        $this->assertSame('03:18:49', $normalRow['duration']);
        $this->assertEquals(11929, $normalRow['duration_sort']);
    }

    /**
     * Test E: Excel Export Consistency & Child Hyphen Preservation
     * Verifies that:
     * - Parent Course Duration and Total Recognized Duration match UI
     * - Child rows have '-' for Course Duration, Total Recognized Duration, and Learning Hours
     */
    public function test_e_excel_export_consistency_and_child_hyphen_preservation(): void
    {
        $suffix = rand(1000, 9999);
        $child1 = Content::create([
            'title'             => 'Excel Child 1 ' . $suffix,
            'slug'              => 'excel-child1-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:03:40', // 220s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $child2 = Content::create([
            'title'             => 'Excel Child 2 ' . $suffix,
            'slug'              => 'excel-child2-' . $suffix,
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'duration'          => '00:04:17', // 257s
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        $legacyPlan = TrainingPlan::create([
            'title'                  => 'Excel Legacy Plan ' . $suffix,
            'slug'                   => 'excel-legacy-plan-' . $suffix,
            'total_duration_seconds' => 0,
            'active'                 => 'Active',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $legacyPlan->id, 'content_id' => $child1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $legacyPlan->id, 'content_id' => $child2->id, 'content_order' => 2]);

        // Explicitly set total_duration_seconds = 0 to simulate legacy playlist created before PlaylistDurationService
        TrainingPlan::where('id', $legacyPlan->id)->update(['total_duration_seconds' => 0]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $legacyPlan->id,
            'content_id'           => $child1->id,
            'actual_watch_seconds' => 220,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        PlaylistContentUser::create([
            'user_id'              => $this->user->id,
            'trainingplan_id'      => $legacyPlan->id,
            'content_id'           => $child2->id,
            'actual_watch_seconds' => 257,
            'progress'             => 100,
            'finish'               => '1',
        ]);

        $export = new ExportMyLearningRecord([]);
        $data = $export->array();

        // Find parent row
        $parentRow = null;
        $childRows = [];
        for ($i = 1; $i < count($data); $i++) {
            $r = $data[$i];
            if (trim($r[4]) === $legacyPlan->title) {
                $parentRow = $r;
            } elseif ($r[6] === 'Content Playlist' && (str_contains($r[4], $child1->title) || str_contains($r[4], $child2->title))) {
                $childRows[] = $r;
            }
        }

        $this->assertNotNull($parentRow, 'Parent playlist row should be exported');
        // Parent Course Duration in Excel = 477 / 86400
        $this->assertEqualsWithDelta(477 / 86400, $parentRow[8], 0.0001);
        // Parent Recognized Duration in Excel = 477 / 86400
        $this->assertEqualsWithDelta(477 / 86400, $parentRow[9], 0.0001);

        // Child rows must have '-' for duration, recognized duration, and learning hours
        $this->assertCount(2, $childRows);
        foreach ($childRows as $childRow) {
            $this->assertSame('-', $childRow[8], 'Child Course Duration must be hyphen to prevent double SUM');
            $this->assertSame('-', $childRow[9], 'Child Total Recognized Duration must be hyphen to prevent double SUM');
            $this->assertSame('-', $childRow[10], 'Child Learning Hours must be hyphen to prevent double SUM');
        }
    }
}
