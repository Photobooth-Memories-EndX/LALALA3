<?php

namespace Tests\Feature;

use App\Exports\ExportMyAllLearningRecord;
use App\Exports\ExportMyLearningRecord;
use App\Models\Classification;
use App\Models\Content;
use App\Models\PlaylistContentUser;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Tests\TestCase;

class LearningRecordPlaylistExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Classification $classification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'       => 'Export Tester ' . rand(1000, 9999),
            'id_emp'     => 'EMP-EXP-' . rand(1000, 9999),
            'team'       => 'Engineering',
            'department' => 'Technology',
            'division'   => 'LMS',
            'ssohash'    => 'hash-exp-' . Str::uuid(),
            'sap_code'   => 'SAP-EXP-' . rand(1000, 9999),
            'role'       => 1,
            'active'     => 1,
        ]);

        $this->classification = Classification::firstOrCreate(
            ['classification_name' => 'Export Classification'],
            ['slug' => 'export-classification-' . rand(1000, 9999)]
        );
    }

    /**
     * Test parent playlist with child contents having various assessment score states:
     * - Child 1: Assessment score 85
     * - Child 2: Assessment score 0 (must be preserved as 0, not empty or '-')
     * - Child 3: Multiple assessment attempts (scores 60 and 95, MAX must pick 95)
     * - Child 4: No assessment (score must be '-')
     */
    public function test_playlist_children_assessment_score_variations_and_zero_score(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Complete Playlist Assessment Suite',
            'slug'                   => 'complete-pl-suite-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 7200,
            'learning_hours'         => 2.0,
        ]);

        // Child 1: score 85
        $c1 = Content::create([
            'title'             => 'Module 1 With Score 85',
            'slug'              => 'mod-1-score-85-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:30:00',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'content_order' => 1]);
        PlaylistContentUser::create(['trainingplan_id' => $plan->id, 'content_id' => $c1->id, 'user_id' => $this->user->id, 'progress' => 100, 'finish' => '1', 'actual_watch_seconds' => 1800]);

        $a1Id = DB::table('assesments')->insertGetId([
            'content_id' => $c1->id,
            'title'      => 'Assessment Module 1',
            'status'     => 'OK',
            'batch'      => 1,
            'timer'      => 'n',
            'has_access_period' => 1,
            'deadline_type' => 'period',
            'total_remedials' => 0,
            'assasment_content_for' => 'content',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assesment_results')->insert([
            'assesment_id' => $a1Id,
            'user_id'      => $this->user->id,
            'score'        => 85,
            'final'        => 1,
            'user_answer'  => '[]',
            'batch'        => 1,
            'pass'         => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Child 2: score 0
        $c2 = Content::create([
            'title'             => 'Module 2 With Score 0',
            'slug'              => 'mod-2-score-0-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:30:00',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'content_order' => 2]);
        PlaylistContentUser::create(['trainingplan_id' => $plan->id, 'content_id' => $c2->id, 'user_id' => $this->user->id, 'progress' => 100, 'finish' => '1', 'actual_watch_seconds' => 1800]);

        $a2Id = DB::table('assesments')->insertGetId([
            'content_id' => $c2->id,
            'title'      => 'Assessment Module 2',
            'status'     => 'OK',
            'batch'      => 1,
            'timer'      => 'n',
            'has_access_period' => 1,
            'deadline_type' => 'period',
            'total_remedials' => 0,
            'assasment_content_for' => 'content',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assesment_results')->insert([
            'assesment_id' => $a2Id,
            'user_id'      => $this->user->id,
            'score'        => 0,
            'final'        => 1,
            'user_answer'  => '[]',
            'batch'        => 1,
            'pass'         => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Child 3: multiple assessment results (scores 60 and 95)
        $c3 = Content::create([
            'title'             => 'Module 3 Multiple Attempts',
            'slug'              => 'mod-3-multi-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:30:00',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'content_order' => 3]);
        PlaylistContentUser::create(['trainingplan_id' => $plan->id, 'content_id' => $c3->id, 'user_id' => $this->user->id, 'progress' => 100, 'finish' => '1', 'actual_watch_seconds' => 1800]);

        $a3Id = DB::table('assesments')->insertGetId([
            'content_id' => $c3->id,
            'title'      => 'Assessment Module 3',
            'status'     => 'OK',
            'batch'      => 1,
            'timer'      => 'n',
            'has_access_period' => 1,
            'deadline_type' => 'period',
            'total_remedials' => 0,
            'assasment_content_for' => 'content',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('assesment_results')->insert([
            'assesment_id' => $a3Id,
            'user_id'      => $this->user->id,
            'score'        => 60,
            'final'        => 1,
            'user_answer'  => '[]',
            'batch'        => 1,
            'pass'         => 0,
            'created_at'   => now()->subMinutes(10),
            'updated_at'   => now()->subMinutes(10),
        ]);
        DB::table('assesment_results')->insert([
            'assesment_id' => $a3Id,
            'user_id'      => $this->user->id,
            'score'        => 95,
            'final'        => 1,
            'user_answer'  => '[]',
            'batch'        => 2,
            'pass'         => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Child 4: No assessment
        $c4 = Content::create([
            'title'             => 'Module 4 No Assessment',
            'slug'              => 'mod-4-no-ass-' . rand(1000, 9999),
            'type'              => 'Document',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '00:30:00',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $c4->id, 'content_order' => 4]);
        PlaylistContentUser::create(['trainingplan_id' => $plan->id, 'content_id' => $c4->id, 'user_id' => $this->user->id, 'progress' => 100, 'finish' => '1', 'actual_watch_seconds' => 1800]);

        $this->actingAs($this->user);

        // 1. Verify KP OWL Export Array
        $exportKp = new ExportMyLearningRecord();
        $kpData = $exportKp->array();

        // Must not throw ErrorException and have all children
        $r1 = collect($kpData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 1 With Score 85'));
        $this->assertNotNull($r1);
        $this->assertEquals(85, $r1[10]); // Score column in KP OWL is index 10

        $r2 = collect($kpData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 2 With Score 0'));
        $this->assertNotNull($r2);
        $this->assertTrue($r2[10] === 0 || $r2[10] === '0', 'Score 0 must be preserved as 0, not empty or dash');

        $r3 = collect($kpData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 3 Multiple Attempts'));
        $this->assertNotNull($r3);
        $this->assertEquals(95, $r3[10], 'MAX score should pick highest attempt (95)');

        $r4 = collect($kpData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 4 No Assessment'));
        $this->assertNotNull($r4);
        $this->assertEquals('-', $r4[10], 'Content without assessment must display "-"');

        // 2. Verify All Record Export Array
        $exportAll = new ExportMyAllLearningRecord();
        $allData = $exportAll->array();

        $allR1 = collect($allData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 1 With Score 85'));
        $this->assertNotNull($allR1);
        $this->assertEquals(85, $allR1[10]); // Score column in All Record is index 10

        $allR2 = collect($allData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 2 With Score 0'));
        $this->assertNotNull($allR2);
        $this->assertTrue($allR2[10] === 0 || $allR2[10] === '0', 'Score 0 must be preserved as 0 in All Record');

        $allR3 = collect($allData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 3 Multiple Attempts'));
        $this->assertNotNull($allR3);
        $this->assertEquals(95, $allR3[10]);

        $allR4 = collect($allData)->first(fn ($r) => str_contains((string) ($r[1] ?? ''), 'Module 4 No Assessment'));
        $this->assertNotNull($allR4);
        $this->assertEquals('-', $allR4[10]);
    }

    /**
     * Test HTTP download endpoints actually generate a valid Excel spreadsheet without ErrorException.
     * Open the actual generated xlsx binary with PhpSpreadsheet and inspect the sheet structure.
     */
    public function test_http_download_endpoints_generate_valid_excel_files_with_parent_child_structure(): void
    {
        $plan = TrainingPlan::create([
            'title'                  => 'Real Download Playlist',
            'slug'                   => 'real-dl-pl-' . rand(1000, 9999),
            'active'                 => 'Active',
            'total_duration_seconds' => 3600,
            'learning_hours'         => 1.0,
        ]);

        $child = Content::create([
            'title'             => 'Download Child Course',
            'slug'              => 'dl-child-course-' . rand(1000, 9999),
            'type'              => 'Video',
            'classification_id' => $this->classification->id,
            'active'            => 'Active',
            'duration'          => '01:00:00',
            'playlistonly'      => 'Y',
        ]);
        TrainingPlanDetail::create(['trainingplan_id' => $plan->id, 'content_id' => $child->id, 'content_order' => 1]);
        PlaylistContentUser::create(['trainingplan_id' => $plan->id, 'content_id' => $child->id, 'user_id' => $this->user->id, 'progress' => 100, 'finish' => '1', 'actual_watch_seconds' => 3600]);

        $this->actingAs($this->user);

        // 1. Download KP OWL (source=online)
        $onlineResponse = $this->get(route('user.learning.record.export', ['source' => 'online']));
        $onlineResponse->assertOk();
        $this->assertStringContainsString('MyLearningRecord_', $onlineResponse->headers->get('content-disposition', ''));

        // Read via PhpSpreadsheet directly from the response's temporary file
        $tempFileOnline = $onlineResponse->getFile()->getPathname();
        $spreadsheetOnline = IOFactory::load($tempFileOnline);
        $sheetOnline = $spreadsheetOnline->getActiveSheet();

        // Verify Row 1: Headers
        $this->assertEquals('No', $sheetOnline->getCell('A1')->getValue());
        $this->assertEquals('Course', $sheetOnline->getCell('B1')->getValue());
        $this->assertEquals('Type', $sheetOnline->getCell('C1')->getValue());
        $this->assertEquals('Category', $sheetOnline->getCell('D1')->getValue());
        $this->assertEquals('Classification', $sheetOnline->getCell('E1')->getValue());
        $this->assertEquals('Duration', $sheetOnline->getCell('F1')->getValue());
        $this->assertEquals('Learning Hour', $sheetOnline->getCell('G1')->getValue());
        $this->assertEquals('Progress', $sheetOnline->getCell('H1')->getValue());
        $this->assertEquals('Date Time', $sheetOnline->getCell('I1')->getValue());
        $this->assertEquals('Attendance', $sheetOnline->getCell('J1')->getValue());
        $this->assertEquals('Assessment Score', $sheetOnline->getCell('K1')->getValue());
        $this->assertEquals('Certificate', $sheetOnline->getCell('L1')->getValue());

        // Find Parent Row
        $highestRowOnline = $sheetOnline->getHighestRow();
        $foundParentRow = null;
        $foundChildRow = null;

        for ($r = 2; $r <= $highestRowOnline; $r++) {
            $val = $sheetOnline->getCell("B{$r}")->getValue();
            if ($val === 'Real Download Playlist') {
                $foundParentRow = $r;
            } elseif (str_contains((string) $val, 'Download Child Course')) {
                $foundChildRow = $r;
            }
        }

        $this->assertNotNull($foundParentRow, 'Parent playlist row must exist in sheet');
        $this->assertNotNull($foundChildRow, 'Child content row must exist in sheet');
        $this->assertEquals($foundParentRow + 1, $foundChildRow, 'Child row must be placed directly beneath parent row');

        // Verify parent row styling
        $parentFill = $sheetOnline->getStyle("A{$foundParentRow}:L{$foundParentRow}")->getFill()->getStartColor()->getRGB();
        $this->assertEquals('EDEDED', strtoupper($parentFill), 'Parent row must have #EDEDED fill');
        $this->assertTrue($sheetOnline->getStyle("A{$foundParentRow}")->getFont()->getBold(), 'Parent row must be bold');
        $this->assertEquals('Present', $sheetOnline->getCell("J{$foundParentRow}")->getValue());

        // Verify child row
        $this->assertNull($sheetOnline->getCell("A{$foundChildRow}")->getValue(), 'Child row No must be empty');
        $this->assertEquals(1, $sheetOnline->getRowDimension($foundChildRow)->getOutlineLevel(), 'Child row must have outline level 1');
        $this->assertEquals(1, $sheetOnline->getStyle("B{$foundChildRow}")->getAlignment()->getIndent(), 'Child row must have indent 1');
        $this->assertEquals('Present', $sheetOnline->getCell("J{$foundChildRow}")->getValue());

        // 2. Download All Record (source=all)
        $allResponse = $this->get(route('user.learning.record.export', ['source' => 'all']));
        $allResponse->assertOk();
        $this->assertStringContainsString('MyAllLearningRecord_', $allResponse->headers->get('content-disposition', ''));

        $tempFileAll = $allResponse->getFile()->getPathname();
        $spreadsheetAll = IOFactory::load($tempFileAll);
        $sheetAll = $spreadsheetAll->getActiveSheet();

        // Verify Row 1: Headers in All Record
        $this->assertEquals('No', $sheetAll->getCell('A1')->getValue());
        $this->assertEquals('Training Title', $sheetAll->getCell('B1')->getValue());
        $this->assertEquals('Attendance', $sheetAll->getCell('I1')->getValue());
        $this->assertEquals('Date', $sheetAll->getCell('J1')->getValue());
        $this->assertEquals('Score', $sheetAll->getCell('K1')->getValue());
        $this->assertEquals('Certificate', $sheetAll->getCell('L1')->getValue());
    }

    /**
     * Test edge case: Playlist without any child contents should export safely without error.
     */
    public function test_export_handles_empty_playlist_without_exception(): void
    {
        $emptyPlan = TrainingPlan::create([
            'title'  => 'Empty Playlist Zero Modules',
            'slug'   => 'empty-pl-zero-' . rand(1000, 9999),
            'active' => 'Active',
        ]);

        $this->actingAs($this->user);

        $exportService = app(\App\Services\Learning\LearningRecordPlaylistExportService::class);
        $childrenMap = $exportService->getPlaylistChildrenBatch($this->user->id, [$emptyPlan->id]);

        $this->assertTrue($childrenMap->isEmpty() || $childrenMap->get($emptyPlan->id, collect())->isEmpty());

        $exportOnline = new ExportMyLearningRecord();
        $onlineData = $exportOnline->array();
        $this->assertIsArray($onlineData);

        $exportAll = new ExportMyAllLearningRecord();
        $allData = $exportAll->array();
        $this->assertIsArray($allData);
    }
}
