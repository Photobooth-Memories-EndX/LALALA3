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
use App\Services\Learning\CompletedPlaylistService;
use App\Services\Learning\LearningRecordLearningHourService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LearningRecordFilterCardExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classificationA;
    protected Classification $classificationB;
    protected LearningRecordController $controller;
    protected CertificateEligibilityService $eligibilityService;
    protected LearningRecordLearningHourService $recordLhService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(LearningRecordController::class);
        $this->eligibilityService = app(CertificateEligibilityService::class);
        $this->recordLhService = app(LearningRecordLearningHourService::class);

        $this->user = User::factory()->create([
            'name'       => 'Test Learner Filter ' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'test-hash-filter-' . Str::uuid(),
            'sap_code'   => 'SAP-FIL-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classificationA = Classification::firstOrCreate(
            ['classification_name' => 'Technical Operations'],
            ['slug' => 'technical-operations']
        );

        $this->classificationB = Classification::firstOrCreate(
            ['classification_name' => 'Human Resources'],
            ['slug' => 'human-resources']
        );

        // Course 1: 2023, Video, Technical, 360s, current_time 180s -> 0.05 h
        $c1 = Content::create([
            'title'             => 'Technical Video Course 2023',
            'slug'              => 'tech-vid-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classificationA->id,
            'duration'          => '00:06:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        $cu1 = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $c1->id,
            'current_time'     => 180, // 0.05 h
            'duration_seconds' => 360,
            'progress'         => 100,
            'finish'           => '1',
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cu1->id)->update([
            'created_at'   => '2023-03-10 10:00:00',
            'updated_at'   => '2023-03-15 11:00:00',
            'completed_at' => '2023-03-15 11:00:00',
        ]);

        // Course 2: 2025, Document, HR, 600s, current_time 360s -> 0.10 h
        $c2 = Content::create([
            'title'             => 'HR Document Guide 2025',
            'slug'              => 'hr-doc-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classificationB->id,
            'duration'          => '00:10:00',
            'learning_hours'    => 2.0,
            'active'            => 'Active',
            'playlistonly'      => 'N',
        ]);
        $cu2 = ContentUser::create([
            'user_id'          => $this->user->id,
            'content_id'       => $c2->id,
            'current_time'     => 360, // 0.10 h
            'duration_seconds' => 600,
            'progress'         => 100,
            'finish'           => '1',
            'status'           => 'optional',
        ]);
        DB::table('content_users')->where('id', $cu2->id)->update([
            'created_at'   => '2025-05-01 09:00:00',
            'updated_at'   => '2025-05-02 14:30:00',
            'completed_at' => '2025-05-02 14:30:00',
        ]);

        // Playlist 1: 2025, 2 modules, total duration 1800s, consumed 720s -> 0.20 h
        $p1 = TrainingPlan::create([
            'title'          => 'Safety Leadership Playlist 2025',
            'slug'           => 'safety-leader-' . rand(1000, 9999),
            'learning_hours' => 5.0, // Catalog: MUST NOT BE USED
            'active'         => 'Active',
            'is_published'   => 1,
            'created_at'     => Carbon::parse('2025-06-01 08:00:00'),
            'updated_at'     => Carbon::parse('2025-06-10 16:00:00'),
        ]);

        $m1 = Content::create([
            'title'             => 'Playlist Mod 1',
            'slug'              => 'pl-mod-1-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classificationA->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);
        $m2 = Content::create([
            'title'             => 'Playlist Mod 2',
            'slug'              => 'pl-mod-2-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classificationA->id,
            'duration'          => '00:15:00',
            'learning_hours'    => 1.0,
            'active'            => 'Active',
            'playlistonly'      => 'Y',
        ]);

        TrainingPlanDetail::create(['trainingplan_id' => $p1->id, 'content_id' => $m1->id, 'content_order' => 1]);
        TrainingPlanDetail::create(['trainingplan_id' => $p1->id, 'content_id' => $m2->id, 'content_order' => 2]);

        $pcu1 = PlaylistContentUser::create([
            'trainingplan_id'  => $p1->id,
            'content_id'       => $m1->id,
            'user_id'          => $this->user->id,
            'current_time'     => 360,
            'duration_seconds' => 900,
            'finished'         => '1',
            'progress'         => 100,
        ]);
        DB::table('playlist_content_users')->where('id', $pcu1->id)->update([
            'created_at'   => '2025-06-02 08:00:00',
            'updated_at'   => '2025-06-05 10:00:00',
        ]);

        $pcu2 = PlaylistContentUser::create([
            'trainingplan_id'  => $p1->id,
            'content_id'       => $m2->id,
            'user_id'          => $this->user->id,
            'current_time'     => 360,
            'duration_seconds' => 900,
            'finished'         => '1',
            'progress'         => 100,
        ]);
        DB::table('playlist_content_users')->where('id', $pcu2->id)->update([
            'created_at'   => '2025-06-05 11:00:00',
            'updated_at'   => '2025-06-10 16:00:00',
        ]);
    }

    public function test_all_filters_baseline_matches_across_table_card_and_export(): void
    {
        $this->actingAs($this->user);
        request()->replace([]);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        // Table & Card
        $this->assertCount(3, $rows);
        $this->assertEquals(2, $stats['courseCompleted']);
        $this->assertEquals(1, $stats['playlistCompleted']);
        $this->assertEquals(0.35, $stats['totalLearningHours']); // 0.05 + 0.10 + 0.20 = 0.35

        // Export
        $export = new ExportMyLearningRecord([]);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
        $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

        $this->assertCount(3, $exportItems);
        $this->assertEquals(0.35, round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_year_2023_filter_controls_table_card_and_export(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_year' => '2023']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        $this->assertCount(1, $rows);
        $this->assertEquals(1, $stats['courseCompleted']);
        $this->assertEquals(0, $stats['playlistCompleted']);
        $this->assertEquals(0.05, $stats['totalLearningHours']);

        $export = new ExportMyLearningRecord(['record_filter_year' => '2023']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
        $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

        $this->assertCount(1, $exportItems);
        $this->assertEquals(0.05, round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_year_2025_filter_controls_table_card_and_export(): void
    {
        $this->actingAs($this->user);
        request()->replace(['year' => '2025']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        $this->assertCount(2, $rows);
        $this->assertEquals(1, $stats['courseCompleted']);
        $this->assertEquals(1, $stats['playlistCompleted']);
        $this->assertEquals(0.30, $stats['totalLearningHours']); // 0.10 + 0.20 = 0.30

        $export = new ExportMyLearningRecord(['year' => '2025']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
        $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

        $this->assertCount(2, $exportItems);
        $this->assertEquals(0.30, round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_combined_type_and_year_filter_matches(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_type' => 'Document', 'record_filter_year' => '2025']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        $this->assertCount(1, $rows);
        $this->assertEquals(1, $stats['courseCompleted']);
        $this->assertEquals(0, $stats['playlistCompleted']);
        $this->assertEquals(0.10, $stats['totalLearningHours']);

        $export = new ExportMyLearningRecord(['type' => 'Document', 'year' => '2025']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
        $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

        $this->assertCount(1, $exportItems);
        $this->assertEquals(0.10, round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_search_and_year_filter_matches(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_search' => 'Technical', 'record_filter_year' => '2023']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        $this->assertCount(1, $rows);
        $this->assertEquals(1, $stats['courseCompleted']);
        $this->assertEquals(0.05, $stats['totalLearningHours']);

        $export = new ExportMyLearningRecord(['search' => 'Technical', 'year' => '2023']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
        $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

        $this->assertCount(1, $exportItems);
        $this->assertEquals(0.05, round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_classification_filter_controls_table_card_and_export(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_classification' => 'Technical Operations']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        $this->assertCount(1, $rows);
        $this->assertEquals(1, $stats['courseCompleted']);
        $this->assertEquals(0, $stats['playlistCompleted']);
        $this->assertEquals(0.05, $stats['totalLearningHours']);

        $export = new ExportMyLearningRecord(['classification' => 'Technical Operations']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
        $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

        $this->assertCount(1, $exportItems);
        $this->assertEquals(0.05, round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_quick_filter_mode_controls_table_card_and_export(): void
    {
        $this->actingAs($this->user);

        // Mode: Content only
        request()->replace(['record_quick' => 'content']);
        $viewContent = $this->controller->partial($this->eligibilityService);
        $rowsContent = $viewContent->getData()['rows'];
        $statsContent = $viewContent->getData()['recordStats'];

        $this->assertCount(2, $rowsContent);
        $this->assertEquals(2, $statsContent['courseCompleted']);
        $this->assertEquals(0, $statsContent['playlistCompleted']);
        $this->assertEquals(0.15, $statsContent['totalLearningHours']); // 0.05 + 0.10 = 0.15

        $exportContent = new ExportMyLearningRecord(['quickFilter' => 'content']);
        $dataContent = $exportContent->array();
        $itemsContent = array_filter(array_slice($dataContent, 1), fn ($r) => !empty($r[0]));
        $this->assertCount(2, $itemsContent);
        $this->assertEquals(0.15, round((float) collect($itemsContent)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));

        // Mode: Playlist only
        request()->replace(['record_quick' => 'playlist']);
        $viewPl = $this->controller->partial($this->eligibilityService);
        $rowsPl = $viewPl->getData()['rows'];
        $statsPl = $viewPl->getData()['recordStats'];

        $this->assertCount(1, $rowsPl);
        $this->assertEquals(0, $statsPl['courseCompleted']);
        $this->assertEquals(1, $statsPl['playlistCompleted']);
        $this->assertEquals(0.20, $statsPl['totalLearningHours']);

        $exportPl = new ExportMyLearningRecord(['quickFilter' => 'playlist']);
        $dataPl = $exportPl->array();
        $itemsPl = array_filter(array_slice($dataPl, 1), fn ($r) => !empty($r[0]));
        $this->assertCount(1, $itemsPl);
        $this->assertEquals(0.20, round((float) collect($itemsPl)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2));
    }

    public function test_non_matching_filter_yields_zero_across_all(): void
    {
        $this->actingAs($this->user);
        request()->replace(['record_filter_year' => '2019']);

        $view = $this->controller->partial($this->eligibilityService);
        $rows = $view->getData()['rows'];
        $stats = $view->getData()['recordStats'];

        $this->assertCount(0, $rows);
        $this->assertEquals(0, $stats['courseCompleted']);
        $this->assertEquals(0, $stats['playlistCompleted']);
        $this->assertEquals(0.0, $stats['totalLearningHours']);

        $export = new ExportMyLearningRecord(['record_filter_year' => '2019']);
        $data = $export->array();
        $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));

        $this->assertCount(0, $exportItems);
        $this->assertCount(1, $data); // Only headings row
    }

    public function test_real_database_user_consistency_across_years(): void
    {
        $realUser = User::find(2);
        if (!$realUser) {
            $this->markTestSkipped('Real user 2 not found');
        }

        $this->actingAs($realUser);

        foreach (['2024', '2025', '2026'] as $targetYear) {
            request()->replace(['record_filter_year' => $targetYear]);
            $view = $this->controller->partial($this->eligibilityService);
            $rows = $view->getData()['rows'];
            $stats = $view->getData()['recordStats'];

            $export = new ExportMyLearningRecord(['year' => $targetYear]);
            $data = $export->array();
            $exportItems = array_filter(array_slice($data, 1), fn ($r) => !empty($r[0]));
            $this->assertNull(collect($data)->firstWhere(0, 'TOTAL'));

            // 1. Table Count == Card Total == Export Count
            $this->assertCount($rows->count(), $exportItems, "Mismatch in count for year {$targetYear}");
            $this->assertEquals($rows->count(), $stats['courseCompleted'] + $stats['playlistCompleted']);

            // 2. Table Hours == Card Hours == Export Data Sum
            $expectedHours = round((float) $stats['totalLearningHours'], 2);
            $exportedHours = round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2);
            $this->assertEqualsWithDelta($expectedHours, $exportedHours, 0.05, "Mismatch in hours for year {$targetYear}");
        }
    }

    public function test_program_mandatory_and_optional_filter_controls_table_and_export(): void
    {
        $this->actingAs($this->user);

        // Set c1 to mandatory, c2 remains optional
        ContentUser::where('user_id', $this->user->id)
            ->where('content_id', Content::where('title', 'Technical Video Course 2023')->value('id'))
            ->update(['status' => 'mandatory']);

        // 1. Mandatory filter
        request()->replace(['record_filter_program' => 'mandatory']);
        $viewMandatory = $this->controller->partial($this->eligibilityService);
        $rowsMandatory = $viewMandatory->getData()['rows'];

        $exportMandatory = new ExportMyLearningRecord(['program' => 'mandatory']);
        $exportItemsMandatory = array_values(array_filter(array_slice($exportMandatory->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(1, $rowsMandatory);
        $this->assertCount(1, $exportItemsMandatory);
        $this->assertEquals($rowsMandatory->first()['title'], $exportItemsMandatory[0][1]);

        // 2. Optional filter
        request()->replace(['record_filter_program' => 'optional']);
        $viewOptional = $this->controller->partial($this->eligibilityService);
        $rowsOptional = $viewOptional->getData()['rows'];

        $exportOptional = new ExportMyLearningRecord(['program' => 'optional']);
        $exportItemsOptional = array_values(array_filter(array_slice($exportOptional->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(1, $rowsOptional);
        $this->assertCount(1, $exportItemsOptional);
        $this->assertEquals($rowsOptional->first()['title'], $exportItemsOptional[0][1]);
    }

    public function test_duration_ranges_controls_table_and_export(): void
    {
        $this->actingAs($this->user);

        // Duration 0-600 should match c1 (360s) and c2 (600s)
        request()->replace(['record_filter_duration' => '0-600']);
        $rows = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $export = new ExportMyLearningRecord(['duration' => '0-600']);
        $exportItems = array_values(array_filter(array_slice($export->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(2, $rows);
        $this->assertCount(2, $exportItems);
        $this->assertEquals($rows->pluck('title')->values()->all(), array_column($exportItems, 1));

        // Duration 600-1800 should match c2 (600s) and p1 (1800s)
        request()->replace(['record_filter_duration' => '600-1800']);
        $rows = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $export = new ExportMyLearningRecord(['duration' => '600-1800']);
        $exportItems = array_values(array_filter(array_slice($export->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(2, $rows);
        $this->assertCount(2, $exportItems);
        $this->assertEquals($rows->pluck('title')->values()->all(), array_column($exportItems, 1));
    }

    public function test_learning_hour_range_filter_controls_table_and_export(): void
    {
        $this->actingAs($this->user);

        // Hours: c1 = 0.05, c2 = 0.10, p1 = 0.20. All are in 0-1 range.
        request()->replace(['record_filter_learning_hour' => '0-1']);
        $rows = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $export = new ExportMyLearningRecord(['learningHour' => '0-1']);
        $exportItems = array_values(array_filter(array_slice($export->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(3, $rows);
        $this->assertCount(3, $exportItems);
        $this->assertEquals($rows->pluck('title')->values()->all(), array_column($exportItems, 1));

        // > 3 hours should be 0
        request()->replace(['record_filter_learning_hour' => 'gt3']);
        $rowsGt3 = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $exportGt3 = new ExportMyLearningRecord(['learningHour' => 'gt3']);
        $exportItemsGt3 = array_values(array_filter(array_slice($exportGt3->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(0, $rowsGt3);
        $this->assertCount(0, $exportItemsGt3);
    }

    public function test_completion_mode_completed_controls_table_and_export(): void
    {
        $this->actingAs($this->user);

        request()->replace(['record_filter_completion' => 'completed']);
        $rows = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $export = new ExportMyLearningRecord(['completion' => 'completed']);
        $exportItems = array_values(array_filter(array_slice($export->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(3, $rows);
        $this->assertCount(3, $exportItems);
        $this->assertEquals($rows->pluck('title')->values()->all(), array_column($exportItems, 1));
    }

    public function test_modal_month_filter_controls_table_and_export(): void
    {
        $this->actingAs($this->user);

        // c2 is May (month 5) 2025.
        request()->replace(['record_filter_modal_month' => ['5']]);
        $rows = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $export = new ExportMyLearningRecord(['modalMonths' => ['5']]);
        $exportItems = array_values(array_filter(array_slice($export->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertCount(1, $rows);
        $this->assertCount(1, $exportItems);
        $this->assertEquals('HR Document Guide 2025', $rows->first()['title']);
        $this->assertEquals('HR Document Guide 2025', $exportItems[0][1]);
    }

    public function test_activity_sort_controls_table_and_export_order(): void
    {
        $this->actingAs($this->user);

        // Sort: oldest
        request()->replace(['record_filter_activity_sort' => 'oldest']);
        $rowsOldest = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $exportOldest = new ExportMyLearningRecord(['record_filter_activity_sort' => 'oldest']);
        $exportItemsOldest = array_values(array_filter(array_slice($exportOldest->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertEquals($rowsOldest->pluck('title')->values()->all(), array_column($exportItemsOldest, 1));
        $this->assertEquals('Technical Video Course 2023', $rowsOldest->first()['title']);

        // Sort: ascending (alphabetical by title)
        request()->replace(['record_filter_activity_sort' => 'ascending']);
        $rowsAsc = $this->controller->partial($this->eligibilityService)->getData()['rows'];
        $exportAsc = new ExportMyLearningRecord(['record_filter_activity_sort' => 'ascending']);
        $exportItemsAsc = array_values(array_filter(array_slice($exportAsc->array(), 1), fn ($r) => !empty($r[0])));

        $this->assertEquals($rowsAsc->pluck('title')->values()->all(), array_column($exportItemsAsc, 1));
    }

    public function test_dataset_identity_across_all_filter_dimensions(): void
    {
        $realUser = User::find(2);
        if (!$realUser) {
            $this->markTestSkipped('Real user 2 not found');
        }

        $this->actingAs($realUser);

        $filterMatrix = [
            'All baseline'                       => [],
            'Year 2025'                          => ['record_filter_year' => '2025'],
            'Modal Year 2024'                    => ['record_filter_modal_year' => '2024'],
            'Month 5'                            => ['record_filter_month' => '5'],
            'Classification Basic & Common'      => ['record_filter_classification' => 'Basic & Common Duties'],
            'Program Mandatory'                  => ['record_filter_program' => 'mandatory'],
            'Program Optional'                   => ['record_filter_program' => 'optional'],
            'Duration 0-600'                     => ['record_filter_duration' => '0-600'],
            'Duration 600-1800'                  => ['record_filter_duration' => '600-1800'],
            'Learning Status Completed'          => ['record_filter_learning_status' => ['completed']],
            'Completion Completed'               => ['record_filter_completion' => 'completed'],
            'Score None'                         => ['record_filter_score' => 'none'],
            'Certificate Ready'                  => ['record_filter_certificate' => 'ready'],
            'Quick Content'                      => ['record_quick' => 'content'],
            'Quick Playlist'                     => ['record_quick' => 'playlist'],
            'Combined Year + Classification'     => ['record_filter_year' => '2025', 'record_filter_classification' => 'Basic & Common Duties'],
            'Combined Modal Year + Program'      => ['record_filter_modal_year' => '2025', 'record_filter_program' => 'optional'],
            'Activity Sort Oldest'               => ['record_filter_activity_sort' => 'oldest'],
            'Activity Sort Ascending'            => ['record_filter_activity_sort' => 'ascending'],
        ];

        foreach ($filterMatrix as $scenario => $filters) {
            request()->replace($filters);
            $rows = $this->controller->partial($this->eligibilityService)->getData()['rows'];
            $export = new ExportMyLearningRecord($filters);
            $exportData = $export->array();
            $exportItems = array_values(array_filter(array_slice($exportData, 1), fn ($r) => !empty($r[0])));
            $this->assertNull(collect($exportData)->firstWhere(0, 'TOTAL'));

            $this->assertCount($rows->count(), $exportItems, "Mismatch in item count for scenario: {$scenario}");
            $this->assertEquals($rows->pluck('title')->values()->all(), array_column($exportItems, 1), "Mismatch in title dataset order for scenario: {$scenario}");

            $expectedHours = round((float) $rows->sum(fn (array $r) => (float) ($r['learning_hour_value'] ?? 0)), 2);
            $exportedHours = round((float) collect($exportItems)->sum(fn($r) => (float) str_replace([' hrs', ' hr'], '', $r[6])), 2);
            $this->assertEqualsWithDelta($expectedHours, $exportedHours, 0.05, "Mismatch in total learning hours for scenario: {$scenario}");
        }
    }
}
